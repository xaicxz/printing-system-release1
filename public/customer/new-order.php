<?php
require_once __DIR__.'/../../app/layout.php';
$u=require_login('customer');
$rates=rates();
$error='';
$_SESSION['order_nonce'] ??= bin2hex(random_bytes(16));
if (is_post()) {
    require_csrf();
    $paper=(string)($_POST['paper_size']??''); $color=(string)($_POST['print_color']??''); $sides=(string)($_POST['sides']??'');
    $pages=valid_positive_int($_POST['pages']??null,10000);
    $copies=valid_positive_int($_POST['copies']??null,100);
    $nonce=(string)($_POST['nonce']??'');
    $file=$_FILES['pdf']??null;
    if (!hash_equals($_SESSION['order_nonce']??'', $nonce)) $error='This request has already been submitted. Refresh to start a new one.';
    elseif (!in_array($paper,['A4','Letter','Legal'],true)||!in_array($color,['bw','color'],true)||!in_array($sides,['single','double'],true)||$pages===null||$copies===null) $error='Check the print settings and page count.';
    elseif (!$file||!is_array($file)||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) $error='Select a PDF file under 15 MB. Check PHP upload limits if this keeps happening.';
    elseif ($file['size']<1||$file['size']>MAX_PDF_SIZE) $error='Your PDF must be 15 MB or smaller.';
    elseif (!is_uploaded_file($file['tmp_name'])) $error='Invalid file upload.';
    else {
        $name=basename(str_replace('\\','/',(string)$file['name']));
        $extension=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $fh=fopen($file['tmp_name'],'rb'); $head=$fh?fread($fh,5):''; if($fh)fclose($fh);
        if ($extension!=='pdf'||$mime!=='application/pdf'||$head!=='%PDF-') $error='Please upload a valid PDF document.';
        elseif (strlen($name)>255) $error='The filename is too long. Rename the PDF and try again.';
        else {
            try {
                $rate=rate_for($paper,$color);
                if (!$rate) throw new RuntimeException('This combination is temporarily unavailable.');
                if (!is_dir(PRIVATE_FILE_DIR) && !mkdir(PRIVATE_FILE_DIR,0770,true) && !is_dir(PRIVATE_FILE_DIR)) throw new RuntimeException('Upload storage is not available.');
                $stored=bin2hex(random_bytes(24));
                $target=PRIVATE_FILE_DIR.'/'.$stored;
                if (!move_uploaded_file($file['tmp_name'],$target)) throw new RuntimeException('Could not store uploaded PDF.');
                chmod($target,0640);
                try {
                    $pdo=db(); $pdo->beginTransaction();
                    $total=quote_total((string)$rate['price_per_page'],$pages,$copies);
                    $s=$pdo->prepare('INSERT INTO print_orders (order_no,submission_token,customer_id,rate_id,paper_size,print_color,sides,pages_per_copy,copies,unit_price_snapshot,total_price) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                    $created=false;
                    for($attempt=0;$attempt<4&&!$created;$attempt++) {
                        try { $s->execute([order_no(),$nonce,$u['id'],$rate['id'],$paper,$color,$sides,$pages,$copies,$rate['price_per_page'],$total]); $created=true; }
                        catch(PDOException $ex) { if($ex->getCode()!=='23000'||$attempt===3)throw $ex; }
                    }
                    $id=(int)$pdo->lastInsertId();
                    $s=$pdo->prepare('INSERT INTO order_files (order_id,original_name,stored_name,file_size) VALUES (?,?,?,?)');
                    $s->execute([$id,$name,$stored,$file['size']]);
                    set_status($pdo,$id,(int)$u['id'],'','pending','Print request submitted');
                    $pdo->commit();
                    unset($_SESSION['order_nonce']);
                    flash('success','Your request was submitted successfully.');
                    redirect('customer/order.php?id='.$id);
                } catch (Throwable $ex) { if (isset($pdo)&&$pdo->inTransaction())$pdo->rollBack(); @unlink($target); throw $ex; }
            } catch (Throwable $ex) { error_log((string)$ex); $error=$ex instanceof RuntimeException?$ex->getMessage():'Could not submit the request. Please try again.'; }
        }
    }
}
page_header('New print request','customer/new-order.php','Upload one PDF and choose how you want it printed.');
if($error):?><div class="notice notice-error" role="alert"><?=e($error)?></div><?php endif;?>
<?php if (!$rates):?><div class="notice notice-error">Print rates are not set up yet. Contact the administrator.</div><?php else:?>
<form method="post" enctype="multipart/form-data" id="print-form" class="form-layout" data-rates="<?=e(json_encode($rates))?>" data-max="<?=MAX_PDF_SIZE?>">
<?=csrf_field()?><input type="hidden" name="nonce" value="<?=e($_SESSION['order_nonce'])?>">
<div class="form-primary">
<section class="panel form-panel"><div class="form-step-head"><span class="step-number">1</span><div><h2>Choose your document</h2><p>PDF only · Maximum 15 MB</p></div></div>
<label class="upload-drop" id="upload-drop" for="pdf"><input class="upload-input" type="file" id="pdf" name="pdf" accept=".pdf,application/pdf" required><span class="upload-icon"><?=svg_icon('upload')?></span><strong id="file-name">Click to choose a PDF</strong><span id="file-help">or drag and drop your document here</span><span class="upload-file-meta" id="file-meta"></span></label>
<div id="auto-pages-hint" class="inline-hint"><?=svg_icon('info')?> Page count will be detected automatically when possible.</div>
</section>
<section class="panel form-panel"><div class="form-step-head"><span class="step-number">2</span><div><h2>Print preferences</h2><p>Pick the options you need</p></div></div>
<div class="pref-group"><label class="group-label">Paper size</label><div class="choice-grid three-col"><label class="choice-tile"><input name="paper_size" type="radio" value="A4" <?=($_POST['paper_size']??'A4')==='A4'?'checked':''?>><span><strong>A4</strong><small>210 × 297 mm</small></span></label><label class="choice-tile"><input name="paper_size" type="radio" value="Letter" <?=($_POST['paper_size']??'')==='Letter'?'checked':''?>><span><strong>Letter</strong><small>8.5 × 11 in</small></span></label><label class="choice-tile"><input name="paper_size" type="radio" value="Legal" <?=($_POST['paper_size']??'')==='Legal'?'checked':''?>><span><strong>Legal</strong><small>8.5 × 14 in</small></span></label></div></div>
<div class="pref-group"><label class="group-label">Print color</label><div class="choice-grid two-col"><label class="choice-tile"><input type="radio" name="print_color" value="bw" <?=($_POST['print_color']??'bw')==='bw'?'checked':''?>><span><strong>Black & white</strong><small>Standard documents</small></span></label><label class="choice-tile"><input type="radio" name="print_color" value="color" <?=($_POST['print_color']??'')==='color'?'checked':''?>><span><strong>Full color</strong><small>Images and graphics</small></span></label></div></div>
<div class="pref-group"><label class="group-label">Print sides</label><div class="choice-grid two-col"><label class="choice-tile"><input type="radio" name="sides" value="single" <?=($_POST['sides']??'single')==='single'?'checked':''?>><span><strong>Single-sided</strong><small>One page per sheet</small></span></label><label class="choice-tile"><input type="radio" name="sides" value="double" <?=($_POST['sides']??'')==='double'?'checked':''?>><span><strong>Double-sided</strong><small>Both sides of sheet</small></span></label></div></div>
<div class="inline-fields"><div class="field"><label for="pages">Pages in PDF <span class="auto-tag" id="page-mode">Auto-detected</span></label><input id="pages" name="pages" type="number" min="1" max="10000" step="1" inputmode="numeric" placeholder="Detecting…" value="<?=e($_POST['pages']??'')?>" required><small id="pages-help">Auto-filled after selecting a PDF. You can correct it.</small></div><div class="field"><label for="copies">Number of copies</label><div class="number-stepper"><button type="button" class="step-btn" data-step="-1" data-target="copies" aria-label="Decrease copies">−</button><input id="copies" name="copies" type="number" min="1" max="100" value="<?=e($_POST['copies']??1)?>" required><button type="button" class="step-btn" data-step="1" data-target="copies" aria-label="Increase copies">+</button></div></div></div>
</section>
</div><aside class="form-sidebar"><div class="panel quote-panel"><div class="quote-heading"><span class="quote-icon"><?=svg_icon('receipt')?></span><h2>Order summary</h2></div><div class="quote-line"><span>Paper</span><strong id="sum-paper">A4</strong></div><div class="quote-line"><span>Color</span><strong id="sum-color">Black & white</strong></div><div class="quote-line"><span>Sides</span><strong id="sum-sides">Single-sided</strong></div><div class="quote-line"><span>Pages × Copies</span><strong id="sum-quantity">—</strong></div><div class="quote-line"><span>Rate per page</span><strong id="sum-rate">—</strong></div><div class="quote-total"><span>Estimated total</span><strong id="sum-total">₱0.00</strong></div><p class="quote-note">Final amount is confirmed after the shop verifies your PDF.</p><button class="btn btn-primary btn-full btn-lg" type="submit" id="submit-order" disabled><?=svg_icon('upload')?> Submit print request</button><p class="quote-footnote">Pay in cash when you pick up your prints.</p></div><a href="<?=e(url('customer/orders.php'))?>" class="return-link"><?=svg_icon('back')?> Back to my orders</a></aside></form>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<?php endif; page_footer(); ?>

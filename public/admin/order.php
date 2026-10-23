<?php
require_once __DIR__.'/../../app/layout.php';
$u=require_login('admin');
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$id)forbidden();
$error='';
if(is_post()) {
    require_csrf();
    $action=(string)($_POST['action']??'');
    try {
        $pdo=db();$pdo->beginTransaction();
        $o=get_order((int)$id,$u,true); $from=$o['status'];$to='';$note='';
        $options=['approve'=>['pending','approved'],'reject'=>['pending','rejected'], 'start'=>['approved','printing'],'ready'=>['printing','ready'],'complete'=>['ready','completed']];
        if(!isset($options[$action])||$options[$action][0]!==$from) throw new RuntimeException('This action is not available at the current order status.');
        $to=$options[$action][1];
        if($action==='approve') {
            $pages=valid_positive_int($_POST['verified_pages']??null,10000);
            if($pages===null) throw new RuntimeException('Enter a valid verified page count.');
            $rate=rate_for($o['paper_size'],$o['print_color']);
            if(!$rate) throw new RuntimeException('A print rate is not configured for this option.');
            $price=quote_total((string)$rate['price_per_page'],$pages,(int)$o['copies']);
            $note=$pages!==(int)$o['pages_per_copy']?'Admin verified the PDF and updated the page count from '.$o['pages_per_copy'].' to '.$pages.'.':'Page count verified and quote approved.';
            if($price!==number_format((float)$o['total_price'],2,'.',''))$note.=' Quote updated to '.money($price).'.';
            $s=$pdo->prepare("UPDATE print_orders SET pages_per_copy=?,rate_id=?,unit_price_snapshot=?,total_price=?,status='approved',approved_at=NOW(),admin_note=? WHERE id=?");
            $s->execute([$pages,$rate['id'],$rate['price_per_page'],$price,$note,$id]);
        } elseif($action==='reject') {
            $note=trim((string)($_POST['reason']??''));
            if(mb_strlen($note)<3||mb_strlen($note)>1000) throw new RuntimeException('Give a short rejection reason (3–1000 characters).');
            $pdo->prepare("UPDATE print_orders SET status='rejected',admin_note=? WHERE id=?")->execute([$note,$id]);
        } elseif($action==='complete') {
            if(($_POST['cash_confirm']??'')!=='yes')throw new RuntimeException('Confirm that the exact cash amount was collected.');
            $pdo->prepare("INSERT INTO payments (order_id,amount,method,status,recorded_by) VALUES (?,?,'cash','paid',?)")->execute([$id,$o['total_price'],$u['id']]);
            $pdo->prepare("UPDATE print_orders SET status='completed',completed_at=NOW() WHERE id=?")->execute([$id]);
            $note='Cash payment of '.money($o['total_price']).' recorded at pickup.';
        } else {
            $pdo->prepare('UPDATE print_orders SET status=? WHERE id=?')->execute([$to,$id]);
            $note=match($action) {'start'=>'Document sent for manual printing.','ready'=>'Print checked and ready for pickup.',default=>''};
        }
        set_status($pdo,(int)$id,(int)$u['id'],$from,$to,$note);
        $pdo->commit();flash('success',status_label($to).' — order updated successfully.');redirect('admin/order.php?id='.$id);
    } catch (Throwable $ex) { if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();error_log((string)$ex);$error=$ex instanceof RuntimeException?$ex->getMessage():'Unable to update this order. Please retry.'; }
}
$o=get_order((int)$id,$u);$history=order_history((int)$id);
page_header('Process order','admin/orders.php','Verify the document, update its status, and manage pickup.'); ?>
<?php if($error):?><div class="notice notice-error" role="alert"><?=e($error)?></div><?php endif;?>
<div class="detail-title-row"><div><a class="back-link" href="<?=e(url('admin/orders.php'))?>"><?=svg_icon('back')?> All orders</a><h2><?=e($o['order_no'])?></h2><p><?=e($o['customer_name'])?> · <?=e(nice_date($o['created_at']))?></p></div><?=status_badge($o['status'])?></div>
<div class="detail-grid"><div class="stack gap-lg"><section class="panel detail-panel"><div class="section-top"><div><h2>Customer document</h2><p>Inspect the PDF before approving</p></div><a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="<?=e(url('download.php?id='.$id))?>"><?=svg_icon('download')?> Open PDF</a></div><div class="document-tile"><span class="pdf-tile">PDF</span><div><strong><?=e($o['original_name'])?></strong><small><?=number_format($o['file_size']/1024,0)?> KB</small></div></div><div class="data-grid"><div><span>Customer</span><strong><?=e($o['customer_name'])?></strong></div><div><span>Email</span><strong class="break-all"><?=e($o['customer_email'])?></strong></div><div><span>Paper size</span><strong><?=e($o['paper_size'])?></strong></div><div><span>Color</span><strong><?=$o['print_color']==='bw'?'Black & white':'Full color'?></strong></div><div><span>Sides</span><strong><?=$o['sides']==='double'?'Double-sided':'Single-sided'?></strong></div><div><span>Copies</span><strong><?=(int)$o['copies']?></strong></div><div><span>Pages per copy</span><strong><?=(int)$o['pages_per_copy']?></strong></div><div><span>Payment</span><strong><?=$o['paid_amount']!==null?'Paid · Cash':'Not yet paid'?></strong></div></div><?php if($o['admin_note']):?><div class="admin-note"><strong>Last admin note</strong><p><?=nl2br(e($o['admin_note']))?></p></div><?php endif;?></section>
<section class="panel detail-panel"><div class="section-top"><div><h2>Activity timeline</h2><p>Complete history of updates</p></div></div><div class="timeline"><?php foreach(array_reverse($history) as $h):?><div class="timeline-item"><span class="timeline-marker"></span><div><strong><?=e(status_label($h['new_status']))?></strong><small><?=e(nice_date($h['created_at']))?> · <?=e($h['actor_name'])?></small><?php if($h['note']):?><p><?=e($h['note'])?></p><?php endif;?></div></div><?php endforeach;?></div></section></div>
<aside class="stack gap-lg"><section class="panel price-panel"><h2>Order total</h2><div class="quote-line"><span>Price per page</span><strong><?=money($o['unit_price_snapshot'])?></strong></div><div class="quote-line"><span>Pages × Copies</span><strong><?=$o['pages_per_copy']?> × <?=$o['copies']?></strong></div><div class="quote-total"><span><?= $o['status']==='pending'?'Estimated':'Final total' ?></span><strong><?=money($o['total_price'])?></strong></div><?php if($o['status']==='completed'):?><a class="btn btn-primary btn-full" href="<?=e(url('receipt.php?id='.$id))?>"><?=svg_icon('receipt')?> Print receipt</a><?php endif;?></section>
<section class="panel detail-panel action-panel"><h2>Next action</h2><?php if($o['status']==='pending'):?><p class="muted">Check the PDF page count, then approve the request.</p><form method="post" class="stack gap-md"><?=csrf_field()?><input type="hidden" name="action" value="approve"><div class="field"><label for="verified_pages">Verified pages per copy</label><div class="number-stepper"><button type="button" class="step-btn" data-step="-1" data-target="verified_pages">−</button><input id="verified_pages" name="verified_pages" type="number" value="<?=$o['pages_per_copy']?>" min="1" max="10000" required><button type="button" class="step-btn" data-step="1" data-target="verified_pages">+</button></div><small>Automatically recalculates price when changed.</small></div><button type="submit" class="btn btn-primary btn-full"><?=svg_icon('check')?> Verify & approve</button></form><div class="subaction"><button class="text-danger" type="button" data-reveal="reject-form">Reject this request</button><form method="post" id="reject-form" class="stack gap-sm hidden" data-confirm="Reject this request?"><?=csrf_field()?><input type="hidden" name="action" value="reject"><div class="field"><label for="reason">Reason for rejection</label><textarea id="reason" name="reason" rows="3" minlength="3" maxlength="1000" placeholder="e.g. PDF is password protected" required></textarea></div><button class="btn btn-danger-outline btn-full" type="submit">Confirm rejection</button></form></div>
<?php elseif($o['status']==='approved'):?><p class="muted">Download the PDF and print it using your regular printer software.</p><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="start"><button class="btn btn-primary btn-full" type="submit"><?=svg_icon('printer')?> Mark as printing</button></form>
<?php elseif($o['status']==='printing'):?><p class="muted">Check the printed pages before notifying the customer.</p><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="ready"><button class="btn btn-primary btn-full" type="submit"><?=svg_icon('check')?> Ready for pickup</button></form>
<?php elseif($o['status']==='ready'):?><p class="muted">Collect the exact total from the customer, then complete pickup.</p><div class="cash-box"><small>Cash to collect</small><strong><?=money($o['total_price'])?></strong></div><form method="post" class="stack gap-md" data-confirm="Have you received exactly <?=e(money($o['total_price']))?> in cash?"><?=csrf_field()?><input type="hidden" name="action" value="complete"><label class="checkbox-line"><input type="checkbox" name="cash_confirm" value="yes" required><span>I received the full cash payment.</span></label><button class="btn btn-primary btn-full" type="submit"><?=svg_icon('check')?> Record cash & complete</button></form>
<?php else:?><div class="finished-state"><?=svg_icon('check')?> This order is <?=e(strtolower(status_label($o['status'])))?>. No further actions are available.</div><?php endif;?></section></aside></div>
<?php page_footer(); ?>

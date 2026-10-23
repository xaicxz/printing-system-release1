<?php
require_once __DIR__.'/../../app/layout.php';
$u=require_login('admin');
$error='';
if(is_post()) {
    require_csrf();
    $posted=$_POST['rates']??null;
    try {
        if(!is_array($posted))throw new RuntimeException('No prices were submitted.');
        $pdo=db();$pdo->beginTransaction();
        $all=$pdo->query('SELECT id,price_per_page FROM print_rates FOR UPDATE')->fetchAll();
        foreach($all as $r) {
            $value=$posted[$r['id']]??null;
            if(!is_scalar($value)||!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/',(string)$value)||(float)$value<0.01) throw new RuntimeException('Enter prices from ₱0.01 to ₱999.99, up to 2 decimal places.');
            $new=number_format((float)$value,2,'.','');
            if($new!==number_format((float)$r['price_per_page'],2,'.','')) {
                $pdo->prepare('UPDATE print_rates SET price_per_page=?,updated_by=? WHERE id=?')->execute([$new,$u['id'],$r['id']]);
                $pdo->prepare('INSERT INTO rate_history (rate_id,actor_id,old_price,new_price) VALUES (?,?,?,?)')->execute([$r['id'],$u['id'],$r['price_per_page'],$new]);
            }
        }
        $pdo->commit();flash('success','Print rates saved successfully.');redirect('admin/prices.php');
    } catch(Throwable $ex){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();error_log((string)$ex);$error=$ex instanceof RuntimeException?$ex->getMessage():'Could not save rates.';}
}
$rates=db()->query('SELECT * FROM print_rates ORDER BY FIELD(paper_size,"A4","Letter","Legal"), FIELD(print_color,"bw","color")')->fetchAll();
page_header('Print rates','admin/prices.php','Manage what you charge for each printed page.'); ?>
<?php if($error):?><div class="notice notice-error"><?=e($error)?></div><?php endif;?>
<div class="rates-layout"><section class="panel detail-panel"><div class="section-top"><div><h2>Pricing matrix</h2><p>Prices are in Philippine pesos, per printed page.</p></div></div><form method="post" data-confirm="Save these price changes? New approvals will use updated rates."><?=csrf_field()?><div class="rates-grid"><div class="rate-head"><span>PAPER SIZE</span><span>BLACK & WHITE</span><span>FULL COLOR</span></div><?php foreach(['A4','Letter','Legal'] as $paper):?><div class="rate-row"><strong><?=e($paper)?></strong><?php foreach(['bw','color'] as $color):?><?php $r=null;foreach($rates as $item){if($item['paper_size']===$paper && $item['print_color']===$color){$r=$item;break;}} ?><?php if($r):?><label class="currency-field"><span>₱</span><input name="rates[<?=$r['id']?>]" aria-label="<?=e($paper.' '.$color)?> price per page" type="number" min="0.01" max="999.99" step="0.01" value="<?=e($r['price_per_page'])?>" required></label><?php else:?><span class="muted">Not set</span><?php endif;?><?php endforeach;?></div><?php endforeach;?></div><div class="rate-submit"><button type="submit" class="btn btn-primary"><?=svg_icon('check')?> Save print rates</button></div></form></section><aside class="panel detail-panel helper-panel"><span class="stat-icon blue"><?=svg_icon('info')?></span><h3>How pricing works</h3><p>The estimated cost is calculated automatically:</p><div class="formula-card">Pages × Copies × Rate per page</div><p>Double-sided printing changes the number of sheets, not the page billing amount.</p><p>Rates shown here are <strong>sample values</strong> until you customize them. Approved orders keep their original saved price.</p></aside></div>
<?php page_footer(); ?>

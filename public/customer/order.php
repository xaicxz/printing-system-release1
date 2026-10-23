<?php
require_once __DIR__.'/../../app/layout.php';
$u=require_login('customer');
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$id)forbidden();
$order=get_order((int)$id,$u);
if (is_post()) {
    require_csrf();
    if (($_POST['action']??'')!=='cancel') { http_response_code(400);exit('Invalid action.'); }
    try {
        $pdo=db();$pdo->beginTransaction();
        $locked=get_order((int)$id,$u,true);
        if($locked['status']!=='pending')throw new RuntimeException('Only pending requests can be canceled.');
        $pdo->prepare("UPDATE print_orders SET status='canceled' WHERE id=?")->execute([$id]);
        set_status($pdo,(int)$id,(int)$u['id'],'pending','canceled','Canceled by customer');
        $pdo->commit();flash('success','Request canceled.');redirect('customer/order.php?id='.$id);
    } catch(Throwable $ex){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();flash('error',$ex instanceof RuntimeException?$ex->getMessage():'Unable to cancel.');redirect('customer/order.php?id='.$id);}
}
$history=order_history((int)$id);
page_header('Order details','customer/orders.php','Review your request and follow its progress.'); ?>
<div class="detail-title-row"><div><a class="back-link" href="<?=e(url('customer/orders.php'))?>"><?=svg_icon('back')?> All orders</a><h2><?=e($order['order_no'])?></h2><p>Submitted <?=e(nice_date($order['created_at']))?></p></div><?=status_badge($order['status'])?></div>
<?php if($order['status']==='ready'):?><div class="notice notice-success"><?=svg_icon('check')?> Your prints are ready! Visit the printing counter to pay and pick them up.</div><?php endif;?>
<div class="detail-grid"><div class="stack gap-lg"><section class="panel detail-panel"><div class="section-top"><div><h2>Print details</h2><p>Your selected preferences</p></div><a class="btn btn-outline btn-sm" href="<?=e(url('download.php?id='.$id))?>" target="_blank" rel="noopener"><?=svg_icon('download')?> View PDF</a></div><div class="document-tile"><span class="pdf-tile">PDF</span><div><strong><?=e($order['original_name'])?></strong><small><?=number_format($order['file_size']/1024,0)?> KB</small></div></div><div class="data-grid"><div><span>Paper size</span><strong><?=e($order['paper_size'])?></strong></div><div><span>Color</span><strong><?= $order['print_color']==='bw'?'Black & white':'Full color' ?></strong></div><div><span>Sides</span><strong><?= $order['sides']==='double'?'Double-sided':'Single-sided' ?></strong></div><div><span>Pages per copy</span><strong><?= (int)$order['pages_per_copy'] ?></strong></div><div><span>Copies</span><strong><?= (int)$order['copies'] ?></strong></div><div><span>Payment</span><strong><?= $order['paid_amount']!==null?'Paid (cash)':'Cash on pickup' ?></strong></div></div><?php if($order['admin_note']):?><div class="admin-note"><strong>Message from print shop</strong><p><?=nl2br(e($order['admin_note']))?></p></div><?php endif;?></section>
<section class="panel detail-panel"><div class="section-top"><div><h2>Order timeline</h2><p>Updates from the print shop</p></div></div><div class="timeline"><?php foreach(array_reverse($history) as $h):?><div class="timeline-item"><span class="timeline-marker"></span><div><strong><?=e(status_label($h['new_status']))?></strong><small><?=e(nice_date($h['created_at']))?></small><?php if($h['note']):?><p><?=e($h['note'])?></p><?php endif;?></div></div><?php endforeach;?></div></section></div>
<aside class="stack gap-lg"><section class="panel price-panel"><h2>Price summary</h2><div class="quote-line"><span>Price per page</span><strong><?=money($order['unit_price_snapshot'])?></strong></div><div class="quote-line"><span>Total printed pages</span><strong><?=(int)$order['pages_per_copy']*(int)$order['copies']?></strong></div><div class="quote-total"><span><?= $order['status']==='pending'?'Estimated amount':'Order total' ?></span><strong><?=money($order['total_price'])?></strong></div><p class="quote-note">Payment is collected at pickup.</p><?php if($order['status']==='completed' && $order['paid_amount']!==null):?><a class="btn btn-primary btn-full" href="<?=e(url('receipt.php?id='.$id))?>"><?=svg_icon('receipt')?> View receipt</a><?php endif;?></section><?php if($order['status']==='pending'):?><section class="panel detail-panel"><h2>Need to cancel?</h2><p class="muted">You can cancel while your request is still pending.</p><form method="post" data-confirm="Cancel this print request? This cannot be undone."><?=csrf_field()?><input type="hidden" name="action" value="cancel"><button type="submit" class="btn btn-danger-outline btn-full">Cancel request</button></form></section><?php endif;?></aside></div>
<?php page_footer(); ?>

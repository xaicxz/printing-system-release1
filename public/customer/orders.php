<?php
require_once __DIR__.'/../../app/layout.php';
$u=require_login('customer');
$filter=(string)($_GET['status']??'all');
$allowed=['all','pending','approved','printing','ready','completed','rejected','canceled'];
if(!in_array($filter,$allowed,true))$filter='all';
$sql='SELECT o.id,o.order_no,o.total_price,o.status,o.created_at,f.original_name FROM print_orders o JOIN order_files f ON f.order_id=o.id WHERE o.customer_id=?';
$params=[$u['id']];
if($filter!=='all') { $sql.=' AND o.status=?'; $params[]=$filter; }
$sql.=' ORDER BY o.id DESC LIMIT 150';
$s=db()->prepare($sql);$s->execute($params);$orders=$s->fetchAll();
page_header('My orders','customer/orders.php','All your printing requests, in one place.'); ?>
<div class="toolbar"><form class="filter-form" method="get"><label for="status" class="sr-only">Filter by status</label><select name="status" id="status" onchange="this.form.submit()"><option value="all">All statuses</option><?php foreach(array_slice($allowed,1) as $status):?><option value="<?=e($status)?>" <?=$filter===$status?'selected':''?>><?=e(status_label($status))?></option><?php endforeach;?></select></form><a href="<?=e(url('customer/new-order.php'))?>" class="btn btn-primary"><?=svg_icon('plus')?> New request</a></div>
<section class="panel"><?php if(!$orders):?><div class="empty-state"><span class="empty-icon"><?=svg_icon('file')?></span><h3>Nothing here yet</h3><p>No requests match this filter.</p><a class="btn btn-outline" href="<?=e(url('customer/orders.php'))?>">Show all orders</a></div><?php else:?><div class="table-wrap"><table><thead><tr><th>ORDER</th><th>SUBMITTED</th><th>AMOUNT</th><th>STATUS</th><th></th></tr></thead><tbody><?php foreach($orders as $r):?><tr><td><strong><?=e($r['order_no'])?></strong><div class="secondary-line"><?=e($r['original_name'])?></div></td><td><?=e(nice_date($r['created_at']))?></td><td class="price-cell"><?=money($r['total_price'])?></td><td><?=status_badge($r['status'])?></td><td><a class="table-action" href="<?=e(url('customer/order.php?id='.$r['id']))?>">View <?=svg_icon('arrow')?></a></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section>
<?php page_footer(); ?>

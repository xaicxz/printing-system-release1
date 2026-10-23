<?php
require_once __DIR__.'/../../app/layout.php';
$u=require_login('admin');
$filter=(string)($_GET['status']??'all');$search=trim((string)($_GET['q']??''));
$allowed=['all','pending','approved','printing','ready','completed','rejected','canceled'];if(!in_array($filter,$allowed,true))$filter='all';
$sql='SELECT o.id,o.order_no,o.total_price,o.status,o.created_at,u.full_name AS customer_name,f.original_name FROM print_orders o JOIN users u ON u.id=o.customer_id JOIN order_files f ON f.order_id=o.id WHERE 1=1';$params=[];
if($filter!=='all'){$sql.=' AND o.status=?';$params[]=$filter;}
if($search!==''){$sql.=' AND (o.order_no LIKE ? OR u.full_name LIKE ? OR f.original_name LIKE ?)';$like='%'.str_replace(['%','_'],['\\%','\\_'],mb_substr($search,0,80)).'%';$params= array_merge($params,[$like,$like,$like]);}
$sql.=' ORDER BY FIELD(o.status,"pending","approved","printing","ready","completed","rejected","canceled"),o.id DESC LIMIT 250';
$s=db()->prepare($sql);$s->execute($params);$orders=$s->fetchAll();
page_header('Manage orders','admin/orders.php','Find, verify, and process customer requests.'); ?>
<form class="toolbar toolbar-filters" method="get"><div class="search-field"><?=svg_icon('search')?><input name="q" aria-label="Search orders" placeholder="Search order, customer or filename" value="<?=e($search)?>" maxlength="80"></div><select name="status" aria-label="Filter by status"><option value="all">All statuses</option><?php foreach(array_slice($allowed,1) as $status):?><option value="<?=e($status)?>" <?=$filter===$status?'selected':''?>><?=e(status_label($status))?></option><?php endforeach;?></select><button class="btn btn-outline" type="submit">Filter</button><?php if($filter!=='all'||$search!==''):?><a class="btn btn-ghost" href="<?=e(url('admin/orders.php'))?>">Clear</a><?php endif;?></form>
<section class="panel"><div class="section-top"><h2>Orders <span class="count-pill"><?=count($orders)?></span></h2></div><?php if(!$orders):?><div class="empty-state"><h3>No matching orders</h3><p>Try a different search or status filter.</p></div><?php else:?><div class="table-wrap"><table><thead><tr><th>ORDER / FILE</th><th>CUSTOMER</th><th>SUBMITTED</th><th>AMOUNT</th><th>STATUS</th><th></th></tr></thead><tbody><?php foreach($orders as $r):?><tr><td><strong><?=e($r['order_no'])?></strong><div class="secondary-line"><?=e($r['original_name'])?></div></td><td><?=e($r['customer_name'])?></td><td><?=e(nice_date($r['created_at']))?></td><td class="price-cell"><?=money($r['total_price'])?></td><td><?=status_badge($r['status'])?></td><td><a class="table-action" href="<?=e(url('admin/order.php?id='.$r['id']))?>">Manage <?=svg_icon('arrow')?></a></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section>
<?php page_footer(); ?>

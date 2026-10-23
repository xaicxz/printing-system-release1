<?php
require_once __DIR__.'/../../app/layout.php';
$u=require_login('customer');
$s=db()->prepare("SELECT COUNT(*) AS total, SUM(status='pending') AS pending_count, SUM(status='ready') AS ready_count, SUM(status='completed') AS completed_count FROM print_orders WHERE customer_id=?");
$s->execute([$u['id']]); $stats=$s->fetch();
$s = db()->prepare('
    SELECT
        o.id,
        o.order_no,
        f.original_name,
        o.total_price,
        o.status,
        o.created_at
    FROM print_orders o
    JOIN order_files f ON f.order_id = o.id
    WHERE o.customer_id = ?
    ORDER BY o.id DESC
    LIMIT 6
');$s->execute([$u['id']]); $recent=$s->fetchAll();
page_header('Dashboard','customer/dashboard.php','Here’s what’s happening with your print requests.'); ?>
<section class="welcome-panel"><div><span class="welcome-label">YOUR PRINTING HUB</span><h2>Ready to print something?</h2><p>Upload your PDF, choose your options, and we’ll take it from there.</p><a href="<?=e(url('customer/new-order.php'))?>" class="btn btn-white"><?=svg_icon('plus')?> New print request</a></div><div class="welcome-art" aria-hidden="true"><div class="art-sheet art-sheet-two"></div><div class="art-sheet art-sheet-one"><span class="art-topline"></span><span></span><span></span><span></span></div><div class="art-check"><?=svg_icon('check')?></div></div></section>
<div class="stats-grid three"><div class="stat-card"><span class="stat-icon amber"><?=svg_icon('clock')?></span><div><div class="stat-number"><?= (int)($stats['pending_count']??0) ?></div><div class="stat-label">Pending approval</div></div></div><div class="stat-card"><span class="stat-icon teal"><?=svg_icon('check')?></span><div><div class="stat-number"><?= (int)($stats['ready_count']??0) ?></div><div class="stat-label">Ready for pickup</div></div></div><div class="stat-card"><span class="stat-icon blue"><?=svg_icon('file')?></span><div><div class="stat-number"><?= (int)($stats['completed_count']??0) ?></div><div class="stat-label">Completed orders</div></div></div></div>
<section class="panel"><div class="section-top"><div><h2>Recent requests</h2><p>Your latest printing activity</p></div><a class="text-link" href="<?=e(url('customer/orders.php'))?>">View all <?=svg_icon('arrow')?></a></div>
<?php if(!$recent):?><div class="empty-state"><span class="empty-icon"><?=svg_icon('file')?></span><h3>No print requests yet</h3><p>When you submit a request, it will appear right here.</p><a href="<?=e(url('customer/new-order.php'))?>" class="btn btn-primary"><?=svg_icon('plus')?> Create first request</a></div><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>ORDER</th><th>SUBMITTED</th><th>AMOUNT</th><th>STATUS</th><th></th></tr></thead><tbody><?php foreach($recent as $r):?><tr><td><strong><?=e($r['order_no'])?></strong><div class="secondary-line"><?=e($r['original_name'])?></div></td><td><?=e(nice_date($r['created_at']))?></td><td class="price-cell"><?=money($r['total_price'])?></td><td><?=status_badge($r['status'])?></td><td><a class="table-action" href="<?=e(url('customer/order.php?id='.$r['id']))?>">View <?=svg_icon('arrow')?></a></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section>
<?php page_footer(); ?>

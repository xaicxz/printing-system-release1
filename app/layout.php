<?php
require_once __DIR__ . '/bootstrap.php';
function svg_icon(string $icon): string {
    $icons = [
        'printer'=>'<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/><path d="M18 12h.01"/>',
        'grid'=>'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'file'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>',
        'plus'=>'<path d="M12 5v14M5 12h14"/>',
        'tag'=>'<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'logout'=>'<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'arrow'=>'<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'clock'=>'<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'check'=>'<path d="m4 12 5 5L20 6"/>',
        'upload'=>'<path d="M12 16V3m-5 5 5-5 5 5"/><path d="M4 17v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>',
        'search'=>'<circle cx="11" cy="11" r="7"/><path d="m16.5 16.5 4 4"/>',
        'back'=>'<path d="m15 18-6-6 6-6"/>',
        'download'=>'<path d="M12 3v12m-5-5 5 5 5-5M5 17v4h14v-4"/>',
        'receipt'=>'<path d="M4 2h16v20l-4-2-4 2-4-2-4 2zM8 7h8M8 11h8M8 15h4"/>',
        'info'=>'<circle cx="12" cy="12" r="10"/><path d="M12 11v5M12 7h.01"/>',
        'menu'=>'<path d="M4 6h16M4 12h16M4 18h16"/>',
        'users'=>'<circle cx="9" cy="8" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2M17 4a4 4 0 0 1 0 8M18 15a6 6 0 0 1 4 6"/>',
        'shield'=>'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/>',
    ];
    return '<svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($icons[$icon]??$icons['file']).'</svg>';
}
function page_header(string $title, string $active = '', string $subtitle = ''): void {
    $u=current_user();
    $admin=$u && $u['role']==='admin';
    $items = $admin ? [
        ['Dashboard','admin/dashboard.php','grid'],['Orders','admin/orders.php','file'],['Print rates','admin/prices.php','tag']
    ] : [
        ['Dashboard','customer/dashboard.php','grid'],['New request','customer/new-order.php','plus'],['My orders','customer/orders.php','file']
    ];
    ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light"><title><?=e($title)?> · <?=e(APP_NAME)?></title><link rel="stylesheet" href="<?=e(url('assets/css/style.css'))?>"><script defer src="<?=e(url('assets/js/app.js'))?>"></script></head><body>
<div class="app-shell">
<aside class="sidebar" id="sidebar">
 <a class="brand" href="<?=e(url($admin?'admin/dashboard.php':'customer/dashboard.php'))?>"><span class="brand-mark"><?=svg_icon('printer')?></span><span>Print<span class="brand-accent">Flow</span><small>PRINT MANAGEMENT</small></span></a>
 <div class="nav-section-label">WORKSPACE</div>
 <nav class="nav-links" aria-label="Main navigation">
 <?php foreach ($items as $item): ?><a href="<?=e(url($item[1]))?>" class="nav-link <?= $active===$item[1]?'selected':'' ?>"><?=svg_icon($item[2])?><span><?=e($item[0])?></span></a><?php endforeach; ?>
 </nav>
 <div class="sidebar-bottom"><div class="side-user"><div class="avatar"><?=e(strtoupper(substr($u['name'],0,1)))?></div><div class="side-user-copy"><strong><?=e($u['name'])?></strong><small><?= $admin?'Administrator':'Customer' ?></small></div></div><form action="<?=e(url('logout.php'))?>" method="post"><?=csrf_field()?><button class="nav-link logout-button" type="submit"><?=svg_icon('logout')?><span>Log out</span></button></form></div>
</aside>
<div class="main-area"><header class="topbar"><button class="mobile-menu icon-button" id="menu-toggle" type="button" aria-label="Open navigation"><?=svg_icon('menu')?></button><span class="topbar-context"><?= $admin?'Admin workspace':'Customer workspace' ?></span><div class="topbar-right"><span class="topbar-greeting">Hi, <?=e(explode(' ',$u['name'])[0])?></span><div class="avatar avatar-sm"><?=e(strtoupper(substr($u['name'],0,1)))?></div></div></header>
<main class="page-container"><div class="page-heading"><div><h1><?=e($title)?></h1><?php if($subtitle):?><p class="page-subtitle"><?=e($subtitle)?></p><?php endif;?></div></div>
<?php foreach (consume_flash() as [$type,$message]):?><div class="notice notice-<?=e($type)?>" role="status"><?=svg_icon($type==='success'?'check':'info')?><?=e($message)?></div><?php endforeach;?>
<?php
}
function page_footer(): void { echo '</main><footer class="app-footer">PrintFlow · Release 1 <span>Made for simpler printing.</span></footer></div></div><div id="mobile-overlay" class="mobile-overlay"></div></body></html>'; }
function blank_header(string $title): void { ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?=e($title)?> · <?=e(APP_NAME)?></title><link rel="stylesheet" href="<?=e(url('assets/css/style.css'))?>"><script defer src="<?=e(url('assets/js/app.js'))?>"></script></head><body class="auth-body"><div class="auth-layout"><aside class="auth-side"><div class="auth-side-top"><div class="brand brand-inverted"><span class="brand-mark"><?=svg_icon('printer')?></span><span>Print<span class="brand-accent">Flow</span><small>PRINT MANAGEMENT</small></span></div></div><div class="auth-side-content"><div class="auth-eyebrow">PRINTING, MADE SIMPLE</div><h1>From upload<br>to pickup.<br><em>Without the hassle.</em></h1><p>Submit your files, choose your print preferences, and track every request in one place.</p><div class="auth-illustration"><span class="auth-paper paper-a"></span><span class="auth-paper paper-b"><span class="auth-illustration-icon"><?=svg_icon('printer')?></span><span class="fake-line"></span><span class="fake-line short"></span></span><span class="auth-float"><?=svg_icon('check')?> Ready for pickup</span></div></div><div class="auth-foot">Simple requests. Clear updates. Faster pickups.</div></aside><main class="auth-main"><div class="auth-form-shell"> <?php foreach (consume_flash() as [$type,$msg]):?><div class="notice notice-<?=e($type)?>"><?=e($msg)?></div><?php endforeach; ?><?php }
function blank_footer(): void { echo '</div></main></div></body></html>'; }

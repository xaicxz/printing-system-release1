<?php
require_once __DIR__.'/../app/bootstrap.php';
if (!is_post()) { http_response_code(405); exit('Method not allowed.'); }
require_csrf();
$_SESSION=[];
if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']); }
session_destroy(); redirect('login.php');

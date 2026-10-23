<?php
require_once __DIR__.'/../app/bootstrap.php';
$u=current_user();
redirect($u ? ($u['role']==='admin'?'admin/dashboard.php':'customer/dashboard.php') : 'login.php');

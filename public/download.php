<?php
require_once __DIR__.'/../app/bootstrap.php';
$u=require_login();$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$id)forbidden();
$o=get_order((int)$id,$u);
$path=PRIVATE_FILE_DIR.'/'.$o['stored_name'];
if(!is_file($path)||!is_readable($path)){http_response_code(404);exit('PDF file is not available.');}
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="print-request.pdf"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header('Content-Security-Policy: sandbox');
header('Content-Length: '.filesize($path));
readfile($path);exit;

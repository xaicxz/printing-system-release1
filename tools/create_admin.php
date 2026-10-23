<?php
if (PHP_SAPI!=='cli') { http_response_code(403); exit('CLI only.'); }
require_once __DIR__.'/../app/bootstrap.php';
try {
    $name=trim(readline('Admin full name: '));
    $email=strtolower(trim(readline('Admin email: ')));
    $password=readline('Admin password (visible in terminal; 8+ chars): ');
    if (mb_strlen($name)<2 || !filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<8) throw new RuntimeException('Invalid name, email or password.');
    $s=db()->prepare("INSERT INTO users (full_name,email,password_hash,role) VALUES (?,?,?,'admin')");
    $s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
    echo "Admin account created.\n";
} catch (Throwable $e) { fwrite(STDERR,"Error: ".$e->getMessage()."\n"); exit(1); }

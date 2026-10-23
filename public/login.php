<?php
require_once __DIR__.'/../app/layout.php';
if (current_user()) redirect('index.php');
$error='';
if (is_post()) {
    require_csrf();
    $email=strtolower(trim((string)($_POST['email']??'')));
    $password=(string)($_POST['password']??'');
    $tries=$_SESSION['login_attempts']??[];
    $tries=array_values(array_filter($tries,fn($t)=>$t>time()-900));
    if (count($tries)>=7) { $error='Too many attempts. Try again after 15 minutes.'; }
    elseif (!filter_var($email,FILTER_VALIDATE_EMAIL) || $password==='') $error='Enter your email and password.';
    else {
        try {
            $stmt=db()->prepare('SELECT id,full_name,email,password_hash,role FROM users WHERE email=? AND is_active=1');
            $stmt->execute([$email]); $u=$stmt->fetch();
            if ($u && password_verify($password,$u['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user']=['id'=>(int)$u['id'],'name'=>$u['full_name'],'email'=>$u['email'],'role'=>$u['role']];
                unset($_SESSION['login_attempts']); redirect('index.php');
            }
            $tries[]=time(); $_SESSION['login_attempts']=$tries; $error='Incorrect email or password.';
        } catch (Throwable $e) { error_log((string)$e); $error='Unable to sign in. Check that the database is configured.'; }
    }
}
blank_header('Sign in'); ?>
<div class="auth-head"><span class="eyebrow">WELCOME BACK</span><h2>Sign in to PrintFlow</h2><p>Track your requests and manage your printing.</p></div>
<?php if($error):?><div class="notice notice-error" role="alert"><?=e($error)?></div><?php endif;?>
<form method="post" class="stack gap-lg"><?=csrf_field()?><div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" autocomplete="email" value="<?=e($_POST['email']??'')?>" placeholder="you@example.com" maxlength="190" required></div><div class="field"><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required></div><button class="btn btn-primary btn-full btn-lg" type="submit">Sign in <?=svg_icon('arrow')?></button></form>
<p class="auth-switch">New to PrintFlow? <a href="<?=e(url('register.php'))?>">Create an account</a></p>
<?php blank_footer(); ?>

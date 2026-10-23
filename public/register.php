<?php
require_once __DIR__.'/../app/layout.php';
if (current_user()) redirect('index.php');
$error='';
if (is_post()) {
    require_csrf();
    $name=trim(preg_replace('/\s+/u',' ',(string)($_POST['name']??'')));
    $email=strtolower(trim((string)($_POST['email']??'')));
    $pass=(string)($_POST['password']??'');
    $confirm=(string)($_POST['confirm']??'');
    if (mb_strlen($name)<2||mb_strlen($name)>120) $error='Enter your full name (2–120 characters).';
    elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190) $error='Enter a valid email address.';
    elseif (strlen($pass)<8||strlen($pass)>72) $error='Use a password between 8 and 72 characters.';
    elseif ($pass!==$confirm) $error='Passwords do not match.';
    else {
        try {
            $check=db()->prepare('SELECT id FROM users WHERE email=?'); $check->execute([$email]);
            if ($check->fetch()) $error='An account with this email already exists.';
            else {
                $stmt=db()->prepare("INSERT INTO users (full_name,email,password_hash,role) VALUES (?,?,?,'customer')");
                $stmt->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT)]);
                session_regenerate_id(true);
                $_SESSION['user']=['id'=>(int)db()->lastInsertId(),'name'=>$name,'email'=>$email,'role'=>'customer'];
                flash('success','Your account is ready. You can now submit a print request.');
                redirect('customer/dashboard.php');
            }
        } catch (Throwable $e) { error_log((string)$e); $error='Could not create your account. Try again.'; }
    }
}
blank_header('Create account'); ?>
<div class="auth-head"><span class="eyebrow">LET’S GET STARTED</span><h2>Create your account</h2><p>Just a few details and you’re ready to print.</p></div>
<?php if($error):?><div class="notice notice-error" role="alert"><?=e($error)?></div><?php endif;?>
<form method="post" class="stack gap-md"><?=csrf_field()?><div class="field"><label for="name">Full name</label><input id="name" type="text" name="name" value="<?=e($_POST['name']??'')?>" placeholder="Your full name" autocomplete="name" maxlength="120" required></div><div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" value="<?=e($_POST['email']??'')?>" placeholder="you@example.com" autocomplete="email" maxlength="190" required></div><div class="field"><label for="pass">Password <span class="muted">· At least 8 characters</span></label><input id="pass" type="password" name="password" placeholder="Create a password" autocomplete="new-password" minlength="8" required></div><div class="field"><label for="confirm">Confirm password</label><input id="confirm" type="password" name="confirm" placeholder="Repeat password" autocomplete="new-password" minlength="8" required></div><button class="btn btn-primary btn-full btn-lg" type="submit">Create account <?=svg_icon('arrow')?></button></form>
<p class="auth-switch">Already have an account? <a href="<?=e(url('login.php'))?>">Sign in</a></p>
<?php blank_footer(); ?>

<?php
require_once '../includes/auth.php';
require_once '../config/email.php';

$sent=false;

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $email=filter_var($_POST['email']??'',FILTER_VALIDATE_EMAIL);

    if($email){
        $q=$pdo->prepare("SELECT id,name,email FROM users WHERE email=? AND account_status='active'");
        $q->execute([$email]);
        $u=$q->fetch();

        if($u){
            $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id=?')->execute([$u['id']]);

            $token=bin2hex(random_bytes(32));
            $tokenHash=hash('sha256',$token);

            $q=$pdo->prepare('INSERT INTO password_reset_tokens(user_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 1 HOUR))');
            $q->execute([$u['id'],$tokenHash]);

            $config=email_config();
            $resetUrl=$config['app_url'].'/auth/reset-password.php?token='.urlencode($token);
            send_password_reset_email($u['email'],$u['name'],$resetUrl);
        }
    }

    $sent=true;
}

$title='Forgot password';
require '../includes/header.php';
?>
<h1>Forgot password</h1>
<?php if($sent):?>
<div class="alert alert-success">
    If an active StoreBridge account exists for that email, a password reset link has been sent.
    Please check your inbox and spam folder.
</div>
<a class="btn btn-outline-success" href="/auth/login.php">Back to login</a>
<?php else:?>
<p class="text-muted">Enter the email address associated with your StoreBridge account.</p>
<form method="post" class="card card-body col-lg-6">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <input class="form-control mb-2" type="email" name="email" placeholder="Email" required>
    <button class="btn btn-success">Send reset link</button>
</form>
<?php endif;?>
<?php require '../includes/footer.php';?>
<?php
require_once '../includes/auth.php';
require_once '../includes/google_oauth.php';

$notice = $_SESSION['auth_notice'] ?? '';
unset($_SESSION['auth_notice']);
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $q=$pdo->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
    $q->execute([strtolower(trim($_POST['email']??''))]);
    $u=$q->fetch();

    if($u && !empty($u['password_hash']) && password_verify($_POST['password']??'',$u['password_hash'])){
        if($u['account_status']==='suspended'){
            $error='This account is suspended.';
        }else{
            session_regenerate_id(true);
            $_SESSION['user_id']=(int)$u['id'];
            go(($u['role']??'seller')==='buyer' ? '/buyer/dashboard.php' : (($u['role']??'')==='admin' ? '/admin/' : '/seller/dashboard.php'));
        }
    }else{
        $error='Invalid credentials.';
    }
}

$title='Login';
require '../includes/header.php';
?>
<h1>Log in to Choosery</h1>
<p class="text-muted">Access your buyer or seller account.</p>
<?php if($notice):?><div class="alert alert-info"><?=e($notice)?></div><?php endif;?>
<?php if(!empty($error)):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<div class="card card-body col-lg-6 mb-3">
    <?php if(google_oauth_enabled()):?>
        <a class="btn btn-outline-dark w-100" href="/auth/google-start.php">Continue with Google</a>
    <?php else:?>
        <button class="btn btn-outline-secondary w-100" type="button" disabled>Google sign-in setup pending</button>
        <p class="small text-muted mt-2 mb-0">Google sign-in will be available after the platform credentials are configured.</p>
    <?php endif;?>
</div>
<form method="post" class="card card-body col-lg-6">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <label class="form-label" for="login-email">Email</label>
    <input id="login-email" class="form-control mb-2" type="email" name="email" autocomplete="email" placeholder="Email" required>
    <label class="form-label" for="login-password">Password</label>
    <input id="login-password" class="form-control mb-2" type="password" name="password" autocomplete="current-password" placeholder="Password" required>
    <button class="btn btn-success">Login</button>
    <div class="mt-3"><a href="/auth/forgot-password.php">Forgot password?</a></div>
    <div class="mt-2 text-muted">Don't have an account? <a href="/auth/register.php" class="fw-semibold">Register.</a></div>
</form>
<?php require '../includes/footer.php';?>

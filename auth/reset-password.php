<?php
require_once '../includes/auth.php';

$token=trim($_GET['token']??$_POST['token']??'');
$tokenHash=$token!==''?hash('sha256',$token):'';
$valid=false;
$userId=null;

if($tokenHash!==''){
    $q=$pdo->prepare("SELECT user_id FROM password_reset_tokens WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW()");
    $q->execute([$tokenHash]);
    $row=$q->fetch();
    if($row){
        $valid=true;
        $userId=(int)$row['user_id'];
    }
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();

    if(!$valid){
        $error='This reset link is invalid or has expired.';
    }else{
        $password=$_POST['password']??'';
        $confirm=$_POST['password_confirm']??'';

        if(strlen($password)<8){
            $error='Password must be at least 8 characters.';
        }elseif($password!==$confirm){
            $error='Passwords do not match.';
        }else{
            $pdo->beginTransaction();
            try{
                $q=$pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
                $q->execute([password_hash($password,PASSWORD_DEFAULT),$userId]);

                $q=$pdo->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE user_id=? AND token_hash=?');
                $q->execute([$userId,$tokenHash]);

                $q=$pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id=? AND token_hash<>?');
                $q->execute([$userId,$tokenHash]);

                $pdo->commit();
                $success=true;
                $valid=false;
            }catch(Throwable $e){
                if($pdo->inTransaction())$pdo->rollBack();
                $error='Unable to reset your password. Please request a new link.';
            }
        }
    }
}

$title='Reset password';
require '../includes/header.php';
?>
<h1>Reset password</h1>

<?php if(!empty($success)):?>
<div class="alert alert-success">Your password has been reset successfully.</div>
<a class="btn btn-success" href="/auth/login.php">Go to login</a>
<?php elseif(!$valid):?>
<div class="alert alert-danger">
    This reset link is invalid or has expired. Please request a new one.
</div>
<a class="btn btn-outline-success" href="/auth/forgot-password.php">Request a new link</a>
<?php else:?>
<?php if(!empty($error)):?>
<div class="alert alert-danger"><?=e($error)?></div>
<?php endif;?>
<form method="post" class="card card-body col-lg-6">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <input type="hidden" name="token" value="<?=e($token)?>">
    <input class="form-control mb-2" type="password" name="password" placeholder="New password (8+ characters)" required>
    <input class="form-control mb-2" type="password" name="password_confirm" placeholder="Confirm new password" required>
    <button class="btn btn-success">Reset password</button>
</form>
<?php endif;?>
<?php require '../includes/footer.php';?>
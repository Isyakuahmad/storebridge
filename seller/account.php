<?php
require_once '../includes/auth.php';
login_required();

$q=$pdo->prepare('SELECT id,name,email,password_hash FROM users WHERE id=?');
$q->execute([$_SESSION['user_id']]);
$u=$q->fetch();

if(!$u){
    $_SESSION=[];
    session_destroy();
    go('/auth/login.php');
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $action=$_POST['action']??'';

    if($action==='profile'){
        $name=trim($_POST['name']??'');
        $email=filter_var($_POST['email']??'',FILTER_VALIDATE_EMAIL);

        if(!$name||!$email){
            $error='Name and a valid email are required.';
        }else{
            try{
                $q=$pdo->prepare('UPDATE users SET name=?,email=? WHERE id=?');
                $q->execute([$name,$email,$u['id']]);
                $success='Account details updated.';
                $u['name']=$name;
                $u['email']=$email;
            }catch(PDOException $x){
                $error='That email address is already in use.';
            }
        }
    }elseif($action==='password'){
        $current=$_POST['current_password']??'';
        $new=$_POST['new_password']??'';
        $confirm=$_POST['confirm_password']??'';

        if(!password_verify($current,$u['password_hash'])){
            $error='Current password is incorrect.';
        }elseif(strlen($new)<8){
            $error='New password must be at least 8 characters.';
        }elseif($new!==$confirm){
            $error='New passwords do not match.';
        }else{
            $q=$pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
            $q->execute([password_hash($new,PASSWORD_DEFAULT),$u['id']]);
            $success='Password updated successfully.';
        }
    }
}

$title='Account settings';
require '../includes/header.php';
?>
<h1>Account settings</h1>

<?php if(!empty($error)):?>
<div class="alert alert-danger"><?=e($error)?></div>
<?php endif;?>

<?php if(!empty($success)):?>
<div class="alert alert-success"><?=e($success)?></div>
<?php endif;?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card card-body">
            <h2 class="h5">Account details</h2>
            <form method="post">
                <input type="hidden" name="csrf" value="<?=e(csrf())?>">
                <input type="hidden" name="action" value="profile">
                <label class="form-label">Name</label>
                <input class="form-control mb-2" name="name" value="<?=e($u['name'])?>" required>

                <label class="form-label">Email</label>
                <input class="form-control mb-3" type="email" name="email" value="<?=e($u['email'])?>" required>

                <button class="btn btn-success">Save account details</button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card card-body">
            <h2 class="h5">Change password</h2>
            <form method="post">
                <input type="hidden" name="csrf" value="<?=e(csrf())?>">
                <input type="hidden" name="action" value="password">
                <input class="form-control mb-2" type="password" name="current_password" placeholder="Current password" required>
                <input class="form-control mb-2" type="password" name="new_password" placeholder="New password (8+ characters)" required>
                <input class="form-control mb-3" type="password" name="confirm_password" placeholder="Confirm new password" required>
                <button class="btn btn-outline-success">Change password</button>
            </form>
        </div>
    </div>
</div>

<?php require '../includes/footer.php';?>
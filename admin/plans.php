<?php
require_once '../includes/auth.php';
admin_required();
$error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $id=(int)($_POST['id']??0);
    $raw=trim($_POST['monthly_price']??'');
    if($raw==='' || !is_numeric($raw) || (float)$raw<0 || (float)$raw>9999999999.99){
        $error='Enter a valid non-negative monthly price.';
    }else{
        $q=$pdo->prepare("UPDATE plans SET monthly_price=? WHERE id=? AND code IN ('moderate','premium')");
        $q->execute([number_format((float)$raw,2,'.',''),$id]);
        if($q->rowCount()>=0){audit_log('plan_price_updated','plan',$id,'Monthly price set to '.number_format((float)$raw,2,'.',''));$success='Plan price saved.';}
    }
}
$plans=$pdo->query('SELECT * FROM plans ORDER BY FIELD(code,"free","moderate","premium")')->fetchAll();
$title='Subscription plans'; require '../includes/header.php';
?>
<h1>Subscription plans</h1>
<p class="text-muted">Set monthly prices for Moderate and Premium. A paid plan stays unavailable until its price is configured. Free plan price is fixed at ₦0.</p>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<?php if($success):?><div class="alert alert-success"><?=e($success)?></div><?php endif;?>
<?php foreach($plans as $p):?>
<div class="card card-body mb-3">
 <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
  <div><h2 class="h5 mb-1"><?=e($p['name'])?></h2><p class="text-muted mb-0"><?=e($p['description'])?></p></div>
  <?php if($p['code']==='free'):?><span class="badge text-bg-success">₦0 / month</span><?php else:?>
  <form method="post" class="d-flex gap-2 align-items-center">
   <input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="id" value="<?=e($p['id'])?>">
   <label class="visually-hidden" for="price<?=e($p['id'])?>">Monthly price in naira</label>
   <div class="input-group"><span class="input-group-text">₦</span><input id="price<?=e($p['id'])?>" class="form-control" type="number" name="monthly_price" min="0.01" step="0.01" value="<?=e($p['monthly_price']??'')?>" placeholder="Monthly price" required></div>
   <button class="btn btn-success">Save price</button>
  </form><?php endif;?>
 </div>
</div>
<?php endforeach;?>
<?php require '../includes/footer.php';?>

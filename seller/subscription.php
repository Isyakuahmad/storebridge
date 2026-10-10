<?php
require_once '../includes/auth.php';
login_required();
$userId=(int)$_SESSION['user_id']; $error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $planId=(int)($_POST['plan_id']??0);
    $sender=trim($_POST['sender_name']??'');
    $reference=trim($_POST['transfer_reference']??'');
    $q=$pdo->prepare('SELECT id,code,name,monthly_price FROM plans WHERE id=? AND is_active=1');
    $q->execute([$planId]); $plan=$q->fetch();
    if(!$plan || $plan['code']==='free' || $plan['monthly_price']===null || (float)$plan['monthly_price']<=0){
        $error='That paid plan is not currently available. Please contact support.';
    }elseif(strlen($sender)<2 || strlen($sender)>160 || strlen($reference)<3 || strlen($reference)>190){
        $error='Enter the bank transfer sender name and transfer reference.';
    }else{
        $q=$pdo->prepare("SELECT id FROM payments WHERE user_id=? AND status='pending' LIMIT 1");
        $q->execute([$userId]);
        if($q->fetch()){$error='You already have a payment awaiting review. Please wait for admin confirmation.';}
        else{
            $q=$pdo->prepare("INSERT INTO payments(user_id,plan_id,amount,billing_cycle,payment_method,sender_name,transfer_reference) VALUES(?,?,?,'monthly','bank_transfer',?,?)");
            $q->execute([$userId,$plan['id'],$plan['monthly_price'],$sender,$reference]);
            audit_log('subscription_payment_submitted','payment',(int)$pdo->lastInsertId(),'Plan '.$plan['code'].'; amount '.$plan['monthly_price']);
            $success='Payment details submitted. Your plan will change only after an admin verifies the bank transfer.';
        }
    }
}
$q=$pdo->prepare("SELECT s.*,p.name AS plan_name FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE s.user_id=? AND s.status='active' AND s.starts_at<=NOW() AND s.ends_at>NOW() ORDER BY s.ends_at DESC LIMIT 1");
$q->execute([$userId]); $current=$q->fetch();
$q=$pdo->prepare("SELECT p.*,pl.name AS plan_name FROM payments p JOIN plans pl ON pl.id=p.plan_id WHERE p.user_id=? ORDER BY p.created_at DESC LIMIT 10");
$q->execute([$userId]); $payments=$q->fetchAll();
$plans=$pdo->query("SELECT * FROM plans WHERE is_active=1 ORDER BY FIELD(code,'free','moderate','premium')")->fetchAll();
$title='Subscription'; require '../includes/header.php';
?>
<h1>Subscription &amp; plan</h1>
<p class="text-muted">Monthly billing · Bank transfer · Admin verifies payment manually.</p>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<?php if($success):?><div class="alert alert-success"><?=e($success)?></div><?php endif;?>
<div class="card card-body mb-4">
 <h2 class="h5">Your current plan</h2>
 <?php if($current):?><div class="fs-4"><?=e($current['plan_name'])?></div><div class="text-muted">Active until <?=e(date('M j, Y',strtotime($current['ends_at'])))?></div>
 <?php else:?><div class="fs-4">Free</div><p class="text-muted mb-0">No active paid subscription.</p><?php endif;?>
</div>
<h2 class="h4">Choose a monthly plan</h2>
<div class="row g-3 mb-4">
<?php foreach($plans as $p):?><div class="col-md-4"><div class="card card-body h-100">
<h3 class="h5"><?=e($p['name'])?></h3><div class="fs-4 mb-2"><?=($p['monthly_price']===null?'Price not set':money($p['monthly_price']).' / month')?></div>
<p class="text-muted small"><?=e($p['description'])?></p>
<?php if($p['code']==='free'):?><span class="text-muted small">Default plan</span>
<?php elseif($p['monthly_price']===null || (float)$p['monthly_price']<=0):?><span class="badge text-bg-secondary">Not available yet</span>
<?php else:?>
<form method="post">
<input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="plan_id" value="<?=e($p['id'])?>">
<label class="form-label">Name on bank transfer</label><input class="form-control mb-2" name="sender_name" maxlength="160" required>
<label class="form-label">Transfer reference / narration</label><input class="form-control mb-3" name="transfer_reference" maxlength="190" required>
<p class="small text-muted">Transfer <?=e(money($p['monthly_price']))?> to the official Choosery bank account supplied by support. Do not submit until you have made the transfer.</p>
<button class="btn btn-success w-100">Submit transfer for review</button>
</form><?php endif;?>
</div></div><?php endforeach;?>
</div>
<div class="card card-body"><h2 class="h5">Payment history</h2>
<?php if(!$payments):?><p class="text-muted mb-0">No payment submissions yet.</p><?php else:?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Date</th><th>Plan</th><th>Amount</th><th>Transfer reference</th><th>Status</th></tr></thead><tbody>
<?php foreach($payments as $p):?><tr><td><?=e(date('M j, Y',strtotime($p['created_at'])))?></td><td><?=e($p['plan_name'])?></td><td><?=e(money($p['amount']))?></td><td><?=e($p['transfer_reference'])?></td><td><span class="badge text-bg-<?=($p['status']==='confirmed'?'success':($p['status']==='pending'?'warning':'secondary'))?>"><?=e(ucfirst($p['status']))?></span><?php if($p['admin_note']):?><div class="small text-muted"><?=e($p['admin_note'])?></div><?php endif;?></td></tr><?php endforeach;?>
</tbody></table></div><?php endif;?></div>
<?php require '../includes/footer.php';?>

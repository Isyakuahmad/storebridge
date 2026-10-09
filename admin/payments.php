<?php
require_once '../includes/auth.php';
admin_required();
$error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 csrf_check(); $id=(int)($_POST['payment_id']??0); $action=$_POST['action']??''; $note=trim($_POST['admin_note']??'');
 if(!in_array($action,['confirm','reject'],true)){$error='Invalid action.';}
 else{
  try{
   $pdo->beginTransaction();
   $q=$pdo->prepare("SELECT p.*,pl.code AS plan_code,pl.name AS plan_name,pl.monthly_price FROM payments p JOIN plans pl ON pl.id=p.plan_id WHERE p.id=? FOR UPDATE");
   $q->execute([$id]); $payment=$q->fetch();
   if(!$payment || $payment['status']!=='pending'){$pdo->rollBack();$error='This payment is no longer pending.';}
   elseif($action==='confirm' && ($payment['monthly_price']===null || number_format((float)$payment['amount'],2,'.','')!==number_format((float)$payment['monthly_price'],2,'.','') || (float)$payment['amount']<=0 || $payment['plan_code']==='free')){$pdo->rollBack();$error='Payment amount no longer matches the configured plan price. Resolve the plan price before confirming.';}
   else{
    if($action==='confirm'){
      $start=new DateTimeImmutable('now');
      $q=$pdo->prepare("SELECT ends_at FROM subscriptions WHERE user_id=? AND status='active' AND ends_at>? ORDER BY ends_at DESC LIMIT 1 FOR UPDATE");
      $q->execute([$payment['user_id'],$start->format('Y-m-d H:i:s')]); $existingEnd=$q->fetchColumn();
      if($existingEnd && strtotime($existingEnd)>$start->getTimestamp()){$start=new DateTimeImmutable($existingEnd);}
      $end=$start->modify('+1 month');
      $q=$pdo->prepare("UPDATE subscriptions SET status='expired' WHERE user_id=? AND status='active' AND ends_at<=?");
      $q->execute([$payment['user_id'],$start->format('Y-m-d H:i:s')]);
      $q=$pdo->prepare("INSERT INTO subscriptions(user_id,plan_id,payment_id,status,starts_at,ends_at) VALUES(?,?,?,'active',?,?)");
      $q->execute([$payment['user_id'],$payment['plan_id'],$payment['id'],$start->format('Y-m-d H:i:s'),$end->format('Y-m-d H:i:s')]);
      $status='confirmed'; $auditAction='subscription_payment_confirmed';
    }else{$status='rejected';$auditAction='subscription_payment_rejected';}
    $q=$pdo->prepare('UPDATE payments SET status=?,admin_note=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?');
    $q->execute([$status,($note!==''?substr($note,0,500):null),$_SESSION['user_id'],$id]);
    audit_log($auditAction,'payment',$id,$payment['plan_name'].'; seller ID '.$payment['user_id']);
    $pdo->commit(); $success=($action==='confirm'?'Payment confirmed and one-month subscription activated.':'Payment rejected.');
   }
  }catch(Throwable $ex){if($pdo->inTransaction())$pdo->rollBack();$error='Unable to process this payment. Please retry and check the database if it persists.';}
 }
}
$q=$pdo->query("SELECT p.*,u.name AS seller_name,u.email,pl.name AS plan_name,admin.name AS reviewer_name FROM payments p JOIN users u ON u.id=p.user_id JOIN plans pl ON pl.id=p.plan_id LEFT JOIN users admin ON admin.id=p.reviewed_by ORDER BY (p.status='pending') DESC,p.created_at DESC LIMIT 300");
$rows=$q->fetchAll();
$title='Payment review'; require '../includes/header.php';
?>
<h1>Payment review</h1><p class="text-muted">Verify the actual bank credit in your bank account before confirming. A submitted reference alone is not proof of payment.</p>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
<?php if($success):?><div class="alert alert-success"><?=e($success)?></div><?php endif;?>
<?php if(!$rows):?><p class="text-muted">No payments submitted yet.</p><?php else:?>
<div class="table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>Seller</th><th>Plan / amount</th><th>Transfer details</th><th>Status</th><th>Review</th></tr></thead><tbody>
<?php foreach($rows as $p):?><tr>
<td><strong><?=e($p['seller_name'])?></strong><div class="small"><?=e($p['email'])?></div><div class="small text-muted"><?=e($p['created_at'])?></div></td>
<td><?=e($p['plan_name'])?><div><?=e(money($p['amount']))?> / month</div></td>
<td>Sender: <?=e($p['sender_name'])?><br>Reference: <?=e($p['transfer_reference'])?></td>
<td><span class="badge text-bg-<?=($p['status']==='confirmed'?'success':($p['status']==='pending'?'warning':'secondary'))?>"><?=e(ucfirst($p['status']))?></span><?php if($p['admin_note']):?><div class="small text-muted"><?=e($p['admin_note'])?></div><?php endif;?></td>
<td><?php if($p['status']==='pending'):?><form method="post" class="d-grid gap-2"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="payment_id" value="<?=e($p['id'])?>"><input class="form-control form-control-sm" name="admin_note" maxlength="500" placeholder="Optional note"><button class="btn btn-sm btn-success" name="action" value="confirm" onclick="return confirm('Confirm you have verified this money in the bank account?')">Confirm payment</button><button class="btn btn-sm btn-outline-danger" name="action" value="reject" onclick="return confirm('Reject this payment submission?')">Reject</button></form><?php else:?><div class="small text-muted"><?=e($p['reviewer_name']??'')?><br><?=e($p['reviewed_at']??'')?></div><?php endif;?></td>
</tr><?php endforeach;?>
</tbody></table></div><?php endif;?>
<?php require '../includes/footer.php';?>

<?php
require_once '../includes/auth.php';
admin_required();

$q=$pdo->query("SELECT
    u.id,u.name,u.email,u.account_status,u.created_at,u.referred_by_user_id,
    r.name AS referrer_name,r.email AS referrer_email
    FROM users u
    LEFT JOIN users r ON r.id=u.referred_by_user_id
    WHERE u.referred_by_user_id IS NOT NULL
    ORDER BY u.created_at DESC");
$rows=$q->fetchAll();

$title='Referral tracking';
require '../includes/header.php';
?>
<h1>Referral tracking</h1>
<p class="text-muted">Track who invited each referred seller. Payment qualification and commission can be connected to this later.</p>
<div class="card card-body">
<?php if(!$rows): ?>
<p class="mb-0 text-muted">No referrals yet.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table align-middle mb-0">
<thead><tr><th>Referred seller</th><th>Referrer</th><th>Account status</th><th>Joined</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
<td><strong><?=e($r['name'])?></strong><div class="small text-muted"><?=e($r['email'])?></div></td>
<td><?=e($r['referrer_name'])?><div class="small text-muted"><?=e($r['referrer_email'])?></div></td>
<td><span class="badge text-bg-<?=($r['account_status']==='active'?'success':'secondary')?>"><?=e(ucfirst($r['account_status']))?></span></td>
<td><?=e(date('M j, Y',strtotime($r['created_at'])))?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
<?php require '../includes/footer.php'; ?>

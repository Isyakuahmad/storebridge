<?php
require_once '../includes/auth.php';
admin_required();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $note = trim($_POST['admin_note'] ?? '');
    if ($id > 0 && in_array($status, ['reviewed', 'dismissed', 'open'], true)) {
        $q = $pdo->prepare("UPDATE registration_flags SET status=?, admin_note=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?");
        $q->execute([$status, $note !== '' ? mb_substr($note, 0, 500) : null, $_SESSION['user_id'], $id]);
        audit_log('registration_flag_'.$status, 'registration_flag', $id, $note !== '' ? mb_substr($note, 0, 300) : null);
    }
    go('/admin/registration-flags.php');
}
$filter = $_GET['status'] ?? 'open';
if (!in_array($filter, ['open', 'reviewed', 'dismissed', 'all'], true)) $filter = 'open';
$sql = "SELECT f.*, u.name AS account_name, u.email AS account_email, u.phone AS account_phone,
        r.name AS related_name, r.email AS related_email, r.phone AS related_phone,
        a.name AS reviewer_name FROM registration_flags f JOIN users u ON u.id=f.user_id
        LEFT JOIN users r ON r.id=f.related_user_id LEFT JOIN users a ON a.id=f.reviewed_by";
if ($filter !== 'all') $sql .= " WHERE f.status=".$pdo->quote($filter);
$sql .= " ORDER BY f.created_at DESC LIMIT 200";
$flags = $pdo->query($sql)->fetchAll();
$title = 'Registration flags';
require '../includes/header.php';
?>
<h1>Registration &amp; identity flags</h1>
<p class="text-muted">Duplicate email/phone registration attempts for review. A flag is a signal, not proof of fraud. Use context and avoid penalizing legitimate shared contact details.</p>
<div class="btn-group mb-3" role="group" aria-label="Filter registration flags">
<a class="btn btn-outline-success" href="?status=open">Open</a><a class="btn btn-outline-success" href="?status=reviewed">Reviewed</a><a class="btn btn-outline-success" href="?status=dismissed">Dismissed</a><a class="btn btn-outline-success" href="?status=all">All</a>
</div>
<?php foreach ($flags as $f): ?>
<div class="card mb-3"><div class="card-body">
<div class="d-flex flex-wrap justify-content-between gap-2"><strong><?=e(str_replace('_', ' ', ucfirst($f['flag_type'])))?></strong><span class="badge <?=$f['status']==='open'?'bg-warning text-dark':($f['status']==='reviewed'?'bg-success':'bg-secondary')?>"><?=e($f['status'])?></span></div>
<div class="mt-2">Existing account: <?=e($f['account_name'])?> · <?=e($f['account_email'])?> · <?=e($f['account_phone'] ?: 'No phone on file')?></div>
<?php if ($f['related_user_id']): ?><div>Related account: <?=e($f['related_name'])?> · <?=e($f['related_email'])?> · <?=e($f['related_phone'] ?: 'No phone on file')?></div><?php endif; ?>
<div class="small text-muted mt-1">Flag #<?=e($f['id'])?> · <?=e($f['created_at'])?><?php if ($f['reviewed_at']): ?> · reviewed <?=e($f['reviewed_at'])?> by <?=e($f['reviewer_name'] ?? 'admin')?><?php endif; ?></div>
<?php if ($f['admin_note']): ?><p class="small mt-2 mb-1">Note: <?=e($f['admin_note'])?></p><?php endif; ?>
<form method="post" class="row g-2 mt-2"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="id" value="<?=e($f['id'])?>">
<div class="col-md-7"><input class="form-control" name="admin_note" maxlength="500" placeholder="Review note (optional)" value="<?=e($f['admin_note'] ?? '')?>"></div>
<div class="col-md-3"><select class="form-select" name="status"><option value="open" <?=$f['status']==='open'?'selected':''?>>Open</option><option value="reviewed" <?=$f['status']==='reviewed'?'selected':''?>>Reviewed</option><option value="dismissed" <?=$f['status']==='dismissed'?'selected':''?>>Dismissed</option></select></div>
<div class="col-md-2"><button class="btn btn-success w-100">Save</button></div></form>
</div></div>
<?php endforeach; ?>
<?php if (!$flags): ?><div class="alert alert-light border">No registration flags match this filter.</div><?php endif; ?>
<?php require '../includes/footer.php'; ?>

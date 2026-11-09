<?php
require_once '../includes/auth.php';
admin_required();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $storeId = (int)($_POST['store_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $note = trim((string)($_POST['note'] ?? ''));

    if ($storeId > 0 && in_array($action, ['verified', 'rejected'], true)) {
        if ($action === 'rejected' && $note === '') {
            $error = 'Add a short reason when rejecting a verification request.';
        } else {
            $q = $pdo->prepare("UPDATE stores SET verification_status=?, verification_reviewed_by=?, verification_reviewed_at=NOW(), verification_note=? WHERE id=? AND verification_status='pending'");
            $q->execute([$action, $_SESSION['user_id'], $note !== '' ? $note : null, $storeId]);
            if ($q->rowCount()) {
                audit_log('seller_business_'.$action, 'store', $storeId, $note);
            }
            go('/admin/seller-verification.php');
        }
    }
}

$filter = $_GET['status'] ?? 'pending';
$allowed = ['pending','verified','rejected','unsubmitted','all'];
if (!in_array($filter, $allowed, true)) $filter = 'pending';
$sql = "SELECT s.id,s.name,s.slug,s.description,s.business_country,s.business_state,s.business_city,s.business_area,s.business_address,s.verification_status,s.verification_submitted_at,s.verification_note,u.name AS seller_name,u.email
        FROM stores s JOIN users u ON u.id=s.user_id";
$params = [];
if ($filter !== 'all') { $sql .= " WHERE s.verification_status=?"; $params[] = $filter; }
$sql .= " ORDER BY CASE s.verification_status WHEN 'pending' THEN 0 ELSE 1 END,s.verification_submitted_at DESC,s.id DESC";
$q = $pdo->prepare($sql);
$q->execute($params);
$stores = $q->fetchAll();

$title = 'Seller business verification';
require '../includes/header.php';
?>
<h1>Seller business verification</h1>
<p class="text-muted">Review the business details submitted by sellers. Approval means the submitted profile was reviewed by Choosery; it is not a government identity or company-registration certification.</p>
<div class="d-flex flex-wrap gap-2 mb-3">
<?php foreach ($allowed as $item): ?><a class="btn btn-sm <?= $filter === $item ? 'btn-success' : 'btn-outline-success' ?>" href="/admin/seller-verification.php?status=<?=e($item)?>"><?=e(ucfirst($item))?></a><?php endforeach; ?>
</div>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<?php foreach ($stores as $store): ?>
<div class="card card-body mb-3">
    <div class="d-flex flex-wrap justify-content-between gap-2">
        <div><h2 class="h5 mb-1"><?=e($store['name'])?></h2><div class="small text-muted">Seller: <?=e($store['seller_name'])?> · <?=e($store['email'])?></div></div>
        <span class="badge <?= $store['verification_status']==='verified'?'text-bg-success':($store['verification_status']==='pending'?'text-bg-warning':'text-bg-secondary')?>"><?=e(ucfirst($store['verification_status']))?></span>
    </div>
    <?php if ($store['description']): ?><p class="mt-2 mb-1"><?=e($store['description'])?></p><?php endif; ?>
    <div class="mt-2"><strong>Location:</strong> <?=e(implode(', ', array_filter([$store['business_area'],$store['business_city'],$store['business_state'],$store['business_country']])))?></div>
    <?php if ($store['business_address']): ?><div><strong>Street address:</strong> <?=e($store['business_address'])?></div><?php endif; ?>
    <?php if ($store['verification_submitted_at']): ?><div class="small text-muted mt-1">Submitted <?=e($store['verification_submitted_at'])?></div><?php endif; ?>
    <?php if ($store['verification_note']): ?><div class="alert alert-secondary mt-2 mb-0">Previous review note: <?=e($store['verification_note'])?></div><?php endif; ?>
    <?php if ($store['verification_status']==='pending'): ?>
    <form method="post" class="mt-3">
        <input type="hidden" name="csrf" value="<?=e(csrf())?>">
        <input type="hidden" name="store_id" value="<?=e($store['id'])?>">
        <label class="form-label" for="note-<?=e($store['id'])?>">Review note (required for rejection)</label>
        <textarea class="form-control mb-2" id="note-<?=e($store['id'])?>" name="note" rows="2" maxlength="500"></textarea>
        <button class="btn btn-success" name="action" value="verified">Approve business profile</button>
        <button class="btn btn-outline-danger" name="action" value="rejected">Reject</button>
    </form>
    <?php endif; ?>
    <a class="btn btn-sm btn-outline-secondary mt-2 align-self-start" href="/public/store.php?slug=<?=e($store['slug'])?>" target="_blank" rel="noopener">View public store</a>
</div>
<?php endforeach; ?>
<?php if (!$stores): ?><p>No stores in this view.</p><?php endif; ?>
<?php require '../includes/footer.php'; ?>

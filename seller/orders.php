<?php
require_once '../includes/auth.php';
login_required();
$s = store();
if (!$s) go('/seller/store.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];
    $status = $_POST['status'] ?? 'pending';
    if (!in_array($status, $allowed, true)) {
        $error = 'Choose a valid order status.';
    } else {
        $q = $pdo->prepare('UPDATE orders SET status=? WHERE id=? AND store_id=?');
        $q->execute([$status, (int)($_POST['id'] ?? 0), $s['id']]);
        if (!$q->rowCount()) $notice = 'No order status change was made.';
        else $notice = 'Order status updated.';
    }
}

$q = $pdo->prepare('SELECT * FROM orders WHERE store_id=? ORDER BY created_at DESC');
$q->execute([$s['id']]);
$os = $q->fetchAll();
$orderItems = [];
if ($os) {
    $ids = array_map(static fn($o) => (int)$o['id'], $os);
    $in = implode(',', array_fill(0, count($ids), '?'));
    $q = $pdo->prepare("SELECT order_id,product_name,unit_price,quantity FROM order_items WHERE order_id IN ($in) ORDER BY id");
    $q->execute($ids);
    foreach ($q as $item) $orderItems[(int)$item['order_id']][] = $item;
}
$title = 'Orders';
require '../includes/header.php';
?>
<h1>Orders</h1>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<?php if (!empty($notice)): ?><div class="alert alert-info"><?=e($notice)?></div><?php endif; ?>
<?php foreach ($os as $o): ?>
<div class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
        <div><b>Order #<?=e($o['id'])?></b> — <?=e($o['customer_name'])?> — <strong><?=money($o['total'])?></strong>
        <div class="small text-muted">Placed <?=e(date('M j, Y g:i a', strtotime($o['created_at'])))?></div></div>
        <span class="badge text-bg-secondary"><?=e(ucfirst($o['status']))?></span>
    </div>
    <hr>
    <div><strong>Customer phone:</strong> <?=e($o['customer_phone'])?></div>
    <div class="mt-1"><strong>Delivery address:</strong><br><?=nl2br(e($o['customer_address']))?></div>
    <?php if (!empty($o['notes'])): ?><div class="mt-1"><strong>Customer notes:</strong><br><?=nl2br(e($o['notes']))?></div><?php endif; ?>
    <div class="mt-3"><strong>Items ordered</strong>
    <?php if (empty($orderItems[(int)$o['id']])): ?><p class="text-muted mb-0">No item details found for this order.</p>
    <?php else: ?><ul class="mb-0"><?php foreach ($orderItems[(int)$o['id']] as $item): ?>
        <li><?=e($item['product_name'])?> × <?=e($item['quantity'])?> — <?=money($item['unit_price'])?> each (<?=money($item['unit_price'] * $item['quantity'])?>)</li>
    <?php endforeach; ?></ul><?php endif; ?></div>
    <form method="post" class="d-flex gap-2 mt-3">
        <input type="hidden" name="csrf" value="<?=e(csrf())?>">
        <input type="hidden" name="id" value="<?=e($o['id'])?>">
        <select name="status" class="form-select" style="max-width:220px" aria-label="Order status">
        <?php foreach (['pending','confirmed','completed','cancelled'] as $x): ?><option value="<?=e($x)?>" <?=$o['status'] === $x ? 'selected' : ''?>><?=e(ucfirst($x))?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-success">Update</button>
    </form>
</div></div>
<?php endforeach; ?>
<?php if (!$os): ?><p class="text-muted">No orders yet.</p><?php endif; ?>
<?php require '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_once '../config/phone.php';

$slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['slug'] ?? ''));
$q = $pdo->prepare('SELECT s.* FROM stores s JOIN users u ON u.id=s.user_id WHERE s.slug=? AND u.account_status=?');
$q->execute([$slug, 'active']);
$s = $q->fetch();
if (!$s) { http_response_code(404); exit('Store not found'); }

$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0;
if ($cart) {
    $ids = array_map('intval', array_keys($cart));
    $in = implode(',', array_fill(0, count($ids), '?'));
    $q = $pdo->prepare("SELECT * FROM products WHERE id IN($in) AND store_id=? AND available=1 AND moderation_status=?");
    $q->execute([...$ids, $s['id'], 'approved']);
    foreach ($q as $p) {
        $p['qty'] = max(1, min(99, (int)($cart[$p['id']] ?? 1)));
        $p['line'] = $p['qty'] * (float)$p['price'];
        $total += $p['line'];
        $items[] = $p;
    }
}

$sellerWhatsApp = normalize_nigerian_whatsapp($s['whatsapp_number'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $items) {
    csrf_check();
    $n = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $addr = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!$n || !$phone || !$addr) {
        $error = 'Please complete your name, phone number, and delivery address.';
    } elseif ($sellerWhatsApp === false) {
        $error = 'This store has an invalid WhatsApp contact number. Your order has not been placed and your cart is unchanged. Please return to the store page and ask the seller to update their WhatsApp number before ordering.';
    } else {
        try {
            $pdo->beginTransaction();
            $q = $pdo->prepare('INSERT INTO orders(store_id,customer_name,customer_phone,customer_address,notes,total) VALUES(?,?,?,?,?,?)');
            $q->execute([$s['id'], $n, $phone, $addr, $notes, $total]);
            $oid = (int)$pdo->lastInsertId();

            $i = $pdo->prepare('INSERT INTO order_items(order_id,product_id,product_name,unit_price,quantity) VALUES(?,?,?,?,?)');
            foreach ($items as $p) {
                $i->execute([$oid, $p['id'], $p['name'], $p['price'], $p['qty']]);
            }

            // Build the WhatsApp draft from the saved order and item snapshots.
            $q = $pdo->prepare('SELECT id,customer_name,customer_phone,customer_address,notes,total,status FROM orders WHERE id=? AND store_id=?');
            $q->execute([$oid, $s['id']]);
            $savedOrder = $q->fetch();
            if (!$savedOrder) {
                throw new RuntimeException('Saved order could not be read back.');
            }

            $q = $pdo->prepare('SELECT product_name,unit_price,quantity FROM order_items WHERE order_id=? ORDER BY id');
            $q->execute([$oid]);
            $savedItems = $q->fetchAll();
            if (!$savedItems) {
                throw new RuntimeException('Saved order items could not be read back.');
            }

            $message = [
                "New order #{$savedOrder['id']}",
                "Store: {$s['name']}",
                "Customer: {$savedOrder['customer_name']}",
                "Customer phone: {$savedOrder['customer_phone']}",
                "Delivery address: {$savedOrder['customer_address']}",
            ];
            if (trim((string)$savedOrder['notes']) !== '') {
                $message[] = "Customer notes: {$savedOrder['notes']}";
            }
            $message[] = '';
            $message[] = 'Order items:';
            foreach ($savedItems as $item) {
                $lineTotal = (float)$item['unit_price'] * (int)$item['quantity'];
                $message[] = '- ' . $item['product_name'] . ' x ' . $item['quantity'] . ' @ ' . money($item['unit_price']) . ' each = ' . money($lineTotal);
            }
            $message[] = '';
            $message[] = 'Order total: ' . money($savedOrder['total']);
            $message[] = 'Order status: ' . ucfirst($savedOrder['status']);
            $message[] = '';
            $message[] = 'Please confirm item availability, the delivery fee, and the estimated delivery time. Thank you.';

            $whatsAppUrl = 'https://wa.me/' . $sellerWhatsApp . '?text=' . rawurlencode(implode("\n", $message));
            $pdo->commit();
            $_SESSION['cart'] = [];
            go($whatsAppUrl);
        } catch (Throwable $x) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Choosery checkout error: ' . $x->getMessage());
            $error = 'We could not save your order. Your cart has been kept. Please try again.';
        }
    }
}

$title = 'Cart';
require '../includes/header.php';
?>
<h1>Cart — <?=e($s['name'])?></h1>
<?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?=e($error)?></div><?php endif; ?>
<?php if ($sellerWhatsApp === false): ?>
<div class="alert alert-warning" role="alert">
    This store's WhatsApp number is missing or invalid, so checkout is temporarily unavailable. Your cart is safe. Please return to the <a href="/public/store.php?slug=<?=e(rawurlencode($slug))?>">store page</a> and ask the seller to correct their contact number.
</div>
<?php endif; ?>
<?php foreach ($items as $p): ?>
    <p><?=e($p['name'])?> × <?=e($p['qty'])?> — <?=money($p['line'])?></p>
<?php endforeach; ?>
<h4>Total <?=money($total)?></h4>
<?php if ($items): ?>
<form method="post" class="card card-body col-lg-7">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <input class="form-control mb-2" name="name" placeholder="Name" value="<?=e($_POST['name'] ?? '')?>" required>
    <input class="form-control mb-2" type="tel" name="phone" placeholder="Phone" value="<?=e($_POST['phone'] ?? '')?>" autocomplete="tel" required>
    <textarea class="form-control mb-2" name="address" placeholder="Delivery address" required><?=e($_POST['address'] ?? '')?></textarea>
    <textarea class="form-control mb-2" name="notes" placeholder="Notes"><?=e($_POST['notes'] ?? '')?></textarea>
    <button class="btn btn-success" <?= $sellerWhatsApp === false ? 'disabled' : '' ?>>Place order via WhatsApp</button>
</form>
<?php else: ?>
<p>Your cart has no available items for this store.</p>
<?php endif; ?>
<?php require '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_once '../config/phone.php';
login_required();

$s = store();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $n = trim($_POST['name'] ?? '');
    $slugBase = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($n)), '-');
    $w = normalize_nigerian_whatsapp($_POST['whatsapp'] ?? '');

    if (!$n || !$slugBase) {
        $error = 'Enter a valid store name.';
    } elseif ($w === false) {
        $error = 'Enter a valid Nigerian mobile WhatsApp number, e.g. 08012345678 or +2348012345678.';
    } else {
        $slug = $slugBase;
        $i = 2;
        while (true) {
            $q = $pdo->prepare('SELECT id FROM stores WHERE slug=?' . ($s ? ' AND id<>?' : ''));
            $params = $s ? [$slug, $s['id']] : [$slug];
            $q->execute($params);
            if (!$q->fetch()) break;
            $slug = $slugBase . '-' . $i++;
        }

        try {
            if ($s) {
                $q = $pdo->prepare('UPDATE stores SET name=?,slug=?,description=?,whatsapp_number=?,delivery_note=? WHERE id=? AND user_id=?');
                $q->execute([$n, $slug, trim($_POST['description'] ?? ''), $w, trim($_POST['delivery_note'] ?? ''), $s['id'], $_SESSION['user_id']]);
            } else {
                $q = $pdo->prepare('INSERT INTO stores(user_id,name,slug,description,whatsapp_number,delivery_note) VALUES(?,?,?,?,?,?)');
                $q->execute([$_SESSION['user_id'], $n, $slug, trim($_POST['description'] ?? ''), $w, trim($_POST['delivery_note'] ?? '')]);
            }
            go('/seller/dashboard.php');
        } catch (PDOException $x) {
            error_log('Choosery store settings database error: ' . $x->getMessage());
            $error = 'Unable to save store settings. Please try again.';
        }
    }
}

$s = $s ?: ['name' => '', 'description' => '', 'whatsapp_number' => '', 'delivery_note' => ''];
$title = 'Store settings';
require '../includes/header.php';
?>
<h1>Store settings</h1>
<p class="text-muted">Update your store information and catalogue contact details.</p>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<form method="post" class="card card-body col-lg-8">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <input class="form-control mb-2" name="name" placeholder="Store name" value="<?=e($_POST['name'] ?? $s['name'])?>" required>
    <textarea class="form-control mb-2" name="description" placeholder="Description"><?=e($_POST['description'] ?? $s['description'])?></textarea>
    <input class="form-control mb-2" type="tel" name="whatsapp" placeholder="08012345678 or +2348012345678" value="<?=e($_POST['whatsapp'] ?? $s['whatsapp_number'])?>" required inputmode="tel" autocomplete="tel">
    <div class="form-text mb-2">Use a valid Nigerian mobile number starting with 0 or +234. The number is validated before saving.</div>
    <textarea class="form-control mb-2" name="delivery_note" placeholder="Delivery note"><?=e($_POST['delivery_note'] ?? $s['delivery_note'])?></textarea>
    <button class="btn btn-success">Save changes</button>
</form>
<?php require '../includes/footer.php'; ?>

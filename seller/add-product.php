<?php
require_once '../includes/auth.php';
require_once '../config/uploads.php';
login_required();

$s = store();
if (!$s) go('/seller/store.php');

$allowance = product_limit_for_user();
$countQuery = $pdo->prepare('SELECT COUNT(*) FROM products WHERE store_id = ?');
$countQuery->execute([$s['id']]);
$productCount = (int)$countQuery->fetchColumn();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
    $category = trim($_POST['category'] ?? '');
    $image = null;

    if (!$name || $price === false || $price < 0 || !$category) {
        $error = 'Name, valid price and category are required.';
    }

    if (!$error && !empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if ($file['error'] || $file['size'] > 2097152 || !isset($allowedTypes[$mime])) {
            $error = 'Image must be JPG, PNG or WebP under 2MB.';
        } else {
            $image = bin2hex(random_bytes(12)) . '.' . $allowedTypes[$mime];
            if (!move_uploaded_file($file['tmp_name'], rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $image)) {
                $image = null;
                $error = 'Image upload failed.';
            }
        }
    }

    if (!$error) {
        try {
            $pdo->beginTransaction();
            // Lock this store row to serialize concurrent product additions.
            $lock = $pdo->prepare('SELECT id FROM stores WHERE id = ? AND user_id = ? FOR UPDATE');
            $lock->execute([$s['id'], $_SESSION['user_id']]);
            if (!$lock->fetch()) throw new RuntimeException('Store not found.');

            $countQuery = $pdo->prepare('SELECT COUNT(*) FROM products WHERE store_id = ?');
            $countQuery->execute([$s['id']]);
            $productCount = (int)$countQuery->fetchColumn();

            if ($productCount >= $allowance['limit']) {
                $pdo->rollBack();
                if ($image) {
                    $path = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $image;
                    if (is_file($path)) @unlink($path);
                }
                $error = 'You have reached your ' . ucfirst($allowance['plan_code']) . ' plan limit of ' . $allowance['limit'] . ' products. Upgrade your plan or delete an existing product to add another.';
            } else {
                $insert = $pdo->prepare('INSERT INTO products(store_id,name,description,price,category,image,variations,available,moderation_status) VALUES(?,?,?,?,?,?,?,?,?)');
                $insert->execute([
                    $s['id'], $name, trim($_POST['description'] ?? ''), $price, $category,
                    $image, trim($_POST['variations'] ?? ''), isset($_POST['available']) ? 1 : 0, 'pending'
                ]);
                $pdo->commit();
                go('/seller/products.php');
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($image) {
                $path = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $image;
                if (is_file($path)) @unlink($path);
            }
            error_log('Choosery product creation failed: ' . $exception->getMessage());
            $error = 'Unable to save this product. Please try again.';
        }
    }
}

$title = 'Add product';
require '../includes/header.php';
?>
<h1>Add product</h1>
<p class="text-muted">New products are reviewed before they appear in the public store.</p>
<div class="alert alert-info" role="status">
    Product allowance: <strong><?= e($productCount) ?> / <?= e($allowance['limit']) ?></strong>
    (<?= e(ucfirst($allowance['plan_code'])) ?> plan)
</div>
<?php if ($productCount >= $allowance['limit'] && !$error): ?>
    <div class="alert alert-warning">You have reached your plan's product limit. <a href="/seller/subscription.php">View upgrade options</a> or delete an existing product.</div>
<?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>

<?php if ($productCount < $allowance['limit']): ?>
<form method="post" enctype="multipart/form-data" class="card card-body col-lg-8">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
    <input class="form-control mb-2" name="name" placeholder="Name" required>
    <textarea class="form-control mb-2" name="description" placeholder="Description"></textarea>
    <input class="form-control mb-2" type="number" step="0.01" min="0" name="price" placeholder="Price in naira" required>
    <input class="form-control mb-2" name="category" placeholder="Category" required>
    <input class="form-control mb-2" name="variations" placeholder="Variations e.g. Small, Medium, Large">
    <input class="form-control mb-1" type="file" name="image" accept="image/jpeg,image/png,image/webp">
    <div class="form-text mb-2">JPG, PNG or WebP · maximum 2 MB.</div>
    <label><input type="checkbox" name="available" checked> Available</label>
    <button class="btn btn-success mt-3" type="submit">Save product</button>
</form>
<?php endif; ?>
<?php require '../includes/footer.php'; ?>
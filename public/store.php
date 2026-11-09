<?php
require_once '../includes/auth.php';
require_once '../config/uploads.php';
require_once '../config/phone.php';
$slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['slug'] ?? ''));
$q = $pdo->prepare('SELECT s.* FROM stores s JOIN users u ON u.id=s.user_id WHERE s.slug=? AND u.account_status=?');
$q->execute([$slug,'active']);
$s = $q->fetch();
if (!$s) { http_response_code(404); exit('Store not found'); }
$sellerWhatsApp = normalize_nigerian_whatsapp($s['whatsapp_number'] ?? '');
$category = trim((string)($_GET['category'] ?? ''));
$sql = 'SELECT p.* FROM products p WHERE p.store_id=? AND p.available=1 AND p.moderation_status=?';
$params = [$s['id'],'approved'];
if ($category !== '') { $sql .= ' AND p.category=?'; $params[] = $category; }
$q = $pdo->prepare($sql . ' ORDER BY category,name');
$q->execute($params);
$products = $q->fetchAll();
$location = implode(', ', array_filter([$s['business_area'],$s['business_city'],$s['business_state'],$s['business_country']]));
$title = $s['name'];
require '../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
<div><div class="d-flex flex-wrap align-items-center gap-2"><h1 class="mb-0"><?=e($s['name'])?></h1><?php if ($s['verification_status']==='verified'): ?><span class="badge text-bg-success">Business profile reviewed</span><?php endif; ?></div>
<p class="mb-1"><?=e($s['description'])?></p><?php if ($location): ?><p class="small text-muted mb-0">📍 <?=e($location)?></p><?php endif; ?></div>
<div class="d-flex flex-wrap gap-2"><?php if ($sellerWhatsApp): ?><a class="btn btn-success" href="https://wa.me/<?=e($sellerWhatsApp)?>?text=<?=urlencode('Hello, I found your store on Choosery.')?>" target="_blank" rel="noopener">WhatsApp Seller</a><a class="btn btn-outline-success" href="tel:+<?=e($sellerWhatsApp)?>">Call Seller</a><?php endif; ?><a class="btn btn-outline-secondary" href="/public/cart.php?slug=<?=e($slug)?>">Cart (<?=cart_count()?>)</a></div>
</div>
<?php if (!$sellerWhatsApp): ?><div class="alert alert-warning">The seller has not provided a valid contact number yet.</div><?php endif; ?>
<div class="row g-3"><?php foreach ($products as $p): ?><div class="col-6 col-md-4"><div class="card h-100"><?php if ($p['image']): ?><a href="/public/product.php?id=<?=e($p['id'])?>&amp;slug=<?=e($slug)?>"><img class="card-img-top" style="height:180px;object-fit:cover" src="<?=e($uploadUrl . '/' . $p['image'])?>" alt="<?=e($p['name'])?>"></a><?php endif; ?><div class="card-body"><h2 class="h5"><?=e($p['name'])?></h2><p><?=e($p['category'])?> · <?=money($p['price'])?></p><a class="btn btn-success btn-sm" href="/public/product.php?id=<?=e($p['id'])?>&amp;slug=<?=e($slug)?>">View</a></div></div></div><?php endforeach; ?></div>
<?php if (!$products): ?><p class="text-muted mt-3">No approved products are currently available.</p><?php endif; ?>
<?php require '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_once '../config/uploads.php';

$search = trim((string)($_GET['q'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$minRaw = trim((string)($_GET['min_price'] ?? ''));
$maxRaw = trim((string)($_GET['max_price'] ?? ''));
$sort = (string)($_GET['sort'] ?? 'newest');
$location = trim((string)($_GET['location'] ?? ''));

$minPrice = ($minRaw !== '' && is_numeric($minRaw) && (float)$minRaw >= 0) ? (float)$minRaw : null;
$maxPrice = ($maxRaw !== '' && is_numeric($maxRaw) && (float)$maxRaw >= 0) ? (float)$maxRaw : null;
if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
    [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
    [$minRaw, $maxRaw] = [$maxRaw, $minRaw];
}

$categoryQuery = $pdo->query("SELECT DISTINCT p.category
    FROM products p
    JOIN stores s ON s.id=p.store_id
    JOIN users u ON u.id=s.user_id
    WHERE p.available=1 AND p.moderation_status='approved'
      AND u.account_status='active' AND p.category<>''
    ORDER BY p.category");
$categories = $categoryQuery->fetchAll(PDO::FETCH_COLUMN);

$where = ["p.available=1", "p.moderation_status='approved'", "u.account_status='active'"];
$params = [];
if ($search !== '') {
    $where[] = "(p.name LIKE ? OR p.description LIKE ? OR p.category LIKE ? OR s.name LIKE ?)";
    $term = '%' . $search . '%';
    array_push($params, $term, $term, $term, $term);
}
if ($location !== '') {
    $where[] = "(s.business_city LIKE ? OR s.business_state LIKE ? OR s.business_area LIKE ? OR s.business_country LIKE ?)";
    $locationTerm = '%' . $location . '%';
    array_push($params, $locationTerm, $locationTerm, $locationTerm, $locationTerm);
}
if ($category !== '') {
    $where[] = "p.category = ?";
    $params[] = $category;
}
if ($minPrice !== null) {
    $where[] = "p.price >= ?";
    $params[] = $minPrice;
}
if ($maxPrice !== null) {
    $where[] = "p.price <= ?";
    $params[] = $maxPrice;
}

$orderBy = [
    'newest' => 'p.created_at DESC, p.id DESC',
    'price_low' => 'p.price ASC, p.created_at DESC',
    'price_high' => 'p.price DESC, p.created_at DESC',
    'name' => 'p.name ASC'
][$sort] ?? 'p.created_at DESC, p.id DESC';

$sql = "SELECT p.id,p.name,p.description,p.price,p.category,p.image,s.name AS store_name,s.slug AS store_slug,s.business_city,s.business_state,s.business_country,s.business_area,s.verification_status
        FROM products p
        JOIN stores s ON s.id=p.store_id
        JOIN users u ON u.id=s.user_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY " . $orderBy . " LIMIT 60";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$title = 'Explore products';
require '../includes/header.php';
?>
<section class="mb-4">
    <span class="badge text-bg-success mb-2">Choosery marketplace</span>
    <h1 class="mb-1">Find products from local businesses</h1>
    <p class="text-muted mb-0">Search approved products, compare prices and visit the seller's storefront.</p>
</section>

<form method="get" action="/public/products.php" class="storebridge-panel bg-white p-3 p-md-4 mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-lg-5">
            <label class="form-label" for="product-search">Search products or sellers</label>
            <input class="form-control" id="product-search" name="q" type="search" value="<?=e($search)?>" placeholder="e.g. shoes, rice, fashion">
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label" for="product-category">Category</label>
            <select class="form-select" id="product-category" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $item): ?>
                    <option value="<?=e($item)?>" <?=$category === $item ? 'selected' : ''?>><?=e($item)?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label" for="min-price">Min price (₦)</label>
            <input class="form-control" id="min-price" name="min_price" type="number" min="0" step="0.01" value="<?=e($minRaw)?>" placeholder="0">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label" for="max-price">Max price (₦)</label>
            <input class="form-control" id="max-price" name="max_price" type="number" min="0" step="0.01" value="<?=e($maxRaw)?>" placeholder="Any">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="product-location">Seller location</label>
            <input class="form-control" id="product-location" name="location" value="<?=e($location)?>" placeholder="City, state or country">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="product-sort">Sort results</label>
            <select class="form-select" id="product-sort" name="sort">
                <option value="newest" <?=$sort === 'newest' ? 'selected' : ''?>>Newest first</option>
                <option value="price_low" <?=$sort === 'price_low' ? 'selected' : ''?>>Price: low to high</option>
                <option value="price_high" <?=$sort === 'price_high' ? 'selected' : ''?>>Price: high to low</option>
                <option value="name" <?=$sort === 'name' ? 'selected' : ''?>>Product name</option>
            </select>
        </div>
        <div class="col-12 col-md-6 d-flex gap-2">
            <button class="btn btn-success flex-grow-1" type="submit">Search products</button>
            <a class="btn btn-outline-secondary" href="/public/products.php">Clear</a>
        </div>
    </div>
</form>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="h5 mb-0">Available products</h2>
    <span class="small text-muted"><?=count($products)?> result<?=count($products) === 1 ? '' : 's'?><?=count($products) === 60 ? ' · showing first 60' : ''?></span>
</div>

<div class="row g-3">
<?php foreach ($products as $product): ?>
    <div class="col-6 col-md-4 col-xl-3">
        <article class="card h-100 overflow-hidden">
            <?php if (!empty($product['image'])): ?>
                <a href="/public/product.php?id=<?=e($product['id'])?>&amp;slug=<?=e($product['store_slug'])?>" aria-label="View <?=e($product['name'])?>">
                    <img class="card-img-top" src="<?=e(rtrim($uploadUrl, '/') . '/' . $product['image'])?>" alt="<?=e($product['name'])?>" loading="lazy" style="height:190px;object-fit:cover">
                </a>
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center bg-light text-muted" style="height:190px"><span>No product image</span></div>
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
                <div class="small text-muted mb-1"><?=e($product['category'])?></div>
                <h3 class="h6 mb-2"><?=e($product['name'])?></h3>
                <div class="fw-semibold mb-2"><?=money($product['price'])?></div>
                <div class="small text-muted mb-2">By <?=e($product['store_name'])?></div>
                <?php $sellerLocation = implode(', ', array_filter([$product['business_city'], $product['business_state'], $product['business_country']])); ?>
                <?php if ($sellerLocation): ?><div class="small text-muted mb-2"><?=e($sellerLocation)?></div><?php endif; ?>
                <?php if ($product['verification_status'] === 'verified'): ?><span class="badge text-bg-success align-self-start mb-2">Business profile reviewed</span><?php endif; ?>
                <a class="btn btn-success btn-sm mt-auto" href="/public/product.php?id=<?=e($product['id'])?>&amp;slug=<?=e($product['store_slug'])?>">View product</a>
            </div>
        </article>
    </div>
<?php endforeach; ?>
</div>

<?php if (!$products): ?>
    <div class="storebridge-panel bg-white text-center p-4 p-md-5 mt-3">
        <h2 class="h5">No matching products found</h2>
        <p class="text-muted mb-3">Try another search term, category or price range.</p>
        <a class="btn btn-outline-success" href="/public/products.php">View all products</a>
    </div>
<?php endif; ?>

<?php require '../includes/footer.php'; ?>

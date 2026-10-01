<?php
require_once '../includes/auth.php';
login_required();
$s=store();
$title='Dashboard';
require '../includes/header.php';

$storeUrl=$s?'https://'.($_SERVER['HTTP_HOST']??'storebridge.freedev.app').'/public/store.php?slug='.rawurlencode($s['slug']):'';
?>
<h1>Seller dashboard</h1>
<p>Welcome back.</p>

<?php if(!$s):?>
<div class="alert alert-warning">Create your store first.</div>
<a class="btn btn-success" href="/seller/store.php">Create your store</a>
<?php else:?>
<div class="card card-body mb-4">
    <h2 class="h5 mb-2">Your Store Link</h2>
    <p class="text-muted mb-2">Share this one link with customers so they can see your catalogue.</p>
    <div class="bg-light border rounded p-2 mb-3 text-break">
        <a href="<?=e($storeUrl)?>" target="_blank"><?=e($storeUrl)?></a>
    </div>
    <div>
        <a class="btn btn-success me-2" href="<?=e($storeUrl)?>" target="_blank">View Store</a>
        <a class="btn btn-outline-success" href="https://wa.me/?text=<?=rawurlencode('Here is my StoreBridge catalogue: '.$storeUrl)?>" target="_blank">Share on WhatsApp</a>
    </div>
</div>
<?php endif;?>

<a class="btn btn-success" href="/seller/store.php">Store settings</a>
<a class="btn btn-outline-success" href="/seller/products.php">Products</a>
<a class="btn btn-outline-success" href="/seller/orders.php">Orders</a>

<?php require '../includes/footer.php';?>
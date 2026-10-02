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

    <div class="d-flex align-items-center gap-2 mb-3">
        <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            id="copyStoreLink"
            aria-label="Copy store link"
            title="Copy store link"
            data-store-link="<?=e($storeUrl)?>"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                <path d="M4 1.5A1.5 1.5 0 0 1 5.5 0h6A1.5 1.5 0 0 1 13 1.5v7A1.5 1.5 0 0 1 11.5 10h-1V8h1a.5.5 0 0 0 .5-.5v-6a.5.5 0 0 0-.5-.5h-6a.5.5 0 0 0-.5.5v1H4z"/>
                <path d="M1.5 5A1.5 1.5 0 0 1 3 3.5h6A1.5 1.5 0 0 1 10.5 5v7A1.5 1.5 0 0 1 9 13.5H3A1.5 1.5 0 0 1 1.5 12zM3 4.5a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .5.5h6a.5.5 0 0 0 .5-.5V5a.5.5 0 0 0-.5-.5z"/>
            </svg>
        </button>

        <div class="bg-light border rounded p-2 flex-grow-1 text-break">
            <a href="<?=e($storeUrl)?>" target="_blank"><?=e($storeUrl)?></a>
        </div>
    </div>

    <div>
        <a class="btn btn-success me-2" href="<?=e($storeUrl)?>" target="_blank">View Store</a>
        <a class="btn btn-outline-success" href="https://wa.me/?text=<?=rawurlencode('Here is my StoreBridge catalogue: '.$storeUrl)?>" target="_blank">Share on WhatsApp</a>
    </div>
</div>

<script>
document.getElementById('copyStoreLink')?.addEventListener('click', async function () {
    const button = this;
    const link = button.dataset.storeLink;

    try {
        await navigator.clipboard.writeText(link);
        button.innerHTML = '<span aria-hidden="true">✓</span> Copied!';
        button.classList.remove('btn-outline-secondary');
        button.classList.add('btn-success');

        setTimeout(() => {
            button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M4 1.5A1.5 1.5 0 0 1 5.5 0h6A1.5 1.5 0 0 1 13 1.5v7A1.5 1.5 0 0 1 11.5 10h-1V8h1a.5.5 0 0 0 .5-.5v-6a.5.5 0 0 0-.5-.5h-6a.5.5 0 0 0-.5.5v1H4z"/><path d="M1.5 5A1.5 1.5 0 0 1 3 3.5h6A1.5 1.5 0 0 1 10.5 5v7A1.5 1.5 0 0 1 9 13.5H3A1.5 1.5 0 0 1 1.5 12zM3 4.5a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .5.5h6a.5.5 0 0 0 .5-.5V5a.5.5 0 0 0-.5-.5z"/></svg>';
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
        }, 1800);
    } catch (error) {
        button.textContent = 'Copy failed';
        setTimeout(() => {
            button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M4 1.5A1.5 1.5 0 0 1 5.5 0h6A1.5 1.5 0 0 1 13 1.5v7A1.5 1.5 0 0 1 11.5 10h-1V8h1a.5.5 0 0 0 .5-.5v-6a.5.5 0 0 0-.5-.5h-6a.5.5 0 0 0-.5.5v1H4z"/><path d="M1.5 5A1.5 1.5 0 0 1 3 3.5h6A1.5 1.5 0 0 1 10.5 5v7A1.5 1.5 0 0 1 9 13.5H3A1.5 1.5 0 0 1 1.5 12zM3 4.5a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .5.5h6a.5.5 0 0 0 .5-.5V5a.5.5 0 0 0-.5-.5z"/></svg>';
        }, 1800);
    }
});
</script>
<?php endif;?>

<a class="btn btn-success" href="/seller/store.php">Store settings</a>
<a class="btn btn-outline-success" href="/seller/products.php">Products</a>
<a class="btn btn-outline-success" href="/seller/orders.php">Orders</a>

<?php require '../includes/footer.php';?>
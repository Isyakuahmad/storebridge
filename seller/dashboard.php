<?php
require_once '../includes/auth.php';
login_required();

$s = store();
$referralCode = ensure_referral_code($_SESSION['user_id']);
$referralUrl = referral_url($referralCode);

$q = $pdo->prepare(
    "SELECT pl.name, pl.code, s.ends_at
     FROM subscriptions s
     JOIN plans pl ON pl.id = s.plan_id
     WHERE s.user_id = ? AND s.status = 'active'
       AND s.starts_at <= NOW() AND s.ends_at > NOW()
     ORDER BY s.ends_at DESC LIMIT 1"
);
$q->execute([$_SESSION['user_id']]);
$activeSubscription = $q->fetch();

$q = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE user_id = ? AND status = 'pending'");
$q->execute([$_SESSION['user_id']]);
$pendingPayment = (int)$q->fetchColumn();

$title = 'Seller dashboard';
require '../includes/header.php';
$storeUrl = $s ? app_url() . '/public/store.php?slug=' . rawurlencode($s['slug']) : '';
?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
    <div>
        <h1 class="mb-1">Seller dashboard</h1>
        <p class="text-muted mb-0">Manage your Choosery store, catalogue, orders and plan.</p>
    </div>
    <a class="btn btn-success" href="/seller/subscription.php">View plans &amp; upgrades</a>
</div>

<div class="card card-body mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <h2 class="h5 mb-1">Your plan</h2>
            <?php if ($activeSubscription): ?>
                <div class="fs-5"><?= e($activeSubscription['name']) ?></div>
                <div class="small text-muted">Active until <?= e(date('M j, Y', strtotime($activeSubscription['ends_at']))) ?></div>
            <?php else: ?>
                <div class="fs-5">Free</div>
                <div class="small text-muted">No active paid subscription.</div>
            <?php endif; ?>
            <?php if ($pendingPayment): ?>
                <div class="small text-warning mt-2">Your payment is awaiting admin review. Your plan will change after the bank transfer is verified.</div>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (!$activeSubscription || $activeSubscription['code'] === 'free'): ?>
                <a class="btn btn-success" href="/seller/subscription.php#plan-moderate">Upgrade to Moderate</a>
                <a class="btn btn-outline-success" href="/seller/subscription.php#plan-premium">Upgrade to Premium</a>
            <?php elseif ($activeSubscription['code'] === 'moderate'): ?>
                <a class="btn btn-success" href="/seller/subscription.php#plan-premium">Upgrade to Premium</a>
                <a class="btn btn-outline-success" href="/seller/subscription.php">Manage subscription</a>
            <?php else: ?>
                <a class="btn btn-outline-success" href="/seller/subscription.php">Manage subscription</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card card-body mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <h2 class="h5 mb-1">Refer a seller</h2>
            <p class="text-muted mb-2">Invite another business to Choosery and track your referrals.</p>
        </div>
        <a class="btn btn-outline-success" href="/seller/referrals.php">View referrals</a>
    </div>
    <div class="input-group mt-2">
        <input class="form-control" value="<?= e($referralUrl) ?>" readonly aria-label="Your referral link">
        <a class="btn btn-success" href="/seller/referrals.php">Manage</a>
    </div>
</div>

<?php if (!$s): ?>
    <div class="alert alert-warning">Create your store first to publish your catalogue and receive orders.</div>
    <a class="btn btn-success" href="/seller/store.php">Create your store</a>
<?php else: ?>
    <div class="card card-body mb-4">
        <h2 class="h5 mb-2">Your store link</h2>
        <p class="text-muted mb-2">Share one link so customers can browse your catalogue and place orders.</p>
        <div class="d-flex align-items-center gap-2 mb-3">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="copyStoreLink" aria-label="Copy store link" title="Copy store link" data-store-link="<?= e($storeUrl) ?>">Copy link</button>
            <div class="bg-light border rounded p-2 flex-grow-1 text-break">
                <a href="<?= e($storeUrl) ?>" target="_blank" rel="noopener"><?= e($storeUrl) ?></a>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-success" href="<?= e($storeUrl) ?>" target="_blank" rel="noopener">View store</a>
            <a class="btn btn-outline-success" href="https://wa.me/?text=<?= rawurlencode('Here is my Choosery catalogue: ' . $storeUrl) ?>" target="_blank" rel="noopener">Share on WhatsApp</a>
        </div>
    </div>
    <script>
    document.getElementById('copyStoreLink')?.addEventListener('click', async function () {
        const button = this;
        try {
            await navigator.clipboard.writeText(button.dataset.storeLink);
            button.textContent = 'Copied!';
        } catch (error) {
            button.textContent = 'Copy failed';
        }
    });
    </script>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 mb-4">
    <a class="btn btn-success" href="/seller/store.php">Store settings</a>
    <a class="btn btn-outline-success" href="/seller/products.php">Products</a>
    <a class="btn btn-outline-success" href="/seller/orders.php">Orders</a>
</div>

<?php require '../includes/footer.php'; ?>

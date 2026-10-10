<?php
require_once '../includes/auth.php';
login_required();
$s = store();
$referralCode = ensure_referral_code($_SESSION['user_id']);
$referralUrl = referral_url($referralCode);
$q = $pdo->prepare("SELECT pl.name,s.ends_at FROM subscriptions s JOIN plans pl ON pl.id=s.plan_id WHERE s.user_id=? AND s.status='active' AND s.starts_at<=NOW() AND s.ends_at>NOW() ORDER BY s.ends_at DESC LIMIT 1");
$q->execute([$_SESSION['user_id']]);
$activeSubscription = $q->fetch();
$q = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE user_id=? AND status='pending'");
$q->execute([$_SESSION['user_id']]);
$pendingPayment = (int)$q->fetchColumn();
$title = 'Dashboard';
require '../includes/header.php';
$storeUrl = $s ? app_url() . '/public/store.php?slug=' . rawurlencode($s['slug']) : '';
?>
<h1>Seller dashboard</h1><p>Welcome back.</p>
<div class="card card-body mb-4"><div class="d-flex justify-content-between align-items-start gap-3 flex-wrap"><div><h2 class="h5 mb-1">Your plan</h2><?php if ($activeSubscription): ?><div class="fs-5"><?=e($activeSubscription['name'])?></div><div class="small text-muted">Active until <?=e(date('M j, Y', strtotime($activeSubscription['ends_at'])))?></div><?php else: ?><div class="fs-5">Free</div><div class="small text-muted">No active paid subscription.</div><?php endif; ?><?php if ($pendingPayment): ?><div class="small text-warning mt-1">You have a payment awaiting admin review.</div><?php endif; ?></div><a class="btn btn-success" href="/seller/subscription.php">Manage plan &amp; payments</a></div></div>
<div class="card card-body mb-4"><div class="d-flex justify-content-between align-items-start gap-3 flex-wrap"><div><h2 class="h5 mb-1">Refer a seller</h2><p class="text-muted mb-2">Invite another business to Choosery and track your referrals.</p></div><a class="btn btn-outline-success" href="/seller/referrals.php">View referrals</a></div><div class="input-group mt-2"><input class="form-control" value="<?=e($referralUrl)?>" readonly><a class="btn btn-success" href="/seller/referrals.php">Manage</a></div></div>
<?php if (!$s): ?><div class="alert alert-warning">Create your store first.</div><a class="btn btn-success" href="/seller/store.php">Create your store</a><?php else: ?>
<div class="card card-body mb-4"><h2 class="h5 mb-2">Your Store Link</h2><p class="text-muted mb-2">Share this one link with customers so they can see your catalogue.</p><div class="d-flex align-items-center gap-2 mb-3"><button type="button" class="btn btn-outline-secondary btn-sm" id="copyStoreLink" aria-label="Copy store link" title="Copy store link" data-store-link="<?=e($storeUrl)?>">Copy link</button><div class="bg-light border rounded p-2 flex-grow-1 text-break"><a href="<?=e($storeUrl)?>" target="_blank" rel="noopener"><?=e($storeUrl)?></a></div></div><div><a class="btn btn-success me-2" href="<?=e($storeUrl)?>" target="_blank" rel="noopener">View Store</a><a class="btn btn-outline-success" href="https://wa.me/?text=<?=rawurlencode('Here is my Choosery catalogue: ' . $storeUrl)?>" target="_blank" rel="noopener">Share on WhatsApp</a></div></div>
<script>document.getElementById('copyStoreLink')?.addEventListener('click',async function(){const button=this,link=button.dataset.storeLink;try{await navigator.clipboard.writeText(link);button.textContent='Copied!';}catch(error){button.textContent='Copy failed';}});</script>
<?php endif; ?>
<a class="btn btn-success" href="/seller/store.php">Store settings</a> <a class="btn btn-outline-success" href="/seller/products.php">Products</a> <a class="btn btn-outline-success" href="/seller/orders.php">Orders</a>
<?php require '../includes/footer.php'; ?>

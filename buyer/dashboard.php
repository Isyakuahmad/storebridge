<?php
require_once '../includes/auth.php';
login_required();
if (current_user_role() !== 'buyer') go('/seller/dashboard.php');
$title = 'Buyer dashboard';
require '../includes/header.php';
$q = $pdo->prepare('SELECT name,email,google_sub FROM users WHERE id=?');
$q->execute([$_SESSION['user_id']]);
$buyer = $q->fetch();
?>
<h1>Welcome to Choosery, <?=e($buyer['name'] ?? 'buyer')?></h1>
<p class="text-muted">Discover products and shop directly from businesses on Choosery.</p>
<div class="card card-body col-lg-7 mb-3">
    <h2 class="h5">Your account</h2>
    <p class="mb-1"><?=e($buyer['email'] ?? '')?></p>
    <a class="small" href="/auth/forgot-password.php">Set or reset a password</a>
    <?php if (!empty($buyer['google_sub'])): ?>
        <span class="badge text-bg-success align-self-start">Google account connected</span>
    <?php else: ?>
        <a class="btn btn-outline-success align-self-start" href="/auth/google-start.php?mode=link">Connect Google account</a>
    <?php endif; ?>
</div>
<a class="btn btn-success" href="/public/products.php">Explore products</a>
<?php require '../includes/footer.php'; ?>

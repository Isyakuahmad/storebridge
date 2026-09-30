<?php
require_once 'includes/auth.php';
$title = 'Home';
require 'includes/header.php';
?>
<div class="text-center py-5">
    <h1 class="display-5 fw-bold">StoreBridge</h1>
    <p class="lead text-muted">Simple online storefronts for Nigerian small businesses.</p>
    <p class="mb-4">Create a storefront, showcase your products, receive orders, and connect with customers on WhatsApp.</p>
    <a class="btn btn-success btn-lg me-2" href="/auth/register.php">Create your store</a>
    <a class="btn btn-outline-success btn-lg" href="/welcome.php">See how it works</a>
</div>
<?php require 'includes/footer.php'; ?>

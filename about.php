<?php
require_once 'includes/auth.php';
$title = 'About Us';
require 'includes/header.php';
?>
<section class="py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="mb-3">About StoreBridge</h1>
            <p class="lead text-muted">
                StoreBridge is a simple online storefront platform built to help Nigerian small businesses present their products online and make it easier for customers to place orders.
            </p>

            <div class="card card-body mt-4">
                <h2 class="h4">What we do</h2>
                <p class="text-muted mb-0">
                    We provide sellers with a shareable storefront where they can add products, prices, descriptions, images, availability, and WhatsApp contact details.
                </p>
            </div>

            <div class="card card-body mt-3">
                <h2 class="h4">Why StoreBridge</h2>
                <p class="text-muted mb-0">
                    Many small businesses already sell through WhatsApp and other social channels. StoreBridge gives them a more organized place to display their products while keeping customer conversations simple.
                </p>
            </div>

            <div class="card card-body mt-3">
                <h2 class="h4">Our approach</h2>
                <p class="text-muted mb-0">
                    We focus on keeping online selling straightforward: create a store, add products, share the storefront, and receive customer orders.
                </p>
            </div>

            <div class="mt-4">
                <a class="btn btn-success" href="/auth/register.php">Create your store</a>
                <a class="btn btn-outline-success ms-2" href="/">Back to home</a>
            </div>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
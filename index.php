<?php
require_once 'includes/auth.php';
$title = 'Welcome to StoreBridge';
require 'includes/header.php';
?>
<div class="py-4">
    <section class="text-center py-5">
        <span class="badge text-bg-success mb-3">Welcome to StoreBridge</span>
        <h1 class="display-4 fw-bold">Turn your products into a simple online storefront.</h1>
        <p class="lead text-muted mx-auto" style="max-width: 760px;">
            StoreBridge helps Nigerian small businesses create a shareable storefront,
            present their products clearly, receive customer orders, and continue the conversation on WhatsApp.
        </p>
        <div class="mt-4">
            <a class="btn btn-success btn-lg" href="/auth/register.php">Create your store</a>
            <div class="mt-3 text-muted">
                Already registered?
                <a href="/auth/login.php" class="fw-semibold">Log in.</a>
            </div>
        </div>
    </section>

    <section class="row g-4 py-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h5">For sellers</h3>
                    <p class="text-muted mb-0">
                        Set up your store, add products and images, manage availability, and keep track of customer orders.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h5">For customers</h3>
                    <p class="text-muted mb-0">
                        Browse a seller's storefront, view product details, add items to your cart, and submit your order.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h5">Built with moderation</h3>
                    <p class="text-muted mb-0">
                        New product listings can be reviewed by the platform administrator before they become publicly available.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <h2 class="h3 text-center mb-4">How StoreBridge works</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="border rounded p-4 h-100">
                    <div class="fw-bold mb-2">1. Create your store</div>
                    <p class="text-muted mb-0">Register, add your store details, delivery note, and WhatsApp number.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-4 h-100">
                    <div class="fw-bold mb-2">2. Add products</div>
                    <p class="text-muted mb-0">Add product names, prices, categories, descriptions, variations, availability, and images.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-4 h-100">
                    <div class="fw-bold mb-2">3. Receive orders</div>
                    <p class="text-muted mb-0">Customers place orders through the storefront, then continue the conversation through WhatsApp.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white border rounded-3 p-4 p-md-5 text-center">
        <h2 class="h3">Ready to put your business online?</h2>
        <p class="text-muted">Start with a simple storefront and grow from there.</p>
        <a class="btn btn-success btn-lg" href="/auth/register.php">Get started with StoreBridge</a>
        <div class="mt-2">
            <a href="/auth/login.php">Already registered? Log in.</a>
        </div>
    </section>
</div>
<?php require 'includes/footer.php'; ?>
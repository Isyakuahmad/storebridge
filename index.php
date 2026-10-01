<?php
require_once 'includes/auth.php';
$title = 'StoreBridge — One link for your product catalogue';
require 'includes/header.php';
?>
<div class="py-4">

    <section class="text-center py-5">
        <span class="badge text-bg-success mb-3">Made for Nigerian small businesses</span>
        <h1 class="display-4 fw-bold mx-auto" style="max-width: 900px;">
            Stop sending your products one by one on WhatsApp.
        </h1>
        <p class="lead text-muted mx-auto mt-3" style="max-width: 760px;">
            Give your customers one link to see everything you sell, choose what they want,
            and place an order.
        </p>

        <div class="mt-4">
            <a class="btn btn-success btn-lg px-4" href="/auth/register.php">Create your store</a>
            <div class="mt-3 text-muted">
                Already registered?
                <a href="/auth/login.php" class="fw-semibold">Log in.</a>
            </div>
        </div>
    </section>

    <section class="py-4">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h3 mb-4">Sound familiar?</h2>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted">Customer:</div>
                                    <div class="fw-semibold">“How much is this?”</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted">Customer:</div>
                                    <div class="fw-semibold">“Do you have other colours?”</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted">Customer:</div>
                                    <div class="fw-semibold">“Send me more pictures.”</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted">Customer:</div>
                                    <div class="fw-semibold">“What else do you have?”</div>
                                </div>
                            </div>
                        </div>

                        <p class="mt-4 mb-0 text-muted">
                            Instead of repeating the same answers and sending product photos again and again,
                            give customers a catalogue they can browse themselves.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 text-center">
        <span class="badge text-bg-light border mb-3">The StoreBridge idea</span>
        <h2 class="display-6 fw-bold">One link → entire catalogue → WhatsApp order</h2>
        <p class="lead text-muted mx-auto mt-3" style="max-width: 720px;">
            StoreBridge gives your business a simple storefront link you can share on WhatsApp,
            Instagram, Facebook, TikTok, or anywhere your customers already find you.
        </p>

        <div class="row g-4 mt-3 text-start">
            <div class="col-md-4">
                <div class="border rounded p-4 h-100">
                    <div class="fw-bold mb-2">1. Your catalogue</div>
                    <p class="text-muted mb-0">
                        Add your products, prices, descriptions, images, variations, and availability in one place.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-4 h-100">
                    <div class="fw-bold mb-2">2. One shareable link</div>
                    <p class="text-muted mb-0">
                        Give customers one link instead of sending product pictures one by one.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-4 h-100">
                    <div class="fw-bold mb-2">3. Customer chooses</div>
                    <p class="text-muted mb-0">
                        Customers browse your catalogue, select products, and submit an order before continuing through WhatsApp.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="py-5">
        <h2 class="h3 text-center mb-4">How StoreBridge works</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Create your store</div>
                        <p class="text-muted mb-0">
                            Register, add your store details, delivery note, and WhatsApp number.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Add your products</div>
                        <p class="text-muted mb-0">
                            Upload your catalogue and keep product information organised and easy to browse.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Share your link</div>
                        <p class="text-muted mb-0">
                            Share your StoreBridge link with customers and receive their orders through your storefront.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white border rounded-3 p-4 p-md-5 text-center">
        <h2 class="h3">Your products already have a home.</h2>
        <p class="text-muted mb-4">
            Make it easier for customers to see what you sell.
        </p>
        <a class="btn btn-success btn-lg px-4" href="/auth/register.php">Create your store</a>
        <div class="mt-3">
            <span class="text-muted">Already have a StoreBridge account?</span>
            <a href="/auth/login.php" class="fw-semibold">Log in.</a>
        </div>
    </section>

    <div class="text-end mt-4">
        <a href="/about.php" class="text-muted">About Us</a>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
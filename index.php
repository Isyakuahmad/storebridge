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

    <section id="plans" class="py-5">
        <div class="text-center mb-4">
            <span class="badge text-bg-light border mb-2">Plans for every stage</span>
            <h2 class="display-6 fw-bold">Start free. Upgrade as your business grows.</h2>
            <p class="text-muted mx-auto" style="max-width: 720px;">Choose the plan that suits your business. Paid subscriptions begin only after your bank transfer is verified.</p>
        </div>
        <?php
        $publicPlans = [];
        try {
            $publicPlans = $pdo->query("SELECT code, name, monthly_price FROM plans WHERE is_active = 1 ORDER BY FIELD(code, 'free', 'moderate', 'premium')")->fetchAll();
        } catch (Throwable $ex) {
            error_log('Choosery public plans could not be loaded: ' . $ex->getMessage());
        }
        $publicPlanFeatures = [
            'free' => ['Create a store and catalogue', 'Public storefront page', 'Orders handed off through WhatsApp', 'Core selling tools'],
            'moderate' => ['Everything in Free', 'Higher product limits and more store tools as they become available', 'Expanded reports and order tools as they become available'],
            'premium' => ['Everything in Moderate', 'More product and order-management tools as they become available', 'Additional reports and growth tools as they become available', 'Store-promotion tools when released'],
        ];
        ?>
        <div class="row g-3">
            <?php foreach ($publicPlans as $plan): ?>
                <?php
                $planCode = (string)$plan['code'];
                if (!isset($publicPlanFeatures[$planCode])) continue;
                $planPrice = $plan['monthly_price'] === null ? 'Price not set' : ($planCode === 'free' ? '₦0' : money($plan['monthly_price']));
                $upgradeUrl = !empty($_SESSION['user_id'])
                    ? '/seller/subscription.php' . ($planCode === 'free' ? '' : '#plan-' . rawurlencode($planCode))
                    : '/auth/register.php';
                ?>
                <div class="col-md-4">
                    <div class="card card-body h-100 <?= $planCode === 'premium' ? 'border-success shadow-sm' : '' ?>">
                        <h3 class="h4"><?= e($plan['name']) ?></h3>
                        <div class="fs-3 fw-semibold"><?= e($planPrice) ?></div>
                        <div class="small text-muted mb-3"><?= $planCode === 'free' ? 'No monthly fee' : 'per month' ?></div>
                        <ul class="small ps-3 flex-grow-1">
                            <?php foreach ($publicPlanFeatures[$planCode] as $feature): ?>
                                <li class="mb-2"><?= e($feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a class="btn <?= $planCode === 'premium' ? 'btn-success' : 'btn-outline-success' ?> w-100" href="<?= e($upgradeUrl) ?>">
                            <?php if ($planCode === 'free'): ?>Get started free<?php elseif ($planCode === 'moderate'): ?>Upgrade to Moderate<?php else: ?>Upgrade to Premium<?php endif; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="small text-muted text-center mt-3 mb-0">Some advanced limits, reports and promotional tools are planned and are not yet enforced by the current app. Confirm feature availability before paying for a plan based on those benefits.</p>
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
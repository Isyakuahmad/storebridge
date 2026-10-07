<?php
require_once 'includes/auth.php';
$title = 'Contact Choosery';
require 'includes/header.php';

$supportEmail = 'isyakusalehahmad10@gmail.com';
$supportWhatsApp = '2348029033160';
$supportPhone = '2348120223478';
?>
<section class="py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1>Contact Choosery</h1>
            <p class="lead text-muted">Need help with your store, an order, or the Choosery platform? Use the appropriate contact below.</p>

            <div class="card card-body mb-3">
                <h2 class="h4">Contact a Seller</h2>
                <p class="text-muted mb-0">For questions about a product, price, availability, delivery, or an order, contact the seller directly from their storefront or product page.</p>
            </div>

            <div class="card card-body mb-3">
                <h2 class="h4">Choosery / Developer Support</h2>
                <p class="text-muted">For platform problems, account issues, or technical support, contact the Choosery developer/support team.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-success" href="mailto:<?=e($supportEmail)?>">Email Support</a>
                    <a class="btn btn-success" href="https://wa.me/<?=e($supportWhatsApp)?>" target="_blank" rel="noopener">WhatsApp Support</a>
                    <a class="btn btn-outline-success" href="tel:+<?=e($supportPhone)?>">Call Support</a>
                </div>
                <div class="small text-muted mt-3">
                    <div><strong>Email:</strong> <?=e($supportEmail)?></div>
                    <div><strong>WhatsApp:</strong> +234 802 903 3160</div>
                    <div><strong>Phone:</strong> +234 812 022 3478</div>
                </div>
            </div>

            <div class="alert alert-light border small">
                <strong>Important:</strong> Product, delivery, payment, and order questions should be directed to the relevant seller first. Choosery support is for platform-related issues.
            </div>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
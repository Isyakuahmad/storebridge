<?php
require_once 'includes/auth.php';
$title = 'Contact Choosery';
require 'includes/header.php';

$supportEmail = trim((string)(getenv('CHOOSERY_SUPPORT_EMAIL') ?: ''));
$supportWhatsApp = preg_replace('/\D+/', '', (string)(getenv('CHOOSERY_SUPPORT_WHATSAPP') ?: ''));
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
                <?php if($supportEmail || $supportWhatsApp): ?>
                    <p class="text-muted">For platform problems, account issues, or technical support:</p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if($supportEmail): ?><a class="btn btn-outline-success" href="mailto:<?=e($supportEmail)?>">Email Support</a><?php endif; ?>
                        <?php if($supportWhatsApp): ?><a class="btn btn-success" href="https://wa.me/<?=e($supportWhatsApp)?>" target="_blank" rel="noopener">WhatsApp Support</a><?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Platform support contact details will be published here. Seller contact is available directly on each seller's store and product page.</p>
                <?php endif; ?>
            </div>

            <div class="alert alert-light border small">
                <strong>Important:</strong> Product, delivery, payment, and order questions should be directed to the relevant seller first. Choosery support is for platform-related issues.
            </div>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
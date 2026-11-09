<?php
require_once '../includes/auth.php';
login_required();

$userId = (int)$_SESSION['user_id'];
$error = '';
$success = '';
// Payment details are supplied by a server-local config or environment variables.
// Never put bank account details directly in this tracked source file.
$paymentConfig = [];
$localPaymentConfig = __DIR__ . '/../config/payments.local.php';
if (is_file($localPaymentConfig)) {
    $loadedPaymentConfig = require $localPaymentConfig;
    if (is_array($loadedPaymentConfig)) {
        $paymentConfig = $loadedPaymentConfig;
    }
}
$bankName = trim((string)($paymentConfig['bank_name'] ?? getenv('CHOOSERY_BANK_NAME') ?: ''));
$bankAccountName = trim((string)($paymentConfig['account_name'] ?? getenv('CHOOSERY_BANK_ACCOUNT_NAME') ?: ''));
$bankAccountNumber = trim((string)($paymentConfig['account_number'] ?? getenv('CHOOSERY_BANK_ACCOUNT_NUMBER') ?: ''));
$whatsappNumber = preg_replace('/[^0-9]/', '', (string)($paymentConfig['whatsapp_number'] ?? getenv('CHOOSERY_SUPPORT_WHATSAPP') ?: '2348029033160'));
$bankDetailsConfigured = $bankName !== '' && $bankAccountName !== '' && preg_match('/^\\d{10}$/', $bankAccountNumber) === 1;
$selectedPlan = strtolower(trim((string)($_GET['plan'] ?? '')));
if (!in_array($selectedPlan, ['moderate', 'premium'], true)) {
    $selectedPlan = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $planId = (int)($_POST['plan_id'] ?? 0);
    $sender = trim($_POST['sender_name'] ?? '');
    $reference = trim($_POST['transfer_reference'] ?? '');

    try {
        $q = $pdo->prepare('SELECT id, code, name, monthly_price FROM plans WHERE id = ? AND is_active = 1 LIMIT 1');
        $q->execute([$planId]);
        $plan = $q->fetch();

        if (!$plan || !in_array($plan['code'], ['moderate', 'premium'], true) || $plan['monthly_price'] === null || (float)$plan['monthly_price'] <= 0) {
            $error = 'That paid plan is not currently available. Please try again later.';
        } elseif (strlen($sender) < 2 || strlen($sender) > 160 || strlen($reference) < 3 || strlen($reference) > 190) {
            $error = 'Enter the bank transfer sender name and transfer reference.';
            $selectedPlan = $plan['code'];
        } else {
            $q = $pdo->prepare("SELECT id FROM payments WHERE user_id = ? AND status = 'pending' LIMIT 1");
            $q->execute([$userId]);

            if ($q->fetch()) {
                $error = 'You already have a payment awaiting review. Please wait for admin confirmation before submitting another.';
                $selectedPlan = $plan['code'];
            } else {
                $q = $pdo->prepare("INSERT INTO payments (user_id, plan_id, amount, billing_cycle, payment_method, sender_name, transfer_reference) VALUES (?, ?, ?, 'monthly', 'bank_transfer', ?, ?)");
                $q->execute([$userId, $plan['id'], $plan['monthly_price'], $sender, $reference]);
                audit_log('subscription_payment_submitted', 'payment', (int)$pdo->lastInsertId(), 'Plan ' . $plan['code'] . '; amount ' . $plan['monthly_price']);
                $success = 'Payment details submitted. Your plan will change only after an admin verifies the bank transfer.';
            }
        }
    } catch (Throwable $ex) {
        error_log('Choosery subscription submission failed for user ' . $userId . ': ' . $ex->getMessage());
        $error = 'Unable to submit payment details right now. Please try again.';
    }
}

$q = $pdo->prepare(
    "SELECT s.*, p.name AS plan_name, p.code AS plan_code
     FROM subscriptions s
     JOIN plans p ON p.id = s.plan_id
     WHERE s.user_id = ? AND s.status = 'active'
       AND s.starts_at <= NOW() AND s.ends_at > NOW()
     ORDER BY s.ends_at DESC LIMIT 1"
);
$q->execute([$userId]);
$current = $q->fetch();

$q = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE user_id = ? AND status = 'pending'");
$q->execute([$userId]);
$hasPendingPayment = (int)$q->fetchColumn() > 0;

$q = $pdo->prepare(
    "SELECT p.*, pl.name AS plan_name
     FROM payments p JOIN plans pl ON pl.id = p.plan_id
     WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 10"
);
$q->execute([$userId]);
$payments = $q->fetchAll();

$plans = $pdo->query(
    "SELECT * FROM plans WHERE is_active = 1
     ORDER BY FIELD(code, 'free', 'moderate', 'premium')"
)->fetchAll();

$planFeatures = [
    'free' => [
        'Create a store and catalogue',
        'Public storefront page',
        'Receive orders and hand them off through WhatsApp',
        'Core selling tools',
    ],
    'moderate' => [
        'Everything in Free',
        'Higher product limits and additional store tools as they become available',
        'Expanded reports and order-management tools as they become available',
    ],
    'premium' => [
        'Everything in Moderate',
        'More product and order-management capabilities as they become available',
        'Additional reports and business-growth tools as they become available',
        'Store-promotion tools when released',
    ],
];

$title = 'Subscription plans';
require '../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="mb-1">Plans &amp; upgrades</h1>
        <p class="text-muted mb-0">Choose the monthly plan that fits your business. Paid upgrades are activated only after payment verification.</p>
    </div>
    <a class="btn btn-outline-success" href="/seller/dashboard.php">Back to dashboard</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success" role="status"><?= e($success) ?></div>
<?php endif; ?>

<div class="card card-body mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h2 class="h5 mb-1">Your current plan</h2>
            <?php if ($current): ?>
                <div class="fs-4"><?= e($current['plan_name']) ?></div>
                <div class="text-muted">Active until <?= e(date('M j, Y', strtotime($current['ends_at']))) ?></div>
            <?php else: ?>
                <div class="fs-4">Free</div>
                <p class="text-muted mb-0">No active paid subscription.</p>
            <?php endif; ?>
            <?php if ($hasPendingPayment): ?>
                <div class="small text-warning mt-2">A payment is awaiting admin review. You cannot submit another payment while it is pending.</div>
            <?php endif; ?>
        </div>
        <?php if ($current && $current['plan_code'] === 'premium'): ?>
            <span class="badge text-bg-success fs-6">Premium active</span>
        <?php elseif ($current && $current['plan_code'] === 'moderate'): ?>
            <span class="badge text-bg-success fs-6">Moderate active</span>
        <?php else: ?>
            <a class="btn btn-success" href="#plan-moderate">Upgrade your plan</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
<?php foreach ($plans as $p): ?>
    <?php
        $code = (string)$p['code'];
        if (!isset($planFeatures[$code])) continue;
        $isCurrent = $current && $current['plan_code'] === $code;
        $isPaid = in_array($code, ['moderate', 'premium'], true);
        $priceLabel = $p['monthly_price'] === null
            ? 'Price not set'
            : ($code === 'free' ? '₦0' : money($p['monthly_price']));
    ?>
    <div class="col-md-4" id="plan-<?= e($code) ?>">
        <div class="card card-body h-100 <?= $code === 'premium' ? 'border-success shadow-sm' : '' ?>">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <h2 class="h4 mb-1"><?= e($p['name']) ?></h2>
                <?php if ($isCurrent): ?><span class="badge text-bg-success">Current plan</span><?php endif; ?>
            </div>
            <div class="fs-3 fw-semibold mb-1"><?= e($priceLabel) ?></div>
            <div class="text-muted small mb-3"><?= $code === 'free' ? 'No monthly subscription fee' : 'per month' ?></div>
            <p class="text-muted"><?= e($p['description']) ?></p>
            <ul class="small ps-3 mb-3">
                <?php foreach ($planFeatures[$code] as $feature): ?>
                    <li class="mb-2"><?= e($feature) ?></li>
                <?php endforeach; ?>
            </ul>

            <?php if ($code === 'free'): ?>
                <?php if (!$current): ?>
                    <span class="btn btn-outline-secondary w-100 disabled" aria-disabled="true">Your free plan</span>
                <?php else: ?>
                    <span class="text-muted small">Your paid subscription remains active until its end date.</span>
                <?php endif; ?>
            <?php elseif ($isCurrent): ?>
                <span class="btn btn-outline-success w-100 disabled" aria-disabled="true">Current plan</span>
            <?php elseif ($p['monthly_price'] === null || (float)$p['monthly_price'] <= 0): ?>
                <span class="btn btn-outline-secondary w-100 disabled" aria-disabled="true">Temporarily unavailable</span>
            <?php elseif ($hasPendingPayment): ?>
                <button type="button" class="btn btn-outline-secondary w-100" disabled>Payment awaiting review</button>
            <?php else: ?>
                <a class="btn <?= $code === 'premium' ? 'btn-success' : 'btn-outline-success' ?> w-100 mb-3" href="#payment-options-<?= e($code) ?>">
                    <?= $code === 'premium' ? 'Upgrade to Premium' : 'Upgrade to Moderate' ?>
                </a>
                <div id="payment-options-<?= e($code) ?>" class="border-top pt-3">
                    <p class="small fw-semibold mb-2">Choose how to pay</p>
                    <button type="button" class="btn btn-outline-success w-100 mb-2" data-payment-toggle="bank-<?= e($code) ?>" aria-expanded="false">
                        Bank Transfer
                    </button>
                    <?php
                        $waMessage = 'Hello Choosery, I want to subscribe to the ' . $p['name'] . ' plan for ' . money($p['monthly_price']) . ' per month. Please guide me through payment.';
                        $waUrl = 'https://wa.me/' . $whatsappNumber . '?text=' . rawurlencode($waMessage);
                    ?>
                    <a class="btn btn-success w-100 mb-3" href="<?= e($waUrl) ?>" target="_blank" rel="noopener noreferrer">
                        Pay via WhatsApp
                    </a>
                    <div id="bank-<?= e($code) ?>" class="rounded border p-3 bg-light" hidden>
                        <?php if ($bankDetailsConfigured): ?>
                            <h3 class="h6">Bank account details</h3>
                            <dl class="row small mb-3">
                                <dt class="col-5">Bank</dt><dd class="col-7"><?= e($bankName) ?></dd>
                                <dt class="col-5">Account name</dt><dd class="col-7"><?= e($bankAccountName) ?></dd>
                                <dt class="col-5">Account number</dt><dd class="col-7 fw-bold"><?= e($bankAccountNumber) ?></dd>
                                <dt class="col-5">Amount</dt><dd class="col-7 fw-bold"><?= e(money($p['monthly_price'])) ?></dd>
                            </dl>
                            <p class="small text-muted">Transfer the exact amount above, then submit your sender name and transfer reference. Your plan will activate only after admin verifies the payment.</p>
                            <form method="post">
                                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                <input type="hidden" name="plan_id" value="<?= e($p['id']) ?>">
                                <label class="form-label" for="sender-<?= e($code) ?>">Name on bank transfer</label>
                                <input class="form-control mb-2" id="sender-<?= e($code) ?>" name="sender_name" maxlength="160" minlength="2" autocomplete="name" required>
                                <label class="form-label" for="reference-<?= e($code) ?>">Transfer reference / narration</label>
                                <input class="form-control mb-3" id="reference-<?= e($code) ?>" name="transfer_reference" maxlength="190" minlength="3" required>
                                <button class="btn btn-success w-100" type="submit">Submit payment for review</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0" role="alert">Bank transfer is temporarily unavailable because the payment account has not been configured on this server. Please use WhatsApp for payment instructions.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<div class="alert alert-info" role="note">
    <strong>Feature availability:</strong> product-limit changes, advanced reports, and promotional tools are not yet enforced by the current app. They are listed as planned benefits, not as features already active. Please confirm their availability before paying for a plan based on those benefits.
</div>

<div class="card card-body">
    <h2 class="h5">Payment history</h2>
    <?php if (!$payments): ?>
        <p class="text-muted mb-0">No payment submissions yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Date</th><th>Plan</th><th>Amount</th><th>Transfer reference</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= e(date('M j, Y', strtotime($p['created_at']))) ?></td>
                        <td><?= e($p['plan_name']) ?></td>
                        <td><?= e(money($p['amount'])) ?></td>
                        <td><?= e($p['transfer_reference']) ?></td>
                        <td>
                            <span class="badge text-bg-<?= $p['status'] === 'confirmed' ? 'success' : ($p['status'] === 'pending' ? 'warning' : 'secondary') ?>">
                                <?= e(ucfirst($p['status'])) ?>
                            </span>
                            <?php if (!empty($p['admin_note'])): ?><div class="small text-muted"><?= e($p['admin_note']) ?></div><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-payment-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const panel = document.getElementById(button.getAttribute('data-payment-toggle'));
            if (!panel) return;
            const willOpen = panel.hidden;
            panel.hidden = !willOpen;
            button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (willOpen) panel.scrollIntoView({behavior: 'smooth', block: 'nearest'});
        });
    });
});
</script>

<?php require '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';

admin_required();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $id = (int)($_POST['id'] ?? 0);
    $raw = trim($_POST['monthly_price'] ?? '');

    if ($id <= 0) {
        $error = 'Invalid plan selected.';
    } elseif (
        $raw === ''
        || !preg_match('/^\d{1,10}(\.\d{1,2})?$/', $raw)
        || (float)$raw <= 0
        || (float)$raw > 9999999999.99
    ) {
        $error = 'Enter a valid monthly price greater than zero, with up to two decimal places.';
    } else {
        try {
            $price = number_format((float)$raw, 2, '.', '');

            $q = $pdo->prepare(
                "SELECT id, code, name, monthly_price
                 FROM plans
                 WHERE id = ?
                   AND code IN ('moderate', 'premium')
                 LIMIT 1"
            );
            $q->execute([$id]);
            $plan = $q->fetch();

            if (!$plan) {
                $error = 'The selected paid plan was not found. The Free plan cannot be edited here.';
            } elseif (
                $plan['monthly_price'] !== null
                && number_format((float)$plan['monthly_price'], 2, '.', '') === $price
            ) {
                $success = 'The plan price is already set to this amount.';
            } else {
                $q = $pdo->prepare(
                    "UPDATE plans
                     SET monthly_price = ?
                     WHERE id = ?
                       AND code IN ('moderate', 'premium')"
                );
                $q->execute([$price, $id]);

                audit_log(
                    'plan_price_updated',
                    'plan',
                    $id,
                    'Plan ' . $plan['code'] . '; monthly price set to ' . $price
                );

                $success = 'Plan price saved successfully.';
            }
        } catch (Throwable $ex) {
            error_log(
                'Plan price update failed for plan ID '
                . $id . ': ' . $ex->getMessage()
            );
            $error = 'Unable to save the plan price. Please try again.';
        }
    }
}

$plans = $pdo->query(
    "SELECT *
     FROM plans
     ORDER BY FIELD(code, 'free', 'moderate', 'premium')"
)->fetchAll();

$title = 'Subscription plans';
require '../includes/header.php';
?>

<h1>Subscription plans</h1>
<p class="text-muted">
    Set monthly prices for Moderate and Premium.
    The Free plan remains fixed at ₦0.
</p>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<?php foreach ($plans as $p): ?>
    <div class="card card-body mb-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h2 class="h5 mb-1"><?= e($p['name']) ?></h2>
                <p class="text-muted mb-0"><?= e($p['description']) ?></p>

                <?php if ($p['code'] !== 'free'): ?>
                    <p class="small text-muted mt-2 mb-0">
                        Current price:
                        <?php if ($p['monthly_price'] === null): ?>
                            Not configured
                        <?php else: ?>
                            <?= e(money($p['monthly_price'])) ?> / month
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if ($p['code'] === 'free'): ?>
                <span class="badge text-bg-success">₦0 / month</span>
            <?php elseif (in_array($p['code'], ['moderate', 'premium'], true)): ?>
                <form method="post" class="d-flex gap-2 align-items-center flex-wrap">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <input type="hidden" name="id" value="<?= e($p['id']) ?>">

                    <label class="visually-hidden" for="price<?= e($p['id']) ?>">
                        Monthly price in naira
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">₦</span>
                        <input
                            id="price<?= e($p['id']) ?>"
                            class="form-control"
                            type="number"
                            name="monthly_price"
                            min="0.01"
                            max="9999999999.99"
                            step="0.01"
                            value="<?= e($p['monthly_price'] ?? '') ?>"
                            placeholder="Monthly price"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-success">Save price</button>
                </form>
            <?php else: ?>
                <span class="badge text-bg-secondary">Not editable here</span>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<?php require '../includes/footer.php'; ?>

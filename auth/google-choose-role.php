<?php
require_once '../includes/auth.php';
$pending = $_SESSION['google_pending_user'] ?? null;
if (!is_array($pending) || empty($pending['sub']) || empty($pending['email']) || time() - (int)($pending['created_at'] ?? 0) > 600) {
    unset($_SESSION['google_pending_user']);
    $_SESSION['auth_notice'] = 'Your Google registration session expired. Please start again.';
    go('/auth/login.php');
}

function normalize_google_registration_phone($value) {
    $digits = preg_replace('/\D+/', '', (string)$value);
    if (strlen($digits) === 11 && str_starts_with($digits, '0')) return '+234' . substr($digits, 1);
    if (strlen($digits) === 13 && str_starts_with($digits, '234')) return '+' . $digits;
    if (strlen($digits) >= 8 && strlen($digits) <= 15) return '+' . $digits;
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $role = $_POST['account_type'] ?? '';
    $name = trim((string)($pending['name'] ?? ''));
    if ($name === '') $name = trim((string)($_POST['name'] ?? ''));
    $phone = normalize_google_registration_phone($_POST['phone'] ?? '');
    if (!in_array($role, ['buyer', 'seller'], true) || $name === '') {
        $error = 'Choose an account type and provide your name.';
    } elseif ($role === 'seller' && $phone === '') {
        $error = 'A valid phone number is required for seller accounts.';
    } else {
        try {
            $pdo->beginTransaction();
            $q = $pdo->prepare('SELECT id FROM users WHERE email=? OR google_sub=? LIMIT 1');
            $q->execute([$pending['email'], $pending['sub']]);
            if ($q->fetchColumn()) {
                throw new RuntimeException('This Google account or email is already registered. Please log in instead.');
            }
            if ($phone !== '') {
                $q = $pdo->prepare('SELECT id FROM users WHERE phone=? LIMIT 1');
                $q->execute([$phone]);
                if ($q->fetchColumn()) throw new RuntimeException('That phone number is already associated with a Choosery account.');
            }
            $referralCode = $role === 'seller' ? preg_replace('/[^A-Za-z0-9]/', '', (string)($_SESSION['referral_code'] ?? '')) : '';
            $referrerId = null;
            if ($referralCode !== '') {
                $q = $pdo->prepare('SELECT id FROM users WHERE referral_code=? AND account_status=?');
                $q->execute([$referralCode, 'active']);
                $referrerId = $q->fetchColumn() ?: null;
            }
            $q = $pdo->prepare('INSERT INTO users(name,email,phone,password_hash,role,google_sub,referred_by_user_id) VALUES(?,?,?,?,?,?,?)');
            $q->execute([$name, $pending['email'], $phone !== '' ? $phone : null, null, $role, $pending['sub'], $referrerId]);
            $newUserId = (int)$pdo->lastInsertId();
            if ($role === 'seller') ensure_referral_code($newUserId);
            $pdo->commit();
            session_regenerate_id(true);
            unset($_SESSION['google_pending_user'], $_SESSION['referral_code'], $_SESSION['csrf']);
            $_SESSION['user_id'] = $newUserId;
            go($role === 'buyer' ? '/buyer/dashboard.php' : '/seller/dashboard.php');
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Choosery Google registration error: ' . $error->getMessage());
            $errorMessage = $error instanceof RuntimeException ? $error->getMessage() : 'We could not create your account. Please try again.';
            $error = $errorMessage;
        }
    }
}
$title = 'Choose your Choosery account';
require '../includes/header.php';
?>
<h1>Finish creating your account</h1>
<p class="text-muted">Google verified <?=e($pending['email'])?>. Choose how you want to use Choosery.</p>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<form method="post" class="card card-body col-lg-6">
    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
    <label class="form-label" for="google-name">Name</label>
    <input class="form-control mb-3" id="google-name" name="name" value="<?=e($pending['name'] ?? '')?>" required>
    <label class="form-label" for="google-account-type">I want to join as</label>
    <select class="form-select mb-3" id="google-account-type" name="account_type" required>
        <option value="">Choose account type</option>
        <option value="buyer" <?=($_POST['account_type'] ?? '') === 'buyer' ? 'selected' : ''?>>Buyer — discover and shop from businesses</option>
        <option value="seller" <?=($_POST['account_type'] ?? '') === 'seller' ? 'selected' : ''?>>Seller — create and manage a store</option>
    </select>
    <label class="form-label" for="google-phone">Phone number <span class="text-muted">(required for sellers)</span></label>
    <input class="form-control mb-3" id="google-phone" type="tel" name="phone" autocomplete="tel" placeholder="e.g. 08012345678 or +2348012345678" value="<?=e($_POST['phone'] ?? '')?>">
    <p class="small text-muted">By continuing, you agree to our <a href="/terms.php">Terms</a>, <a href="/privacy.php">Privacy Policy</a>, and <a href="/acceptable-use.php">Acceptable Use Policy</a>.</p>
    <button class="btn btn-success">Create Choosery account</button>
</form>
<?php require '../includes/footer.php'; ?>

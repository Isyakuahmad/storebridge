<?php
require_once '../includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['ref'])) {
    $ref = preg_replace('/[^A-Za-z0-9]/', '', $_GET['ref']);
    if ($ref) $_SESSION['referral_code'] = $ref;
}
$referralCode = $_SESSION['referral_code'] ?? '';
function normalize_registration_phone($value) {
    $digits = preg_replace('/\D+/', '', (string)$value);
    if (strlen($digits) === 11 && str_starts_with($digits, '0')) return '+234' . substr($digits, 1);
    if (strlen($digits) === 13 && str_starts_with($digits, '234')) return '+' . $digits;
    if (strlen($digits) >= 8 && strlen($digits) <= 15) return '+' . $digits;
    return '';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $n = trim($_POST['name'] ?? '');
    $e = filter_var(strtolower(trim($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $phone = normalize_registration_phone($_POST['phone'] ?? '');
    $p = $_POST['password'] ?? '';
    $referralCode = preg_replace('/[^A-Za-z0-9]/', '', $_POST['referral_code'] ?? $referralCode);
    if ($n && $e && $phone && strlen($p) >= 8) {
        try {
            $pdo->beginTransaction();
            $q = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
            $q->execute([$e]); $emailOwner = (int)($q->fetchColumn() ?: 0);
            $q = $pdo->prepare('SELECT id FROM users WHERE phone=? LIMIT 1');
            $q->execute([$phone]); $phoneOwner = (int)($q->fetchColumn() ?: 0);
            if ($emailOwner || $phoneOwner) {
                $ids = array_values(array_unique(array_filter([$emailOwner, $phoneOwner])));
                $type = $emailOwner && $phoneOwner ? 'duplicate_email_phone_attempt' : ($emailOwner ? 'duplicate_email_attempt' : 'duplicate_phone_attempt');
                foreach ($ids as $uid) {
                    $other = array_values(array_diff($ids, [$uid]));
                    $q = $pdo->prepare('INSERT INTO registration_flags(user_id,related_user_id,flag_type) VALUES(?,?,?)');
                    $q->execute([$uid, $other[0] ?? null, $type]);
                }
                $pdo->commit();
                $error = 'We could not complete registration with those details. If you already have an account, please log in or use password recovery. If you believe this is a mistake, contact support.';
            } else {
                $referrerId = null;
                if ($referralCode) {
                    $q = $pdo->prepare('SELECT id FROM users WHERE referral_code=? AND account_status=?');
                    $q->execute([$referralCode, 'active']);
                    $referrerId = $q->fetchColumn() ?: null;
                }
                $q = $pdo->prepare('INSERT INTO users(name,email,phone,password_hash,referred_by_user_id) VALUES(?,?,?,?,?)');
                $q->execute([$n, $e, $phone, password_hash($p, PASSWORD_DEFAULT), $referrerId]);
                $newUserId = (int)$pdo->lastInsertId();
                ensure_referral_code($newUserId);
                $pdo->commit();
                session_regenerate_id(true);
                unset($_SESSION['referral_code'], $_SESSION['csrf']);
                $_SESSION['user_id'] = $newUserId;
                go('/seller/dashboard.php');
            }
        } catch (PDOException $x) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Choosery registration database error: ' . $x->getMessage());
            $error = 'We could not complete registration. Please check the details or try again later.';
        } catch (Throwable $x) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Choosery registration error: ' . $x->getMessage());
            $error = 'We could not complete registration. Please try again later.';
        }
    } else {
        $error = 'Name, valid email, phone number, and password of 8+ characters are required.';
    }
}
$title = 'Register';
require '../includes/header.php';
?>
<h1>Register</h1>
<p class="text-muted">Create your Choosery seller account.</p>
<?php if ($referralCode): ?><div class="alert alert-success">You were invited to join Choosery through a referral.</div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif; ?>
<form method="post" class="card card-body col-lg-6">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<input type="hidden" name="referral_code" value="<?=e($referralCode)?>">
<label class="form-label" for="reg-name">Full name</label><input id="reg-name" class="form-control mb-2" name="name" autocomplete="name" placeholder="Name" value="<?=e($_POST['name'] ?? '')?>" required>
<label class="form-label" for="reg-email">Email address</label><input id="reg-email" class="form-control mb-2" type="email" name="email" autocomplete="email" placeholder="Email" value="<?=e($_POST['email'] ?? '')?>" required>
<label class="form-label" for="reg-phone">Phone number</label><input id="reg-phone" class="form-control mb-2" type="tel" name="phone" autocomplete="tel" placeholder="e.g. 08012345678 or +2348012345678" value="<?=e($_POST['phone'] ?? '')?>" required>
<label class="form-label" for="reg-password">Password</label><input id="reg-password" class="form-control mb-2" type="password" name="password" autocomplete="new-password" placeholder="Password (8+ characters)" minlength="8" required>
<p class="small text-muted">By registering, you agree to our <a href="/terms.php">Terms</a>, <a href="/privacy.php">Privacy Policy</a>, and <a href="/acceptable-use.php">Acceptable Use Policy</a>.</p>
<button class="btn btn-success">Register</button>
<div class="text-center mt-3 text-muted">Already registered? <a href="/auth/login.php" class="fw-semibold">Log in.</a></div>
</form>
<?php require '../includes/footer.php'; ?>

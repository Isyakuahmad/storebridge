<?php
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['ref'])) {
    $ref = preg_replace('/[^A-Za-z0-9]/', '', $_GET['ref']);
    if ($ref) $_SESSION['referral_code'] = $ref;
}
$referralCode = $_SESSION['referral_code'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $n = trim($_POST['name'] ?? '');
    $e = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $p = $_POST['password'] ?? '';
    $referralCode = preg_replace('/[^A-Za-z0-9]/', '', $_POST['referral_code'] ?? $referralCode);

    if ($n && $e && strlen($p) >= 8) {
        try {
            // Create the account and its referral code atomically. This avoids leaving
            // an unusable account behind if referral-code creation fails.
            $pdo->beginTransaction();

            $referrerId = null;
            if ($referralCode) {
                $q = $pdo->prepare('SELECT id FROM users WHERE referral_code=? AND account_status=?');
                $q->execute([$referralCode, 'active']);
                $referrerId = $q->fetchColumn() ?: null;
            }

            $q = $pdo->prepare('INSERT INTO users(name,email,password_hash,referred_by_user_id) VALUES(?,?,?,?)');
            $q->execute([$n, $e, password_hash($p, PASSWORD_DEFAULT), $referrerId]);
            $newUserId = (int)$pdo->lastInsertId();

            ensure_referral_code($newUserId);
            $pdo->commit();

            // Rotate the session only after the database transaction succeeds.
            session_regenerate_id(true);
            unset($_SESSION['referral_code'], $_SESSION['csrf']);
            $_SESSION['user_id'] = $newUserId;
            go('/seller/dashboard.php');
        } catch (PDOException $x) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Error 1062 can be caused by any unique key, not just email.
            // Only show the duplicate-email message when that email actually exists.
            $emailExists = false;
            try {
                $q = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
                $q->execute([$e]);
                $emailExists = (bool)$q->fetchColumn();
            } catch (Throwable $lookupError) {
                error_log('Choosery registration duplicate-email check failed: ' . $lookupError->getMessage());
            }

            if ($emailExists) {
                $error = 'That email address is already registered. Please log in or use another email.';
            } else {
                error_log('Choosery registration database error: ' . $x->getMessage());
                $error = 'We could not complete registration because of a database problem. Please try again later.';
            }
        } catch (RuntimeException $x) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Choosery registration referral setup error: ' . $x->getMessage());
            $error = 'We could not complete your account setup. Please try again later.';
        } catch (Throwable $x) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Choosery registration unexpected error: ' . $x->getMessage());
            $error = 'We could not complete registration. Please try again later.';
        }
    } else {
        $error = 'Name, valid email, and password of 8+ characters are required.';
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
<input class="form-control mb-2" name="name" placeholder="Name" value="<?=e($_POST['name'] ?? '')?>" required>
<input class="form-control mb-2" type="email" name="email" placeholder="Email" value="<?=e($_POST['email'] ?? '')?>" required>
<input class="form-control mb-2" type="password" name="password" placeholder="Password (8+ characters)" required>
<button class="btn btn-success">Register</button>
<div class="text-center mt-3 text-muted">Already registered? <a href="/auth/login.php" class="fw-semibold">Log in.</a></div>
</form>
<?php require '../includes/footer.php'; ?>

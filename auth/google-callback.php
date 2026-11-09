<?php
require_once '../includes/auth.php';
require_once '../includes/google_oauth.php';

$flow = $_SESSION['google_oauth'] ?? null;
unset($_SESSION['google_oauth']);
if (!is_array($flow) || empty($flow['state']) || empty($flow['nonce']) ||
    empty($flow['verifier']) || time() - (int)($flow['started_at'] ?? 0) > 600 ||
    !isset($_GET['state']) || !hash_equals((string)$flow['state'], (string)$_GET['state'])) {
    $_SESSION['auth_notice'] = 'Google sign-in expired or could not be verified. Please try again.';
    go('/auth/login.php');
}
if (!empty($_GET['error']) || empty($_GET['code'])) {
    $_SESSION['auth_notice'] = 'Google sign-in was cancelled or could not be completed.';
    go('/auth/login.php');
}

try {
    $tokens = google_exchange_code((string)$_GET['code'], (string)$flow['verifier']);
    if (empty($tokens['id_token'])) throw new RuntimeException('Google did not return an identity token.');
    $identity = google_verify_id_token($tokens['id_token'], (string)$flow['nonce']);

    if (($flow['mode'] ?? 'login') === 'link') {
        $userId = (int)($flow['user_id'] ?? 0);
        if (!$userId || empty($_SESSION['user_id']) || (int)$_SESSION['user_id'] !== $userId) {
            throw new RuntimeException('Your Choosery session changed. Sign in again before linking Google.');
        }
        $q = $pdo->prepare('SELECT id,email,google_sub,account_status FROM users WHERE id=? LIMIT 1');
        $q->execute([$userId]);
        $user = $q->fetch();
        if (!$user || $user['account_status'] !== 'active') throw new RuntimeException('This Choosery account is not active.');
        if (!hash_equals(strtolower((string)$user['email']), $identity['email'])) {
            throw new RuntimeException('The Google email must match the email address on your Choosery account.');
        }
        $q = $pdo->prepare('SELECT id FROM users WHERE google_sub=? AND id<>? LIMIT 1');
        $q->execute([$identity['sub'], $userId]);
        if ($q->fetchColumn()) throw new RuntimeException('This Google account is already linked to another Choosery account.');
        if (!empty($user['google_sub']) && !hash_equals((string)$user['google_sub'], $identity['sub'])) {
            throw new RuntimeException('A different Google account is already linked. Contact support if you need help changing it.');
        }
        $q = $pdo->prepare('UPDATE users SET google_sub=? WHERE id=?');
        $q->execute([$identity['sub'], $userId]);
        audit_log('google_account_linked', 'user', $userId, 'Google identity linked after authenticated session and matching verified email.');
        $_SESSION['auth_notice'] = 'Your Google account is now linked to Choosery.';
        go('/seller/account.php');
    }

    $q = $pdo->prepare('SELECT id,role,account_status FROM users WHERE google_sub=? LIMIT 1');
    $q->execute([$identity['sub']]);
    $user = $q->fetch();
    if (!$user) {
        $q = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $q->execute([$identity['email']]);
        if ($q->fetchColumn()) {
            $_SESSION['auth_notice'] = 'A Choosery account already uses this email. Sign in with that account first, then connect Google from your account page.';
            go('/auth/login.php');
        }
        $_SESSION['google_pending_user'] = $identity;
        go('/auth/google-choose-role.php');
    }
    if ($user['account_status'] !== 'active') {
        $_SESSION['auth_notice'] = 'This Choosery account is suspended. Contact support if you believe this is a mistake.';
        go('/auth/login.php');
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    unset($_SESSION['google_pending_user']);
    go(($user['role'] ?? 'seller') === 'buyer' ? '/buyer/dashboard.php' : (($user['role'] ?? '') === 'admin' ? '/admin/' : '/seller/dashboard.php'));
} catch (Throwable $error) {
    error_log('Choosery Google authentication error: ' . $error->getMessage());
    $_SESSION['auth_notice'] = $error instanceof RuntimeException ? $error->getMessage() : 'Google sign-in could not be completed. Please try again.';
    go('/auth/login.php');
}

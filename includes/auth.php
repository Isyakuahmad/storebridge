<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function go($u) { header("Location: $u"); exit; }
function csrf() { return $_SESSION['csrf'] ?? ($_SESSION['csrf'] = bin2hex(random_bytes(32))); }
function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Invalid form token. Please reload the page and try again.');
    }
}
function login_required() {
    global $pdo;
    if (empty($_SESSION['user_id'])) go('/auth/login.php');
    $q = $pdo->prepare('SELECT account_status FROM users WHERE id=?');
    $q->execute([$_SESSION['user_id']]);
    $u = $q->fetch();
    if (!$u || $u['account_status'] !== 'active') {
        $_SESSION = [];
        session_destroy();
        go('/auth/login.php');
    }
}
function is_admin() {
    global $pdo;
    if (empty($_SESSION['user_id'])) return false;
    $q = $pdo->prepare("SELECT role,account_status FROM users WHERE id=?");
    $q->execute([$_SESSION['user_id']]);
    $u = $q->fetch();
    return $u && $u['role'] === 'admin' && $u['account_status'] === 'active';
}
function admin_required() { login_required(); if (!is_admin()) go('/seller/dashboard.php'); }
function store() {
    global $pdo;
    $q = $pdo->prepare('SELECT * FROM stores WHERE user_id=?');
    $q->execute([$_SESSION['user_id'] ?? 0]);
    return $q->fetch();
}
function money($v) { return '₦' . number_format((float)$v, 2); }
function cart_count() { return array_sum($_SESSION['cart'] ?? []); }
function audit_log($action, $targetType, $targetId = null, $details = null) {
    global $pdo;
    if (empty($_SESSION['user_id'])) return;
    $q = $pdo->prepare('INSERT INTO audit_logs(actor_user_id,action,target_type,target_id,details)VALUES(?,?,?,?,?)');
    $q->execute([$_SESSION['user_id'], $action, $targetType, $targetId, $details]);
}
function ensure_referral_code($userId) {
    global $pdo;
    $q = $pdo->prepare('SELECT referral_code FROM users WHERE id=?');
    $q->execute([$userId]);
    $existing = $q->fetchColumn();
    if ($existing) return $existing;
    for ($i = 0; $i < 5; $i++) {
        $code = 'CH' . strtoupper(bin2hex(random_bytes(4)));
        try {
            $q = $pdo->prepare('UPDATE users SET referral_code=? WHERE id=? AND referral_code IS NULL');
            $q->execute([$code, $userId]);
            if ($q->rowCount()) return $code;
            $q = $pdo->prepare('SELECT referral_code FROM users WHERE id=?');
            $q->execute([$userId]);
            $existing = $q->fetchColumn();
            if ($existing) return $existing;
        } catch (PDOException $e) {
            if ((int)($e->errorInfo[1] ?? 0) !== 1062) throw $e;
        }
    }
    throw new RuntimeException('Unable to create referral code.');
}
function app_url() {
    $configured = trim((string)(getenv('APP_URL') ?: 'https://storebridge.freedev.app'));
    $parts = parse_url($configured);
    if (!$parts || empty($parts['scheme']) || empty($parts['host']) ||
        !in_array(strtolower($parts['scheme']), ['https', 'http'], true) ||
        isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
        return 'https://storebridge.freedev.app';
    }
    return rtrim($configured, '/');
}
function referral_url($code) {
    return app_url() . '/auth/register.php?ref=' . rawurlencode($code);
}

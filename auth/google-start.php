<?php
require_once '../includes/auth.php';
require_once '../includes/google_oauth.php';

$mode = ($_GET['mode'] ?? 'login') === 'link' ? 'link' : 'login';
if (!google_oauth_enabled()) {
    $_SESSION['auth_notice'] = 'Google sign-in is not configured yet. Please use your email and password for now.';
    go('/auth/login.php');
}
if ($mode === 'link' && empty($_SESSION['user_id'])) go('/auth/login.php');
if ($mode === 'login' && !empty($_SESSION['user_id'])) go('/');

$state = bin2hex(random_bytes(32));
$nonce = bin2hex(random_bytes(32));
$verifier = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
$challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
$_SESSION['google_oauth'] = [
    'state' => $state,
    'nonce' => $nonce,
    'verifier' => $verifier,
    'mode' => $mode,
    'user_id' => $mode === 'link' ? (int)$_SESSION['user_id'] : null,
    'started_at' => time(),
];

$params = [
    'client_id' => GOOGLE_OAUTH_CLIENT_ID,
    'redirect_uri' => GOOGLE_OAUTH_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'nonce' => $nonce,
    'code_challenge' => $challenge,
    'code_challenge_method' => 'S256',
    'prompt' => 'select_account',
];
go('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));

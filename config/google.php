<?php
/**
 * Google OpenID Connect configuration.
 * Secrets belong in environment variables or config/google.local.php (never Git).
 */
$googleLocalConfig = __DIR__ . '/google.local.php';
if (is_file($googleLocalConfig)) {
    require $googleLocalConfig;
}
if (!isset($googleClientId) || $googleClientId === '') $googleClientId = getenv('GOOGLE_CLIENT_ID') ?: '';
if (!isset($googleClientSecret) || $googleClientSecret === '') $googleClientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: '';
if (!isset($googleRedirectUri) || $googleRedirectUri === '') $googleRedirectUri = getenv('GOOGLE_REDIRECT_URI') ?: '';
$googleClientId = trim((string)$googleClientId);
$googleClientSecret = trim((string)$googleClientSecret);
$googleRedirectUri = trim((string)$googleRedirectUri);
if ($googleRedirectUri === '') {
    $googleAppUrl = rtrim((string)(getenv('APP_URL') ?: 'https://storebridge.freedev.app'), '/');
    $googleRedirectUri = $googleAppUrl . '/auth/google-callback.php';
}
if (!defined('GOOGLE_OAUTH_CLIENT_ID')) define('GOOGLE_OAUTH_CLIENT_ID', $googleClientId);
if (!defined('GOOGLE_OAUTH_CLIENT_SECRET')) define('GOOGLE_OAUTH_CLIENT_SECRET', $googleClientSecret);
if (!defined('GOOGLE_OAUTH_REDIRECT_URI')) define('GOOGLE_OAUTH_REDIRECT_URI', $googleRedirectUri);

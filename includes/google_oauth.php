<?php
require_once __DIR__ . '/../config/google.php';

function google_oauth_enabled() {
    return GOOGLE_OAUTH_CLIENT_ID !== '' && GOOGLE_OAUTH_CLIENT_SECRET !== '' && GOOGLE_OAUTH_REDIRECT_URI !== '';
}

function google_base64url_decode($value) {
    $value = strtr((string)$value, '-_', '+/');
    $value .= str_repeat('=', (4 - strlen($value) % 4) % 4);
    $decoded = base64_decode($value, true);
    return $decoded === false ? false : $decoded;
}

function google_http_request($url, $method = 'GET', $body = null, $headers = []) {
    $headerLines = array_merge(['Accept: application/json'], $headers);
    $options = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headerLines),
            'timeout' => 12,
            'ignore_errors' => true,
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ];
    if ($body !== null) {
        $options['http']['content'] = $body;
    }
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    $status = 0;
    foreach (($http_response_header ?? []) as $line) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $line, $matches)) {
            $status = (int)$matches[1];
        }
    }
    if ($response === false || $status < 200 || $status >= 300) {
        throw new RuntimeException('Google authentication service could not be reached.');
    }
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Google returned an invalid authentication response.');
    }
    return $decoded;
}

function google_der_length($length) {
    if ($length < 128) return chr($length);
    $bytes = '';
    while ($length > 0) {
        $bytes = chr($length & 0xff) . $bytes;
        $length >>= 8;
    }
    return chr(0x80 | strlen($bytes)) . $bytes;
}

function google_der_integer($bytes) {
    $bytes = ltrim($bytes, "\x00");
    if ($bytes === '') $bytes = "\x00";
    if ((ord($bytes[0]) & 0x80) !== 0) $bytes = "\x00" . $bytes;
    return "\x02" . google_der_length(strlen($bytes)) . $bytes;
}

function google_rsa_jwk_to_pem($modulus, $exponent) {
    $n = google_base64url_decode($modulus);
    $e = google_base64url_decode($exponent);
    if ($n === false || $e === false) throw new RuntimeException('Invalid Google signing key.');
    $rsa = google_der_integer($n) . google_der_integer($e);
    $rsa = "\x30" . google_der_length(strlen($rsa)) . $rsa;
    $algorithm = hex2bin('300d06092a864886f70d0101010500');
    $bitString = "\x03" . google_der_length(strlen($rsa) + 1) . "\x00" . $rsa;
    $spki = $algorithm . $bitString;
    $spki = "\x30" . google_der_length(strlen($spki)) . $spki;
    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----";
}

function google_verify_id_token($idToken, $expectedNonce) {
    if (!function_exists('openssl_verify')) {
        throw new RuntimeException('Google sign-in requires the PHP OpenSSL extension.');
    }
    $parts = explode('.', (string)$idToken);
    if (count($parts) !== 3) throw new RuntimeException('Google returned an invalid identity token.');
    $header = json_decode(google_base64url_decode($parts[0]), true);
    $claims = json_decode(google_base64url_decode($parts[1]), true);
    $signature = google_base64url_decode($parts[2]);
    if (!is_array($header) || !is_array($claims) || $signature === false ||
        ($header['alg'] ?? '') !== 'RS256' || empty($header['kid'])) {
        throw new RuntimeException('Google returned an invalid identity token.');
    }

    $audience = $claims['aud'] ?? null;
    $audienceValid = is_string($audience)
        ? hash_equals(GOOGLE_OAUTH_CLIENT_ID, $audience)
        : (is_array($audience) && in_array(GOOGLE_OAUTH_CLIENT_ID, $audience, true));
    $issuerValid = in_array($claims['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true);
    if (!$audienceValid || !$issuerValid ||
        (isset($claims['azp']) && !hash_equals(GOOGLE_OAUTH_CLIENT_ID, (string)$claims['azp'])) ||
        !isset($claims['exp'], $claims['iat']) ||
        !is_numeric($claims['exp']) || !is_numeric($claims['iat']) ||
        (int)$claims['exp'] <= time() ||
        (int)$claims['iat'] > time() + 60 ||
        (isset($claims['nbf']) && (int)$claims['nbf'] > time() + 60) ||
        empty($claims['sub']) || empty($claims['email']) ||
        !filter_var($claims['email'], FILTER_VALIDATE_EMAIL) ||
        !in_array($claims['email_verified'] ?? false, [true, 'true', 1, '1'], true) ||
        !isset($claims['nonce']) || !hash_equals((string)$expectedNonce, (string)$claims['nonce'])) {
        throw new RuntimeException('Google could not verify this account for Choosery.');
    }

    $jwks = google_http_request('https://www.googleapis.com/oauth2/v3/certs');
    $key = null;
    foreach (($jwks['keys'] ?? []) as $candidate) {
        if (($candidate['kid'] ?? '') === $header['kid'] &&
            ($candidate['kty'] ?? '') === 'RSA' &&
            ($candidate['use'] ?? 'sig') === 'sig') {
            $key = $candidate;
            break;
        }
    }
    if (!$key || empty($key['n']) || empty($key['e'])) {
        throw new RuntimeException('Google signing key could not be validated.');
    }
    $publicKey = openssl_pkey_get_public(google_rsa_jwk_to_pem($key['n'], $key['e']));
    if ($publicKey === false || openssl_verify($parts[0] . '.' . $parts[1], $signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
        throw new RuntimeException('Google identity token signature is invalid.');
    }
    return [
        'sub' => (string)$claims['sub'],
        'email' => strtolower(trim((string)$claims['email'])),
        'name' => trim((string)($claims['name'] ?? '')),
    ];
}

function google_exchange_code($code, $verifier) {
    $payload = http_build_query([
        'code' => $code,
        'client_id' => GOOGLE_OAUTH_CLIENT_ID,
        'client_secret' => GOOGLE_OAUTH_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_OAUTH_REDIRECT_URI,
        'grant_type' => 'authorization_code',
        'code_verifier' => $verifier,
    ]);
    return google_http_request(
        'https://oauth2.googleapis.com/token',
        'POST',
        $payload,
        ['Content-Type: application/x-www-form-urlencoded']
    );
}

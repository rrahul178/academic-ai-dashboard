<?php
/**
 * Minimal HS256 JWT encode/decode.
 *
 * A demo-scale project doesn't need a Composer dependency for
 * this, but the same interface (encode/decode, exp claim,
 * signature check) is what a library like firebase/php-jwt gives
 * you — swapping this file for that package is a one-line change
 * if the project grows past a demo.
 */

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_encode(array $payload): string {
    $header = ['typ' => 'JWT', 'alg' => 'HS256'];
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_TTL_SECONDS;

    $segments = [
        base64url_encode(json_encode($header)),
        base64url_encode(json_encode($payload)),
    ];
    $signature = hash_hmac('sha256', implode('.', $segments), JWT_SECRET, true);
    $segments[] = base64url_encode($signature);

    return implode('.', $segments);
}

/**
 * Returns the decoded payload array, or null if the token is
 * missing, malformed, expired, or has a bad signature.
 */
function jwt_decode(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    [$headerB64, $payloadB64, $sigB64] = $parts;

    $expectedSig = hash_hmac('sha256', "$headerB64.$payloadB64", JWT_SECRET, true);
    if (!hash_equals($expectedSig, base64url_decode($sigB64))) {
        return null; // tampered or wrong secret
    }

    $payload = json_decode(base64url_decode($payloadB64), true);
    if (!is_array($payload) || !isset($payload['exp']) || $payload['exp'] < time()) {
        return null; // expired
    }
    return $payload;
}

/**
 * Auth middleware. Call at the top of any protected endpoint.
 * Exits with 401 if the request has no valid bearer token,
 * otherwise returns the decoded token payload (contains user_id,
 * role).
 */
function require_auth(): array {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/', $authHeader, $m)) {
        json_response(['error' => 'Missing or malformed Authorization header'], 401);
    }
    $payload = jwt_decode($m[1]);
    if ($payload === null) {
        json_response(['error' => 'Invalid or expired token'], 401);
    }
    return $payload;
}

/** Restrict an endpoint to specific roles, e.g. require_role($user, ['admin']). */
function require_role(array $user, array $allowedRoles): void {
    if (!in_array($user['role'] ?? '', $allowedRoles, true)) {
        json_response(['error' => 'Forbidden: insufficient role'], 403);
    }
}

<?php
/**
 * POST /auth.php
 * Body: { "email": "...", "password": "..." }
 * Returns: { "token": "...", "user": { id, name, role } }
 *
 * Demonstrates: input validation, bcrypt password verification
 * (never store or compare plaintext passwords), and a signed
 * JWT issued on success — no session state kept server-side.
 */
require __DIR__ . '/config.php';
require __DIR__ . '/jwt_helper.php';

apply_cors_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
$email = trim($body['email'] ?? '');
$password = (string) ($body['password'] ?? '');

if ($email === '' || $password === '') {
    json_response(['error' => 'Email and password are required'], 400);
}

$pdo = get_db();
$stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

// Same generic error whether the email doesn't exist or the
// password is wrong — avoids leaking which accounts exist.
if (!$user || !password_verify($password, $user['password_hash'])) {
    json_response(['error' => 'Invalid email or password'], 401);
}

$token = jwt_encode([
    'user_id' => $user['id'],
    'role' => $user['role'],
]);

json_response([
    'token' => $token,
    'user' => ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']],
]);

<?php
/**
 * Academic AI Dashboard — PHP Backend Configuration
 * -----------------------------------------------------------
 * Central config: database connection, JWT secret, and the
 * base URL of the Django AI microservice.
 *
 * Demo uses SQLite so the whole backend runs with zero setup.
 * In production this would point to MySQL/Postgres with the
 * same PDO calls.
 */

// --- Database -------------------------------------------------
define('DB_PATH', __DIR__ . '/database.sqlite');

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON;');
    }
    return $pdo;
}

// --- Security ---------------------------------------------------
// In production, load this from an environment variable
// (getenv('JWT_SECRET')) — never commit a real secret to source
// control. This placeholder is here only so the demo runs.
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'demo-secret-change-me-in-production');
define('JWT_TTL_SECONDS', 60 * 60 * 8); // 8-hour session

// --- AI microservice ---------------------------------------------
define('AI_SERVICE_BASE_URL', getenv('AI_SERVICE_URL') ?: 'http://localhost:8000/api');

// --- CORS (frontend served separately during dev) ------------------
function apply_cors_headers(): void {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

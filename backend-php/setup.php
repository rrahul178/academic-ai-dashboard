<?php
/**
 * Run once: `php setup.php`
 * Creates database.sqlite from db_schema.sql and seeds a demo
 * admin account (email: admin@pahmc.edu.bd / password: admin123).
 */
require __DIR__ . '/config.php';

$pdo = get_db();
$pdo->exec(file_get_contents(__DIR__ . '/db_schema.sql'));

$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
$stmt->execute(['admin@pahmc.edu.bd']);
if ((int) $stmt->fetchColumn() === 0) {
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)')
        ->execute(['Rahul Acharjee', 'admin@pahmc.edu.bd', $hash, 'admin']);
    echo "Seeded demo admin: admin@pahmc.edu.bd / admin123\n";
} else {
    echo "Demo admin already exists.\n";
}

echo "Database ready at " . DB_PATH . "\n";

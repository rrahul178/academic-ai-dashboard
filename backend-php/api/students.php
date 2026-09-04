<?php
/**
 * RESTful students resource.
 *   GET    /api/students.php           -> list all
 *   GET    /api/students.php?id=5      -> one student
 *   POST   /api/students.php           -> create
 *   PUT    /api/students.php?id=5      -> update
 *   DELETE /api/students.php?id=5      -> delete
 *
 * All writes require a valid JWT (require_auth). Every value
 * from the client goes through prepared statements — never
 * string-concatenated into SQL — to prevent injection.
 */
require __DIR__ . '/../config.php';
require __DIR__ . '/../jwt_helper.php';

apply_cors_headers();
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            $row ? json_response($row) : json_response(['error' => 'Not found'], 404);
        } else {
            $batch = $_GET['batch'] ?? null;
            if ($batch) {
                $stmt = $pdo->prepare('SELECT * FROM students WHERE batch = ? ORDER BY name');
                $stmt->execute([$batch]);
            } else {
                $stmt = $pdo->query('SELECT * FROM students ORDER BY name');
            }
            json_response(['students' => $stmt->fetchAll()]);
        }
        break;

    case 'POST':
        require_auth();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        foreach (['roll_no', 'name', 'batch', 'department'] as $field) {
            if (empty($body[$field])) {
                json_response(['error' => "Field '$field' is required"], 400);
            }
        }
        $stmt = $pdo->prepare(
            'INSERT INTO students (roll_no, name, batch, department) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$body['roll_no'], $body['name'], $body['batch'], $body['department']]);
        json_response(['id' => (int) $pdo->lastInsertId()], 201);
        break;

    case 'PUT':
        require_auth();
        if (!$id) {
            json_response(['error' => 'id query parameter is required'], 400);
        }
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $fields = array_intersect_key($body, array_flip(['roll_no', 'name', 'batch', 'department']));
        if (!$fields) {
            json_response(['error' => 'No updatable fields provided'], 400);
        }
        $setClause = implode(', ', array_map(fn($f) => "$f = ?", array_keys($fields)));
        $stmt = $pdo->prepare("UPDATE students SET $setClause WHERE id = ?");
        $stmt->execute([...array_values($fields), $id]);
        json_response(['updated' => $stmt->rowCount() > 0]);
        break;

    case 'DELETE':
        $user = require_auth();
        require_role($user, ['admin', 'coordinator']);
        if (!$id) {
            json_response(['error' => 'id query parameter is required'], 400);
        }
        $stmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
        $stmt->execute([$id]);
        json_response(['deleted' => $stmt->rowCount() > 0]);
        break;

    default:
        json_response(['error' => 'Method not allowed'], 405);
}

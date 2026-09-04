<?php
/**
 * RESTful faculty resource — same pattern as students.php.
 *   GET /api/faculty.php[?id=]   POST / PUT?id= / DELETE?id=
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
            $stmt = $pdo->prepare('SELECT * FROM faculty WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            $row ? json_response($row) : json_response(['error' => 'Not found'], 404);
        } else {
            json_response(['faculty' => $pdo->query('SELECT * FROM faculty ORDER BY name')->fetchAll()]);
        }
        break;

    case 'POST':
        require_auth();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($body['name']) || empty($body['department'])) {
            json_response(['error' => 'name and department are required'], 400);
        }
        $stmt = $pdo->prepare(
            'INSERT INTO faculty (name, department, designation, email) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$body['name'], $body['department'], $body['designation'] ?? null, $body['email'] ?? null]);
        json_response(['id' => (int) $pdo->lastInsertId()], 201);
        break;

    case 'PUT':
        require_auth();
        if (!$id) json_response(['error' => 'id query parameter is required'], 400);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $fields = array_intersect_key($body, array_flip(['name', 'department', 'designation', 'email']));
        if (!$fields) json_response(['error' => 'No updatable fields provided'], 400);
        $setClause = implode(', ', array_map(fn($f) => "$f = ?", array_keys($fields)));
        $stmt = $pdo->prepare("UPDATE faculty SET $setClause WHERE id = ?");
        $stmt->execute([...array_values($fields), $id]);
        json_response(['updated' => $stmt->rowCount() > 0]);
        break;

    case 'DELETE':
        $user = require_auth();
        require_role($user, ['admin', 'coordinator']);
        if (!$id) json_response(['error' => 'id query parameter is required'], 400);
        $stmt = $pdo->prepare('DELETE FROM faculty WHERE id = ?');
        $stmt->execute([$id]);
        json_response(['deleted' => $stmt->rowCount() > 0]);
        break;

    default:
        json_response(['error' => 'Method not allowed'], 405);
}

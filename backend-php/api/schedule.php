<?php
/**
 * RESTful schedule resource — academic/class scheduling.
 *   GET /api/schedule.php[?course_id=]   POST   PUT?id=   DELETE?id=
 */
require __DIR__ . '/../config.php';
require __DIR__ . '/../jwt_helper.php';

apply_cors_headers();
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($method) {
    case 'GET':
        $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : null;
        $sql = 'SELECT schedule.*, courses.title AS course_title
                FROM schedule JOIN courses ON courses.id = schedule.course_id';
        if ($courseId) {
            $stmt = $pdo->prepare("$sql WHERE schedule.course_id = ? ORDER BY day_of_week, start_time");
            $stmt->execute([$courseId]);
        } else {
            $stmt = $pdo->query("$sql ORDER BY day_of_week, start_time");
        }
        json_response(['schedule' => $stmt->fetchAll()]);
        break;

    case 'POST':
        require_auth();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        foreach (['course_id', 'day_of_week', 'start_time', 'end_time'] as $field) {
            if (empty($body[$field])) json_response(['error' => "Field '$field' is required"], 400);
        }
        $stmt = $pdo->prepare(
            'INSERT INTO schedule (course_id, day_of_week, start_time, end_time, room) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$body['course_id'], $body['day_of_week'], $body['start_time'], $body['end_time'], $body['room'] ?? null]);
        json_response(['id' => (int) $pdo->lastInsertId()], 201);
        break;

    case 'DELETE':
        $user = require_auth();
        require_role($user, ['admin', 'coordinator']);
        if (!$id) json_response(['error' => 'id query parameter is required'], 400);
        $stmt = $pdo->prepare('DELETE FROM schedule WHERE id = ?');
        $stmt->execute([$id]);
        json_response(['deleted' => $stmt->rowCount() > 0]);
        break;

    default:
        json_response(['error' => 'Method not allowed'], 405);
}

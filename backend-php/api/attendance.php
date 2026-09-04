<?php
/**
 * Attendance resource + AI risk-score proxy.
 *   GET  /api/attendance.php?student_id=5   -> raw attendance log
 *   POST /api/attendance.php                -> record one attendance mark
 *   GET  /api/attendance.php?risk=1&student_id=5
 *        -> aggregates this student's attendance, calls the Django
 *           AI microservice for a risk score, and returns both.
 *
 * This is the integration point the job posting calls out:
 * "Integrate frontend applications/dashboard with RESTful APIs
 * and backend services" — the PHP layer here is itself a client
 * of another backend service (Django), not just a server to the
 * frontend.
 */
require __DIR__ . '/../config.php';
require __DIR__ . '/../jwt_helper.php';

apply_cors_headers();
$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];

function call_ai_service(string $path, array $payload): array {
    $ch = curl_init(AI_SERVICE_BASE_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 5,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode >= 400) {
        return ['error' => 'AI service unavailable', 'detail' => $error ?: "HTTP $httpCode"];
    }
    return json_decode($response, true) ?? [];
}

switch ($method) {
    case 'GET':
        $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : null;
        if (!$studentId) {
            json_response(['error' => 'student_id query parameter is required'], 400);
        }

        if (isset($_GET['risk'])) {
            // Aggregate this student's attendance into simple stats,
            // then hand those stats to the Django AI service, which
            // owns the actual risk-scoring logic.
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) AS total, SUM(present) AS attended
                 FROM attendance WHERE student_id = ?'
            );
            $stmt->execute([$studentId]);
            $agg = $stmt->fetch();
            $total = (int) ($agg['total'] ?? 0);
            $attended = (int) ($agg['attended'] ?? 0);
            $attendanceRate = $total > 0 ? round($attended / $total, 3) : null;

            $aiResult = call_ai_service('/risk-score/', [
                'student_id' => $studentId,
                'attendance_rate' => $attendanceRate,
                'classes_logged' => $total,
            ]);

            json_response([
                'student_id' => $studentId,
                'attendance_rate' => $attendanceRate,
                'classes_logged' => $total,
                'ai_assessment' => $aiResult,
            ]);
        }

        $stmt = $pdo->prepare('SELECT * FROM attendance WHERE student_id = ? ORDER BY date DESC');
        $stmt->execute([$studentId]);
        json_response(['attendance' => $stmt->fetchAll()]);
        break;

    case 'POST':
        require_auth();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        foreach (['student_id', 'course_id', 'date', 'present'] as $field) {
            if (!isset($body[$field])) json_response(['error' => "Field '$field' is required"], 400);
        }
        $stmt = $pdo->prepare(
            'INSERT OR REPLACE INTO attendance (student_id, course_id, date, present) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$body['student_id'], $body['course_id'], $body['date'], (int) (bool) $body['present']]);
        json_response(['recorded' => true], 201);
        break;

    default:
        json_response(['error' => 'Method not allowed'], 405);
}

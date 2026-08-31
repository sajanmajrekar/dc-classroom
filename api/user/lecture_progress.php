<?php
require_once '../cors.php';
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit();
}

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$userId = 0;
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $tokenData = json_decode(base64_decode($matches[1]), true);
    $userId = (int)($tokenData['id'] ?? 0);
}
if (!$userId) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$lectureId = (int)($input['lecture_id'] ?? 0);
$completed = (bool)($input['completed'] ?? false);
if (!$lectureId) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Lecture ID is required"]);
    exit();
}

// Only learners assigned to the lecture's class can update their own progress.
$access = $pdo->prepare("SELECT 1 FROM lectures l JOIN user_classes uc ON uc.class_id = l.class_id WHERE l.id = ? AND uc.user_id = ?");
$access->execute([$lectureId, $userId]);
if (!$access->fetch()) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Forbidden"]);
    exit();
}

if ($completed) {
    $stmt = $pdo->prepare("INSERT INTO lecture_progress (user_id, lecture_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE completed_at = CURRENT_TIMESTAMP");
    $stmt->execute([$userId, $lectureId]);
} else {
    $stmt = $pdo->prepare("DELETE FROM lecture_progress WHERE user_id = ? AND lecture_id = ?");
    $stmt->execute([$userId, $lectureId]);
}

echo json_encode(["status" => "success", "completed" => $completed]);
?>

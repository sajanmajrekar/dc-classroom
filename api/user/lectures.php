<?php
require_once '../cors.php';
require_once '../config.php';
// Auth Check
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$user_id = 0;
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $tokenData = json_decode(base64_decode($matches[1]), true);
    $user_id = $tokenData['id'] ?? 0;
}
if (!$user_id) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$class_id = $_GET['class_id'] ?? 0;

// Verify user has access to this class
$stmt = $pdo->prepare("SELECT * FROM user_classes WHERE user_id = ? AND class_id = ?");
$stmt->execute([$user_id, $class_id]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Forbidden"]);
    exit();
}

$stmt = $pdo->prepare("SELECT l.*, CASE WHEN lp.lecture_id IS NULL THEN 0 ELSE 1 END AS is_completed
    FROM lectures l
    LEFT JOIN lecture_progress lp ON lp.lecture_id = l.id AND lp.user_id = ?
    WHERE l.class_id = ?
    ORDER BY l.created_at ASC, l.id ASC");
$stmt->execute([$user_id, $class_id]);
echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
?>

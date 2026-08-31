<?php
require_once '../cors.php';
require_once '../config.php';

$headers = getallheaders();
if (empty($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT uc.user_id, uc.class_id, u.name AS user_name, u.email AS user_email,
        c.title AS class_title, COUNT(DISTINCT l.id) AS total_lectures,
        COUNT(DISTINCT lp.lecture_id) AS completed_lectures
        FROM user_classes uc
        JOIN users u ON u.id = uc.user_id
        JOIN classes c ON c.id = uc.class_id
        LEFT JOIN lectures l ON l.class_id = c.id
        LEFT JOIN lecture_progress lp ON lp.lecture_id = l.id AND lp.user_id = u.id
        GROUP BY uc.user_id, uc.class_id, u.name, u.email, c.title
        ORDER BY c.title ASC, u.name ASC");
    echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
} elseif ($method === 'POST') {
    $stmt = $pdo->prepare("INSERT IGNORE INTO user_classes (user_id, class_id) VALUES (?, ?)");
    $stmt->execute([$input['user_id'], $input['class_id']]);
    echo json_encode(["status" => "success", "message" => "Class assigned successfully"]);
} elseif ($method === 'DELETE') {
    $stmt = $pdo->prepare("DELETE FROM user_classes WHERE user_id = ? AND class_id = ?");
    $stmt->execute([$input['user_id'], $input['class_id']]);
    echo json_encode(["status" => "success", "message" => "Class access removed"]);
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>

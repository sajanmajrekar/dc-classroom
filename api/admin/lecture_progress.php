<?php
require_once '../cors.php';
require_once '../config.php';

$headers = getallheaders();
if (empty($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit();
}

$stmt = $pdo->query("SELECT lp.lecture_id, lp.user_id, lp.completed_at, u.name AS user_name, u.email AS user_email
    FROM lecture_progress lp
    JOIN users u ON u.id = lp.user_id
    ORDER BY lp.completed_at DESC");
echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
?>

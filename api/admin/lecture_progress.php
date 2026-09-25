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

$stmt = $pdo->query("SELECT lrp.resource_id, lr.lecture_id, lrp.user_id, lrp.completed_at,
    lr.label AS resource_label, u.name AS user_name, u.email AS user_email
    FROM lecture_resource_progress lrp
    JOIN lecture_resources lr ON lr.id = lrp.resource_id
    JOIN users u ON u.id = lrp.user_id
    ORDER BY lrp.completed_at DESC");
echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
?>

<?php
require_once '../cors.php';
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM classes");
    echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
} elseif ($method === 'POST') {
    $title = trim($input['title'] ?? '');
    if ($title === '') {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Class title is required"]);
        exit();
    }
    $stmt = $pdo->prepare("INSERT INTO classes (title, description) VALUES (?, ?)");
    $stmt->execute([$title, trim($input['description'] ?? '')]);
    echo json_encode(["status" => "success", "message" => "Class created"]);
} elseif ($method === 'PUT') {
    $classId = (int)($input['id'] ?? 0);
    $title = trim($input['title'] ?? '');
    if (!$classId || $title === '') {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Class ID and title are required"]);
        exit();
    }
    $stmt = $pdo->prepare("UPDATE classes SET title = ?, description = ? WHERE id = ?");
    $stmt->execute([$title, trim($input['description'] ?? ''), $classId]);
    echo json_encode(["status" => "success", "message" => "Class updated"]);
} elseif ($method === 'DELETE') {
    $classId = (int)($input['id'] ?? 0);
    if (!$classId) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Class ID is required"]);
        exit();
    }
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->execute([$classId]);
    echo json_encode(["status" => "success", "message" => "Class deleted"]);
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>

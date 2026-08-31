<?php
require_once '../cors.php';
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM classes");
    echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("INSERT INTO classes (title, description) VALUES (?, ?)");
    $stmt->execute([$input['title'] ?? '', $input['description'] ?? '']);
    echo json_encode(["status" => "success", "message" => "Class created"]);
}
?>
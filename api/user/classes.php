<?php
require_once '../cors.php';
require_once '../config.php';
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$user_id = 0;
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $tokenData = json_decode(base64_decode($matches[1]), true);
    $user_id = $tokenData['id'] ?? 0;
}
if (!$user_id) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$stmt = $pdo->prepare("SELECT c.* FROM classes c JOIN user_classes uc ON c.id = uc.class_id WHERE uc.user_id = ?");
$stmt->execute([$user_id]);
echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
?>
<?php
require_once '../cors.php';
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

// A simple auth guard helper for this mock (checking if standard 'token' is passed for admin)
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
if (!$authHeader) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

try {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT id, name, email, role, is_active, created_at FROM users ORDER BY created_at DESC");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, ?)");
        $hashed = password_hash($input['password'], PASSWORD_DEFAULT);
        $stmt->execute([$input['name'], $input['email'], $hashed, $input['role'] ?? 'user', $input['is_active'] ?? 1]);
        echo json_encode(["status" => "success", "message" => "User created"]);
    } elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = (int)($input['id'] ?? 0);
        if (!$userId) {
            throw new Exception("User ID is required");
        }

        $updates = [];
        $values = [];
        foreach (['name', 'email', 'role', 'is_active'] as $field) {
            if (array_key_exists($field, $input)) {
                $updates[] = "$field = ?";
                $values[] = $input[$field];
            }
        }
        if (!empty($input['password'])) {
            $updates[] = "password = ?";
            $values[] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        if (!$updates) {
            throw new Exception("No user changes provided");
        }

        $values[] = $userId;
        $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?");
        $stmt->execute($values);
        echo json_encode(["status" => "success", "message" => "User updated"]);
    } else {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

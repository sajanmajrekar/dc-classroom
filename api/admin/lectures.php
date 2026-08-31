<?php
require_once '../cors.php';
require_once '../config.php';
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    if ($method === 'GET') {
        $class_id = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? (int) $_GET['class_id'] : 0;

        if ($class_id > 0) {
            $stmt = $pdo->prepare("
                SELECT l.*, c.title AS class_title
                FROM lectures l
                JOIN classes c ON c.id = l.class_id
                WHERE l.class_id = ?
                ORDER BY l.created_at DESC, l.id DESC
            ");
            $stmt->execute([$class_id]);
        } else {
            $stmt = $pdo->query("
                SELECT l.*, c.title AS class_title
                FROM lectures l
                JOIN classes c ON c.id = l.class_id
                ORDER BY c.title ASC, l.created_at DESC, l.id DESC
            ");
        }

        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
    } elseif ($method === 'POST') {
        $stmt = $pdo->prepare("INSERT INTO lectures (class_id, title, content, type, resource_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['class_id'],
            $input['title'],
            $input['content'] ?? '',
            $input['type'] ?? 'text',
            $input['resource_url'] ?? ''
        ]);

        echo json_encode(["status" => "success", "message" => "Lecture created"]);
    } elseif ($method === 'PUT') {
        $stmt = $pdo->prepare("
            UPDATE lectures
            SET class_id = ?, title = ?, content = ?, type = ?, resource_url = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $input['class_id'],
            $input['title'],
            $input['content'] ?? '',
            $input['type'] ?? 'text',
            $input['resource_url'] ?? '',
            $input['id']
        ]);

        echo json_encode(["status" => "success", "message" => "Lecture updated"]);
    } elseif ($method === 'DELETE') {
        $stmt = $pdo->prepare("DELETE FROM lectures WHERE id = ?");
        $stmt->execute([$input['id'] ?? 0]);
        echo json_encode(["status" => "success", "message" => "Lecture deleted"]);
    } else {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

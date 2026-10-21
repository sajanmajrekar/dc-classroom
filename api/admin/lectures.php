<?php
require_once '../cors.php';
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

function supportsLectureSortOrder($pdo) {
    static $supported = null;
    if ($supported === null) {
        $supported = (bool)$pdo->query("SHOW COLUMNS FROM lectures LIKE 'sort_order'")->fetch();
    }
    return $supported;
}

function normalizeResources($resources, $legacyUrl = '') {
    $normalized = [];
    if (is_array($resources)) {
        foreach ($resources as $resource) {
            $url = trim($resource['resource_url'] ?? '');
            if ($url !== '') {
                $normalized[] = [
                    'label' => trim($resource['label'] ?? ''),
                    'resource_url' => $url
                ];
            }
        }
    }
    if (!$normalized && trim($legacyUrl) !== '') {
        $normalized[] = ['label' => '', 'resource_url' => trim($legacyUrl)];
    }
    return $normalized;
}

function saveResources($pdo, $lectureId, $resources) {
    $delete = $pdo->prepare("DELETE FROM lecture_resources WHERE lecture_id = ?");
    $delete->execute([$lectureId]);
    if (!$resources) return;

    $insert = $pdo->prepare("INSERT INTO lecture_resources (lecture_id, label, resource_url) VALUES (?, ?, ?)");
    foreach ($resources as $resource) {
        $insert->execute([$lectureId, $resource['label'], $resource['resource_url']]);
    }
}

function addResourcesToLectures($pdo, $lectures) {
    if (!$lectures) return $lectures;

    $ids = array_map(function ($lecture) { return (int)$lecture['id']; }, $lectures);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $resourceStmt = $pdo->prepare("SELECT id, lecture_id, label, resource_url FROM lecture_resources WHERE lecture_id IN ($placeholders) ORDER BY id ASC");
    $resourceStmt->execute($ids);

    $resourcesByLecture = [];
    foreach ($resourceStmt->fetchAll() as $resource) {
        $resourcesByLecture[$resource['lecture_id']][] = $resource;
    }

    foreach ($lectures as &$lecture) {
        $lecture['resources'] = $resourcesByLecture[$lecture['id']] ?? [];
        if (!$lecture['resources'] && !empty($lecture['resource_url'])) {
            $lecture['resources'][] = [
                'id' => null,
                'lecture_id' => $lecture['id'],
                'label' => 'Lecture material',
                'resource_url' => $lecture['resource_url']
            ];
        }
    }
    unset($lecture);
    return $lectures;
}

try {
    if ($method === 'GET') {
        $classId = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? (int)$_GET['class_id'] : 0;
        $orderBy = supportsLectureSortOrder($pdo) ? 'l.sort_order ASC, l.id ASC' : 'l.created_at ASC, l.id ASC';
        if ($classId > 0) {
            $stmt = $pdo->prepare("SELECT l.*, c.title AS class_title FROM lectures l JOIN classes c ON c.id = l.class_id WHERE l.class_id = ? ORDER BY $orderBy");
            $stmt->execute([$classId]);
        } else {
            $stmt = $pdo->query("SELECT l.*, c.title AS class_title FROM lectures l JOIN classes c ON c.id = l.class_id ORDER BY c.title ASC, $orderBy");
        }
        echo json_encode(["status" => "success", "data" => addResourcesToLectures($pdo, $stmt->fetchAll())]);
    } elseif ($method === 'PUT' && ($input['action'] ?? '') === 'reorder') {
        if (!supportsLectureSortOrder($pdo)) {
            http_response_code(409);
            echo json_encode(["status" => "error", "message" => "Run migration 005_add_lecture_sort_order.sql before reordering lectures"]);
            exit();
        }

        $classId = (int)($input['class_id'] ?? 0);
        $lectureIds = array_values(array_filter($input['lecture_ids'] ?? [], 'is_numeric'));
        if (!$classId || !$lectureIds) throw new Exception("Class and lecture order are required");

        $pdo->beginTransaction();
        $check = $pdo->prepare("SELECT id FROM lectures WHERE id = ? AND class_id = ?");
        $update = $pdo->prepare("UPDATE lectures SET sort_order = ? WHERE id = ? AND class_id = ?");
        foreach ($lectureIds as $index => $lectureId) {
            $check->execute([(int)$lectureId, $classId]);
            if (!$check->fetch()) throw new Exception("Invalid lecture order");
            $update->execute([$index + 1, (int)$lectureId, $classId]);
        }
        $pdo->commit();
        echo json_encode(["status" => "success", "message" => "Lecture order saved"]);
    } elseif ($method === 'POST' || $method === 'PUT') {
        $resources = normalizeResources($input['resources'] ?? [], $input['resource_url'] ?? '');
        $primaryUrl = $resources[0]['resource_url'] ?? '';

        if ($method === 'POST') {
            $classId = (int)($input['class_id'] ?? 0);
            if (!$classId) throw new Exception("Class ID is required");
            if (supportsLectureSortOrder($pdo)) {
                $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM lectures WHERE class_id = ?");
                $orderStmt->execute([$classId]);
                $sortOrder = (int)$orderStmt->fetch()['next_order'];
                $stmt = $pdo->prepare("INSERT INTO lectures (class_id, sort_order, title, subtitle, content, type, resource_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$classId, $sortOrder, $input['title'], $input['subtitle'] ?? '', $input['content'] ?? '', $input['type'] ?? 'text', $primaryUrl]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO lectures (class_id, title, subtitle, content, type, resource_url) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$classId, $input['title'], $input['subtitle'] ?? '', $input['content'] ?? '', $input['type'] ?? 'text', $primaryUrl]);
            }
            $lectureId = (int)$pdo->lastInsertId();
        } else {
            $lectureId = (int)($input['id'] ?? 0);
            if (!$lectureId) throw new Exception("Lecture ID is required");
            $stmt = $pdo->prepare("UPDATE lectures SET class_id = ?, title = ?, subtitle = ?, content = ?, type = ?, resource_url = ? WHERE id = ?");
            $stmt->execute([$input['class_id'], $input['title'], $input['subtitle'] ?? '', $input['content'] ?? '', $input['type'] ?? 'text', $primaryUrl, $lectureId]);
        }

        saveResources($pdo, $lectureId, $resources);
        echo json_encode(["status" => "success", "message" => $method === 'POST' ? "Lecture created" : "Lecture updated"]);
    } elseif ($method === 'DELETE') {
        $stmt = $pdo->prepare("DELETE FROM lectures WHERE id = ?");
        $stmt->execute([$input['id'] ?? 0]);
        echo json_encode(["status" => "success", "message" => "Lecture deleted"]);
    } else {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>

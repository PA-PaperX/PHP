<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$db = (new Database())->getConnection();
$user = getCurrentUser();

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Type is required']);
    exit;
}

$type = $input['type'];

if ($user['role'] !== 'admin') {
    if ($type === 'all') {
        $db->prepare("UPDATE issues SET is_read = TRUE WHERE user_id = ?")->execute([$user['id']]);
        $db->prepare("UPDATE borrows SET is_read = TRUE WHERE user_id = ?")->execute([$user['id']]);
    } else {
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required']);
            exit;
        }
        $id = $input['id'];
        if ($type === 'issue') {
            $stmt = $db->prepare("UPDATE issues SET is_read = TRUE WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['id']]);
        } else if ($type === 'borrow') {
            $stmt = $db->prepare("UPDATE borrows SET is_read = TRUE WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['id']]);
        }
    }
} else {
    // Admin role
    if ($type === 'all') {
        $db->query("UPDATE issues SET is_read_admin = TRUE");
        $db->query("UPDATE borrows SET is_read_admin = TRUE");
        $db->query("UPDATE password_reset_tickets SET is_read_admin = TRUE");
    } else {
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required']);
            exit;
        }
        $id = $input['id'];
        if ($type === 'issue') {
            $stmt = $db->prepare("UPDATE issues SET is_read_admin = TRUE WHERE id = ?");
            $stmt->execute([$id]);
        } else if ($type === 'borrow') {
            $stmt = $db->prepare("UPDATE borrows SET is_read_admin = TRUE WHERE id = ?");
            $stmt->execute([$id]);
        } else if ($type === 'ticket') {
            $stmt = $db->prepare("UPDATE password_reset_tickets SET is_read_admin = TRUE WHERE id = ?");
            $stmt->execute([$id]);
        }
    }
}

echo json_encode(['success' => true]);

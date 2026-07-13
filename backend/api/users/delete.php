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

if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

if ($id === $user['id']) {
    http_response_code(400);
    echo json_encode(['error' => 'You cannot delete yourself']);
    exit;
}

// Check if trying to delete the last admin
$stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
$stmt->execute([$id]);
$targetUser = $stmt->fetch();

if ($targetUser && $targetUser['role'] === 'admin') {
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetch()['count'];
    if ($adminCount <= 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete the last admin']);
        exit;
    }
}

$stmt = $db->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$id]);

echo json_encode(['success' => true, 'message' => 'User deleted successfully']);

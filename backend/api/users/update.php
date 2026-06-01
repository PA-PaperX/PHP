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
$role = $_POST['role'] ?? null;
$password = $_POST['password'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

// Check if trying to demote the last admin
if ($role === 'user') {
    $stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $targetUser = $stmt->fetch();
    
    if ($targetUser && $targetUser['role'] === 'admin') {
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $adminCount = $stmt->fetch()['count'];
        if ($adminCount <= 1) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot demote the last admin']);
            exit;
        }
    }
}

if ($password && strlen($password) >= 6) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    if ($role && in_array($role, ['user', 'admin'])) {
        $stmt = $db->prepare('UPDATE users SET role = ?, password = ? WHERE id = ?');
        $stmt->execute([$role, $hashedPassword, $id]);
    } else {
        $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hashedPassword, $id]);
    }
} else if ($role && in_array($role, ['user', 'admin'])) {
    $stmt = $db->prepare('UPDATE users SET role = ? WHERE id = ?');
    $stmt->execute([$role, $id]);
}

echo json_encode(['success' => true, 'message' => 'User updated successfully']);

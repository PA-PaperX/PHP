<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();

$db = (new Database())->getConnection();
$user = getCurrentUser();

if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$stmt = $db->query("
    SELECT 
        u.id, 
        u.email, 
        u.role, 
        u.created_at,
        (SELECT COUNT(*) FROM issues WHERE user_id = u.id) as issues_count,
        (SELECT COUNT(*) FROM borrows WHERE user_id = u.id) as borrows_count
    FROM users u
    ORDER BY u.created_at DESC
");

$users = $stmt->fetchAll();

echo json_encode(['users' => $users]);

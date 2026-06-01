<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();

$db = (new Database())->getConnection();
$user = getCurrentUser();

if ($user['role'] === 'admin') {
    $stmt = $db->query('SELECT i.*, COALESCE(u.email, \'[ผู้ใช้ถูกลบ]\') as user_email FROM issues i LEFT JOIN users u ON i.user_id = u.id ORDER BY i.created_at DESC');
} else {
    $stmt = $db->prepare('SELECT * FROM issues WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user['id']]);
}

echo json_encode(['issues' => $stmt->fetchAll()]);

<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();

$db = (new Database())->getConnection();
$user = getCurrentUser();

if ($user['role'] === 'admin') {
    $stmt = $db->query('SELECT b.*, COALESCE(u.email, \'[ผู้ใช้ถูกลบ]\') as user_email, e.name as equipment_name FROM borrows b LEFT JOIN users u ON b.user_id = u.id JOIN equipment e ON b.equipment_id = e.id ORDER BY b.created_at DESC');
} else {
    $stmt = $db->prepare('SELECT b.*, e.name as equipment_name FROM borrows b JOIN equipment e ON b.equipment_id = e.id WHERE b.user_id = ? ORDER BY b.created_at DESC');
    $stmt->execute([$user['id']]);
}

echo json_encode(['borrows' => $stmt->fetchAll()]);

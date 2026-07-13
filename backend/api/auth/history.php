<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();

$user = getCurrentUser();
$db = (new Database())->getConnection();

// Fetch Issues
$stmt = $db->prepare("SELECT id, title, category, status, created_at, 'issue' as type FROM issues WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$issues = $stmt->fetchAll();

// Fetch Borrows
$stmt = $db->prepare("SELECT b.id, e.name as title, b.quantity, b.status, b.created_at, b.return_date, 'borrow' as type FROM borrows b JOIN equipment e ON b.equipment_id = e.id WHERE b.user_id = ? ORDER BY b.created_at DESC");
$stmt->execute([$user['id']]);
$borrows = $stmt->fetchAll();

$history = array_merge($issues, $borrows);
usort($history, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

echo json_encode(['history' => $history]);

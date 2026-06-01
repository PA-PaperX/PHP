<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();

$db = (new Database())->getConnection();
$user = getCurrentUser();

$notifications = [];
$count = 0;

if ($user['role'] === 'admin') {
    $stmt = $db->query("SELECT id, title, created_at, 'issue' as type FROM issues WHERE status = 'pending' AND is_read_admin = FALSE ORDER BY created_at DESC");
    $pendingIssues = $stmt->fetchAll();
    
    $stmt = $db->query("SELECT b.id, e.name as title, b.created_at, 'borrow' as type FROM borrows b JOIN equipment e ON b.equipment_id = e.id WHERE b.status = 'pending' AND b.is_read_admin = FALSE ORDER BY b.created_at DESC");
    $pendingBorrows = $stmt->fetchAll();
    
    $stmt = $db->query("SELECT id, CONCAT('คำขอรีเซ็ต: ', email) as title, created_at, 'ticket' as type FROM password_reset_tickets WHERE status = 'pending' AND is_read_admin = FALSE ORDER BY created_at DESC");
    $pendingTickets = $stmt->fetchAll();
    
    $notifications = array_merge($pendingIssues, $pendingBorrows, $pendingTickets);
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    $count = count($notifications);
} else {
    $stmt = $db->prepare("SELECT id, title, status, created_at, 'issue' as type FROM issues WHERE user_id = ? AND status IN ('resolved', 'closed', 'cancelled') AND is_read = FALSE ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $userIssues = $stmt->fetchAll();
    
    $stmt = $db->prepare("SELECT b.id, e.name as title, b.status, b.created_at, 'borrow' as type FROM borrows b JOIN equipment e ON b.equipment_id = e.id WHERE b.user_id = ? AND b.status IN ('approved', 'rejected') AND b.is_read = FALSE ORDER BY b.created_at DESC");
    $stmt->execute([$user['id']]);
    $userBorrows = $stmt->fetchAll();
    
    $notifications = array_merge($userIssues, $userBorrows);
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    $count = count($notifications);
}

echo json_encode([
    'count' => $count,
    'notifications' => $notifications
]);

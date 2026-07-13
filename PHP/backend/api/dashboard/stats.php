<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();

$db = (new Database())->getConnection();
$user = getCurrentUser();

if ($user['role'] === 'admin') {
    $stats = [];
    
    // Issue counts
    $stmt = $db->query('SELECT status, COUNT(*) as count FROM issues GROUP BY status');
    $issueCounts = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
    while ($row = $stmt->fetch()) {
        $issueCounts[$row['status']] = (int)$row['count'];
        $issueCounts['total'] += (int)$row['count'];
    }
    $stats['issues'] = $issueCounts;
    
    // Borrow counts
    $stmt = $db->query('SELECT status, COUNT(*) as count FROM borrows GROUP BY status');
    $borrowCounts = ['total' => 0, 'pending' => 0, 'approved' => 0, 'returned' => 0, 'rejected' => 0];
    while ($row = $stmt->fetch()) {
        $borrowCounts[$row['status']] = (int)$row['count'];
        $borrowCounts['total'] += (int)$row['count'];
    }
    $stats['borrows'] = $borrowCounts;
    
    // Equipment count
    $stmt = $db->query('SELECT COUNT(*) as count FROM equipment');
    $stats['equipment_count'] = (int)$stmt->fetch()['count'];
    
    // User count
    $stmt = $db->query('SELECT COUNT(*) as count FROM users');
    $stats['user_count'] = (int)$stmt->fetch()['count'];
    
    // Recent issues
    $stmt = $db->query('SELECT i.*, COALESCE(u.email, \'[ผู้ใช้ถูกลบ]\') as user_email FROM issues i LEFT JOIN users u ON i.user_id = u.id ORDER BY i.created_at DESC LIMIT 10');
    $stats['recent_issues'] = $stmt->fetchAll();
    
    // Chart 1: Issues Trend (Last 7 Days)
    // Create an array of the last 7 dates
    $trend = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $trend[$date] = 0;
    }
    
    $stmt = $db->query('SELECT DATE(created_at) as date, COUNT(*) as count FROM issues WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at)');
    while ($row = $stmt->fetch()) {
        $date = $row['date'];
        if (isset($trend[$date])) {
            $trend[$date] = (int)$row['count'];
        }
    }
    $stats['issues_trend'] = [
        'labels' => array_keys($trend),
        'data' => array_values($trend)
    ];

    // Chart 2: Borrows By Category
    $stmt = $db->query('SELECT e.category, COUNT(*) as count FROM borrows b JOIN equipment e ON b.equipment_id = e.id GROUP BY e.category');
    $categories = [];
    $categoryData = [];
    while ($row = $stmt->fetch()) {
        $category = $row['category'] ?: 'อื่นๆ';
        $categories[] = $category;
        $categoryData[] = (int)$row['count'];
    }
    
    // If no borrow data, provide empty structure for the chart
    if (empty($categories)) {
        $categories = ['ไม่มีข้อมูล'];
        $categoryData = [1]; 
        $stats['borrows_by_category'] = ['labels' => $categories, 'data' => $categoryData, 'empty' => true];
    } else {
        $stats['borrows_by_category'] = ['labels' => $categories, 'data' => $categoryData, 'empty' => false];
    }

    echo json_encode($stats);
} else {
    // Non-admin stats (unchanged for now)
    $stats = [];
    
    $stmt = $db->prepare('SELECT status, COUNT(*) as count FROM issues WHERE user_id = ? GROUP BY status');
    $stmt->execute([$user['id']]);
    $issueCounts = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
    while ($row = $stmt->fetch()) {
        $issueCounts[$row['status']] = (int)$row['count'];
        $issueCounts['total'] += (int)$row['count'];
    }
    $stats['issues'] = $issueCounts;
    
    $stmt = $db->prepare('SELECT status, COUNT(*) as count FROM borrows WHERE user_id = ? GROUP BY status');
    $stmt->execute([$user['id']]);
    $borrowCounts = ['total' => 0, 'pending' => 0, 'approved' => 0, 'returned' => 0, 'rejected' => 0];
    while ($row = $stmt->fetch()) {
        $borrowCounts[$row['status']] = (int)$row['count'];
        $borrowCounts['total'] += (int)$row['count'];
    }
    $stats['borrows'] = $borrowCounts;
    
    echo json_encode($stats);
}

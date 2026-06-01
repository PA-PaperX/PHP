<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth(); // Must be logged in

if ($_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$db = (new Database())->getConnection();

// Fetch all active tickets or a list of recent ones
$stmt = $db->query("SELECT * FROM password_reset_tickets WHERE status = 'pending' ORDER BY created_at DESC LIMIT 50");
$tickets = $stmt->fetchAll();

echo json_encode(['tickets' => $tickets]);

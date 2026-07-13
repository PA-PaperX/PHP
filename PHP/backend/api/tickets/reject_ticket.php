<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();

if ($_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$ticketId = $data['ticket_id'] ?? null;
$reason = $data['reason'] ?? 'ไม่ระบุเหตุผล';

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing ticket_id']);
    exit;
}

$db = (new Database())->getConnection();

// Check if ticket exists and is open
$stmt = $db->prepare("SELECT * FROM password_reset_tickets WHERE id = ?");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    http_response_code(404);
    echo json_encode(['error' => 'Ticket not found']);
    exit;
}
if ($ticket['status'] !== 'pending') {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket is already closed']);
    exit;
}

// Update ticket status to cancelled
$updateStmt = $db->prepare("UPDATE password_reset_tickets SET status = 'cancelled' WHERE id = ?");
$updateStmt->execute([$ticketId]);

// Insert system message for the user
$msg = "Admin ได้ทำการปฏิเสธคำขอของคุณแล้ว (เหตุผล: " . trim($reason) . ")";
$msgStmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (?, 'system', ?)");
$msgStmt->execute([$ticketId, $msg]);

echo json_encode([
    'success' => true,
    'message' => 'Ticket cancelled successfully'
]);

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
if ($ticket['status'] === 'resolved') {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket is already resolved']);
    exit;
}

// Generate secure reset token
$resetToken = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Update ticket
$updateStmt = $db->prepare("UPDATE password_reset_tickets SET reset_token = ?, reset_token_expires_at = ?, status = 'resolved' WHERE id = ?");
$updateStmt->execute([$resetToken, $expiresAt, $ticketId]);

// Insert system message for the user
$msg = "Admin ได้ทำการอนุมัติคำขอรีเซ็ตรหัสผ่านของคุณแล้ว กรุณากดปุ่มเพื่อรีเซ็ตรหัสผ่าน (ลิงก์นี้จะหมดอายุภายใน 5 นาที)";
$msgStmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (?, 'system', ?)");
$msgStmt->execute([$ticketId, $msg]);

echo json_encode([
    'success' => true,
    'message' => 'Password reset granted'
]);

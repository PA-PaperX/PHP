<?php
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$resetToken = $data['token'] ?? null;
$newPassword = $data['password'] ?? null;

if (!$resetToken || !$newPassword) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing token or password']);
    exit;
}

if (strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร']);
    exit;
}

$db = (new Database())->getConnection();

// Find the ticket by token
$stmt = $db->prepare("SELECT * FROM password_reset_tickets WHERE reset_token = ?");
$stmt->execute([$resetToken]);
$ticket = $stmt->fetch();

if (!$ticket) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid reset token']);
    exit;
}

// Check expiry
if (strtotime($ticket['reset_token_expires_at']) < time()) {
    // Expired
    http_response_code(400);
    echo json_encode(['error' => 'เซสชันการรีเซ็ตรหัสผ่านหมดอายุแล้ว กรุณาเริ่มกระบวนการใหม่']);
    exit;
}

// Token is valid. Get the user by email from the ticket
$email = $ticket['email'];
$userStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$userStmt->execute([$email]);
$user = $userStmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User account no longer exists']);
    exit;
}

// Hash and update password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$updateUserStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
$updateUserStmt->execute([$hashedPassword, $user['id']]);

// Delete the ticket to invalidate the token completely
$deleteTicketStmt = $db->prepare("DELETE FROM password_reset_tickets WHERE id = ?");
$deleteTicketStmt->execute([$ticket['id']]);

echo json_encode([
    'success' => true,
    'message' => 'Password reset successfully'
]);

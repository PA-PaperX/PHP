<?php
require_once __DIR__ . '/../../middleware/cors.php';
session_start();
require_once __DIR__ . '/../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'กรุณากรอกอีเมล']);
    exit;
}

$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

$db = (new Database())->getConnection();

// Check if user exists
$stmt = $db->prepare('SELECT id, username FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'ไม่พบบัญชีที่ใช้อีเมลนี้']);
    exit;
}

// Generate random secure access token
$accessToken = bin2hex(random_bytes(32));

$stmt = $db->prepare("INSERT INTO password_reset_tickets (email, access_token) VALUES (?, ?)");
$stmt->execute([$email, $accessToken]);
$ticketId = $db->lastInsertId();

// Create initial system message
$msgStmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (?, 'system', 'Ticket created. Please wait for an admin to assist you.')");
$msgStmt->execute([$ticketId]);

require_once __DIR__ . '/../../utils/discord.php';
$options = [
    'event' => 'ticket',
    'author_name' => '🎫 Password Reset',
    'title' => "Ticket #{$ticketId} • " . $email,
    'ticket_id' => $ticketId,
    'color' => '#8B5CF6',
    'description' => "📌 **รายละเอียด:**\n> มีผู้ใช้ลืมรหัสผ่านและต้องการรีเซ็ตใหม่",
    'fields' => [
        ['name' => 'อีเมล', 'value' => $email, 'inline' => true]
    ]
];
sendDiscordWebhook('ticket', $options);

echo json_encode([
    'ticket_id' => $ticketId,
    'access_token' => $accessToken,
    'message' => 'Ticket created successfully'
]);

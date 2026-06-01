<?php
require_once __DIR__ . '/../../middleware/cors.php';
session_start();
require_once __DIR__ . '/../../config/database.php';

$db = (new Database())->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$ticketId = $_GET['ticket_id'] ?? null;
$accessToken = $_GET['token'] ?? null;

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing ticket_id']);
    exit;
}

// Check authorization (either Admin via session, or User via access_token)
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$isAuthorized = false;
$ticket = null;

if ($isAdmin) {
    $stmt = $db->prepare("SELECT * FROM password_reset_tickets WHERE id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    if ($ticket) $isAuthorized = true;
} else if ($accessToken) {
    $stmt = $db->prepare("SELECT * FROM password_reset_tickets WHERE id = ? AND access_token = ?");
    $stmt->execute([$ticketId, $accessToken]);
    $ticket = $stmt->fetch();
    if ($ticket) $isAuthorized = true;
}

if (!$isAuthorized || !$ticket) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized or Ticket not found']);
    exit;
}

if ($method === 'GET') {
    // Polling messages
    $lastId = $_GET['last_id'] ?? 0;
    
    $stmt = $db->prepare("SELECT * FROM ticket_messages WHERE ticket_id = ? AND id > ? ORDER BY id ASC");
    $stmt->execute([$ticketId, $lastId]);
    $messages = $stmt->fetchAll();
    
    // Also return ticket status in case it was resolved
    echo json_encode([
        'messages' => $messages,
        'ticket_status' => $ticket['status'],
        'reset_token' => $ticket['reset_token']
    ]);
    
} else if ($method === 'POST') {
    // Sending message
    $data = json_decode(file_get_contents('php://input'), true);
    $message = $data['message'] ?? '';
    
    if (empty(trim($message))) {
        http_response_code(400);
        echo json_encode(['error' => 'Message cannot be empty']);
        exit;
    }
    
    $senderType = $isAdmin ? 'admin' : 'user';
    
    $stmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, message) VALUES (?, ?, ?)");
    $stmt->execute([$ticketId, $senderType, trim($message)]);
    
    $msgId = $db->lastInsertId();
    
    echo json_encode([
        'id' => $msgId,
        'ticket_id' => $ticketId,
        'sender_type' => $senderType,
        'message' => trim($message),
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

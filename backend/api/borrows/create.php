<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();

$data = json_decode(file_get_contents('php://input'), true);
$user = getCurrentUser();

$equipmentId = $data['equipment_id'] ?? null;
$quantity = (int)($data['quantity'] ?? 1);
$borrowDate = $data['borrow_date'] ?? date('Y-m-d');
$returnDate = $data['return_date'] ?? null;
$reason = $data['reason'] ?? '';

if (!$equipmentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Equipment ID is required']);
    exit;
}

if (!$returnDate) {
    http_response_code(400);
    echo json_encode(['error' => 'Return date is required']);
    exit;
}

$borrowTimestamp = strtotime($borrowDate);
$returnTimestamp = strtotime($returnDate);
$diffDays = ($returnTimestamp - $borrowTimestamp) / (60 * 60 * 24);

if ($diffDays < 0 || $diffDays > 7) {
    http_response_code(400);
    echo json_encode(['error' => 'Return date must be within 7 days from borrow date']);
    exit;
}

$db = (new Database())->getConnection();

// Check availability
$stmt = $db->prepare('SELECT available FROM equipment WHERE id = ?');
$stmt->execute([$equipmentId]);
$equipment = $stmt->fetch();

if (!$equipment) {
    http_response_code(404);
    echo json_encode(['error' => 'Equipment not found']);
    exit;
}

if ($equipment['available'] < $quantity) {
    http_response_code(400);
    echo json_encode(['error' => 'Not enough available equipment']);
    exit;
}

require_once __DIR__ . '/../../utils/discord.php';

// Create borrow request
$stmt = $db->prepare('INSERT INTO borrows (user_id, equipment_id, quantity, borrow_date, return_date, reason) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$user['id'], $equipmentId, $quantity, $borrowDate, $returnDate, $reason]);
$borrowId = (int)$db->lastInsertId();

// Decrease available count
$stmt = $db->prepare('UPDATE equipment SET available = available - ? WHERE id = ?');
$stmt->execute([$quantity, $equipmentId]);

// Send Discord Webhook
$webhookTitle = "📦 คำขอยืมอุปกรณ์ใหม่ (Borrow #{$borrowId})";
$equipName = $equipment['name'] ?? "อุปกรณ์ ID: {$equipmentId}";
$desc = "💻 **อุปกรณ์:** {$equipName}\n🔢 **จำนวน:** {$quantity} ชิ้น\n📅 **กำหนดคืน:** {$returnDate}\n❓ **เหตุผล:** {$reason}";
$bannerGif = "https://media1.tenor.com/m/kF0-Zw6qaBkAAAAC/itachi-uchiha.gif"; // Itachi Uchiha banner
sendDiscordWebhook($webhookTitle, $desc, [
    ["name" => "👤 ผู้แจ้ง", "value" => $user['email'], "inline" => true]
], "#3B82F6", $bannerGif, 'borrow'); // Blue color

http_response_code(201);
echo json_encode(['message' => 'Borrow request created successfully', 'id' => $borrowId]);

<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();

$data = json_decode(file_get_contents('php://input'), true);
$user = getCurrentUser();

$equipmentId = $data['equipment_id'] ?? null;
$quantity = (int)($data['quantity'] ?? 1);
$borrowDate = $data['borrow_date'] ?? date('Y-m-d'); // วันที่ต้องการมารับ
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

$today = date('Y-m-d');
$todayTs = strtotime($today);
$borrowTs = strtotime($borrowDate);
$returnTs = strtotime($returnDate);

// ห้ามจองย้อนหลัง
if ($borrowTs < $todayTs) {
    http_response_code(400);
    echo json_encode(['error' => 'วันที่มารับไม่สามารถเป็นวันในอดีตได้']);
    exit;
}

// จองล่วงหน้าได้สูงสุด 30 วัน
$maxPickupTs = strtotime('+30 days', $todayTs);
if ($borrowTs > $maxPickupTs) {
    http_response_code(400);
    echo json_encode(['error' => 'วันที่มารับล่วงหน้าได้ไม่เกิน 30 วัน']);
    exit;
}

// วันคืนต้องอยู่หลังวันที่มารับ และไม่เกิน 7 วันนับจากวันมารับ
$diffDays = ($returnTs - $borrowTs) / (60 * 60 * 24);
if ($diffDays < 0 || $diffDays > 7) {
    http_response_code(400);
    echo json_encode(['error' => 'วันคืนต้องอยู่ภายใน 7 วันนับจากวันที่มารับ']);
    exit;
}

$db = (new Database())->getConnection();

// Check availability
$stmt = $db->prepare('SELECT * FROM equipment WHERE id = ?');
$stmt->execute([$equipmentId]);
$equipment = $stmt->fetch();

if (!$equipment) {
    http_response_code(404);
    echo json_encode(['error' => 'Equipment not found']);
    exit;
}

// ตรวจสต็อกว่าพอไหม (available = คงเหลือจริงหลังหักของที่ approve แล้ว)
if ($equipment['available'] < $quantity) {
    http_response_code(400);
    echo json_encode(['error' => "อุปกรณ์ไม่เพียงพอ (เหลือ {$equipment['available']} ชิ้น)"]);
    exit;
}

require_once __DIR__ . '/../../utils/discord.php';

// สร้างคำขอยืม — ยังไม่หักสต็อก รอ admin อนุมัติก่อน
$stmt = $db->prepare('INSERT INTO borrows (user_id, equipment_id, quantity, borrow_date, return_date, reason) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$user['id'], $equipmentId, $quantity, $borrowDate, $returnDate, $reason]);
$borrowId = (int)$db->lastInsertId();

// Discord Webhook
$isAdvance = $borrowDate > $today;
$advanceLabel = $isAdvance ? " 📅 (จองล่วงหน้า)" : "";
$webhookTitle = "📦 คำขอยืมอุปกรณ์ใหม่ (Borrow #{$borrowId}){$advanceLabel}";

// Format dates for display
$tz = new DateTimeZone('Asia/Bangkok');
$borrowDateObj = new DateTime($borrowDate, $tz);
$returnDateObj = new DateTime($returnDate, $tz);
$borrowDateFormatted = $borrowDateObj->format('d/m/Y');
$returnDateFormatted = $returnDateObj->format('d/m/Y');

$maxLen = 150;
$truncatedReason = mb_strlen($reason) > $maxLen ? mb_substr($reason, 0, $maxLen) . '... *<อ่านต่อในระบบ>*' : $reason;
$desc = "📌 **รายละเอียด:**\n> " . str_replace("\n", "\n> ", $truncatedReason);

$options = [
    'event' => 'borrow',
    'author_name' => '📦 New Borrow Request',
    'title' => "Borrow #{$borrowId}{$advanceLabel} • " . ($equipment['name'] ?? "ID: {$equipmentId}"),
    'ticket_id' => $borrowId,
    'color' => '#8E44AD',
    'description' => $desc,
    'fields' => [
        ['name' => 'อุปกรณ์', 'value' => $equipment['name'] ?? "ID: {$equipmentId}", 'inline' => true],
        ['name' => 'จำนวน', 'value' => (string)$quantity . ' ชิ้น', 'inline' => true],
        ['name' => 'ผู้ยืม', 'value' => $user['email'], 'inline' => true],
        ['name' => 'วันที่ยืม', 'value' => $borrowDateFormatted, 'inline' => true],
        ['name' => 'วันที่คืน', 'value' => $returnDateFormatted, 'inline' => true]
    ]
];

sendDiscordWebhook('borrow', $options);

http_response_code(201);
echo json_encode(['message' => 'Borrow request created successfully', 'id' => $borrowId]);

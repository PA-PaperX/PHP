<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();
$currentUser = getCurrentUser();
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? ($_POST['id'] ?? null);
$status = $data['status'] ?? ($_POST['status'] ?? null);

if (!$id || !$status) {
    http_response_code(400);
    echo json_encode(['error' => 'ID and status are required']);
    exit;
}

$validStatuses = ['pending', 'approved', 'pending_return', 'returned', 'rejected'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status']);
    exit;
}

$db = (new Database())->getConnection();

// User can only set pending_return on their own approved borrows
if ($currentUser['role'] !== 'admin') {
    if ($status !== 'pending_return') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
    $stmt = $db->prepare('SELECT * FROM borrows WHERE id = ? AND user_id = ? AND status = "approved"');
    $stmt->execute([$id, $currentUser['id']]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid borrow request']);
        exit;
    }
    $stmt = $db->prepare('UPDATE borrows SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);
    echo json_encode(['message' => 'Return request submitted']);
    exit;
}

$admin_note = $data['admin_note'] ?? ($_POST['admin_note'] ?? null);

// Fetch the borrow record first
$stmt = $db->prepare('SELECT b.*, e.available as equip_available FROM borrows b JOIN equipment e ON b.equipment_id = e.id WHERE b.id = ?');
$stmt->execute([$id]);
$borrow = $stmt->fetch();

if (!$borrow) {
    http_response_code(404);
    echo json_encode(['error' => 'Borrow record not found']);
    exit;
}

$prevStatus = $borrow['status'];

// Admin actions with smart stock management
if ($status === 'approved' && $prevStatus === 'pending') {
    // หักสต็อกตอน Admin อนุมัติ (ไม่ใช่ตอนส่งคำขอ)
    if ($borrow['equip_available'] < $borrow['quantity']) {
        http_response_code(400);
        echo json_encode(['error' => 'สต็อกไม่เพียงพอ ไม่สามารถอนุมัติได้']);
        exit;
    }
    $stmt = $db->prepare('UPDATE equipment SET available = available - ? WHERE id = ?');
    $stmt->execute([$borrow['quantity'], $borrow['equipment_id']]);
    $stmt = $db->prepare('UPDATE borrows SET status = ?, admin_note = ? WHERE id = ?');
    $stmt->execute([$status, $admin_note, $id]);

} elseif ($status === 'returned') {
    // คืนสต็อกเมื่อรับคืนอุปกรณ์แล้ว
    $stmt = $db->prepare('UPDATE equipment SET available = available + ? WHERE id = ?');
    $stmt->execute([$borrow['quantity'], $borrow['equipment_id']]);
    $stmt = $db->prepare('UPDATE borrows SET status = ?, actual_return_date = CURDATE(), admin_note = ? WHERE id = ?');
    $stmt->execute([$status, $admin_note, $id]);

} elseif ($status === 'rejected') {
    // คืนสต็อกเสมอตอน reject (ทั้ง pending และ approved)
    // เพราะถ้า approved → หักไปแล้ว ต้องคืน
    // ถ้า pending (new flow) → ยังไม่ได้หัก ไม่มีผล
    // ถ้า pending (old flow) → หักไปแล้วตอนสร้าง ต้องคืน
    if (in_array($prevStatus, ['approved', 'pending'])) {
        $stmt = $db->prepare('UPDATE equipment SET available = available + ? WHERE id = ?');
        $stmt->execute([$borrow['quantity'], $borrow['equipment_id']]);
    }
    $stmt = $db->prepare('UPDATE borrows SET status = ?, admin_note = ? WHERE id = ?');
    $stmt->execute([$status, $admin_note, $id]);

} else {
    $stmt = $db->prepare('UPDATE borrows SET status = ?, admin_note = ? WHERE id = ?');
    $stmt->execute([$status, $admin_note, $id]);
}

echo json_encode(['message' => 'Borrow status updated successfully']);

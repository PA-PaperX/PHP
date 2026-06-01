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

// Admin actions
if ($status === 'returned') {
    $stmt = $db->prepare('SELECT equipment_id, quantity FROM borrows WHERE id = ?');
    $stmt->execute([$id]);
    $borrow = $stmt->fetch();
    if ($borrow) {
        $stmt = $db->prepare('UPDATE equipment SET available = available + ? WHERE id = ?');
        $stmt->execute([$borrow['quantity'], $borrow['equipment_id']]);
    }
    $stmt = $db->prepare('UPDATE borrows SET status = ?, actual_return_date = CURDATE(), admin_note = ? WHERE id = ?');
    $stmt->execute([$status, $admin_note, $id]);
} elseif ($status === 'rejected') {
    $stmt = $db->prepare('SELECT equipment_id, quantity FROM borrows WHERE id = ? AND status = "pending"');
    $stmt->execute([$id]);
    $borrow = $stmt->fetch();
    if ($borrow) {
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

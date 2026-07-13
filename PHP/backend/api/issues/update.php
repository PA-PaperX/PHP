<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;
$adminNote = $_POST['admin_note'] ?? null;
$paymentStatus = $_POST['payment_status'] ?? null;

if (!$id || !$status) {
    http_response_code(400);
    echo json_encode(['error' => 'ID and status are required']);
    exit;
}

$validStatuses = ['pending', 'in_progress', 'resolved', 'closed'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status']);
    exit;
}

$validPaymentStatuses = ['unpaid', 'paid'];
if ($paymentStatus !== null && !in_array($paymentStatus, $validPaymentStatuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payment status']);
    exit;
}

$adminImagePath = null;
if (isset($_FILES['admin_image']) && $_FILES['admin_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $ext = pathinfo($_FILES['admin_image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('admin_issue_') . '.' . $ext;
    
    if (move_uploaded_file($_FILES['admin_image']['tmp_name'], $uploadDir . $filename)) {
        $adminImagePath = '/uploads/' . $filename;
    }
}

$user = getCurrentUser();
$db = (new Database())->getConnection();

// Fetch existing issue to check status change
$stmt = $db->prepare('SELECT issues.*, users.email as user_email FROM issues JOIN users ON issues.user_id = users.id WHERE issues.id = ?');
$stmt->execute([$id]);
$issue = $stmt->fetch();

if (!$issue) {
    http_response_code(404);
    echo json_encode(['error' => 'Issue not found']);
    exit;
}

$oldStatus = $issue['status'];
$oldPaymentStatus = $issue['payment_status'] ?? 'unpaid';
$newPaymentStatus = $paymentStatus ?? $oldPaymentStatus;

// Removed payment required block to allow Admin to accept before payment is made.

if ($adminImagePath) {
    $stmt = $db->prepare("
        UPDATE issues
        SET status = ?, admin_note = ?, admin_image_path = ?, admin_id = ?, payment_status = ?,
            paid_at = CASE
                WHEN ? = 'paid' AND (paid_at IS NULL OR payment_status <> 'paid') THEN NOW()
                WHEN ? = 'unpaid' THEN NULL
                ELSE paid_at
            END
        WHERE id = ?
    ");
    $stmt->execute([$status, $adminNote, $adminImagePath, $user['id'], $newPaymentStatus, $newPaymentStatus, $newPaymentStatus, $id]);
} else {
    $stmt = $db->prepare("
        UPDATE issues
        SET status = ?, admin_note = ?, admin_id = ?, payment_status = ?,
            paid_at = CASE
                WHEN ? = 'paid' AND (paid_at IS NULL OR payment_status <> 'paid') THEN NOW()
                WHEN ? = 'unpaid' THEN NULL
                ELSE paid_at
            END
        WHERE id = ?
    ");
    $stmt->execute([$status, $adminNote, $user['id'], $newPaymentStatus, $newPaymentStatus, $newPaymentStatus, $id]);
}

if ($oldStatus !== $status) {
    require_once __DIR__ . '/../../utils/discord.php';
    
    // Set Thai timezone for time calculations
    $tz = new DateTimeZone('Asia/Bangkok');
    $now = new DateTime('now', $tz);
    $openedAt = new DateTime($issue['created_at']);
    $openedAt->setTimezone($tz);
    
    // Calculate duration
    $duration = ($diff->d > 0 ? $diff->d . ' วัน ' : '') . ($diff->h > 0 ? $diff->h . ' ชม. ' : '') . $diff->i . ' นาที';
    
    $maxLen = 150;
    $adminNoteStr = $adminNote ?? 'ไม่มีหมายเหตุเพิ่มเติม';
    $truncatedNote = mb_strlen($adminNoteStr) > $maxLen ? mb_substr($adminNoteStr, 0, $maxLen) . '... *<อ่านต่อในระบบ>*' : $adminNoteStr;
    $desc = "📌 **รายละเอียด:**\n> " . str_replace("\n", "\n> ", $truncatedNote);
    
    if ($status === 'in_progress') {
        $options = [
            'event' => 'assigned',
            'author_name' => '🛠 Assigned (รับเรื่อง)',
            'title' => "Issue #{$id} • " . $issue['title'],
            'ticket_id' => $id,
            'color' => '#3498DB',
            'description' => $desc,
            'fields' => [
                ['name' => 'ผู้รับผิดชอบ', 'value' => $user['email'], 'inline' => true],
                ['name' => 'เวลารับเรื่อง', 'value' => $now->format('H:i'), 'inline' => true],
                ['name' => 'ประเมินเวลา (ETA)', 'value' => '30 นาที', 'inline' => true]
            ]
        ];
        sendDiscordWebhook('accept', $options);
    } 
    else if ($status === 'resolved') {
        $options = [
            'event' => 'resolved',
            'author_name' => '✅ Resolved (ปิดงาน)',
            'title' => "Issue #{$id} • " . $issue['title'],
            'ticket_id' => $id,
            'color' => '#2ECC71',
            'description' => $desc,
            'fields' => [
                ['name' => 'ผู้ปิดงาน', 'value' => $user['email'], 'inline' => true],
                ['name' => 'เวลาเปิดงาน', 'value' => $openedAt->format('H:i'), 'inline' => true],
                ['name' => 'เวลาปิดงาน', 'value' => $now->format('H:i'), 'inline' => true],
                ['name' => 'เวลาที่ใช้', 'value' => $duration, 'inline' => true]
            ]
        ];
        sendDiscordWebhook('resolve', $options);
    }
    else if ($status === 'closed') {
        $options = [
            'event' => 'cancelled',
            'author_name' => '❌ Cancelled (ยกเลิก)',
            'title' => "Issue #{$id} • " . $issue['title'],
            'ticket_id' => $id,
            'color' => '#E74C3C',
            'description' => $desc,
            'fields' => [
                ['name' => 'ผู้ยกเลิก', 'value' => $user['email'], 'inline' => true]
            ]
        ];
        sendDiscordWebhook('resolve', $options);
    }
}

echo json_encode(['message' => 'Issue updated successfully']);

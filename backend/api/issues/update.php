<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;
$adminNote = $_POST['admin_note'] ?? null;

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

if ($adminImagePath) {
    $stmt = $db->prepare('UPDATE issues SET status = ?, admin_note = ?, admin_image_path = ?, admin_id = ? WHERE id = ?');
    $stmt->execute([$status, $adminNote, $adminImagePath, $user['id'], $id]);
} else {
    $stmt = $db->prepare('UPDATE issues SET status = ?, admin_note = ?, admin_id = ? WHERE id = ?');
    $stmt->execute([$status, $adminNote, $user['id'], $id]);
}

if ($oldStatus !== $status) {
    require_once __DIR__ . '/../../utils/discord.php';
    
    if ($status === 'in_progress') {
        $webhookTitle = "🛠️ แอดมินรับเรื่องแล้ว (Issue #{$id})";
        $desc = "ปัญหา **{$issue['title']}** กำลังถูกดำเนินการแก้ไข\n👨‍🔧 **รับเรื่องโดย:** {$user['email']}";
        if ($adminNote) $desc .= "\n📝 **ข้อความจากแอดมิน:** {$adminNote}";
        sendDiscordWebhook($webhookTitle, $desc, [], "#3B82F6", null, 'accept'); // Blue
    } 
    else if ($status === 'resolved') {
        $webhookTitle = "✅ ปิดงาน: แก้ไขเสร็จสิ้น (Issue #{$id})";
        $desc = "ปัญหา **{$issue['title']}** ได้รับการแก้ไขเรียบร้อยแล้ว\n👨‍🔧 **ปิดงานโดย:** {$user['email']}";
        if ($adminNote) $desc .= "\n📝 **รายละเอียดการแก้ไข:** {$adminNote}";
        sendDiscordWebhook($webhookTitle, $desc, [], "#10B981", null, 'resolve'); // Green
    }
    else if ($status === 'closed') {
        $webhookTitle = "❌ ปิดงาน: ยกเลิกการแจ้ง (Issue #{$id})";
        $desc = "ปัญหา **{$issue['title']}** ถูกยกเลิก\n👨‍🔧 **ยกเลิกโดย:** {$user['email']}";
        if ($adminNote) $desc .= "\n📝 **เหตุผลที่ยกเลิก:** {$adminNote}";
        sendDiscordWebhook($webhookTitle, $desc, [], "#EF4444", null, 'resolve'); // Red
    }
}

echo json_encode(['message' => 'Issue updated successfully']);

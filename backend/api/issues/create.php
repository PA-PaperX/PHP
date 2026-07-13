<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();

$user = getCurrentUser();
$title = $_POST['title'] ?? null;
$category = $_POST['category'] ?? null;
$description = $_POST['description'] ?? '';
$location = $_POST['location'] ?? '';
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

if (!$title || !$category) {
    http_response_code(400);
    echo json_encode(['error' => 'Title and category are required']);
    exit;
}

$imagePath = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('issue_') . '.' . $ext;
    move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
    $imagePath = '/uploads/' . $filename;
}

require_once __DIR__ . '/../../utils/discord.php';

$db = (new Database())->getConnection();
$stmt = $db->prepare('INSERT INTO issues (user_id, title, category, description, location, lat, lng, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$user['id'], $title, $category, $description, $location, $lat, $lng, $imagePath]);
$issueId = (int)$db->lastInsertId();

// Send Discord Webhook
$webhookTitle = "🚨 แจ้งซ่อมใหม่ (Issue #{$issueId})";
$desc = "📁 **หมวดหมู่:** {$category}\n📝 **หัวข้อ:** {$title}\n📌 **รายละเอียด:** {$description}\n📍 **สถานที่:** {$location}";
sendDiscordWebhook($webhookTitle, $desc, [
    ["name" => "👤 ผู้แจ้ง", "value" => $user['email'], "inline" => true]
], "#F97316", null, 'issue'); // Coral/Orange color

http_response_code(201);
echo json_encode(['message' => 'Issue reported successfully', 'id' => $issueId]);

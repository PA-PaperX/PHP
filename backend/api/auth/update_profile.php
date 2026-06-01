<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();

$user = getCurrentUser();
$db = (new Database())->getConnection();

// Fetch current user details
$stmt = $db->prepare("SELECT id, password, profile_image FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

$username = $_POST['username'] ?? null;
$oldPassword = $_POST['old_password'] ?? null;
$newPassword = $_POST['new_password'] ?? null;

// Validate username (Optional: Check if it already exists for another user)
if ($username) {
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $user['id']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว']);
        exit;
    }
}

// Check password change
$hashedPassword = $currentUser['password'];
if ($newPassword) {
    if (!$oldPassword) {
        http_response_code(400);
        echo json_encode(['error' => 'กรุณากรอกรหัสผ่านเดิมเพื่อยืนยันการเปลี่ยนรหัสผ่าน']);
        exit;
    }
    
    if (!password_verify($oldPassword, $hashedPassword)) {
        http_response_code(401);
        echo json_encode(['error' => 'รหัสผ่านเดิมไม่ถูกต้อง']);
        exit;
    }
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
}

// Handle Profile Image Upload
$imagePath = $currentUser['profile_image'];
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../../uploads/profiles/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array(strtolower($ext), $allowedExts)) {
        http_response_code(400);
        echo json_encode(['error' => 'ไม่อนุญาตให้อัปโหลดไฟล์ประเภทนี้ (รองรับรูปภาพเท่านั้น)']);
        exit;
    }

    $filename = uniqid('profile_') . '.' . $ext;
    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadDir . $filename)) {
        // Optionally delete old image
        if ($imagePath && file_exists(__DIR__ . '/../..' . $imagePath)) {
            @unlink(__DIR__ . '/../..' . $imagePath);
        }
        $imagePath = '/uploads/profiles/' . $filename;
    }
}

// Update Database
if ($username) {
    $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, profile_image = ? WHERE id = ?");
    $stmt->execute([$username, $hashedPassword, $imagePath, $user['id']]);
} else {
    $stmt = $db->prepare("UPDATE users SET password = ?, profile_image = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $imagePath, $user['id']]);
}

// Update session data just in case
if ($username) {
    $_SESSION['user_name'] = $username;
}

echo json_encode([
    'message' => 'อัปเดตโปรไฟล์เรียบร้อยแล้ว',
    'user' => [
        'username' => $username ?? $user['username'],
        'profile_image' => $imagePath
    ]
]);

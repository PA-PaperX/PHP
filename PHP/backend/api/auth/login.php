<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../middleware/cors.php';
session_start();
require_once __DIR__ . '/../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน']);
    exit;
}

$db = (new Database())->getConnection();
$stmt = $db->prepare('SELECT id, email, username, password, role, profile_image FROM users WHERE username = ?');
$stmt->execute([$data['username']]);
$user = $stmt->fetch();

if (!$user || !password_verify($data['password'], $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['username'];
$_SESSION['user_role'] = $user['role'];

echo json_encode([
    'message' => 'Login successful',
    'user' => [
        'id' => $user['id'],
        'email' => $user['email'],
        'username' => $user['username'],
        'profile_image' => $user['profile_image'] ?? null,
        'role' => $user['role']
    ]
]);

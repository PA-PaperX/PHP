<?php
require_once __DIR__ . '/../../middleware/cors.php';
session_start();
require_once __DIR__ . '/../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['password']) || !isset($data['username'])) {
    http_response_code(400);
    echo json_encode(['error' => 'กรุณากรอกข้อมูลให้ครบถ้วน']);
    exit;
}

if (strlen($data['password']) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร']);
    exit;
}

$db = (new Database())->getConnection();

// Check if username exists
$stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$data['username']]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว']);
    exit;
}

// Check if email exists
$stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$data['email']]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'อีเมลนี้ถูกใช้งานแล้ว']);
    exit;
}

$hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
$stmt = $db->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, "user")');
$stmt->execute([$data['username'], $data['email'], $hashedPassword]);

$userId = $db->lastInsertId();

$_SESSION['user_id'] = $userId;
$_SESSION['user_email'] = $data['email'];
$_SESSION['user_name'] = $data['username'];
$_SESSION['user_role'] = 'user';

http_response_code(201);
echo json_encode([
    'message' => 'Registration successful',
    'user' => [
        'id' => (int)$userId,
        'email' => $data['email'],
        'username' => $data['username'],
        'profile_image' => null,
        'role' => 'user'
    ]
]);

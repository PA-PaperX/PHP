<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();
$db = (new Database())->getConnection();
$stmt = $db->prepare('SELECT id, email, username, profile_image, role FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user) {
    echo json_encode([
        'id' => $user['id'],
        'email' => $user['email'],
        'username' => $user['username'],
        'profile_image' => $user['profile_image'],
        'role' => $user['role']
    ]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
}

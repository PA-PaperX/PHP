<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Issue ID is required']);
    exit;
}

$user = getCurrentUser();
$db = (new Database())->getConnection();

$stmt = $db->prepare('
    SELECT i.*, u.email as user_email, a.email as admin_email 
    FROM issues i 
    JOIN users u ON i.user_id = u.id 
    LEFT JOIN users a ON i.admin_id = a.id 
    WHERE i.id = ?
');
$stmt->execute([$id]);
$issue = $stmt->fetch();

if (!$issue) {
    http_response_code(404);
    echo json_encode(['error' => 'Issue not found']);
    exit;
}

// Security: If not admin, users can only view their own issues
if ($user['role'] !== 'admin' && $issue['user_id'] !== $user['id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: You cannot view this issue']);
    exit;
}

echo json_encode(['issue' => $issue]);

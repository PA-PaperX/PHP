<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Equipment ID is required']);
    exit;
}

$db = (new Database())->getConnection();
$stmt = $db->prepare('DELETE FROM equipment WHERE id = ?');
$stmt->execute([$id]);

echo json_encode(['message' => 'Equipment deleted successfully']);

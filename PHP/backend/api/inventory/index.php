<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAuth();

$db = (new Database())->getConnection();

$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;

$sql = 'SELECT * FROM equipment WHERE 1=1';
$params = [];

if ($category) {
    $sql .= ' AND category = ?';
    $params[] = $category;
}
if ($search) {
    $sql .= ' AND (name LIKE ? OR description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= ' ORDER BY name ASC';
$stmt = $db->prepare($sql);
$stmt->execute($params);

echo json_encode(['equipment' => $stmt->fetchAll()]);

<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$name = $_POST['name'] ?? null;
$category = $_POST['category'] ?? '';
$description = $_POST['description'] ?? '';
$quantity = (int)($_POST['quantity'] ?? 0);

if (!$name) {
    http_response_code(400);
    echo json_encode(['error' => 'Equipment name is required']);
    exit;
}

$imagePath = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('equip_') . '.' . $ext;
    move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
    $imagePath = '/uploads/' . $filename;
}

$db = (new Database())->getConnection();
$stmt = $db->prepare('INSERT INTO equipment (name, category, description, image_path, quantity, available) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$name, $category, $description, $imagePath, $quantity, $quantity]);

http_response_code(201);
echo json_encode(['message' => 'Equipment created successfully', 'id' => (int)$db->lastInsertId()]);

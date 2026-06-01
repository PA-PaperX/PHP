<?php
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireAdmin();

$id = $_POST['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Equipment ID is required']);
    exit;
}

$db = (new Database())->getConnection();
$fields = [];
$params = [];

// Handle quantity change to recalculate available
if (isset($_POST['quantity'])) {
    $newQuantity = (int)$_POST['quantity'];
    $stmt = $db->prepare('SELECT quantity, available FROM equipment WHERE id = ?');
    $stmt->execute([$id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($current) {
        $borrowed = $current['quantity'] - $current['available'];
        $newAvailable = max(0, $newQuantity - $borrowed);
        
        $fields[] = "quantity = ?";
        $params[] = $newQuantity;
        $fields[] = "available = ?";
        $params[] = $newAvailable;
    }
}

foreach (['name', 'category', 'description'] as $field) {
    if (isset($_POST[$field])) {
        $fields[] = "$field = ?";
        $params[] = $_POST[$field];
    }
}

// Handle Image Upload
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('equip_') . '.' . $ext;
    move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
    $fields[] = "image_path = ?";
    $params[] = '/uploads/' . $filename;
}

if (empty($fields)) {
    http_response_code(400);
    echo json_encode(['error' => 'No fields to update']);
    exit;
}

$params[] = $id;
$sql = 'UPDATE equipment SET ' . implode(', ', $fields) . ' WHERE id = ?';
$stmt = $db->prepare($sql);
$stmt->execute($params);

echo json_encode(['message' => 'Equipment updated successfully']);

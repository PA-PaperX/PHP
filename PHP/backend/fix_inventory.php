<?php
require_once __DIR__ . '/config/database.php';

$db = (new Database())->getConnection();

echo "Starting inventory fix...\n";

// 1. Recalculate available quantity for all equipment
$stmt = $db->query("SELECT id, quantity FROM equipment");
$equipments = $stmt->fetchAll();

foreach ($equipments as $eq) {
    // Find sum of borrows that are not returned or rejected
    $sumStmt = $db->prepare("SELECT SUM(quantity) as total_borrowed FROM borrows WHERE equipment_id = ? AND status IN ('pending', 'approved', 'pending_return')");
    $sumStmt->execute([$eq['id']]);
    $result = $sumStmt->fetch();
    
    $totalBorrowed = (int)($result['total_borrowed'] ?? 0);
    $correctAvailable = $eq['quantity'] - $totalBorrowed;
    
    // Update equipment
    $updateStmt = $db->prepare("UPDATE equipment SET available = ? WHERE id = ?");
    $updateStmt->execute([$correctAvailable, $eq['id']]);
    
    echo "Equipment ID {$eq['id']}: Set available to {$correctAvailable}\n";
}

// 2. Add MySQL Trigger for future deletions
try {
    $db->exec("SET GLOBAL log_bin_trust_function_creators = 1;");
    $db->exec("DROP TRIGGER IF EXISTS after_borrow_delete");
    $db->exec("
        CREATE TRIGGER after_borrow_delete
        AFTER DELETE ON borrows
        FOR EACH ROW
        BEGIN
            IF OLD.status IN ('pending', 'approved', 'pending_return') THEN
                UPDATE equipment SET available = available + OLD.quantity WHERE id = OLD.equipment_id;
            END IF;
        END;
    ");
    echo "Trigger 'after_borrow_delete' created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating trigger: " . $e->getMessage() . "\n";
}

echo "Inventory fix complete!\n";

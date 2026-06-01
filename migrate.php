<?php
require_once __DIR__ . '/backend/config/database.php';
try {
    $db = (new Database())->getConnection();
    $db->exec("ALTER TABLE issues ADD COLUMN admin_image_path VARCHAR(500) DEFAULT NULL");
    echo "Migration successful\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

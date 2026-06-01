<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();
try {
    $db->exec("ALTER TABLE issues ADD COLUMN admin_id INT DEFAULT NULL");
    $db->exec("ALTER TABLE issues ADD FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL");
    echo "Migration successful.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

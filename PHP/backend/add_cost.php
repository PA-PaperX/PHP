<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();
try {
    $db->exec("ALTER TABLE issues ADD COLUMN cost DECIMAL(10,2) DEFAULT 1.00");
    echo "Added cost column";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

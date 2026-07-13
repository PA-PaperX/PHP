<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();
try {
    $db->exec("UPDATE issues SET cost = 1.00 WHERE cost IS NULL OR cost = 0.00");
    echo "Updated costs";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

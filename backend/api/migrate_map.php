<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = (new Database())->getConnection();
    // Add lat and lng to issues table if they don't exist
    $db->exec("ALTER TABLE issues ADD COLUMN lat DECIMAL(10, 8) DEFAULT NULL AFTER location");
    $db->exec("ALTER TABLE issues ADD COLUMN lng DECIMAL(11, 8) DEFAULT NULL AFTER lat");
    echo "Migration successful - added lat/lng to issues\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

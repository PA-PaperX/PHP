<?php
require_once __DIR__ . '/config/database.php';
$db = (new \Database())->getConnection();

$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables:\n" . implode("\n", $tables) . "\n\n";

foreach ($tables as $table) {
    echo "Table: $table\n";
    $cols = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo " - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
}

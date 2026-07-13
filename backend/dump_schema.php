<?php
require 'config/database.php';
$db = (new Database())->getConnection();

$tables = ['users', 'equipment', 'borrows'];
foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $stmt = $db->query("DESCRIBE $table");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "\n";
}

<?php
require_once __DIR__ . '/config/database.php';

$db = (new Database())->getConnection();

function columnExists(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

try {
    if (!columnExists($db, 'issues', 'payment_status')) {
        $db->exec("ALTER TABLE issues ADD COLUMN payment_status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid' AFTER status");
        echo "Added issues.payment_status\n";
    } else {
        echo "issues.payment_status already exists\n";
    }

    if (!columnExists($db, 'issues', 'paid_at')) {
        $db->exec("ALTER TABLE issues ADD COLUMN paid_at DATETIME DEFAULT NULL AFTER payment_status");
        echo "Added issues.paid_at\n";
    } else {
        echo "issues.paid_at already exists\n";
    }

    echo "Migration successful\n";
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage() . "\n";
}

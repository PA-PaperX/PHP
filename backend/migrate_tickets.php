<?php
require_once __DIR__ . '/config/database.php';

$db = (new Database())->getConnection();

try {
    // 1. password_reset_tickets
    $db->exec("
        CREATE TABLE IF NOT EXISTS password_reset_tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            access_token VARCHAR(255) NOT NULL,
            reset_token VARCHAR(255) NULL,
            reset_token_expires_at DATETIME NULL,
            status ENUM('pending', 'resolved', 'cancelled') DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (email),
            INDEX (access_token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. ticket_messages
    $db->exec("
        CREATE TABLE IF NOT EXISTS ticket_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            sender_type ENUM('user', 'admin', 'system') NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES password_reset_tickets(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "Tickets tables created successfully!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}

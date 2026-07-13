<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();

// Create equipment for testing if none exists
$db->exec("INSERT IGNORE INTO equipment (name, category, description, available) VALUES ('Test Laptop', 'IT', 'A testing laptop', 10)");

// Create test user and admin
$pw = password_hash('password123', PASSWORD_DEFAULT);
$db->exec("INSERT INTO users (username, email, password, role) VALUES ('testuser', 'testuser@gmail.com', '$pw', 'user') ON DUPLICATE KEY UPDATE role='user'");
$db->exec("INSERT INTO users (username, email, password, role) VALUES ('testadmin', 'testadmin@gmail.com', '$pw', 'admin') ON DUPLICATE KEY UPDATE role='admin'");

echo "Seed completed successfully.";

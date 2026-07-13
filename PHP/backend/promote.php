<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();
$db->exec("UPDATE users SET role='admin' WHERE email='testuser@gmail.com'");
echo 'Success! testuser@gmail.com is now an admin.';

<?php
require_once __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->query('SELECT id, email, username, password FROM users');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($users);

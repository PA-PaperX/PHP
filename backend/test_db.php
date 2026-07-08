<?php
$pdo = new PDO('mysql:host=mysql;dbname=iya_db;charset=utf8mb4', 'iya_user', 'iya_pass_2024');
$stmt = $pdo->query('SELECT * FROM equipment');
$equip = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $pdo->query('SELECT * FROM users');
$users = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$result = [
    'equipment' => $equip,
    'users' => $users
];
file_put_contents('/var/www/html/test_output.json', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Data saved to test_output.json";
echo "\n--- Users ---\n";
echo json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

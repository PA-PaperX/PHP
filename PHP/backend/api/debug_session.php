<?php
require_once __DIR__ . '/../middleware/auth.php';
echo json_encode([
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE
]);

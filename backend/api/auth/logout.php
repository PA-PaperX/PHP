<?php
require_once __DIR__ . '/../../middleware/cors.php';
session_start();
session_destroy();
echo json_encode(['message' => 'Logged out successfully']);

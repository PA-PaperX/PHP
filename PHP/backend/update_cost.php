<?php
require 'config/database.php';
$db = (new Database())->getConnection();
$db->exec("UPDATE issues SET cost = 1.00 WHERE cost = 0.00 OR cost IS NULL");
echo "Updated all free issues to 1.00 THB";

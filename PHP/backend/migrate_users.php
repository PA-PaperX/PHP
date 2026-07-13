<?php
require_once __DIR__ . '/config/database.php';

$db = (new Database())->getConnection();

try {
    // Add new columns if they don't exist
    try {
        $db->exec("ALTER TABLE users ADD COLUMN username VARCHAR(255)");
        $db->exec("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255)");
    } catch (Exception $e) {
        // Columns might already exist, so ensure they are VARCHAR not TEXT
        $db->exec("ALTER TABLE users MODIFY username VARCHAR(255)");
        $db->exec("ALTER TABLE users MODIFY profile_image VARCHAR(255)");
    }
    
    // Fetch all existing users
    $stmt = $db->query("SELECT id, email FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Update existing users with a generated username
    $updateStmt = $db->prepare("UPDATE users SET username = ? WHERE id = ?");
    
    foreach ($users as $user) {
        // Extract part before @ for username
        $parts = explode('@', $user['email']);
        $baseUsername = $parts[0];
        
        // Ensure username uniqueness
        $username = $baseUsername;
        $counter = 1;
        while (true) {
            $check = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check->execute([$username, $user['id']]);
            if (!$check->fetch()) {
                break; // Username is unique
            }
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        $updateStmt->execute([$username, $user['id']]);
    }
    
    // Finally, create a unique index on username
    $db->exec("CREATE UNIQUE INDEX idx_users_username ON users(username)");
    
    echo "Migration completed successfully!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}

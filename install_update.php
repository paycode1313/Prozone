<?php
require_once 'config/config.php';
$database = new Database();
$db = $database->getConnection();

echo "Updating database schema...\n";

// 1. Add avatar column to users
try {
    $check = $db->query("SHOW COLUMNS FROM users LIKE 'avatar'");
    if ($check->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
        echo "Added 'avatar' column to users table.\n";
    } else {
        echo "'avatar' column already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error checking/adding avatar column: " . $e->getMessage() . "\n";
}

// 2. Add equipped_title and equipped_frame columns to users for easier access
try {
    $check = $db->query("SHOW COLUMNS FROM users LIKE 'equipped_title'");
    if ($check->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN equipped_title VARCHAR(100) DEFAULT NULL");
        echo "Added 'equipped_title' column to users table.\n";
    }
} catch (PDOException $e) {}

try {
    $check = $db->query("SHOW COLUMNS FROM users LIKE 'equipped_frame'");
    if ($check->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN equipped_frame TEXT DEFAULT NULL");
        echo "Added 'equipped_frame' column to users table.\n";
    }
} catch (PDOException $e) {}

// 3. Run SQL file
$sql = file_get_contents(__DIR__ . '/database/update_schema_v2.sql');
try {
    $db->exec($sql);
    echo "Executed update_schema_v2.sql successfully.\n";
} catch (PDOException $e) {
    echo "Error executing SQL file: " . $e->getMessage() . "\n";
}

echo "Database update complete.\n";
?>
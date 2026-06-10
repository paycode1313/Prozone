<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = file_get_contents(__DIR__ . '/database/update_schema_v3.sql');
    
    // Split by semicolon to execute multiple statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            try {
                $db->exec($stmt);
                echo "Executed: " . substr($stmt, 0, 50) . "...<br>";
            } catch (PDOException $e) {
                // Ignore "Duplicate column name" error
                if (strpos($e->getMessage(), "Duplicate column name") !== false) {
                    echo "Column already exists (Skipped)<br>";
                } else {
                    echo "Error executing statement: " . $e->getMessage() . "<br>";
                }
            }
        }
    }
    
    echo "Database update v3 completed successfully!";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
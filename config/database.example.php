<?php
/**
 * Database Configuration - TEMPLATE
 * --------------------------------
 * Copy this file to `database.php` and fill in your credentials.
 *
 *   cp config/database.example.php config/database.php
 *
 * NEVER commit `config/database.php` to version control.
 */

class Database {
    private $host     = 'localhost';
    private $db_name  = 'prozone';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                )
            );
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}

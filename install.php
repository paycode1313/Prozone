<?php
// Script instalasi database untuk Prozone Learning Platform
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalasi - Prozone</title>
    <link rel="stylesheet" href="assets/css/global.css">
    <style>
        body {
            background: #0f0f23;
            color: #e2e8f0;
            padding: 2rem;
            font-family: 'Inter', sans-serif;
        }
        .install-container {
            max-width: 800px;
            margin: 0 auto;
            background: #1e1e3f;
            border-radius: 15px;
            padding: 3rem;
            border: 1px solid #2d2d5a;
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.2);
        }
        h1 {
            color: #a78bfa;
            margin-bottom: 1rem;
            font-size: 2rem;
        }
        h2 {
            color: #a78bfa;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        p {
            color: #cbd5e1;
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }
        ul {
            color: #cbd5e1;
            margin: 1rem 0;
            padding-left: 2rem;
        }
        li {
            margin-bottom: 0.5rem;
        }
        .success {
            color: #10b981;
        }
        .error {
            color: #ef4444;
        }
        .warning {
            color: #f59e0b;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 1rem;
            transition: transform 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
        }
        hr {
            border: none;
            border-top: 1px solid #2d2d5a;
            margin: 2rem 0;
        }
        small {
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <h1>🚀 Instalasi Database - Prozone Platform</h1>
<?php

// Konfigurasi database
$host = 'localhost';
$db_name = 'prozone';
$username = 'root';
$password = '';

try {
    // Koneksi tanpa database (untuk membuat database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p class='success'>✅ Koneksi ke MySQL berhasil!</p>";
    
    // Buat database jika belum ada
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
    echo "<p class='success'>✅ Database '$db_name' berhasil dibuat!</p>";
    
    // Pilih database
    $pdo->exec("USE `$db_name`");
    
    // Baca dan eksekusi schema SQL
    $schema = file_get_contents('database/schema.sql');
    
    // Split per statement
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Skip error untuk statement yang sudah ada
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "<p class='warning'>⚠️ Warning: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }
    }
    
    echo "<p class='success'>✅ Schema database berhasil diimport!</p>";
    
    // Test koneksi dengan database yang baru
    $test_pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test query
    $stmt = $test_pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p class='success'>✅ Test koneksi ke database berhasil!</p>";
    echo "<p class='success'>✅ Jumlah user default: " . $result['count'] . "</p>";
    
    echo "<h2>🎉 Instalasi Berhasil!</h2>";
    echo "<p><strong>Akun default untuk login:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> username: <code>admin</code>, password: <code>password</code></li>";
    echo "<li><strong>Instructor:</strong> username: <code>instructor1</code>, password: <code>password</code></li>";
    echo "<li><strong>Student:</strong> username: <code>student1</code>, password: <code>password</code></li>";
    echo "</ul>";
    echo "<p><a href='login.php' class='btn'>🚀 Mulai Menggunakan Prozone</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Troubleshooting:</strong></p>";
    echo "<ul>";
    echo "<li>Pastikan MySQL service sudah running</li>";
    echo "<li>Check username dan password di file install.php</li>";
    echo "<li>Pastikan user MySQL memiliki privilege untuk membuat database</li>";
    echo "<li>Pastikan file database/schema.sql ada dan dapat dibaca</li>";
    echo "</ul>";
}
?>
        <hr>
        <p><small>File ini dapat dihapus setelah instalasi selesai untuk keamanan.</small></p>
    </div>
</body>
</html>

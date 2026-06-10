<?php
/**
 * Application Configuration - TEMPLATE
 * ------------------------------------
 * Copy this file to `config.php` and adjust to your environment.
 *
 *   cp config/config.example.php config/config.php
 *
 * NEVER commit `config/config.php` to version control.
 */

// Base URL — change this to your actual domain (NO trailing slash issues, must end with /)
define('BASE_URL', 'http://localhost/ProzoneWeb/');
define('APP_NAME', 'Prozone');
define('APP_DESCRIPTION', 'Platform pembelajaran coding interaktif dengan fitur clan, leaderboard, dan achievement');

// ============================================
// EMAIL CONFIGURATION
// ============================================
// EMAIL_DEBUG = true  → Link reset ditampilkan langsung di halaman (untuk development)
// EMAIL_DEBUG = false → Email dikirim via SMTP (untuk production)
define('EMAIL_DEBUG', true);

// SMTP Configuration — Isi jika EMAIL_DEBUG = false
// Opsi 1: Gmail (perlu App Password)
// Opsi 2: Mailtrap.io (gratis untuk testing)
// Opsi 3: SMTP hosting Anda
define('SMTP_HOST',       'sandbox.smtp.mailtrap.io');
define('SMTP_PORT',       587);
define('SMTP_USERNAME',   '');  // Kosongkan jika EMAIL_DEBUG = true
define('SMTP_PASSWORD',   '');  // Kosongkan jika EMAIL_DEBUG = true
define('SMTP_ENCRYPTION', 'tls');

define('EMAIL_FROM',      'noreply@prozone.local');
define('EMAIL_FROM_NAME', APP_NAME);

// ============================================
// Konfigurasi session
// ============================================
session_start();

// Autoload classes
spl_autoload_register(function ($class_name) {
    $root = dirname(__DIR__);
    $directories = [
        '/classes/',
        '/models/',
        '/controllers/',
    ];

    foreach ($directories as $directory) {
        $file = $root . $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Include database connection
require_once __DIR__ . '/database.php';

// Include language system
require_once __DIR__ . '/language.php';

// Set language & theme from user preference or session
if (isLoggedIn()) {
    $database = new Database();
    $db = $database->getConnection();
    $query = "SELECT language_preference, theme_preference FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $user_pref = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['language'] = $user_pref['language_preference'] ?? 'id';
        $_SESSION['theme']    = $user_pref['theme_preference']    ?? 'light';
    }
} else {
    if (!isset($_SESSION['theme'])) {
        $_SESSION['theme'] = 'light';
    }
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function getThemeClass() {
    $theme = $_SESSION['theme'] ?? 'light';
    return $theme === 'dark' ? 'dark-mode' : 'light-mode';
}

function userLoggedInFlag() {
    return isLoggedIn() ? 'true' : 'false';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireRole($allowedRoles) {
    requireLogin();
    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        header('Location: unauthorized.php');
        exit();
    }
}

function sanitizeInput($data) {
    if ($data === null) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function generateSlug($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '-';
    return date($format, strtotime($datetime));
}

function calculateLevel($xp) {
    return max(1, floor(sqrt($xp / 100)));
}

function getXpForNextLevel($currentLevel) {
    return pow($currentLevel + 1, 2) * 100;
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}
?>

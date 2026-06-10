<?php
/**
 * ============================================
 * API: Set Theme Preference
 * File: api/set-theme.php
 * Deskripsi: Endpoint untuk menyimpan preferensi tema user ke database.
 * Dipanggil dari theme-toggle.js via navigator.sendBeacon.
 * ============================================
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Hanya respond untuk user yang login
if (!isLoggedIn()) {
    http_response_code(204); // Silent fail - tema akan disync di page load berikutnya
    exit;
}

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Baca input
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$theme = $_POST['theme'] ?? $input['theme'] ?? null;

// Validasi tema
if (!in_array($theme, ['light', 'dark'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid theme']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "UPDATE users SET theme_preference = :theme WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':theme', $theme);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();

    // Update session agar konsisten
    $_SESSION['theme'] = $theme;

    http_response_code(200);
    echo json_encode(['success' => true, 'theme' => $theme]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}

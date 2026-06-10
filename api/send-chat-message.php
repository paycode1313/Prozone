<?php
// API endpoint untuk mengirim chat message (AJAX)
require_once '../config/config.php';
requireLogin();
requireRole(['student']);

header('Content-Type: application/json; charset=utf-8');

$database = new Database();
$db = $database->getConnection();

require_once '../models/Chat.php';

// Verify CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

$clan_id = $_POST['clan_id'] ?? 0;
$message = trim($_POST['message'] ?? '');

if (!$clan_id || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Clan ID dan pesan diperlukan']);
    exit();
}

// Check if user is member of this clan
$query_check = "SELECT * FROM clan_members WHERE clan_id = :clan_id AND user_id = :user_id";
$stmt_check = $db->prepare($query_check);
$stmt_check->bindParam(':clan_id', $clan_id);
$stmt_check->bindParam(':user_id', $_SESSION['user_id']);
$stmt_check->execute();

if ($stmt_check->rowCount() == 0) {
    echo json_encode(['success' => false, 'message' => 'Anda bukan member clan ini']);
    exit();
}

// Save the message
$chat = new Chat($db);
$chat->clan_id = $clan_id;
$chat->user_id = $_SESSION['user_id'];
$chat->message = strip_tags($message); // Remove HTML tags but keep emojis

if ($chat->create()) {
    // Get the last inserted message ID
    $last_id = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message_id' => (int)$last_id,
        'message' => 'Pesan berhasil dikirim'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengirim pesan']);
}

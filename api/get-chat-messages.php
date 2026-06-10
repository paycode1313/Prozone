<?php
// API endpoint untuk get chat messages (AJAX)
require_once '../config/config.php';
requireLogin();
requireRole(['student']);

header('Content-Type: application/json; charset=utf-8');

$database = new Database();
$db = $database->getConnection();

require_once '../models/Chat.php';

$clan_id = $_GET['clan_id'] ?? 0;
$last_id = $_GET['last_id'] ?? 0;

if (!$clan_id) {
    echo json_encode(['success' => false, 'message' => 'Clan ID required']);
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

$chat = new Chat($db);
$messages_stmt = $chat->getMessages($clan_id, 50, $last_id);
$messages = [];

while ($row = $messages_stmt->fetch(PDO::FETCH_ASSOC)) {
    $messages[] = [
        'id' => $row['id'],
        'user_id' => $row['user_id'],
        'nama_lengkap' => $row['nama_lengkap'],
        'message' => $row['message'], // Don't double-encode, let JS handle escaping
        'created_at' => $row['created_at']
    ];
}

echo json_encode([
    'success' => true,
    'messages' => $messages
], JSON_UNESCAPED_UNICODE);


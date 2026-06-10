<?php
require_once '../config/config.php';
requireLogin();
require_once '../models/Notification.php';

$database = new Database();
$db = $database->getConnection();
$notification = new Notification($db);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'count') {
        $count = $notification->getUnreadCount($_SESSION['user_id']);
        echo json_encode(['success' => true, 'count' => $count]);
    } else {
        $stmt = $notification->getUserNotifications($_SESSION['user_id']);
        $notifications = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['created_at_formatted'] = date('d M H:i', strtotime($row['created_at']));
            $notifications[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $notifications]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (isset($data->action) && $data->action === 'mark_read') {
        if (isset($data->id)) {
            $notification->markAsRead($data->id, $_SESSION['user_id']);
        } else {
            $notification->markAllAsRead($_SESSION['user_id']);
        }
        echo json_encode(['success' => true]);
    }
}
?>
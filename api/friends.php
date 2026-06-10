<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

require_once '../models/Friend.php';
require_once '../models/PrivateMessage.php';

$database = new Database();
$db = $database->getConnection();

$friend = new Friend($db);
$pm = new PrivateMessage($db);

$action = $_REQUEST['action'] ?? '';
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'search':
        $search = sanitizeInput($_GET['q'] ?? '');
        if (strlen($search) < 2) {
            echo json_encode(['success' => false, 'message' => 'Minimal 2 karakter']);
            exit;
        }
        $results = $friend->searchUsers($user_id, $search);
        $users = [];
        while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $row;
        }
        echo json_encode(['success' => true, 'users' => $users]);
        break;

    case 'send_request':
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            exit;
        }
        $friend_id = intval($_POST['friend_id'] ?? 0);
        if ($friend_id <= 0 || $friend_id == $user_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid user']);
            exit;
        }
        $result = $friend->sendRequest($user_id, $friend_id);
        echo json_encode($result);
        break;

    case 'accept_request':
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            exit;
        }
        $friend_id = intval($_POST['friend_id'] ?? 0);
        $result = $friend->acceptRequest($user_id, $friend_id);
        echo json_encode($result);
        break;

    case 'reject_request':
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            exit;
        }
        $friend_id = intval($_POST['friend_id'] ?? 0);
        $result = $friend->rejectRequest($user_id, $friend_id);
        echo json_encode(['success' => $result]);
        break;

    case 'remove_friend':
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            exit;
        }
        $friend_id = intval($_POST['friend_id'] ?? 0);
        $result = $friend->removeFriend($user_id, $friend_id);
        echo json_encode(['success' => $result]);
        break;

    case 'get_friends':
        $friends_list = $friend->getFriends($user_id);
        $friends = [];
        while ($row = $friends_list->fetch(PDO::FETCH_ASSOC)) {
            $row['unread'] = $pm->getUnreadCount($user_id, $row['id']);
            $friends[] = $row;
        }
        echo json_encode(['success' => true, 'friends' => $friends]);
        break;

    case 'get_pending':
        $pending = $friend->getPendingRequests($user_id);
        $requests = [];
        while ($row = $pending->fetch(PDO::FETCH_ASSOC)) {
            $requests[] = $row;
        }
        echo json_encode(['success' => true, 'requests' => $requests]);
        break;

    case 'send_message':
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            exit;
        }
        $receiver_id = intval($_POST['receiver_id'] ?? 0);
        $message = sanitizeInput($_POST['message'] ?? '');
        
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Pesan tidak boleh kosong']);
            exit;
        }
        
        // Check if they are friends
        $friendship = $friend->getFriendshipStatus($user_id, $receiver_id);
        if (!$friendship || $friendship['status'] !== 'accepted') {
            echo json_encode(['success' => false, 'message' => 'Anda harus berteman untuk mengirim pesan']);
            exit;
        }
        
        $result = $pm->send($user_id, $receiver_id, $message);
        echo json_encode(['success' => $result]);
        break;

    case 'get_messages':
        $friend_id = intval($_GET['friend_id'] ?? 0);
        $last_id = intval($_GET['last_id'] ?? 0);
        
        // Mark as read
        $pm->markAsRead($user_id, $friend_id);
        
        if ($last_id > 0) {
            $messages_stmt = $pm->getNewMessages($user_id, $friend_id, $last_id);
        } else {
            $messages_stmt = $pm->getConversation($user_id, $friend_id);
        }
        
        $messages = [];
        while ($row = $messages_stmt->fetch(PDO::FETCH_ASSOC)) {
            $messages[] = $row;
        }
        
        if ($last_id == 0) {
            $messages = array_reverse($messages);
        }
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    case 'get_conversations':
        $conversations = $pm->getRecentConversations($user_id);
        $convos = [];
        while ($row = $conversations->fetch(PDO::FETCH_ASSOC)) {
            $convos[] = $row;
        }
        echo json_encode(['success' => true, 'conversations' => $convos]);
        break;

    case 'update_status':
        Friend::updateOnlineStatus($db, $user_id, true);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>

<?php
require_once '../config/config.php';
requireLogin();

require_once '../models/Comment.php';
require_once '../models/Notification.php'; // Will implement next

$database = new Database();
$db = $database->getConnection();

$comment = new Comment($db);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $lesson_id = isset($_GET['lesson_id']) ? $_GET['lesson_id'] : die();
    
    $stmt = $comment->getByLesson($lesson_id);
    $comments = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format date
        $row['created_at_formatted'] = date('d M Y H:i', strtotime($row['created_at']));
        // Add avatar path
        if (!empty($row['avatar']) && file_exists('../assets/uploads/avatars/' . $row['avatar'])) {
            $row['avatar_url'] = 'assets/uploads/avatars/' . $row['avatar'];
        } else {
            $row['avatar_url'] = null;
            $row['initial'] = strtoupper(substr($row['nama_lengkap'], 0, 1));
        }
        $comments[] = $row;
    }
    
    // Build tree structure for replies
    $tree = [];
    $ref = [];
    
    foreach ($comments as $c) {
        $thisRef = &$ref[$c['id']];
        $thisRef['parent_id'] = $c['parent_id'];
        $thisRef['data'] = $c;
        $thisRef['children'] = [];
        
        if ($c['parent_id'] == null) {
            $tree[$c['id']] = &$thisRef;
        } else {
            $ref[$c['parent_id']]['children'][] = &$thisRef;
        }
    }
    
    echo json_encode(['success' => true, 'data' => array_values($tree)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->lesson_id) && !empty($data->content)) {
        $comment->lesson_id = $data->lesson_id;
        $comment->user_id = $_SESSION['user_id'];
        $comment->content = $data->content;
        $comment->parent_id = isset($data->parent_id) ? $data->parent_id : null;
        
        if ($comment->create()) {
            // Send notification if reply
            if ($comment->parent_id) {
                $parentComment = $comment->getById($comment->parent_id);
                if ($parentComment && $parentComment['user_id'] != $_SESSION['user_id']) {
                    $notification = new Notification($db);
                    $notification->user_id = $parentComment['user_id'];
                    $notification->type = 'reply';
                    $notification->message = $_SESSION['nama_lengkap'] . ' membalas komentar Anda.';
                    // Ideally link to the lesson
                    $notification->create();
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Komentar berhasil dikirim']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengirim komentar']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    }
}
?>
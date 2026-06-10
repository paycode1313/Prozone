<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Shop.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Check CSRF Token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF Token']);
        exit;
    }
}

$database = new Database();
$db = $database->getConnection();
$shop = new Shop($db);

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'buy':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
            exit;
        }
        
        $item_id = $_POST['item_id'] ?? 0;
        if (!$item_id) {
            echo json_encode(['status' => 'error', 'message' => 'Item ID required']);
            exit;
        }
        
        $result = $shop->buyItem($user_id, $item_id);
        echo json_encode($result);
        break;

    case 'equip':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
            exit;
        }
        
        $item_id = $_POST['item_id'] ?? 0;
        if (!$item_id) {
            echo json_encode(['status' => 'error', 'message' => 'Item ID required']);
            exit;
        }
        
        if ($shop->equipItem($user_id, $item_id)) {
            echo json_encode(['status' => 'success', 'message' => 'Item equipped']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to equip item']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>
<?php
require_once '../config/config.php';
require_once '../models/User.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Check CSRF Token
$token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken($token)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF Token']);
    exit();
}

$amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
$price = isset($_POST['price']) ? intval($_POST['price']) : 0;

if ($amount <= 0 || $price <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid amount or price']);
    exit();
}

// Simulate Payment Gateway Processing
// In a real app, you would create a transaction record, redirect to payment gateway, etc.
// Here we just simulate success.

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if ($user->addCoins($_SESSION['user_id'], $amount)) {
    echo json_encode(['status' => 'success', 'message' => 'Top up successful']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
<?php
// Test file for AI chat
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['nama_lengkap'] = 'Test User';

header('Content-Type: application/json');

// Test data
$message = 'halo';
$lesson_title = 'Test Lesson';
$course_title = 'HTML Dasar';

// Copy the generateSmartResponse function
function generateSmartResponse($message, $lessonTitle, $courseTitle) {
    $message = strtolower($message);
    
    $responses = [
        'halo|hai|hello|hi|hey' => [
            "Halo! 👋 Saya ProBot, asisten belajar coding kamu. Ada yang bisa saya bantu tentang materi **{$lessonTitle}**?",
            "Hey! 👋 Selamat belajar di **{$courseTitle}**! Apa yang ingin kamu tanyakan?"
        ],
        'bantuan|help|tolong|bingung' => [
            "Tentu, saya siap membantu! 🤝"
        ],
    ];
    
    foreach ($responses as $pattern => $responseOptions) {
        if (preg_match('/(' . $pattern . ')/i', $message)) {
            return $responseOptions[array_rand($responseOptions)];
        }
    }
    
    return "Pertanyaan menarik! 🤔 Untuk materi **{$lessonTitle}**, coba jelaskan lebih detail apa yang ingin kamu ketahui.";
}

$response = generateSmartResponse($message, $lesson_title, $course_title);

echo json_encode([
    'success' => true,
    'response' => $response,
    'bot_name' => 'ProBot',
    'test' => 'This is a test response'
]);

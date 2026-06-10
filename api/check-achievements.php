<?php
// API endpoint untuk check achievements secara manual
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

require_once '../models/Achievement.php';
require_once '../models/UserProgress.php';
require_once '../models/Enrollment.php';

$user_id = $_SESSION['user_id'];
$achievement = new Achievement($db);

// Get user stats
$query_stats = "SELECT 
                COUNT(DISTINCT up.lesson_id) as total_lessons_completed,
                COUNT(DISTINCT e.course_id) as total_courses_enrolled,
                COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.course_id END) as total_courses_completed,
                u.total_xp,
                u.level
               FROM users u
               LEFT JOIN user_progress up ON u.id = up.user_id AND up.status = 'completed'
               LEFT JOIN enrollments e ON u.id = e.user_id
               WHERE u.id = :user_id
               GROUP BY u.id";

$stmt_stats = $db->prepare($query_stats);
$stmt_stats->bindParam(':user_id', $user_id);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

$new_achievements = [];

// Check various achievements
$achievements_to_check = [
    'first_lesson' => $stats['total_lessons_completed'] >= 1,
    'ten_lessons' => $stats['total_lessons_completed'] >= 10,
    'fifty_lessons' => $stats['total_lessons_completed'] >= 50,
    'hundred_lessons' => $stats['total_lessons_completed'] >= 100,
    'first_course' => $stats['total_courses_completed'] >= 1,
    'five_courses' => $stats['total_courses_completed'] >= 5,
    'ten_courses' => $stats['total_courses_completed'] >= 10,
    'level_5' => $stats['level'] >= 5,
    'level_10' => $stats['level'] >= 10,
    'level_20' => $stats['level'] >= 20,
    'xp_1000' => $stats['total_xp'] >= 1000,
    'xp_5000' => $stats['total_xp'] >= 5000,
    'xp_10000' => $stats['total_xp'] >= 10000,
];

foreach ($achievements_to_check as $code => $condition) {
    if ($condition) {
        $achievement->kode_achievement = $code;
        if ($achievement->checkAndAward($user_id, $code)) {
            $new_achievements[] = $code;
        }
    }
}

echo json_encode([
    'success' => true,
    'new_achievements' => $new_achievements,
    'message' => count($new_achievements) > 0 
        ? 'Anda mendapatkan ' . count($new_achievements) . ' achievement baru!' 
        : 'Tidak ada achievement baru'
]);


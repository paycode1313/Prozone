<?php
/**
 * API untuk mendapatkan progress course secara realtime
 */

header('Content-Type: application/json');
require_once '../config/config.php';
requireLogin();

require_once '../models/Enrollment.php';
require_once '../models/Course.php';
require_once '../models/Lesson.php';
require_once '../models/UserProgress.php';

$database = new Database();
$db = $database->getConnection();

$enrollment = new Enrollment($db);
$course = new Course($db);
$lesson = new Lesson($db);
$user_progress = new UserProgress($db);

$course_id = $_GET['course_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (!$course_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Course ID required']);
    exit();
}

try {
    // Get course data
    $course->id = $course_id;
    $course_data = $course->readOne();
    
    if (!$course_data) {
        throw new Exception('Course not found');
    }
    
    // Get enrollment data
    $enrollment_stmt = $enrollment->getUserEnrollments($user_id);
    $enrollment_data = null;
    while ($row = $enrollment_stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['course_id'] == $course_id) {
            $enrollment_data = $row;
            break;
        }
    }
    
    if (!$enrollment_data) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not enrolled']);
        exit();
    }
    
    // Get all lessons
    $lessons_stmt = $lesson->readByCourse($course_id);
    $lessons = [];
    while ($row = $lessons_stmt->fetch(PDO::FETCH_ASSOC)) {
        $lessons[] = $row;
    }
    
    // Get progress for each lesson
    $progress_stmt = $user_progress->getCourseProgress($user_id, $course_id);
    $progress_map = [];
    while ($row = $progress_stmt->fetch(PDO::FETCH_ASSOC)) {
        $progress_map[$row['lesson_id']] = $row;
    }
    
    // Calculate statistics
    $completed_lessons = 0;
    $total_xp = 0;
    foreach ($lessons as $l) {
        $lp = $progress_map[$l['id']] ?? null;
        if ($lp && $lp['status'] == 'completed') {
            $completed_lessons++;
            $total_xp += $l['xp_reward'] ?? 10;
        }
    }
    
    $total_lessons = count($lessons);
    $progress_percent = $total_lessons > 0 ? ($completed_lessons / $total_lessons) * 100 : 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'progress_percent' => round($progress_percent, 2),
            'completed_lessons' => $completed_lessons,
            'total_lessons' => $total_lessons,
            'total_xp' => $total_xp
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


<?php
/**
 * API untuk mendapatkan data lesson secara realtime
 * Mengembalikan semua data yang diperlukan untuk halaman lesson
 */

header('Content-Type: application/json');
require_once '../config/config.php';
requireLogin();

require_once '../models/Course.php';
require_once '../models/Lesson.php';
require_once '../models/Enrollment.php';
require_once '../models/UserProgress.php';

$database = new Database();
$db = $database->getConnection();

$course = new Course($db);
$lesson = new Lesson($db);
$enrollment = new Enrollment($db);
$user_progress = new UserProgress($db);

// Get parameters
$course_id = $_GET['course_id'] ?? 0;
$lesson_id = $_GET['lesson_id'] ?? 0;

if (!$course_id || !$lesson_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Course ID and Lesson ID required']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get course data
    $course->id = $course_id;
    $course_data = $course->readOne();
    
    if (!$course_data) {
        throw new Exception('Course not found');
    }
    
    // Check enrollment
    $is_enrolled = $enrollment->isEnrolled($user_id, $course_id);
    if (!$is_enrolled) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not enrolled in this course']);
        exit();
    }
    
    // Get lesson data
    $lesson->id = $lesson_id;
    $lesson->course_id = $course_id;
    $lesson_data = $lesson->readOne();
    
    if (!$lesson_data) {
        throw new Exception('Lesson not found');
    }
    
    // Get all lessons for navigation
    $lessons_stmt = $lesson->readByCourse($course_id);
    $all_lessons = [];
    while ($row = $lessons_stmt->fetch(PDO::FETCH_ASSOC)) {
        $all_lessons[] = $row;
    }
    
    // Get current lesson index
    $current_index = 0;
    foreach ($all_lessons as $index => $l) {
        if ($l['id'] == $lesson_id) {
            $current_index = $index;
            break;
        }
    }
    
    $prev_lesson = $current_index > 0 ? $all_lessons[$current_index - 1] : null;
    $next_lesson = $current_index < count($all_lessons) - 1 ? $all_lessons[$current_index + 1] : null;
    
    // Get user progress
    $progress = $user_progress->getProgress($user_id, $lesson_id);
    if (!$progress) {
        $user_progress->user_id = $user_id;
        $user_progress->course_id = $course_id;
        $user_progress->lesson_id = $lesson_id;
        $user_progress->status = 'not_started';
        $user_progress->create();
        $progress = $user_progress->getProgress($user_id, $lesson_id);
    }
    
    // Determine file extension
    $course_lower = strtolower($course_data['judul_course'] ?? '');
    $main_file = 'main.html';
    
    if (strpos($course_lower, 'python') !== false) {
        $main_file = 'main.py';
    } elseif (strpos($course_lower, 'php') !== false) {
        $main_file = 'main.php';
    } elseif (strpos($course_lower, 'javascript') !== false || strpos($course_lower, 'js') !== false) {
        $main_file = 'main.js';
    } elseif (strpos($course_lower, 'java') !== false && strpos($course_lower, 'javascript') === false) {
        $main_file = 'Main.java';
    } elseif (strpos($course_lower, 'c++') !== false || strpos($course_lower, 'cpp') !== false) {
        $main_file = 'main.cpp';
    } elseif (strpos($course_lower, 'c') !== false && strpos($course_lower, 'css') === false) {
        $main_file = 'main.c';
    }
    
    // Parse instructions
    $instructions = [];
    if ($lesson_data['instruksi']) {
        $parsed = json_decode($lesson_data['instruksi'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
            $instructions = $parsed;
        } else {
            $text = $lesson_data['instruksi'];
            if (preg_match('/index\.html|stylesheet\.css/i', $text)) {
                $parts = preg_split('/(index\.html|stylesheet\.css)/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
                $current_file = $main_file;
                $current_text = '';
                
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (empty($part)) continue;
                    
                    if (preg_match('/\.(html|css)$/i', $part)) {
                        if (!empty($current_text)) {
                            $instructions[] = ['text' => $current_text, 'code' => '', 'file' => $current_file];
                            $current_text = '';
                        }
                        $current_file = $part;
                    } else {
                        $current_text .= ($current_text ? "\n" : '') . $part;
                    }
                }
                
                if (!empty($current_text)) {
                    $instructions[] = ['text' => $current_text, 'code' => '', 'file' => $current_file];
                }
            } else {
                $lines = explode("\n", $text);
                $current_step = ['text' => '', 'code' => '', 'file' => $main_file];
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    if (preg_match('/^```/', $line)) {
                        if (!empty($current_step['text'])) {
                            $instructions[] = $current_step;
                            $current_step = ['text' => '', 'code' => '', 'file' => $main_file];
                        }
                        $in_code = true;
                        $current_code = '';
                    } elseif (preg_match('/^```$/', $line)) {
                        $current_step['code'] = trim($current_code);
                        $in_code = false;
                    } elseif (isset($in_code) && $in_code) {
                        $current_code .= $line . "\n";
                    } else {
                        $current_step['text'] .= ($current_step['text'] ? "\n" : '') . $line;
                    }
                }
                
                if (!empty($current_step['text']) || !empty($current_step['code'])) {
                    $instructions[] = $current_step;
                }
            }
        }
    }
    
    // Return all data
    echo json_encode([
        'success' => true,
        'data' => [
            'course' => $course_data,
            'lesson' => $lesson_data,
            'all_lessons' => $all_lessons,
            'current_index' => $current_index,
            'prev_lesson' => $prev_lesson,
            'next_lesson' => $next_lesson,
            'progress' => $progress,
            'instructions' => $instructions,
            'main_file' => $main_file,
            'lesson_type' => $lesson_data['tipe'] ?? 'theory'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}


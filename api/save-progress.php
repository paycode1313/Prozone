<?php
// API endpoint untuk save progress (AJAX) dengan validasi XP
require_once '../config/config.php';
requireLogin();
// Allow all roles to save progress (students, instructors, admins)
// Instructors and admins may need to test lessons

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

require_once '../models/UserProgress.php';
require_once '../models/Enrollment.php';
require_once '../models/Lesson.php';

$user_progress = new UserProgress($db);
$enrollment = new Enrollment($db);
$lesson = new Lesson($db);

if ($_POST) {
    $user_id = $_SESSION['user_id'];
    $course_id = sanitizeInput($_POST['course_id'] ?? 0);
    $lesson_id = sanitizeInput($_POST['lesson_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? 'in_progress');
    $kode_user = $_POST['kode_user'] ?? '';
    $skor = sanitizeInput($_POST['skor'] ?? 0);
    $waktu_pengerjaan = sanitizeInput($_POST['waktu_pengerjaan'] ?? 0);
    
    if (!$course_id || !$lesson_id) {
        echo json_encode(['success' => false, 'message' => 'Course ID dan Lesson ID diperlukan']);
        exit();
    }
    
    try {
        $db->beginTransaction();
        
        // Check existing progress untuk validasi XP duplicate
        $existing_progress = $user_progress->getProgress($user_id, $lesson_id);
        $xp_earned = 0;
        
        // If completing lesson for first time, calculate XP
        if ($status === 'completed') {
            // Check if already completed (prevent duplicate XP)
            if ($existing_progress && $existing_progress['status'] === 'completed') {
                $xp_earned = 0; // Already completed
            } else {
                // Get lesson XP reward
                $lesson->id = $lesson_id;
                $lesson_data = $lesson->readOne();
                $xp_earned = $lesson_data['xp_reward'] ?? 10;
                
                // Update user XP
                $update_xp = "UPDATE users 
                             SET total_xp = total_xp + :xp_earned,
                                 level = FLOOR((total_xp + :xp_earned) / 100) + 1
                             WHERE id = :user_id";
                $stmt_xp = $db->prepare($update_xp);
                $stmt_xp->bindParam(':xp_earned', $xp_earned);
                $stmt_xp->bindParam(':user_id', $user_id);
                $stmt_xp->execute();
            }
        }
        
        // Update progress
        $user_progress->user_id = $user_id;
        $user_progress->course_id = $course_id;
        $user_progress->lesson_id = $lesson_id;
        $user_progress->status = $status;
        $user_progress->kode_user = $kode_user;
        $user_progress->skor = $skor;
        $user_progress->waktu_pengerjaan = $waktu_pengerjaan;
        $user_progress->xp_earned = $xp_earned;
        
        if (!$user_progress->updateOrCreate()) {
            throw new Exception('Failed to update progress');
        }
        
        // Update enrollment progress
        $enrollment->updateProgress($user_id, $course_id);
        
        // Get updated user data
        $get_user = "SELECT total_xp, level FROM users WHERE id = :user_id";
        $stmt_user = $db->prepare($get_user);
        $stmt_user->bindParam(':user_id', $user_id);
        $stmt_user->execute();
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        $db->commit();
        
        $response = [
            'success' => true,
            'message' => $status == 'completed' 
                ? 'Lesson berhasil diselesaikan!' . ($xp_earned > 0 ? ' +' . $xp_earned . ' XP' : '') 
                : 'Progress berhasil disimpan!'
        ];
        
        if ($xp_earned > 0) {
            $response['xp_earned'] = $xp_earned;
            $response['total_xp'] = $user_data['total_xp'];
            $response['level'] = $user_data['level'];
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}


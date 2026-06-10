<?php
/**
 * API untuk complete lesson dengan validasi XP
 * Mencegah duplicate XP dan memvalidasi completion
 */

require_once '../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../models/UserProgress.php';
require_once '../models/Lesson.php';
require_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();

$user_progress = new UserProgress($db);
$lesson = new Lesson($db);
$user = new User($db);

// Get POST data
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->lesson_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lesson ID required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$lesson_id = $data->lesson_id;
$course_id = $data->course_id ?? null;

try {
    // 1. Validasi lesson exists
    $lesson->id = $lesson_id;
    $lesson_data = $lesson->readOne();
    
    if (!$lesson_data) {
        throw new Exception('Lesson not found');
    }
    
    if (!$course_id) {
        $course_id = $lesson_data['course_id'];
    }
    
    // 2. Check jika sudah completed (prevent duplicate)
    $existing_progress = $user_progress->getProgress($user_id, $lesson_id);
    
    if ($existing_progress && $existing_progress['status'] === 'completed') {
        // Already completed - no additional XP
        echo json_encode([
            'success' => true,
            'message' => 'Lesson already completed',
            'xp_earned' => 0,
            'already_completed' => true,
            'total_xp' => $existing_progress['xp_earned'] ?? 0
        ]);
        exit();
    }
    
    // 3. Calculate XP reward
    $xp_reward = $lesson_data['xp_reward'] ?? 10;
    
    // Bonus XP based on lesson type
    if ($lesson_data['tipe'] === 'practice') {
        $xp_reward = max($xp_reward, 20);
    } elseif ($lesson_data['tipe'] === 'quiz') {
        $xp_reward = max($xp_reward, 30);
    } else {
        $xp_reward = max($xp_reward, 10);
    }
    
    // 4. Update progress status
    $user_progress->user_id = $user_id;
    $user_progress->course_id = $course_id;
    $user_progress->lesson_id = $lesson_id;
    $user_progress->status = 'completed';
    $user_progress->kode_user = $data->kode_user ?? '';
    $user_progress->skor = $data->skor ?? 100;
    $user_progress->waktu_pengerjaan = $data->waktu_pengerjaan ?? 0;
    
    // Start transaction
    $db->beginTransaction();
    
    // Update or create progress
    if ($existing_progress) {
        $update_query = "UPDATE user_progress 
                        SET status = 'completed',
                            kode_user = :kode_user,
                            skor = :skor,
                            waktu_pengerjaan = :waktu_pengerjaan,
                            xp_earned = :xp_earned,
                            completed_at = NOW()
                        WHERE user_id = :user_id AND lesson_id = :lesson_id";
    } else {
        // Check if certificate_code column exists
        try {
            $check_col = $db->query("SHOW COLUMNS FROM user_progress LIKE 'certificate_code'");
            $has_cert_code = $check_col->rowCount() > 0;
        } catch (Exception $e) {
            $has_cert_code = false;
        }
        
        if ($has_cert_code) {
            $update_query = "INSERT INTO user_progress 
                            (user_id, course_id, lesson_id, status, kode_user, skor, waktu_pengerjaan, xp_earned, certificate_code, started_at, completed_at)
                            VALUES (:user_id, :course_id, :lesson_id, 'completed', :kode_user, :skor, :waktu_pengerjaan, :xp_earned, '', NOW(), NOW())";
        } else {
            $update_query = "INSERT INTO user_progress 
                            (user_id, course_id, lesson_id, status, kode_user, skor, waktu_pengerjaan, xp_earned, started_at, completed_at)
                            VALUES (:user_id, :course_id, :lesson_id, 'completed', :kode_user, :skor, :waktu_pengerjaan, :xp_earned, NOW(), NOW())";
        }
    }
    
    $stmt = $db->prepare($update_query);
    $stmt->bindParam(':user_id', $user_id);
    if (!$existing_progress) {
        $stmt->bindParam(':course_id', $course_id);
    }
    $stmt->bindParam(':lesson_id', $lesson_id);
    $stmt->bindParam(':kode_user', $user_progress->kode_user);
    $stmt->bindParam(':skor', $user_progress->skor);
    $stmt->bindParam(':waktu_pengerjaan', $user_progress->waktu_pengerjaan);
    $stmt->bindParam(':xp_earned', $xp_reward);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update progress');
    }
    
    // 5. Update user total XP and level
    $update_user_query = "UPDATE users 
                         SET total_xp = total_xp + :xp_earned,
                             level = FLOOR(total_xp / 100) + 1
                         WHERE id = :user_id";
    
    $stmt_user = $db->prepare($update_user_query);
    $stmt_user->bindParam(':xp_earned', $xp_reward);
    $stmt_user->bindParam(':user_id', $user_id);
    
    if (!$stmt_user->execute()) {
        throw new Exception('Failed to update user XP');
    }
    
    // 6. Get updated user data
    $user->id = $user_id;
    $user_data = $user->readOne();
    
    // 7. Check if course completed
    $check_course_query = "SELECT COUNT(*) as total_lessons,
                          SUM(CASE WHEN up.status = 'completed' THEN 1 ELSE 0 END) as completed_lessons
                          FROM lessons l
                          LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = :user_id
                          WHERE l.course_id = :course_id";
    
    $stmt_course = $db->prepare($check_course_query);
    $stmt_course->bindParam(':user_id', $user_id);
    $stmt_course->bindParam(':course_id', $course_id);
    $stmt_course->execute();
    $course_stats = $stmt_course->fetch(PDO::FETCH_ASSOC);
    
    $course_completed = false;
    if ($course_stats['completed_lessons'] >= $course_stats['total_lessons']) {
        // Update enrollment status
        $update_enrollment = "UPDATE enrollments 
                             SET status = 'completed',
                                 progress_percent = 100,
                                 completed_lessons = :completed_lessons
                             WHERE user_id = :user_id AND course_id = :course_id";
        
        $stmt_enroll = $db->prepare($update_enrollment);
        $stmt_enroll->bindParam(':completed_lessons', $course_stats['completed_lessons']);
        $stmt_enroll->bindParam(':user_id', $user_id);
        $stmt_enroll->bindParam(':course_id', $course_id);
        $stmt_enroll->execute();
        
        $course_completed = true;
        
        // Bonus XP for completing course
        $course_bonus_xp = 50;
        $update_user_bonus = "UPDATE users 
                             SET total_xp = total_xp + :bonus_xp,
                                 level = FLOOR(total_xp / 100) + 1
                             WHERE id = :user_id";
        
        $stmt_bonus = $db->prepare($update_user_bonus);
        $stmt_bonus->bindParam(':bonus_xp', $course_bonus_xp);
        $stmt_bonus->bindParam(':user_id', $user_id);
        $stmt_bonus->execute();
        
        $xp_reward += $course_bonus_xp;
    } else {
        // Update enrollment progress
        $progress_percent = ($course_stats['completed_lessons'] / $course_stats['total_lessons']) * 100;
        
        $update_enrollment = "UPDATE enrollments 
                             SET progress_percent = :progress_percent,
                                 completed_lessons = :completed_lessons
                             WHERE user_id = :user_id AND course_id = :course_id";
        
        $stmt_enroll = $db->prepare($update_enrollment);
        $stmt_enroll->bindParam(':progress_percent', $progress_percent);
        $stmt_enroll->bindParam(':completed_lessons', $course_stats['completed_lessons']);
        $stmt_enroll->bindParam(':user_id', $user_id);
        $stmt_enroll->bindParam(':course_id', $course_id);
        $stmt_enroll->execute();
    }
    
    // Commit transaction
    $db->commit();
    
    // Get final user data
    $user->id = $user_id;
    $final_user_data = $user->readOne();
    
    // 8. Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Lesson completed successfully!',
        'xp_earned' => $xp_reward,
        'total_xp' => $final_user_data['total_xp'],
        'level' => $final_user_data['level'],
        'course_completed' => $course_completed,
        'course_progress' => [
            'completed' => $course_stats['completed_lessons'],
            'total' => $course_stats['total_lessons'],
            'percent' => round(($course_stats['completed_lessons'] / $course_stats['total_lessons']) * 100, 1)
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

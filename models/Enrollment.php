<?php
class Enrollment {
    private $conn;
    private $table_name = "enrollments";

    public $id;
    public $user_id;
    public $course_id;
    public $progress_percent;
    public $completed_lessons;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function enroll() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET user_id=:user_id, course_id=:course_id, status='enrolled'";

        $stmt = $this->conn->prepare($query);

        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->course_id = htmlspecialchars(strip_tags($this->course_id));

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':course_id', $this->course_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function isEnrolled($user_id, $course_id) {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE user_id = :user_id AND course_id = :course_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function getUserEnrollments($user_id) {
        $query = "SELECT e.*, c.judul_course, c.slug, c.thumbnail, c.level, c.total_lessons
                  FROM " . $this->table_name . " e
                  JOIN courses c ON e.course_id = c.id
                  WHERE e.user_id = :user_id
                  ORDER BY e.enrolled_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt;
    }

    public function updateProgress($user_id, $course_id) {
        // Calculate progress
        $query_progress = "SELECT 
                            COUNT(*) as total_lessons,
                            SUM(CASE WHEN up.status = 'completed' THEN 1 ELSE 0 END) as completed
                          FROM lessons l
                          LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = :user_id
                          WHERE l.course_id = :course_id";

        $stmt_progress = $this->conn->prepare($query_progress);
        $stmt_progress->bindParam(':user_id', $user_id);
        $stmt_progress->bindParam(':course_id', $course_id);
        $stmt_progress->execute();
        $progress_data = $stmt_progress->fetch(PDO::FETCH_ASSOC);

        $total = $progress_data['total_lessons'] ?? 0;
        $completed = $progress_data['completed'] ?? 0;
        $progress_percent = $total > 0 ? ($completed / $total) * 100 : 0;

        // Update enrollment
        $query = "UPDATE " . $this->table_name . "
                  SET progress_percent = :progress_percent,
                      completed_lessons = :completed_lessons,
                      status = CASE 
                        WHEN :progress_percent >= 100 THEN 'completed'
                        ELSE 'enrolled'
                      END,
                      completed_at = CASE 
                        WHEN :progress_percent >= 100 AND completed_at IS NULL THEN NOW()
                        ELSE completed_at
                      END
                  WHERE user_id = :user_id AND course_id = :course_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':progress_percent', $progress_percent);
        $stmt->bindParam(':completed_lessons', $completed);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':course_id', $course_id);

        if ($stmt->execute()) {
            // If course completed, award course XP and check for certificate
            if ($progress_percent >= 100) {
                $this->awardCourseXP($user_id, $course_id);
                $this->createCertificate($user_id, $course_id);
                $this->checkCourseAchievements($user_id);
            }
            return true;
        }
        return false;
    }

    private function checkCourseAchievements($user_id) {
        require_once 'Achievement.php';
        $achievement = new Achievement($this->conn);
        
        // Get completed courses count
        $query = "SELECT COUNT(*) as count FROM enrollments 
                 WHERE user_id = :user_id AND status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $completed_courses = $result['count'] ?? 0;
        
        // Check course completion achievements
        if ($completed_courses >= 1) {
            $achievement->kode_achievement = 'first_course';
            $achievement->checkAndAward($user_id, 'first_course');
        }
        if ($completed_courses >= 5) {
            $achievement->kode_achievement = 'five_courses';
            $achievement->checkAndAward($user_id, 'five_courses');
        }
        if ($completed_courses >= 10) {
            $achievement->kode_achievement = 'ten_courses';
            $achievement->checkAndAward($user_id, 'ten_courses');
        }
    }

    private function awardCourseXP($user_id, $course_id) {
        // Get course XP reward
        $query_course = "SELECT xp_reward FROM courses WHERE id = :course_id";
        $stmt_course = $this->conn->prepare($query_course);
        $stmt_course->bindParam(':course_id', $course_id);
        $stmt_course->execute();
        
        if ($stmt_course->rowCount() > 0) {
            $course_data = $stmt_course->fetch(PDO::FETCH_ASSOC);
            $course_xp = $course_data['xp_reward'] ?? 100;
            
            // Check if already awarded
            $query_check = "SELECT completed_at FROM enrollments 
                           WHERE user_id = :user_id AND course_id = :course_id 
                           AND completed_at IS NOT NULL";
            $stmt_check = $this->conn->prepare($query_check);
            $stmt_check->bindParam(':user_id', $user_id);
            $stmt_check->bindParam(':course_id', $course_id);
            $stmt_check->execute();
            
            if ($stmt_check->rowCount() == 0) {
                // Award XP
                $query_update = "UPDATE users 
                                SET total_xp = total_xp + :xp,
                                    level = FLOOR((total_xp + :xp) / 100) + 1
                                WHERE id = :user_id";
                $stmt_update = $this->conn->prepare($query_update);
                $stmt_update->bindParam(':xp', $course_xp);
                $stmt_update->bindParam(':user_id', $user_id);
                $stmt_update->execute();
            }
        }
    }

    private function createCertificate($user_id, $course_id) {
        // Check if certificate already exists
        $query_check = "SELECT id FROM certificates 
                       WHERE user_id = :user_id AND course_id = :course_id";
        $stmt_check = $this->conn->prepare($query_check);
        $stmt_check->bindParam(':user_id', $user_id);
        $stmt_check->bindParam(':course_id', $course_id);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() == 0) {
            // Generate unique certificate code
            $certificate_code = 'CERT-' . strtoupper(uniqid()) . '-' . $user_id . '-' . $course_id;
            
            // Create certificate
            try {
                $query_cert = "INSERT INTO certificates (user_id, course_id, certificate_code, issued_at)
                              VALUES (:user_id, :course_id, :certificate_code, NOW())";
                $stmt_cert = $this->conn->prepare($query_cert);
                $stmt_cert->bindParam(':user_id', $user_id);
                $stmt_cert->bindParam(':course_id', $course_id);
                $stmt_cert->bindParam(':certificate_code', $certificate_code);
                $stmt_cert->execute();
            } catch (PDOException $e) {
                // If certificate_code column doesn't exist, try without it
                if (strpos($e->getMessage(), 'certificate_code') !== false) {
                    $query_cert = "INSERT INTO certificates (user_id, course_id, issued_at)
                                  VALUES (:user_id, :course_id, NOW())";
                    $stmt_cert = $this->conn->prepare($query_cert);
                    $stmt_cert->bindParam(':user_id', $user_id);
                    $stmt_cert->bindParam(':course_id', $course_id);
                    $stmt_cert->execute();
                } else {
                    throw $e;
                }
            }
        }
    }
}
?>


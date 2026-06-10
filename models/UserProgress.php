<?php
class UserProgress {
    private $conn;
    private $table_name = "user_progress";

    public $id;
    public $user_id;
    public $course_id;
    public $lesson_id;
    public $status;
    public $kode_user;
    public $skor;
    public $waktu_pengerjaan;
    public $xp_earned;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $this->user_id = (int)$this->user_id;
        $this->course_id = (int)$this->course_id;
        $this->lesson_id = (int)$this->lesson_id;

        // Always try with certificate_code='' first to handle databases that have this column
        // This prevents "Field 'certificate_code' doesn't have a default value" error
        $query = "INSERT INTO " . $this->table_name . "
                  SET user_id=:user_id, course_id=:course_id, lesson_id=:lesson_id,
                      status='in_progress', started_at=NOW(), certificate_code=''";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
        $stmt->bindParam(':course_id', $this->course_id, PDO::PARAM_INT);
        $stmt->bindParam(':lesson_id', $this->lesson_id, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            $error_msg = strtolower($e->getMessage());
            $error_code = $e->getCode();
            
            // Error 1364 = Field doesn't have a default value (shouldn't happen with certificate_code='')
            // Error about unknown column = column doesn't exist
            if (strpos($error_msg, 'certificate_code') !== false) {
                if (strpos($error_msg, "doesn't exist") !== false || 
                    strpos($error_msg, 'unknown column') !== false ||
                    $error_code == 42) {
                    // Column doesn't exist, try without it
                    $query = "INSERT INTO " . $this->table_name . "
                              SET user_id=:user_id, course_id=:course_id, lesson_id=:lesson_id,
                                  status='in_progress', started_at=NOW()";
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
                    $stmt->bindParam(':course_id', $this->course_id, PDO::PARAM_INT);
                    $stmt->bindParam(':lesson_id', $this->lesson_id, PDO::PARAM_INT);
                    
                    try {
                        if ($stmt->execute()) {
                            return true;
                        }
                    } catch (PDOException $e2) {
                        // If still fails, throw the original error
                        throw $e;
                    }
                } else {
                    // Other certificate_code error (like duplicate, etc) - re-throw
                    throw $e;
                }
            } else {
                // Different error, re-throw
                throw $e;
            }
        }
        
        return false;
    }

    public function updateProgress() {
        // Get lesson XP reward if completing
        $xp_earned = 0;
        if ($this->status == 'completed') {
            $query_lesson = "SELECT xp_reward FROM lessons WHERE id = :lesson_id";
            $stmt_lesson = $this->conn->prepare($query_lesson);
            $stmt_lesson->bindParam(':lesson_id', $this->lesson_id);
            $stmt_lesson->execute();
            if ($stmt_lesson->rowCount() > 0) {
                $lesson_data = $stmt_lesson->fetch(PDO::FETCH_ASSOC);
                $xp_earned = $lesson_data['xp_reward'] ?? 10;
                
                // Check if already earned XP (prevent duplicate)
                $query_check = "SELECT xp_earned FROM " . $this->table_name . " 
                               WHERE user_id = :user_id AND lesson_id = :lesson_id";
                $stmt_check = $this->conn->prepare($query_check);
                $stmt_check->bindParam(':user_id', $this->user_id);
                $stmt_check->bindParam(':lesson_id', $this->lesson_id);
                $stmt_check->execute();
                if ($stmt_check->rowCount() > 0) {
                    $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);
                    if ($existing['xp_earned'] > 0) {
                        $xp_earned = 0; // Already earned
                    }
                }
            }
        }

        $query = "UPDATE " . $this->table_name . "
                  SET status=:status, kode_user=:kode_user, skor=:skor,
                      waktu_pengerjaan=:waktu_pengerjaan, xp_earned=:xp_earned,
                      completed_at = CASE 
                        WHEN :status = 'completed' THEN NOW()
                        ELSE completed_at
                      END
                  WHERE user_id=:user_id AND lesson_id=:lesson_id";

        $stmt = $this->conn->prepare($query);

        $this->status = htmlspecialchars(strip_tags($this->status ?? 'in_progress'));
        $this->kode_user = $this->kode_user ?? '';
        $this->skor = htmlspecialchars(strip_tags($this->skor ?? '0'));
        $this->waktu_pengerjaan = !empty($this->waktu_pengerjaan) ? (int)$this->waktu_pengerjaan : 0;
        $this->xp_earned = $xp_earned;

        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':kode_user', $this->kode_user);
        $stmt->bindParam(':skor', $this->skor);
        $stmt->bindParam(':waktu_pengerjaan', $this->waktu_pengerjaan, PDO::PARAM_INT);
        $stmt->bindParam(':xp_earned', $this->xp_earned);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':lesson_id', $this->lesson_id);

        if ($stmt->execute()) {
            // Update user total XP and level
            if ($xp_earned > 0) {
                $this->updateUserXP($this->user_id, $xp_earned);
            }
            
            // Check achievements when completing lesson
            if ($this->status == 'completed') {
                $this->checkAchievements($this->user_id);
            }
            
            return true;
        }
        return false;
    }

    public function updateOrCreate() {
        // Check if progress exists
        $existing = $this->getProgress($this->user_id, $this->lesson_id);
        
        if ($existing) {
            return $this->updateProgress();
        } else {
            return $this->create();
        }
    }

    private function checkAchievements($user_id) {
        require_once 'Achievement.php';
        $achievement = new Achievement($this->conn);
        
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
        
        $stmt_stats = $this->conn->prepare($query_stats);
        $stmt_stats->bindParam(':user_id', $user_id);
        $stmt_stats->execute();
        $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
        
        // Check various achievements
        $achievements_to_check = [
            'first_lesson' => $stats['total_lessons_completed'] >= 1,
            'ten_lessons' => $stats['total_lessons_completed'] >= 10,
            'fifty_lessons' => $stats['total_lessons_completed'] >= 50,
            'hundred_lessons' => $stats['total_lessons_completed'] >= 100,
            'first_course' => $stats['total_courses_completed'] >= 1,
            'five_courses' => $stats['total_courses_completed'] >= 5,
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
                $achievement->checkAndAward($user_id, $code);
            }
        }
    }

    private function updateUserXP($user_id, $xp_earned) {
        // Update user total XP
        $query_update = "UPDATE users 
                        SET total_xp = total_xp + :xp_earned,
                            level = FLOOR(SQRT((total_xp + :xp_earned) / 100))
                        WHERE id = :user_id";
        $stmt_update = $this->conn->prepare($query_update);
        $stmt_update->bindParam(':xp_earned', $xp_earned);
        $stmt_update->bindParam(':user_id', $user_id);
        $stmt_update->execute();

        // Update clan XP if user is in a clan
        $this->updateClanXP($user_id, $xp_earned);

        // Update leaderboard
        $this->updateLeaderboard($user_id);
    }

    private function updateClanXP($user_id, $xp_earned) {
        // Get user's clan
        $query_clan = "SELECT clan_id FROM clan_members WHERE user_id = :user_id LIMIT 1";
        $stmt_clan = $this->conn->prepare($query_clan);
        $stmt_clan->bindParam(':user_id', $user_id);
        $stmt_clan->execute();
        
        if ($stmt_clan->rowCount() > 0) {
            $clan_data = $stmt_clan->fetch(PDO::FETCH_ASSOC);
            $clan_id = $clan_data['clan_id'];
            
            // Update clan total XP
            $query_update = "UPDATE clans SET total_xp = total_xp + :xp_earned WHERE id = :clan_id";
            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->bindParam(':xp_earned', $xp_earned);
            $stmt_update->bindParam(':clan_id', $clan_id);
            $stmt_update->execute();
            
            // Update member XP contribution
            $query_contrib = "UPDATE clan_members 
                             SET xp_contribution = xp_contribution + :xp_earned 
                             WHERE clan_id = :clan_id AND user_id = :user_id";
            $stmt_contrib = $this->conn->prepare($query_contrib);
            $stmt_contrib->bindParam(':xp_earned', $xp_earned);
            $stmt_contrib->bindParam(':clan_id', $clan_id);
            $stmt_contrib->bindParam(':user_id', $user_id);
            $stmt_contrib->execute();
            
            // Update clan leaderboard
            $this->updateClanLeaderboard($clan_id);
        }
    }

    private function updateClanLeaderboard($clan_id) {
        // Get clan stats
        $query_stats = "SELECT 
                        c.total_xp,
                        COUNT(DISTINCT cm.user_id) as total_members,
                        AVG(u.total_xp) as average_xp
                       FROM clans c
                       LEFT JOIN clan_members cm ON c.id = cm.clan_id
                       LEFT JOIN users u ON cm.user_id = u.id
                       WHERE c.id = :clan_id
                       GROUP BY c.id";
        
        $stmt_stats = $this->conn->prepare($query_stats);
        $stmt_stats->bindParam(':clan_id', $clan_id);
        $stmt_stats->execute();
        $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
        
        if ($stats) {
            // Update or insert clan leaderboard
            $query_leaderboard = "INSERT INTO leaderboard_clan (clan_id, total_xp, total_members, average_xp)
                                 VALUES (:clan_id, :total_xp, :total_members, :average_xp)
                                 ON DUPLICATE KEY UPDATE
                                 total_xp = :total_xp,
                                 total_members = :total_members,
                                 average_xp = :average_xp,
                                 last_updated = NOW()";
            
            $stmt_leaderboard = $this->conn->prepare($query_leaderboard);
            $stmt_leaderboard->bindParam(':clan_id', $clan_id);
            $stmt_leaderboard->bindParam(':total_xp', $stats['total_xp']);
            $stmt_leaderboard->bindParam(':total_members', $stats['total_members']);
            $stmt_leaderboard->bindParam(':average_xp', $stats['average_xp']);
            $stmt_leaderboard->execute();
        }
    }

    private function updateLeaderboard($user_id) {
        // Get user stats
        $query_stats = "SELECT 
                        u.total_xp,
                        COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.course_id END) as completed_courses,
                        COUNT(DISTINCT CASE WHEN up.status = 'completed' THEN up.lesson_id END) as completed_lessons
                       FROM users u
                       LEFT JOIN enrollments e ON u.id = e.user_id
                       LEFT JOIN user_progress up ON u.id = up.user_id
                       WHERE u.id = :user_id
                       GROUP BY u.id";
        
        $stmt_stats = $this->conn->prepare($query_stats);
        $stmt_stats->bindParam(':user_id', $user_id);
        $stmt_stats->execute();
        $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

        // Update or insert leaderboard
        $query_leaderboard = "INSERT INTO leaderboard_solo (user_id, total_xp, completed_courses, completed_lessons)
                             VALUES (:user_id, :total_xp, :completed_courses, :completed_lessons)
                             ON DUPLICATE KEY UPDATE
                             total_xp = :total_xp,
                             completed_courses = :completed_courses,
                             completed_lessons = :completed_lessons,
                             last_updated = NOW()";
        
        $stmt_leaderboard = $this->conn->prepare($query_leaderboard);
        $stmt_leaderboard->bindParam(':user_id', $user_id);
        $stmt_leaderboard->bindParam(':total_xp', $stats['total_xp']);
        $stmt_leaderboard->bindParam(':completed_courses', $stats['completed_courses']);
        $stmt_leaderboard->bindParam(':completed_lessons', $stats['completed_lessons']);
        $stmt_leaderboard->execute();
    }

    public function getProgress($user_id, $lesson_id) {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE user_id = :user_id AND lesson_id = :lesson_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':lesson_id', $lesson_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function getCourseProgress($user_id, $course_id) {
        $query = "SELECT up.*, l.judul_lesson, l.urutan, l.slug
                  FROM " . $this->table_name . " up
                  JOIN lessons l ON up.lesson_id = l.id
                  WHERE up.user_id = :user_id AND up.course_id = :course_id
                  ORDER BY l.urutan ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->execute();

        return $stmt;
    }
}
?>


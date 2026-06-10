<?php
class Achievement {
    private $conn;
    private $table_name = "achievements";

    public $id;
    public $kode_achievement;
    public $nama_achievement;
    public $deskripsi;
    public $icon;
    public $xp_reward;
    public $tipe;
    public $requirement_value;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE is_active = 1
                  ORDER BY xp_reward DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE id = :id OR kode_achievement = :kode";

        $stmt = $this->conn->prepare($query);
        $id = is_numeric($this->id) ? $this->id : 0;
        $kode = $this->kode_achievement ?? '';
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':kode', $kode);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function getUserAchievements($user_id) {
        $query = "SELECT a.*, ua.earned_at
                  FROM " . $this->table_name . " a
                  LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = :user_id
                  WHERE a.is_active = 1
                  ORDER BY ua.earned_at DESC, a.xp_reward DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt;
    }

    public function checkAndAward($user_id, $achievement_code) {
        // Get achievement by code
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE kode_achievement = :code AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $achievement_code);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            return false; // Achievement not found
        }
        
        $achievement = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if user already has this achievement
        $check_query = "SELECT * FROM user_achievements 
                        WHERE user_id = :user_id AND achievement_id = :achievement_id";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->bindParam(':achievement_id', $achievement['id']);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            return false; // Already earned
        }

        // Award achievement
        $award_query = "INSERT INTO user_achievements (user_id, achievement_id, earned_at)
                        VALUES (:user_id, :achievement_id, NOW())";
        $award_stmt = $this->conn->prepare($award_query);
        $award_stmt->bindParam(':user_id', $user_id);
        $award_stmt->bindParam(':achievement_id', $achievement['id']);

        if ($award_stmt->execute()) {
            // Add XP to user and update level
            $xp_reward = $achievement['xp_reward'] ?? 0;
            if ($xp_reward > 0) {
                $xp_query = "UPDATE users 
                            SET total_xp = total_xp + :xp,
                                level = FLOOR(SQRT((total_xp + :xp) / 100))
                            WHERE id = :user_id";
                $xp_stmt = $this->conn->prepare($xp_query);
                $xp_stmt->bindParam(':xp', $xp_reward);
                $xp_stmt->bindParam(':user_id', $user_id);
                $xp_stmt->execute();
            }

            return true;
        }

        return false;
    }
}
?>


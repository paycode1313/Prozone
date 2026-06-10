<?php
class Leaderboard {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getSoloLeaderboard($limit = 100) {
        $query = "SELECT u.id, u.username, u.nama_lengkap, u.avatar, u.total_xp, u.level,
                         COALESCE(lb.`rank`, 0) as `rank`,
                         COALESCE(lb.completed_courses, 0) as completed_courses,
                         COALESCE(lb.completed_lessons, 0) as completed_lessons
                  FROM users u
                  LEFT JOIN leaderboard_solo lb ON u.id = lb.user_id
                  WHERE u.role = 'student'
                  ORDER BY u.total_xp DESC, u.level DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    public function getClanLeaderboard($limit = 50) {
        $query = "SELECT c.id, c.nama_clan, c.slug, c.avatar, c.total_xp, c.total_members,
                         COALESCE(lb.`rank`, 0) as `rank`,
                         COALESCE(lb.average_xp, 0) as average_xp
                  FROM clans c
                  LEFT JOIN leaderboard_clan lb ON c.id = lb.clan_id
                  WHERE c.is_public = 1
                  ORDER BY c.total_xp DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    public function updateSoloRanking() {
        $query = "UPDATE leaderboard_solo lb
                  JOIN (
                      SELECT user_id, 
                             ROW_NUMBER() OVER (ORDER BY total_xp DESC, level DESC) as new_rank
                      FROM users
                      WHERE role = 'student'
                  ) ranked ON lb.user_id = ranked.user_id
                  SET lb.`rank` = ranked.new_rank";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }

    public function updateClanRanking() {
        $query = "UPDATE leaderboard_clan lb
                  JOIN (
                      SELECT clan_id,
                             ROW_NUMBER() OVER (ORDER BY total_xp DESC) as new_rank
                      FROM clans
                      WHERE is_public = 1
                  ) ranked ON lb.clan_id = ranked.clan_id
                  SET lb.`rank` = ranked.new_rank";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }

    public function getUserRank($user_id) {
        $query = "SELECT `rank` FROM leaderboard_solo WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['rank'];
        }
        return 0;
    }
}
?>


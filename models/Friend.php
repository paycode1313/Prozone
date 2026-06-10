<?php
class Friend {
    private $conn;
    private $table_name = "friends";

    public $id;
    public $user_id;
    public $friend_id;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Send friend request
    public function sendRequest($user_id, $friend_id) {
        // Check if already friends or request exists
        if ($this->checkFriendship($user_id, $friend_id)) {
            return ['success' => false, 'message' => 'Permintaan pertemanan sudah ada'];
        }

        $query = "INSERT INTO " . $this->table_name . " (user_id, friend_id, status) VALUES (:user_id, :friend_id, 'pending')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':friend_id', $friend_id);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Permintaan pertemanan terkirim'];
        }
        return ['success' => false, 'message' => 'Gagal mengirim permintaan'];
    }

    // Accept friend request
    public function acceptRequest($user_id, $friend_id) {
        $query = "UPDATE " . $this->table_name . " SET status = 'accepted' WHERE user_id = :friend_id AND friend_id = :user_id AND status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':friend_id', $friend_id);

        if ($stmt->execute() && $stmt->rowCount() > 0) {
            // Create reverse friendship
            $reverse_query = "INSERT INTO " . $this->table_name . " (user_id, friend_id, status) VALUES (:user_id, :friend_id, 'accepted') ON DUPLICATE KEY UPDATE status = 'accepted'";
            $reverse_stmt = $this->conn->prepare($reverse_query);
            $reverse_stmt->bindParam(':user_id', $user_id);
            $reverse_stmt->bindParam(':friend_id', $friend_id);
            $reverse_stmt->execute();

            return ['success' => true, 'message' => 'Permintaan pertemanan diterima'];
        }
        return ['success' => false, 'message' => 'Gagal menerima permintaan'];
    }

    // Reject/Cancel friend request
    public function rejectRequest($user_id, $friend_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE (user_id = :friend_id AND friend_id = :user_id) OR (user_id = :user_id2 AND friend_id = :friend_id2)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':friend_id', $friend_id);
        $stmt->bindParam(':user_id2', $user_id);
        $stmt->bindParam(':friend_id2', $friend_id);

        return $stmt->execute();
    }

    // Remove friend
    public function removeFriend($user_id, $friend_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE (user_id = :user_id AND friend_id = :friend_id) OR (user_id = :friend_id2 AND friend_id = :user_id2)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':friend_id', $friend_id);
        $stmt->bindParam(':user_id2', $user_id);
        $stmt->bindParam(':friend_id2', $friend_id);

        return $stmt->execute();
    }

    // Check if friendship exists
    public function checkFriendship($user_id, $friend_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE (user_id = :user_id AND friend_id = :friend_id) OR (user_id = :friend_id2 AND friend_id = :user_id2)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':friend_id', $friend_id);
        $stmt->bindParam(':user_id2', $user_id);
        $stmt->bindParam(':friend_id2', $friend_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Get friendship status
    public function getFriendshipStatus($user_id, $friend_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE (user_id = :user_id AND friend_id = :friend_id) OR (user_id = :friend_id2 AND friend_id = :user_id2)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':friend_id', $friend_id);
        $stmt->bindParam(':user_id2', $user_id);
        $stmt->bindParam(':friend_id2', $friend_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    // Get all friends
    public function getFriends($user_id) {
        $query = "SELECT u.id, u.username, u.nama_lengkap, u.avatar, u.total_xp, u.is_online, u.last_seen
                  FROM " . $this->table_name . " f
                  JOIN users u ON (f.friend_id = u.id)
                  WHERE f.user_id = :user_id AND f.status = 'accepted'
                  ORDER BY u.is_online DESC, u.nama_lengkap ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt;
    }

    // Get pending requests (received)
    public function getPendingRequests($user_id) {
        $query = "SELECT f.*, u.id as sender_id, u.username, u.nama_lengkap, u.avatar, u.total_xp
                  FROM " . $this->table_name . " f
                  JOIN users u ON f.user_id = u.id
                  WHERE f.friend_id = :user_id AND f.status = 'pending'
                  ORDER BY f.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt;
    }

    // Get sent requests
    public function getSentRequests($user_id) {
        $query = "SELECT f.*, u.id as receiver_id, u.username, u.nama_lengkap, u.avatar
                  FROM " . $this->table_name . " f
                  JOIN users u ON f.friend_id = u.id
                  WHERE f.user_id = :user_id AND f.status = 'pending'
                  ORDER BY f.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt;
    }

    // Search users to add as friend
    public function searchUsers($user_id, $search_term) {
        $query = "SELECT u.id, u.username, u.nama_lengkap, u.avatar, u.total_xp, u.is_online,
                         (SELECT status FROM friends WHERE (user_id = :user_id AND friend_id = u.id) OR (user_id = u.id AND friend_id = :user_id2) LIMIT 1) as friendship_status
                  FROM users u
                  WHERE u.id != :user_id3 
                  AND (u.username LIKE :search OR u.nama_lengkap LIKE :search2)
                  AND u.role = 'student'
                  ORDER BY u.nama_lengkap ASC
                  LIMIT 20";
        $stmt = $this->conn->prepare($query);
        $search = "%$search_term%";
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':user_id2', $user_id);
        $stmt->bindParam(':user_id3', $user_id);
        $stmt->bindParam(':search', $search);
        $stmt->bindParam(':search2', $search);
        $stmt->execute();

        return $stmt;
    }

    // Update online status
    public static function updateOnlineStatus($db, $user_id, $is_online = true) {
        $query = "UPDATE users SET is_online = :is_online, last_seen = NOW() WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $online = $is_online ? 1 : 0;
        $stmt->bindParam(':is_online', $online);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }

    // Get online friends count
    public function getOnlineFriendsCount($user_id) {
        $query = "SELECT COUNT(*) as count
                  FROM " . $this->table_name . " f
                  JOIN users u ON f.friend_id = u.id
                  WHERE f.user_id = :user_id AND f.status = 'accepted' AND u.is_online = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    // Get all users that can be added as friends (suggestions)
    public function getSuggestedUsers($user_id, $limit = 10) {
        $query = "SELECT u.id, u.username, u.nama_lengkap, u.avatar, u.total_xp, u.level, u.is_online, u.last_seen,
                         (SELECT status FROM friends WHERE (user_id = :user_id AND friend_id = u.id) OR (user_id = u.id AND friend_id = :user_id2) LIMIT 1) as friendship_status
                  FROM users u
                  WHERE u.id != :user_id3 
                  AND u.role = 'student'
                  ORDER BY u.is_online DESC, u.total_xp DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':user_id2', $user_id);
        $stmt->bindParam(':user_id3', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }
}
?>

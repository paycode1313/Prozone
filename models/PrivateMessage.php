<?php
class PrivateMessage {
    private $conn;
    private $table_name = "private_messages";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Send message
    public function send($sender_id, $receiver_id, $message) {
        $query = "INSERT INTO " . $this->table_name . " (sender_id, receiver_id, message) VALUES (:sender_id, :receiver_id, :message)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sender_id', $sender_id);
        $stmt->bindParam(':receiver_id', $receiver_id);
        $message = htmlspecialchars(strip_tags($message));
        $stmt->bindParam(':message', $message);

        return $stmt->execute();
    }

    // Get conversation between two users
    public function getConversation($user_id, $friend_id, $limit = 50) {
        $query = "SELECT pm.*, 
                         sender.nama_lengkap as sender_name, sender.avatar as sender_avatar,
                         receiver.nama_lengkap as receiver_name
                  FROM " . $this->table_name . " pm
                  JOIN users sender ON pm.sender_id = sender.id
                  JOIN users receiver ON pm.receiver_id = receiver.id
                  WHERE (pm.sender_id = :user_id AND pm.receiver_id = :friend_id)
                     OR (pm.sender_id = :friend_id2 AND pm.receiver_id = :user_id2)
                  ORDER BY pm.created_at DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':friend_id', $friend_id);
        $stmt->bindParam(':user_id2', $user_id);
        $stmt->bindParam(':friend_id2', $friend_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Get new messages since last_id
    public function getNewMessages($user_id, $friend_id, $last_id) {
        $query = "SELECT pm.*, 
                         sender.nama_lengkap as sender_name, sender.avatar as sender_avatar
                  FROM " . $this->table_name . " pm
                  JOIN users sender ON pm.sender_id = sender.id
                  WHERE ((pm.sender_id = :user_id AND pm.receiver_id = :friend_id)
                     OR (pm.sender_id = :friend_id2 AND pm.receiver_id = :user_id2))
                  AND pm.id > :last_id
                  ORDER BY pm.created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':friend_id', $friend_id);
        $stmt->bindParam(':user_id2', $user_id);
        $stmt->bindParam(':friend_id2', $friend_id);
        $stmt->bindParam(':last_id', $last_id);
        $stmt->execute();

        return $stmt;
    }

    // Mark messages as read
    public function markAsRead($user_id, $sender_id) {
        $query = "UPDATE " . $this->table_name . " SET is_read = 1 WHERE receiver_id = :user_id AND sender_id = :sender_id AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':sender_id', $sender_id);
        return $stmt->execute();
    }

    // Get unread count from specific user
    public function getUnreadCount($user_id, $sender_id = null) {
        if ($sender_id) {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE receiver_id = :user_id AND sender_id = :sender_id AND is_read = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':sender_id', $sender_id);
        } else {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE receiver_id = :user_id AND is_read = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    // Get recent conversations
    public function getRecentConversations($user_id) {
        $query = "SELECT 
                    CASE 
                        WHEN pm.sender_id = :user_id THEN pm.receiver_id 
                        ELSE pm.sender_id 
                    END as friend_id,
                    u.nama_lengkap, u.avatar, u.is_online, u.last_seen,
                    pm.message as last_message,
                    pm.created_at as last_message_time,
                    (SELECT COUNT(*) FROM private_messages WHERE receiver_id = :user_id2 AND sender_id = u.id AND is_read = 0) as unread_count
                  FROM " . $this->table_name . " pm
                  JOIN users u ON (CASE WHEN pm.sender_id = :user_id3 THEN pm.receiver_id ELSE pm.sender_id END) = u.id
                  WHERE pm.sender_id = :user_id4 OR pm.receiver_id = :user_id5
                  AND pm.id IN (
                      SELECT MAX(id) FROM private_messages 
                      WHERE sender_id = :user_id6 OR receiver_id = :user_id7
                      GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)
                  )
                  ORDER BY pm.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':user_id2', $user_id);
        $stmt->bindParam(':user_id3', $user_id);
        $stmt->bindParam(':user_id4', $user_id);
        $stmt->bindParam(':user_id5', $user_id);
        $stmt->bindParam(':user_id6', $user_id);
        $stmt->bindParam(':user_id7', $user_id);
        $stmt->execute();

        return $stmt;
    }
}
?>

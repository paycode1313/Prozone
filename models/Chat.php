<?php
class Chat {
    private $conn;
    private $table_name = "chat_messages";

    public $id;
    public $clan_id;
    public $user_id;
    public $message;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET clan_id=:clan_id, user_id=:user_id, message=:message";

        $stmt = $this->conn->prepare($query);

        $this->clan_id = htmlspecialchars(strip_tags($this->clan_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->message = htmlspecialchars(strip_tags($this->message));

        $stmt->bindParam(':clan_id', $this->clan_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':message', $this->message);

        return $stmt->execute();
    }

    public function getMessages($clan_id, $limit = 50, $last_id = 0) {
        if ($last_id > 0) {
            $query = "SELECT cm.*, u.username, u.nama_lengkap, u.avatar
                      FROM " . $this->table_name . " cm
                      JOIN users u ON cm.user_id = u.id
                      WHERE cm.clan_id = :clan_id AND cm.id > :last_id
                      ORDER BY cm.created_at ASC
                      LIMIT :limit";
        } else {
            $query = "SELECT cm.*, u.username, u.nama_lengkap, u.avatar
                      FROM " . $this->table_name . " cm
                      JOIN users u ON cm.user_id = u.id
                      WHERE cm.clan_id = :clan_id
                      ORDER BY cm.created_at DESC
                      LIMIT :limit";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':clan_id', $clan_id);
        if ($last_id > 0) {
            $stmt->bindParam(':last_id', $last_id, PDO::PARAM_INT);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    public function getLatestMessages($clan_id, $last_message_id = 0) {
        $query = "SELECT cm.*, u.username, u.nama_lengkap, u.avatar
                  FROM " . $this->table_name . " cm
                  JOIN users u ON cm.user_id = u.id
                  WHERE cm.clan_id = :clan_id AND cm.id > :last_message_id
                  ORDER BY cm.created_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':clan_id', $clan_id);
        $stmt->bindParam(':last_message_id', $last_message_id);
        $stmt->execute();

        return $stmt;
    }
}
?>


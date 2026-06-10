<?php
class Comment {
    private $conn;
    private $table_name = "comments";

    public $id;
    public $lesson_id;
    public $user_id;
    public $parent_id;
    public $content;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET lesson_id=:lesson_id, user_id=:user_id, 
                      parent_id=:parent_id, content=:content";

        $stmt = $this->conn->prepare($query);

        $this->lesson_id = htmlspecialchars(strip_tags($this->lesson_id));
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->parent_id = !empty($this->parent_id) ? htmlspecialchars(strip_tags($this->parent_id)) : null;
        $this->content = htmlspecialchars(strip_tags($this->content));

        $stmt->bindParam(':lesson_id', $this->lesson_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':parent_id', $this->parent_id);
        $stmt->bindParam(':content', $this->content);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getByLesson($lesson_id) {
        $query = "SELECT c.*, u.nama_lengkap, u.avatar, u.role
                  FROM " . $this->table_name . " c
                  JOIN users u ON c.user_id = u.id
                  WHERE c.lesson_id = :lesson_id
                  ORDER BY c.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':lesson_id', $lesson_id);
        $stmt->execute();

        return $stmt;
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>
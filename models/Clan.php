<?php
class Clan {
    private $conn;
    private $table_name = "clans";

    public $id;
    public $nama_clan;
    public $slug;
    public $deskripsi;
    public $avatar;
    public $leader_id;
    public $total_members;
    public $total_xp;
    public $is_public;
    public $max_members;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET nama_clan=:nama_clan, slug=:slug, deskripsi=:deskripsi,
                      avatar=:avatar, leader_id=:leader_id, is_public=:is_public,
                      max_members=:max_members";

        $stmt = $this->conn->prepare($query);

        $this->nama_clan = htmlspecialchars(strip_tags($this->nama_clan));
        $this->slug = $this->generateSlug($this->nama_clan);
        $this->deskripsi = htmlspecialchars(strip_tags($this->deskripsi));
        $this->avatar = !empty($this->avatar) ? htmlspecialchars(strip_tags($this->avatar)) : null;
        $this->leader_id = htmlspecialchars(strip_tags($this->leader_id));
        $this->is_public = isset($this->is_public) ? 1 : 0;
        $this->max_members = htmlspecialchars(strip_tags($this->max_members ?? 50));

        $stmt->bindParam(':nama_clan', $this->nama_clan);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':deskripsi', $this->deskripsi);
        $stmt->bindParam(':avatar', $this->avatar);
        $stmt->bindParam(':leader_id', $this->leader_id);
        $stmt->bindParam(':is_public', $this->is_public);
        $stmt->bindParam(':max_members', $this->max_members);

        if ($stmt->execute()) {
            $clan_id = $this->conn->lastInsertId();
            // Add leader as member
            $this->addMember($clan_id, $this->leader_id, 'leader');
            
            // Check achievement for creating clan
            require_once 'Achievement.php';
            $achievement = new Achievement($this->conn);
            $achievement->kode_achievement = 'clan_leader';
            $achievement->checkAndAward($this->leader_id, 'clan_leader');
            
            return $clan_id;
        }
        return false;
    }

    public function readAll() {
        $query = "SELECT c.*, u.nama_lengkap as leader_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN users u ON c.leader_id = u.id
                  WHERE c.is_public = 1
                  ORDER BY c.total_xp DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $query = "SELECT c.*, u.nama_lengkap as leader_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN users u ON c.leader_id = u.id
                  WHERE c.id = :id OR c.slug = :slug";

        $stmt = $this->conn->prepare($query);
        $id = is_numeric($this->id) ? $this->id : 0;
        $slug = $this->slug ?? '';
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function addMember($clan_id, $user_id, $role = 'member') {
        $query = "INSERT INTO clan_members (clan_id, user_id, role)
                  VALUES (:clan_id, :user_id, :role)
                  ON DUPLICATE KEY UPDATE role=:role";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':clan_id', $clan_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':role', $role);

        if ($stmt->execute()) {
            // Update total members
            $this->updateMemberCount($clan_id);
            return true;
        }
        return false;
    }

    public function removeMember($clan_id, $user_id) {
        $query = "DELETE FROM clan_members WHERE clan_id = :clan_id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':clan_id', $clan_id);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            $this->updateMemberCount($clan_id);
            return true;
        }
        return false;
    }

    public function getMembers($clan_id) {
        $query = "SELECT cm.*, u.username, u.nama_lengkap, u.avatar, u.total_xp, 
                         u.is_online, u.last_seen
                  FROM clan_members cm
                  JOIN users u ON cm.user_id = u.id
                  WHERE cm.clan_id = :clan_id
                  ORDER BY u.is_online DESC, cm.role DESC, cm.xp_contribution DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':clan_id', $clan_id);
        $stmt->execute();

        return $stmt;
    }

    public function isMember($clan_id, $user_id) {
        $query = "SELECT * FROM clan_members WHERE clan_id = :clan_id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':clan_id', $clan_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    private function updateMemberCount($clan_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET total_members = (SELECT COUNT(*) FROM clan_members WHERE clan_id = :clan_id)
                  WHERE id = :clan_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':clan_id', $clan_id);
        $stmt->execute();
    }

    private function generateSlug($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
}
?>


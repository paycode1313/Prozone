<?php
class CourseCategory {
    private $conn;
    private $table_name = "course_categories";

    public $id;
    public $nama_kategori;
    public $slug;
    public $deskripsi;
    public $icon;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET nama_kategori=:nama_kategori, slug=:slug, deskripsi=:deskripsi, icon=:icon";

        $stmt = $this->conn->prepare($query);

        $this->nama_kategori = htmlspecialchars(strip_tags($this->nama_kategori));
        $this->slug = $this->generateSlug($this->nama_kategori);
        $this->deskripsi = htmlspecialchars(strip_tags($this->deskripsi));
        $this->icon = htmlspecialchars(strip_tags($this->icon ?? '💻'));

        $stmt->bindParam(':nama_kategori', $this->nama_kategori);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':deskripsi', $this->deskripsi);
        $stmt->bindParam(':icon', $this->icon);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nama_kategori";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nama_kategori = $row['nama_kategori'];
            $this->slug = $row['slug'];
            $this->deskripsi = $row['deskripsi'];
            $this->icon = $row['icon'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET nama_kategori=:nama_kategori, slug=:slug, deskripsi=:deskripsi, icon=:icon
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->nama_kategori = htmlspecialchars(strip_tags($this->nama_kategori));
        $this->slug = $this->generateSlug($this->nama_kategori);
        $this->deskripsi = htmlspecialchars(strip_tags($this->deskripsi));
        $this->icon = htmlspecialchars(strip_tags($this->icon ?? '💻'));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':nama_kategori', $this->nama_kategori);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':deskripsi', $this->deskripsi);
        $stmt->bindParam(':icon', $this->icon);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    private function generateSlug($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
}
?>


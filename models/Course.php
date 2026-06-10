<?php
class Course {
    private $conn;
    private $table_name = "courses";

    public $id;
    public $kode_course;
    public $judul_course;
    public $slug;
    public $kategori_id;
    public $instructor_id;
    public $deskripsi;
    public $thumbnail;
    public $level;
    public $durasi_jam;
    public $harga;
    public $is_free;
    public $is_published;
    public $total_lessons;
    public $total_students;
    public $rating;
    public $xp_reward;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function generateSlug($text) {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET kode_course=:kode_course, judul_course=:judul_course, slug=:slug,
                      kategori_id=:kategori_id, instructor_id=:instructor_id, deskripsi=:deskripsi,
                      thumbnail=:thumbnail, level=:level, durasi_jam=:durasi_jam, harga=:harga,
                      is_free=:is_free, is_published=:is_published, xp_reward=:xp_reward";

        $stmt = $this->conn->prepare($query);

        $this->kode_course = htmlspecialchars(strip_tags($this->kode_course));
        $this->judul_course = htmlspecialchars(strip_tags($this->judul_course));
        // Auto-generate slug if not provided
        if (empty($this->slug)) {
            $this->slug = $this->generateSlug($this->judul_course);
        }
        $this->slug = htmlspecialchars(strip_tags($this->slug));
        $this->kategori_id = htmlspecialchars(strip_tags($this->kategori_id));
        $this->instructor_id = htmlspecialchars(strip_tags($this->instructor_id));
        $this->deskripsi = htmlspecialchars(strip_tags($this->deskripsi));
        $this->thumbnail = !empty($this->thumbnail) ? htmlspecialchars(strip_tags($this->thumbnail)) : null;
        $this->level = htmlspecialchars(strip_tags($this->level));
        $this->durasi_jam = htmlspecialchars(strip_tags($this->durasi_jam));
        $this->harga = htmlspecialchars(strip_tags($this->harga));
        $this->is_free = isset($this->is_free) ? 1 : 0;
        $this->is_published = isset($this->is_published) ? 1 : 0;
        $this->xp_reward = isset($this->xp_reward) ? (int)$this->xp_reward : 100;

        $stmt->bindParam(':kode_course', $this->kode_course);
        $stmt->bindParam(':judul_course', $this->judul_course);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':kategori_id', $this->kategori_id);
        $stmt->bindParam(':instructor_id', $this->instructor_id);
        $stmt->bindParam(':deskripsi', $this->deskripsi);
        $stmt->bindParam(':thumbnail', $this->thumbnail);
        $stmt->bindParam(':level', $this->level);
        $stmt->bindParam(':durasi_jam', $this->durasi_jam);
        $stmt->bindParam(':harga', $this->harga);
        $stmt->bindParam(':is_free', $this->is_free);
        $stmt->bindParam(':is_published', $this->is_published);
        $stmt->bindParam(':xp_reward', $this->xp_reward);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function readAll() {
        $query = "SELECT c.*, cat.nama_kategori, u.nama_lengkap as instructor_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN course_categories cat ON c.kategori_id = cat.id
                  LEFT JOIN users u ON c.instructor_id = u.id
                  ORDER BY c.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $query = "SELECT c.*, cat.nama_kategori, cat.slug as kategori_slug, u.nama_lengkap as instructor_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN course_categories cat ON c.kategori_id = cat.id
                  LEFT JOIN users u ON c.instructor_id = u.id
                  WHERE c.id = :id OR c.slug = :slug";

        $stmt = $this->conn->prepare($query);
        $id = is_numeric($this->id) ? $this->id : 0;
        $slug = $this->slug ?? '';
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->kode_course = $row['kode_course'];
            $this->judul_course = $row['judul_course'];
            $this->slug = $row['slug'];
            $this->kategori_id = $row['kategori_id'];
            $this->instructor_id = $row['instructor_id'];
            $this->deskripsi = $row['deskripsi'];
            $this->thumbnail = $row['thumbnail'] ?? null;
            $this->level = $row['level'];
            $this->durasi_jam = $row['durasi_jam'];
            $this->harga = $row['harga'];
            $this->is_free = $row['is_free'];
            $this->is_published = $row['is_published'];
            $this->total_lessons = $row['total_lessons'];
            $this->total_students = $row['total_students'];
            $this->rating = $row['rating'];
            return $row;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET kode_course=:kode_course, judul_course=:judul_course, slug=:slug, kategori_id=:kategori_id,
                      instructor_id=:instructor_id, deskripsi=:deskripsi, thumbnail=:thumbnail,
                      level=:level, durasi_jam=:durasi_jam, harga=:harga, is_free=:is_free,
                      is_published=:is_published, xp_reward=:xp_reward
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->kode_course = htmlspecialchars(strip_tags($this->kode_course));
        $this->judul_course = htmlspecialchars(strip_tags($this->judul_course));
        // Auto-generate slug if not provided
        if (empty($this->slug)) {
            $this->slug = $this->generateSlug($this->judul_course);
        }
        $this->slug = htmlspecialchars(strip_tags($this->slug));
        $this->kategori_id = htmlspecialchars(strip_tags($this->kategori_id));
        $this->instructor_id = htmlspecialchars(strip_tags($this->instructor_id));
        $this->deskripsi = htmlspecialchars(strip_tags($this->deskripsi));
        $this->thumbnail = !empty($this->thumbnail) ? htmlspecialchars(strip_tags($this->thumbnail)) : null;
        $this->level = htmlspecialchars(strip_tags($this->level));
        $this->durasi_jam = htmlspecialchars(strip_tags($this->durasi_jam));
        $this->harga = htmlspecialchars(strip_tags($this->harga));
        $this->is_free = isset($this->is_free) ? 1 : 0;
        $this->is_published = isset($this->is_published) ? 1 : 0;
        $this->xp_reward = isset($this->xp_reward) ? (int)$this->xp_reward : 100;
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':kode_course', $this->kode_course);
        $stmt->bindParam(':judul_course', $this->judul_course);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':kategori_id', $this->kategori_id);
        $stmt->bindParam(':instructor_id', $this->instructor_id);
        $stmt->bindParam(':deskripsi', $this->deskripsi);
        $stmt->bindParam(':thumbnail', $this->thumbnail);
        $stmt->bindParam(':level', $this->level);
        $stmt->bindParam(':durasi_jam', $this->durasi_jam);
        $stmt->bindParam(':harga', $this->harga);
        $stmt->bindParam(':is_free', $this->is_free);
        $stmt->bindParam(':is_published', $this->is_published);
        $stmt->bindParam(':xp_reward', $this->xp_reward);
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

    public function getTotalCourses() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE is_published = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function getCoursesByCategory($kategori_id) {
        $query = "SELECT c.*, cat.nama_kategori, u.nama_lengkap as instructor_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN course_categories cat ON c.kategori_id = cat.id
                  LEFT JOIN users u ON c.instructor_id = u.id
                  WHERE c.kategori_id = :kategori_id AND c.is_published = 1
                  ORDER BY c.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':kategori_id', $kategori_id);
        $stmt->execute();

        return $stmt;
    }

    public function search($keyword) {
        $query = "SELECT c.*, cat.nama_kategori, u.nama_lengkap as instructor_name
                  FROM " . $this->table_name . " c
                  LEFT JOIN course_categories cat ON c.kategori_id = cat.id
                  LEFT JOIN users u ON c.instructor_id = u.id
                  WHERE (c.judul_course LIKE :keyword OR c.deskripsi LIKE :keyword) 
                  AND c.is_published = 1
                  ORDER BY c.judul_course ASC";

        $stmt = $this->conn->prepare($query);
        $keyword = "%{$keyword}%";
        $stmt->bindParam(':keyword', $keyword);
        $stmt->execute();

        return $stmt;
    }
}
?>


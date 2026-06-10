<?php
class Lesson {
    private $conn;
    private $table_name = "lessons";

    public $id;
    public $course_id;
    public $judul_lesson;
    public $slug;
    public $urutan;
    public $konten;
    public $kode_contoh;
    public $kode_solusi;
    public $hints;
    public $instruksi;
    public $tipe;
    public $durasi_menit;
    public $is_free;
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
                  SET course_id=:course_id, judul_lesson=:judul_lesson, slug=:slug,
                      urutan=:urutan, konten=:konten, kode_contoh=:kode_contoh,
                      kode_solusi=:kode_solusi, hints=:hints, instruksi=:instruksi,
                      tipe=:tipe, durasi_menit=:durasi_menit, is_free=:is_free, xp_reward=:xp_reward";

        $stmt = $this->conn->prepare($query);

        $this->course_id = htmlspecialchars(strip_tags($this->course_id));
        $this->judul_lesson = htmlspecialchars(strip_tags($this->judul_lesson));
        // Auto-generate slug if not provided
        if (empty($this->slug)) {
            $this->slug = $this->generateSlug($this->judul_lesson);
        }
        $this->slug = htmlspecialchars(strip_tags($this->slug));
        $this->urutan = htmlspecialchars(strip_tags($this->urutan));
        $this->konten = $this->konten;
        $this->kode_contoh = $this->kode_contoh ?? '';
        $this->kode_solusi = $this->kode_solusi ?? '';
        $this->hints = $this->hints ?? '';
        $this->instruksi = $this->instruksi ?? '';
        $this->tipe = htmlspecialchars(strip_tags($this->tipe));
        $this->durasi_menit = htmlspecialchars(strip_tags($this->durasi_menit));
        $this->is_free = isset($this->is_free) ? 1 : 0;
        $this->xp_reward = isset($this->xp_reward) ? (int)$this->xp_reward : 10;

        $stmt->bindParam(':course_id', $this->course_id);
        $stmt->bindParam(':judul_lesson', $this->judul_lesson);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':urutan', $this->urutan);
        $stmt->bindParam(':konten', $this->konten);
        $stmt->bindParam(':kode_contoh', $this->kode_contoh);
        $stmt->bindParam(':kode_solusi', $this->kode_solusi);
        $stmt->bindParam(':tipe', $this->tipe);
        $stmt->bindParam(':durasi_menit', $this->durasi_menit);
        $stmt->bindParam(':is_free', $this->is_free);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function readByCourse($course_id) {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE course_id = :course_id
                  ORDER BY urutan ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE id = :id OR (course_id = :course_id AND slug = :slug)";

        $stmt = $this->conn->prepare($query);
        $id = is_numeric($this->id) ? $this->id : 0;
        $course_id = $this->course_id ?? 0;
        $slug = $this->slug ?? '';
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':course_id', $course_id);
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->course_id = $row['course_id'];
            $this->judul_lesson = $row['judul_lesson'];
            $this->slug = $row['slug'];
            $this->urutan = $row['urutan'];
            $this->konten = $row['konten'];
            $this->kode_contoh = $row['kode_contoh'] ?? '';
            $this->kode_solusi = $row['kode_solusi'] ?? '';
            $this->hints = $row['hints'] ?? '';
            $this->instruksi = $row['instruksi'] ?? '';
            $this->tipe = $row['tipe'];
            $this->durasi_menit = $row['durasi_menit'];
            $this->is_free = $row['is_free'];
            $this->xp_reward = $row['xp_reward'] ?? 10;
            return $row;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET judul_lesson=:judul_lesson, slug=:slug, urutan=:urutan,
                      konten=:konten, kode_contoh=:kode_contoh, kode_solusi=:kode_solusi,
                      hints=:hints, instruksi=:instruksi, tipe=:tipe, durasi_menit=:durasi_menit,
                      is_free=:is_free, xp_reward=:xp_reward
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->judul_lesson = htmlspecialchars(strip_tags($this->judul_lesson));
        // Auto-generate slug if not provided
        if (empty($this->slug)) {
            $this->slug = $this->generateSlug($this->judul_lesson);
        }
        $this->slug = htmlspecialchars(strip_tags($this->slug));
        $this->urutan = htmlspecialchars(strip_tags($this->urutan));
        $this->konten = $this->konten ?? '';
        $this->kode_contoh = $this->kode_contoh ?? '';
        $this->kode_solusi = $this->kode_solusi ?? '';
        $this->hints = $this->hints ?? '';
        $this->instruksi = $this->instruksi ?? '';
        $this->tipe = htmlspecialchars(strip_tags($this->tipe));
        $this->durasi_menit = htmlspecialchars(strip_tags($this->durasi_menit));
        $this->is_free = isset($this->is_free) ? 1 : 0;
        $this->xp_reward = isset($this->xp_reward) ? (int)$this->xp_reward : 10;
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':judul_lesson', $this->judul_lesson);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':urutan', $this->urutan);
        $stmt->bindParam(':konten', $this->konten);
        $stmt->bindParam(':kode_contoh', $this->kode_contoh);
        $stmt->bindParam(':kode_solusi', $this->kode_solusi);
        $stmt->bindParam(':hints', $this->hints);
        $stmt->bindParam(':instruksi', $this->instruksi);
        $stmt->bindParam(':tipe', $this->tipe);
        $stmt->bindParam(':durasi_menit', $this->durasi_menit);
        $stmt->bindParam(':is_free', $this->is_free);
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
}
?>


<?php
require_once 'config/config.php';
requireRole(['admin', 'instructor']);
require_once 'includes/icons.php';

require_once 'models/Course.php';
require_once 'models/Lesson.php';

$database = new Database();
$db = $database->getConnection();

$course = new Course($db);
$lesson = new Lesson($db);

$course_id = $_GET['course_id'] ?? 0;
$course->id = $course_id;
$course_data = $course->readOne();

if (!$course_data) {
    header('Location: manage-courses.php');
    exit();
}

// Check permission (instructor can only manage their own courses)
if ($_SESSION['user_role'] === 'instructor' && $course_data['instructor_id'] != $_SESSION['user_id']) {
    header('Location: manage-courses.php');
    exit();
}

$message = '';
$message_type = '';

// Handle form submission
if ($_POST) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Sesi tidak valid (CSRF Token Error). Silakan refresh halaman.';
        $message_type = 'error';
    } elseif (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $lesson->course_id = $course_id;
                $lesson->judul_lesson = sanitizeInput($_POST['judul_lesson']);
                $lesson->slug = ''; // Will be auto-generated in model
                $lesson->urutan = sanitizeInput($_POST['urutan']);
                $lesson->konten = $_POST['konten'] ?? '';
                $lesson->kode_contoh = $_POST['kode_contoh'] ?? '';
                $lesson->kode_solusi = $_POST['kode_solusi'] ?? '';
                $lesson->hints = $_POST['hints'] ?? '';
                $lesson->instruksi = $_POST['instruksi'] ?? '';
                $lesson->tipe = sanitizeInput($_POST['tipe']);
                $lesson->durasi_menit = sanitizeInput($_POST['durasi_menit'] ?? 0);
                $lesson->is_free = isset($_POST['is_free']) ? 1 : 0;
                $lesson->xp_reward = sanitizeInput($_POST['xp_reward'] ?? 10);

                if ($lesson->create()) {
                    // Update total_lessons in course
                    $query_update = "UPDATE courses SET total_lessons = (SELECT COUNT(*) FROM lessons WHERE course_id = :course_id) WHERE id = :course_id";
                    $stmt_update = $db->prepare($query_update);
                    $stmt_update->bindParam(':course_id', $course_id);
                    $stmt_update->execute();
                    
                    $message = 'Lesson berhasil ditambahkan!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menambahkan lesson!';
                    $message_type = 'error';
                }
                break;

            case 'update':
                $lesson->id = sanitizeInput($_POST['id']);
                $lesson->course_id = $course_id;
                $lesson->judul_lesson = sanitizeInput($_POST['judul_lesson']);
                $lesson->slug = ''; // Will be auto-generated in model
                $lesson->urutan = sanitizeInput($_POST['urutan']);
                $lesson->konten = $_POST['konten'] ?? '';
                $lesson->kode_contoh = $_POST['kode_contoh'] ?? '';
                $lesson->kode_solusi = $_POST['kode_solusi'] ?? '';
                $lesson->hints = $_POST['hints'] ?? '';
                $lesson->instruksi = $_POST['instruksi'] ?? '';
                $lesson->tipe = sanitizeInput($_POST['tipe']);
                $lesson->durasi_menit = sanitizeInput($_POST['durasi_menit'] ?? 0);
                $lesson->is_free = isset($_POST['is_free']) ? 1 : 0;
                $lesson->xp_reward = sanitizeInput($_POST['xp_reward'] ?? 10);

                if ($lesson->update()) {
                    $message = 'Lesson berhasil diperbarui!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal memperbarui lesson!';
                    $message_type = 'error';
                }
                break;

            case 'delete':
                $lesson->id = sanitizeInput($_POST['id']);
                if ($lesson->delete()) {
                    // Update total_lessons in course
                    $query_update = "UPDATE courses SET total_lessons = (SELECT COUNT(*) FROM lessons WHERE course_id = :course_id) WHERE id = :course_id";
                    $stmt_update = $db->prepare($query_update);
                    $stmt_update->bindParam(':course_id', $course_id);
                    $stmt_update->execute();
                    
                    $message = 'Lesson berhasil dihapus!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menghapus lesson!';
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Get all lessons for this course
$lessons_stmt = $lesson->readByCourse($course_id);
$lessons = [];
while ($row = $lessons_stmt->fetch(PDO::FETCH_ASSOC)) {
    $lessons[] = $row;
}
// Sort by urutan
usort($lessons, function($a, $b) {
    return $a['urutan'] - $b['urutan'];
});
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('Kelola Lesson - ' . APP_NAME, 'Manajemen lesson untuk kursus', 'admin, lessons, management'); ?>
    <title>Kelola Lesson - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/ui-enhancements.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
        <link rel="stylesheet" href="assets/css/dark-theme.css">
    <style>
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: rgba(139, 92, 246, 0.8);
            font-weight: 600;
            font-size: 0.9rem;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(124, 58, 237, 0.2);
            border-radius: 0.5rem;
            background: rgba(15, 15, 35, 0.6);
            color: #e0e7ff;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: rgba(124, 58, 237, 0.5);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
            background: rgba(15, 15, 35, 0.8);
        }
        .form-group textarea {
            resize: vertical;
            font-family: 'Courier New', monospace;
        }
        .form-group input[type="checkbox"] {
            margin-right: 0.5rem;
            width: auto;
        }
        .btn-primary {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
        }
        .btn-secondary {
            padding: 0.75rem 1.5rem;
            background: rgba(139, 92, 246, 0.1);
            color: #a78bfa;
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background: rgba(139, 92, 246, 0.2);
        }
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border-left: 4px solid #10b981;
            color: #10b981;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border-left: 4px solid #ef4444;
            color: #ef4444;
        }
    </style>
</head>
<body>
    <!-- WYSIWYG Editor Component -->
    <?php include 'includes/wysiwyg-editor.php'; ?>
    
    <!-- Navbar -->
    <?php require_once 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="dashboard-main-container">
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h1>Kelola Lesson</h1>
                <p>Kelola lesson untuk kursus: <?php echo htmlspecialchars($course_data['judul_course']); ?></p>
            </div>

            <div class="content">
                <div style="margin-bottom: 1rem;">
                    <a href="manage-courses.php" style="color: #a78bfa; text-decoration: none;">← Kembali ke Daftar Kursus</a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Add Lesson Button -->
                <div style="margin-bottom: 2rem; text-align: right;">
                    <button onclick="showCreateForm()" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);">➕ Tambah Lesson</button>
                </div>

                <!-- Lessons Table -->
                <div style="background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(30, 30, 63, 0.98) 100%); border-radius: 1rem; padding: 1.75rem; border: 1px solid rgba(124, 58, 237, 0.2); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(124, 58, 237, 0.1);">
                    <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(124, 58, 237, 0.2);">
                        <h2 style="color: #e0e7ff; margin: 0; background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Daftar Lesson</h2>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(124, 58, 237, 0.2);">
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Urutan</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Judul Lesson</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Tipe</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Durasi</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">XP</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($lessons)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 3rem 2rem;">
                                            <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"><?php icon('file', 48); ?></div>
                                            <p style="color: rgba(139, 92, 246, 0.7); font-size: 1rem; margin-bottom: 0.5rem;">Belum ada lesson.</p>
                                            <p style="color: rgba(139, 92, 246, 0.5); font-size: 0.9rem;">Klik "Tambah Lesson" untuk membuat lesson baru.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($lessons as $lesson_item): ?>
                                        <tr style="border-bottom: 1px solid rgba(124, 58, 237, 0.1); transition: all 0.2s ease;" onmouseover="this.style.background='rgba(139, 92, 246, 0.05)'" onmouseout="this.style.background='transparent'">
                                            <td style="padding: 0.75rem 0.5rem; color: #e0e7ff; font-weight: 600; text-align: center;"><?php echo $lesson_item['urutan']; ?></td>
                                            <td style="padding: 0.75rem 0.5rem;"><strong style="color: #a78bfa; font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($lesson_item['judul_lesson']); ?></strong></td>
                                            <td style="padding: 0.75rem 0.5rem;">
                                                <span style="padding: 0.25rem 0.6rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; display: inline-block; text-transform: capitalize; background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">
                                                    <?php echo htmlspecialchars($lesson_item['tipe']); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 0.75rem 0.5rem; color: rgba(139, 92, 246, 0.7); font-size: 0.85rem;"><?php echo $lesson_item['durasi_menit']; ?> menit</td>
                                            <td style="padding: 0.75rem 0.5rem; color: #e0e7ff; font-weight: 500; font-size: 0.85rem;"><?php echo $lesson_item['xp_reward']; ?> XP</td>
                                            <td style="padding: 0.75rem 0.5rem;">
                                                <div style="display: flex; gap: 0.35rem;">
                                                    <button onclick="editLesson(<?php echo htmlspecialchars(json_encode($lesson_item)); ?>)" 
                                                            style="padding: 0.35rem 0.75rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 0.375rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3); font-size: 0.75rem;">
                                                        <?php icon('edit', 12); ?> Edit
                                                    </button>
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Yakin ingin menghapus lesson ini?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $lesson_item['id']; ?>">
                                                        <button type="submit" 
                                                                style="padding: 0.35rem 0.75rem; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none; border-radius: 0.375rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3); font-size: 0.75rem;">
                                                            <?php icon('trash', 12); ?> Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Create/Edit Form Modal -->
                <div id="lessonModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
                     background: rgba(0,0,0,0.7); z-index: 2000; overflow-y: auto; padding: 2rem;">
                    <div style="max-width: 1000px; margin: 2rem auto; background: linear-gradient(135deg, rgba(26, 26, 46, 0.98) 0%, rgba(30, 30, 63, 0.98) 100%); border-radius: 1rem; 
                         padding: 2rem; border: 1px solid rgba(124, 58, 237, 0.2); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(124, 58, 237, 0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(124, 58, 237, 0.2);">
                            <h2 style="color: #e0e7ff; margin: 0; background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;" id="modalTitle">Tambah Lesson</h2>
                            <button onclick="closeModal()" style="background: none; border: none; color: #94a3b8; 
                                    font-size: 1.5rem; cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='#e0e7ff'" onmouseout="this.style.color='#94a3b8'">&times;</button>
                        </div>
                        <form method="POST" id="lessonForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="action" id="formAction" value="create">
                            <input type="hidden" name="id" id="lessonId">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Judul Lesson</label>
                                    <input type="text" name="judul_lesson" id="judul_lesson" required>
                                </div>
                                <div class="form-group">
                                    <label>Urutan</label>
                                    <input type="number" name="urutan" id="urutan" min="1" required>
                                </div>
                                <div class="form-group">
                                    <label>Tipe</label>
                                    <select name="tipe" id="tipe" required>
                                        <option value="theory">Theory</option>
                                        <option value="practice">Practice</option>
                                        <option value="quiz">Quiz</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Konten / Materi</label>
                                
                                <!-- WYSIWYG Editor Container (for theory type) -->
                                <div id="wysiwygContainer" class="wysiwyg-editor" style="display: none;"></div>
                                
                                <!-- Plain textarea (for practice type) -->
                                <textarea name="konten" id="konten" rows="8" style="font-family: monospace;"></textarea>
                                
                                <!-- Quiz Builder UI -->
                                <div id="quizBuilder" style="display: none; margin-top: 1rem; border: 1px solid #4b5563; padding: 1rem; border-radius: 8px; background: rgba(0,0,0,0.2);">
                                    <h3 style="margin-top: 0; margin-bottom: 1rem; color: #e0e7ff;">Quiz Builder</h3>
                                    <div id="questionsContainer"></div>
                                    <button type="button" onclick="addQuestion()" style="margin-top: 1rem; background: #4f46e5; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer;">+ Tambah Pertanyaan</button>
                                </div>
                                
                                <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">
                                    <span id="editorHint">Gunakan editor visual untuk membuat konten materi.</span>
                                </p>
                            </div>

                            <div class="form-group">
                                <label>Instruksi</label>
                                <textarea name="instruksi" id="instruksi" rows="3" 
                                          placeholder="Instruksi untuk student (akan muncul di panel instruksi)"></textarea>
                            </div>

                            <div class="form-group">
                                <label>Kode Contoh</label>
                                <textarea name="kode_contoh" id="kode_contoh" rows="6" 
                                          style="font-family: 'Courier New', monospace;" 
                                          placeholder="Kode contoh yang akan muncul di editor"></textarea>
                            </div>

                            <div class="form-group">
                                <label>Kode Solusi</label>
                                <textarea name="kode_solusi" id="kode_solusi" rows="6" 
                                          style="font-family: 'Courier New', monospace;" 
                                          placeholder="Kode solusi (untuk validasi)"></textarea>
                            </div>

                            <div class="form-group">
                                <label>Hints</label>
                                <textarea name="hints" id="hints" rows="3" 
                                          placeholder="Hints untuk membantu student"></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Durasi (Menit)</label>
                                    <input type="number" name="durasi_menit" id="durasi_menit" min="0" value="0">
                                </div>
                                <div class="form-group">
                                    <label>XP Reward</label>
                                    <input type="number" name="xp_reward" id="xp_reward" min="0" value="10">
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="is_free" id="is_free" checked> 
                                        Lesson Gratis
                                    </label>
                                </div>
                            </div>

                            <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                                <button type="button" onclick="closeModal()" class="btn btn-secondary">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let questions = [];

        function showCreateForm() {
            document.getElementById('modalTitle').textContent = 'Tambah Lesson';
            document.getElementById('formAction').value = 'create';
            document.getElementById('lessonForm').reset();
            document.getElementById('lessonId').value = '';
            document.getElementById('konten').value = ''; // Reset konten
            
            // Set default urutan
            const currentLessons = <?php echo count($lessons); ?>;
            document.getElementById('urutan').value = currentLessons + 1;
            
            // Reset WYSIWYG if exists
            if (wysiwygEditor && wysiwygEditor.content) {
                wysiwygEditor.content.innerHTML = '';
            }
            
            // Reset Quiz Builder
            questions = [];
            toggleQuizBuilder();
            
            document.getElementById('lessonModal').style.display = 'block';
        }

        function editLesson(lesson) {
            document.getElementById('modalTitle').textContent = 'Edit Lesson';
            document.getElementById('formAction').value = 'update';
            document.getElementById('lessonId').value = lesson.id;
            document.getElementById('judul_lesson').value = lesson.judul_lesson;
            document.getElementById('urutan').value = lesson.urutan;
            document.getElementById('konten').value = lesson.konten || '';
            document.getElementById('instruksi').value = lesson.instruksi || '';
            document.getElementById('kode_contoh').value = lesson.kode_contoh || '';
            document.getElementById('kode_solusi').value = lesson.kode_solusi || '';
            document.getElementById('hints').value = lesson.hints || '';
            document.getElementById('tipe').value = lesson.tipe;
            document.getElementById('durasi_menit').value = lesson.durasi_menit || 0;
            document.getElementById('xp_reward').value = lesson.xp_reward || 10;
            document.getElementById('is_free').checked = lesson.is_free == 1;
            
            // Load content to WYSIWYG if theory type
            if (lesson.tipe === 'theory' && wysiwygEditor && wysiwygEditor.content) {
                wysiwygEditor.content.innerHTML = lesson.konten || '';
            }
            
            // Handle Quiz Builder
            questions = [];
            if (lesson.tipe === 'quiz') {
                try {
                    const parsed = JSON.parse(lesson.konten || '[]');
                    if (Array.isArray(parsed)) {
                        questions = parsed;
                    }
                } catch (e) {}
            }
            toggleQuizBuilder();
            
            document.getElementById('lessonModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('lessonModal').style.display = 'none';
        }

        // Close modal on outside click
        document.getElementById('lessonModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // WYSIWYG Editor instance
        let wysiwygEditor = null;

        // Toggle between WYSIWYG, textarea, and Quiz builder based on type
        function toggleQuizBuilder() {
            const type = document.getElementById('tipe').value;
            const builder = document.getElementById('quizBuilder');
            const kontenField = document.getElementById('konten');
            const wysiwygContainer = document.getElementById('wysiwygContainer');
            const editorHint = document.getElementById('editorHint');
            
            // Reset all
            builder.style.display = 'none';
            kontenField.style.display = 'none';
            wysiwygContainer.style.display = 'none';
            
            if (type === 'quiz') {
                // Quiz Builder
                builder.style.display = 'block';
                editorHint.textContent = 'Gunakan Quiz Builder untuk membuat pertanyaan pilihan ganda.';
                renderQuestions();
            } else if (type === 'theory') {
                // WYSIWYG Editor for theory
                wysiwygContainer.style.display = 'block';
                editorHint.textContent = 'Gunakan editor visual untuk membuat konten materi dengan format rich text.';
                
                // Initialize WYSIWYG if not exists
                if (!wysiwygEditor && typeof initWysiwyg === 'function') {
                    wysiwygContainer.innerHTML = ''; // Clear
                    wysiwygEditor = initWysiwyg('wysiwygContainer', 'konten');
                }
                
                // Sync content to WYSIWYG
                if (wysiwygEditor && wysiwygEditor.content) {
                    wysiwygEditor.content.innerHTML = kontenField.value || '';
                }
            } else {
                // Plain textarea for practice
                kontenField.style.display = 'block';
                editorHint.textContent = 'Gunakan format Markdown atau HTML untuk konten praktik.';
            }
        }

        function addQuestion() {
            questions.push({
                question: '',
                options: ['', '', '', ''],
                correct: 0
            });
            renderQuestions();
        }

        function renderQuestions() {
            const container = document.getElementById('questionsContainer');
            container.innerHTML = '';
            
            questions.forEach((q, index) => {
                const qDiv = document.createElement('div');
                qDiv.className = 'question-item';
                qDiv.style.marginBottom = '1.5rem';
                qDiv.style.padding = '1rem';
                qDiv.style.background = 'rgba(0,0,0,0.2)';
                qDiv.style.borderRadius = '8px';
                
                let html = `
                    <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem;">
                        <strong>Pertanyaan ${index + 1}</strong>
                        <button type="button" onclick="removeQuestion(${index})" style="color:#ef4444; background:none; border:none; cursor:pointer;">Hapus</button>
                    </div>
                    <input type="text" value="${q.question}" onchange="updateQuestion(${index}, 'question', this.value)" placeholder="Tulis pertanyaan..." style="width:100%; padding:0.5rem; margin-bottom:0.5rem; background:#1a1a2e; border:1px solid #4b5563; color:white; border-radius:4px;">
                    <div style="display:grid; gap:0.5rem;">
                `;
                
                q.options.forEach((opt, optIndex) => {
                    html += `
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <input type="radio" name="correct_${index}" ${q.correct == optIndex ? 'checked' : ''} onchange="updateQuestion(${index}, 'correct', ${optIndex})">
                            <input type="text" value="${opt}" onchange="updateQuestion(${index}, 'option', this.value, ${optIndex})" placeholder="Pilihan ${optIndex + 1}" style="flex:1; padding:0.5rem; background:#1a1a2e; border:1px solid #4b5563; color:white; border-radius:4px;">
                        </div>
                    `;
                });
                
                html += `</div>`;
                qDiv.innerHTML = html;
                container.appendChild(qDiv);
            });
            
            updateHiddenInput();
        }

        function updateQuestion(index, field, value, optIndex = null) {
            if (field === 'question') {
                questions[index].question = value;
            } else if (field === 'correct') {
                questions[index].correct = parseInt(value);
            } else if (field === 'option') {
                questions[index].options[optIndex] = value;
            }
            updateHiddenInput();
        }

        function removeQuestion(index) {
            questions.splice(index, 1);
            renderQuestions();
        }

        function updateHiddenInput() {
            document.getElementById('konten').value = JSON.stringify(questions);
        }

        document.getElementById('tipe').addEventListener('change', toggleQuizBuilder);
    </script>

    <?php include 'footer.php'; ?>
    <?php include 'includes/loading.php'; ?>
    <?php include 'includes/toast.php'; ?>

    <script src="assets/js/navbar.js"></script>
</body>
</html>



<?php
require_once 'config/config.php';
requireRole(['admin', 'instructor']);
require_once 'includes/icons.php';
require_once 'includes/language-icons.php';
require_once 'includes/FileUpload.php';

require_once 'models/Course.php';
require_once 'models/CourseCategory.php';

$database = new Database();
$db = $database->getConnection();

$course = new Course($db);
$category = new CourseCategory($db);

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
                $course->kode_course = sanitizeInput($_POST['kode_course']);
                $course->judul_course = sanitizeInput($_POST['judul_course']);
                $course->slug = ''; // Will be auto-generated in model
                $course->kategori_id = sanitizeInput($_POST['kategori_id']) ?: null;
                $course->instructor_id = $_SESSION['user_role'] === 'instructor' ? $_SESSION['user_id'] : sanitizeInput($_POST['instructor_id']);
                $course->deskripsi = $_POST['deskripsi'] ?? '';
                $course->level = sanitizeInput($_POST['level']);
                $course->durasi_jam = sanitizeInput($_POST['durasi_jam']);
                $course->harga = sanitizeInput($_POST['harga']);
                $course->is_free = isset($_POST['is_free']) ? 1 : 0;
                $course->is_published = isset($_POST['is_published']) ? 1 : 0;
                $course->xp_reward = sanitizeInput($_POST['xp_reward'] ?? 100);

                // Handle Thumbnail Upload with secure validation
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $upload = FileUpload::uploadThumbnail($_FILES['thumbnail'], 'thumb');
                    if ($upload['success']) {
                        $course->thumbnail = $upload['filename'];
                    }
                }

                if ($course->create()) {
                    $message = 'Kursus berhasil ditambahkan!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menambahkan kursus!';
                    $message_type = 'error';
                }
                break;

            case 'update':
                $course->id = sanitizeInput($_POST['id']);
                $course->kode_course = sanitizeInput($_POST['kode_course']);
                $course->judul_course = sanitizeInput($_POST['judul_course']);
                $course->slug = ''; // Will be auto-generated in model
                $course->kategori_id = sanitizeInput($_POST['kategori_id']) ?: null;
                if ($_SESSION['user_role'] === 'admin') {
                    $course->instructor_id = sanitizeInput($_POST['instructor_id']);
                }
                $course->deskripsi = $_POST['deskripsi'] ?? '';
                $course->level = sanitizeInput($_POST['level']);
                $course->durasi_jam = sanitizeInput($_POST['durasi_jam']);
                $course->harga = sanitizeInput($_POST['harga']);
                $course->is_free = isset($_POST['is_free']) ? 1 : 0;
                $course->is_published = isset($_POST['is_published']) ? 1 : 0;
                $course->xp_reward = sanitizeInput($_POST['xp_reward'] ?? 100);

                // Handle Thumbnail Upload with secure validation
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $upload = FileUpload::uploadThumbnail($_FILES['thumbnail'], 'thumb');
                    if ($upload['success']) {
                        $course->thumbnail = $upload['filename'];
                    }
                }

                if ($course->update()) {
                    $message = 'Kursus berhasil diperbarui!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal memperbarui kursus!';
                    $message_type = 'error';
                }
                break;

            case 'delete':
                $course->id = sanitizeInput($_POST['id']);
                if ($course->delete()) {
                    $message = 'Kursus berhasil dihapus!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menghapus kursus!';
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Get all courses
$stmt = $course->readAll();
$courses = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Filter by instructor if role is instructor
    if ($_SESSION['user_role'] === 'instructor' && $row['instructor_id'] != $_SESSION['user_id']) {
        continue;
    }
    $courses[] = $row;
}

// Get categories
$categories_stmt = $category->readAll();
$categories = [];
while ($row = $categories_stmt->fetch(PDO::FETCH_ASSOC)) {
    $categories[$row['id']] = $row;
}

// Get instructors (for admin)
$instructors = [];
if ($_SESSION['user_role'] === 'admin') {
    $query_instructors = "SELECT id, nama_lengkap FROM users WHERE role = 'instructor'";
    $stmt_instructors = $db->prepare($query_instructors);
    $stmt_instructors->execute();
    while ($row = $stmt_instructors->fetch(PDO::FETCH_ASSOC)) {
        $instructors[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('Kelola Kursus - ' . APP_NAME, 'Manajemen kursus untuk admin dan instructor', 'admin, courses, management'); ?>
    <title>Kelola Kursus - <?php echo APP_NAME; ?></title>
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
            font-family: inherit;
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
    <!-- Navbar -->
    <?php require_once 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="dashboard-main-container">
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h1>Kelola Kursus</h1>
                <p>Kelola semua kursus di platform</p>
            </div>

            <div class="content">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Add Course Button -->
                <div style="margin-bottom: 2rem; text-align: right;">
                    <button onclick="showCreateForm()" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);">➕ Tambah Kursus</button>
                </div>

                <!-- Courses Table -->
                <div style="background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(30, 30, 63, 0.98) 100%); border-radius: 1rem; padding: 1.75rem; border: 1px solid rgba(124, 58, 237, 0.2); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(124, 58, 237, 0.1);">
                    <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(124, 58, 237, 0.2);">
                        <h2 style="color: #e0e7ff; margin: 0; background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Daftar Kursus</h2>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(124, 58, 237, 0.2);">
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Kode</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Judul</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Kategori</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Level</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Instructor</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Students</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($courses)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 3rem 2rem;">
                                            <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"><?php icon('book', 48); ?></div>
                                            <p style="color: rgba(139, 92, 246, 0.7); font-size: 1rem; margin-bottom: 0.5rem;">Belum ada kursus.</p>
                                            <p style="color: rgba(139, 92, 246, 0.5); font-size: 0.9rem;">Klik "Tambah Kursus" untuk membuat kursus baru.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($courses as $course_item): ?>
                                        <tr style="border-bottom: 1px solid rgba(124, 58, 237, 0.1); transition: all 0.2s ease;" onmouseover="this.style.background='rgba(139, 92, 246, 0.05)'" onmouseout="this.style.background='transparent'">
                                                                                        <td style="padding: 0.75rem 0.5rem; color: #e0e7ff; font-weight: 500;"><?php echo htmlspecialchars($course_item['kode_course']); ?></td>
                                            <td style="padding: 1.25rem 0.75rem;">
                                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                    <?php $logo = getLanguageIcon($course_item['judul_course']); if ($logo): ?>
                                                    <img src="<?php echo $logo; ?>" alt="" style="width: 28px; height: 28px; object-fit: contain;">
                                                    <?php endif; ?>
                                                    <strong style="color: #a78bfa; font-weight: 600;"><?php echo htmlspecialchars($course_item['judul_course']); ?></strong>
                                                </div>
                                            </td>
                                            <td style="padding: 0.75rem 0.5rem; color: rgba(139, 92, 246, 0.7); font-size: 0.85rem;">
                                                <?php 
                                                if ($course_item['kategori_id'] && isset($categories[$course_item['kategori_id']])) {
                                                    echo htmlspecialchars($categories[$course_item['kategori_id']]['nama_kategori']);
                                                } else {
                                                    echo '<span style="color: rgba(139, 92, 246, 0.5);">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td style="padding: 0.75rem 0.5rem;">
                                                <span style="padding: 0.25rem 0.6rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; display: inline-block; text-transform: capitalize; background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">
                                                    <?php echo htmlspecialchars($course_item['level']); ?>
                                                </span>
                                            </td>
                                            <td style="padding: 0.75rem 0.5rem; color: #e0e7ff; font-size: 0.85rem;"><?php echo htmlspecialchars($course_item['instructor_name'] ?? '-'); ?></td>
                                            <td style="padding: 0.75rem 0.5rem;">
                                                <?php if ($course_item['is_published']): ?>
                                                    <span style="padding: 0.25rem 0.6rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; display: inline-block; background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Published</span>
                                                <?php else: ?>
                                                    <span style="padding: 0.25rem 0.6rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; display: inline-block; background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">Draft</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 0.75rem 0.5rem; color: #e0e7ff; font-weight: 500; font-size: 0.85rem;"><?php echo $course_item['total_students']; ?></td>
                                            <td style="padding: 0.75rem 0.5rem;">
                                                <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                                                    <button onclick="editCourse(<?php echo htmlspecialchars(json_encode($course_item)); ?>)" 
                                                            style="padding: 0.35rem 0.75rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 0.375rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3); font-size: 0.75rem;">
                                                        <?php icon('edit', 12); ?> Edit
                                                    </button>
                                                    <a href="manage-lessons.php?course_id=<?php echo $course_item['id']; ?>" 
                                                       style="padding: 0.35rem 0.75rem; background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%); color: white; border: none; border-radius: 0.375rem; font-weight: 600; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(124, 58, 237, 0.3); display: inline-block; font-size: 0.75rem;">
                                                        <?php icon('file', 12); ?> Lessons
                                                    </a>
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Yakin ingin menghapus kursus ini?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo $course_item['id']; ?>">
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
                <div id="courseModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
                     background: rgba(0,0,0,0.7); z-index: 2000; overflow-y: auto; padding: 2rem;">
                    <div style="max-width: 900px; margin: 2rem auto; background: linear-gradient(135deg, rgba(26, 26, 46, 0.98) 0%, rgba(30, 30, 63, 0.98) 100%); border-radius: 1rem; 
                         padding: 2rem; border: 1px solid rgba(124, 58, 237, 0.2); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(124, 58, 237, 0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(124, 58, 237, 0.2);">
                            <h2 style="color: #e0e7ff; margin: 0; background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;" id="modalTitle">Tambah Kursus</h2>
                            <button onclick="closeModal()" style="background: none; border: none; color: #94a3b8; 
                                    font-size: 1.5rem; cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='#e0e7ff'" onmouseout="this.style.color='#94a3b8'">&times;</button>
                        </div>
                        <form method="POST" id="courseForm" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="action" id="formAction" value="create">
                            <input type="hidden" name="id" id="courseId">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Kode Kursus</label>
                                    <input type="text" name="kode_course" id="kode_course" required>
                                </div>
                                <div class="form-group">
                                    <label>Level</label>
                                    <select name="level" id="level" required>
                                        <option value="beginner">Beginner</option>
                                        <option value="intermediate">Intermediate</option>
                                        <option value="advanced">Advanced</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Judul Kursus</label>
                                <input type="text" name="judul_course" id="judul_course" required>
                            </div>

                            <div class="form-group">
                                <label>Thumbnail (Optional)</label>
                                <input type="file" name="thumbnail" id="thumbnail" accept="image/*">
                                <small style="color: #94a3b8;">Format: JPG, PNG, WEBP. Max 2MB.</small>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Kategori</label>
                                    <select name="kategori_id" id="kategori_id">
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>">
                                                <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <div class="form-group">
                                    <label>Instructor</label>
                                    <select name="instructor_id" id="instructor_id" required>
                                        <option value="">Pilih Instructor</option>
                                        <?php foreach ($instructors as $inst): ?>
                                            <option value="<?php echo $inst['id']; ?>">
                                                <?php echo htmlspecialchars($inst['nama_lengkap']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea name="deskripsi" id="deskripsi" rows="5"></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Durasi (Jam)</label>
                                    <input type="number" name="durasi_jam" id="durasi_jam" min="0" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Harga</label>
                                    <input type="number" name="harga" id="harga" min="0" step="0.01" value="0">
                                </div>
                                <div class="form-group">
                                    <label>XP Reward</label>
                                    <input type="number" name="xp_reward" id="xp_reward" min="0" value="100">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="is_free" id="is_free" checked> 
                                        Kursus Gratis
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="is_published" id="is_published"> 
                                        Publish Kursus
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
        function showCreateForm() {
            document.getElementById('modalTitle').textContent = 'Tambah Kursus';
            document.getElementById('formAction').value = 'create';
            document.getElementById('courseForm').reset();
            document.getElementById('courseId').value = '';
            document.getElementById('courseModal').style.display = 'block';
        }

        function editCourse(course) {
            document.getElementById('modalTitle').textContent = 'Edit Kursus';
            document.getElementById('formAction').value = 'update';
            document.getElementById('courseId').value = course.id;
            document.getElementById('kode_course').value = course.kode_course;
            document.getElementById('judul_course').value = course.judul_course;
            document.getElementById('kategori_id').value = course.kategori_id || '';
            if (course.instructor_id) {
                document.getElementById('instructor_id').value = course.instructor_id;
            }
            document.getElementById('deskripsi').value = course.deskripsi || '';
            document.getElementById('level').value = course.level;
            document.getElementById('durasi_jam').value = course.durasi_jam;
            document.getElementById('harga').value = course.harga;
            document.getElementById('xp_reward').value = course.xp_reward || 100;
            document.getElementById('is_free').checked = course.is_free == 1;
            document.getElementById('is_published').checked = course.is_published == 1;
            document.getElementById('courseModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('courseModal').style.display = 'none';
        }

        // Close modal on outside click
        document.getElementById('courseModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>

    <?php include 'footer.php'; ?>
    <?php include 'includes/loading.php'; ?>
    <?php include 'includes/toast.php'; ?>

    <script src="assets/js/navbar.js"></script>
</body>
</html>



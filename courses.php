<?php
/**
 * Prozone - Courses Catalog
 * Halaman katalog kursus untuk user terdaftar
 */

if (!defined('PROZONE_ACCESS')) {
    define('PROZONE_ACCESS', true);
}

require_once __DIR__ . '/config/config.php';
requireLogin();
require_once __DIR__ . '/includes/icons.php';

require_once __DIR__ . '/models/Course.php';
require_once __DIR__ . '/models/Enrollment.php';

$database = new Database();
$db = $database->getConnection();

$course = new Course($db);
$enrollment = new Enrollment($db);

// Get filter parameters
$search = sanitizeInput($_GET['search'] ?? '');
$category_filter = sanitizeInput($_GET['category'] ?? '');
$level_filter = sanitizeInput($_GET['level'] ?? '');

// Get all courses with filters
$where_clause = ($_SESSION['user_role'] === 'student') ? "WHERE c.is_published = 1" : "WHERE 1=1";

$query = "SELECT c.*, cc.nama_kategori, u.nama_lengkap as instructor_name
          FROM courses c
          LEFT JOIN course_categories cc ON c.kategori_id = cc.id
          LEFT JOIN users u ON c.instructor_id = u.id
          " . $where_clause;

$params = [];

if (!empty($search)) {
    $query .= " AND (c.judul_course LIKE :search OR c.deskripsi LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($category_filter)) {
    $query .= " AND c.kategori_id = :category";
    $params[':category'] = $category_filter;
}

if (!empty($level_filter)) {
    $query .= " AND c.level = :level";
    $params[':level'] = $level_filter;
}

$query .= " ORDER BY c.created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for filter
$categories_stmt = $db->prepare("SELECT id, nama_kategori FROM course_categories ORDER BY nama_kategori");
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user enrollments
$user_enrollments = [];
if (isset($_SESSION['user_id'])) {
    $enrollment_stmt = $enrollment->getUserEnrollments($_SESSION['user_id']);
    while ($row = $enrollment_stmt->fetch(PDO::FETCH_ASSOC)) {
        $user_enrollments[$row['course_id']] = $row;
    }
}

function getCourseLogo($title) {
    $title = strtolower($title);
    if (strpos($title, 'c++') !== false || strpos($title, 'cpp') !== false) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/cplusplus/cplusplus-original.svg';
    if (strpos($title, 'html') !== false) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/html5/html5-original.svg';
    if (strpos($title, 'css') !== false) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/css3/css3-original.svg';
    if (strpos($title, 'python') !== false) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/python/python-original.svg';
    if (strpos($title, 'php') !== false) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/php/php-original.svg';
    if (strpos($title, 'java') !== false && strpos($title, 'script') === false) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/java/java-original.svg';
    if (strpos($title, 'javascript') !== false || strpos($title, 'js') !== false) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/javascript/javascript-original.svg';
    if (strpos($title, 'typescript') !== false || strpos($title, 'ts') !== false) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/typescript/typescript-original.svg';
    if (strpos($title, 'react') !== false) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/react/react-original.svg';
    if (strpos($title, 'vue') !== false) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/vuejs/vuejs-original.svg';
    if (strpos($title, 'node') !== false) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg';
    if (strpos($title, 'mysql') !== false || strpos($title, 'database') !== false) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/mysql/mysql-original.svg';
    if (strpos($title, 'git') !== false) return 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/git/git-original.svg';
    return null;
}

$page_title = 'Kursus - ' . APP_NAME;
$page_description = 'Jelajahi berbagai kursus pemrograman untuk meningkatkan skill coding Anda';
$page_css = ['assets/css/pages/courses.css'];
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">

    <link rel="stylesheet" href="assets/css/tokens.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <link rel="stylesheet" href="assets/css/components/button.css">
    <link rel="stylesheet" href="assets/css/components/card.css">
    <link rel="stylesheet" href="assets/css/components/badge.css">
    <link rel="stylesheet" href="assets/css/components/form.css">
    <link rel="stylesheet" href="assets/css/components/progress.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/pages/courses.css">
</head>
<body class="theme-dark page-courses">
    <?php include 'navbar.php'; ?>

    <main class="dashboard-main-container">
        <div class="dashboard-content">
            <div class="container container-wide">
                <!-- Page Header -->
                <header class="page-header reveal">
                    <div class="page-header-text">
                        <span class="badge badge-accent badge-soft">📚 Katalog</span>
                        <h1 class="page-title">Jelajahi Kursus</h1>
                        <p class="page-subtitle">Temukan kursus yang tepat untuk mengembangkan skill coding Anda</p>
                    </div>
                </header>

                <!-- Search and Filter -->
                <div class="filter-bar card card-elevated reveal" data-reveal-delay="1">
                    <form method="GET" class="filter-form">
                        <div class="filter-field filter-field-search">
                            <label class="filter-label" for="search">Cari Kursus</label>
                            <div class="input-with-icon">
                                <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                                <input
                                    type="text"
                                    id="search"
                                    name="search"
                                    placeholder="Ketik kata kunci..."
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    class="form-input"
                                    aria-label="Cari kursus"
                                    autocomplete="off"
                                >
                            </div>
                        </div>
                        <div class="filter-field">
                            <label class="filter-label" for="category">Kategori</label>
                            <select id="category" name="category" class="form-select">
                                <option value="">Semua Kategori</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label class="filter-label" for="level">Level</label>
                            <select id="level" name="level" class="form-select">
                                <option value="">Semua Level</option>
                                <option value="beginner" <?php echo $level_filter == 'beginner' ? 'selected' : ''; ?>>Pemula</option>
                                <option value="intermediate" <?php echo $level_filter == 'intermediate' ? 'selected' : ''; ?>>Menengah</option>
                                <option value="advanced" <?php echo $level_filter == 'advanced' ? 'selected' : ''; ?>>Lanjutan</option>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                                Cari
                            </button>
                            <?php if ($search || $category_filter || $level_filter): ?>
                                <a href="courses.php" class="btn btn-ghost btn-sm">✕ Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php if ($search || $category_filter || $level_filter): ?>
                        <div class="filter-result-info">
                            Ditemukan <strong><?php echo count($courses); ?></strong> kursus
                            <?php if ($search): ?>
                                untuk "<em><?php echo htmlspecialchars($search); ?></em>"
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Courses Grid -->
                <?php if (empty($courses)): ?>
                    <div class="empty-state card card-elevated reveal">
                        <div class="empty-state-icon"><?php icon('book', 40); ?></div>
                        <h3 class="empty-state-title">Tidak ada kursus ditemukan</h3>
                        <p class="empty-state-text">Coba gunakan filter yang berbeda atau kata kunci lain.</p>
                        <a href="courses.php" class="btn btn-outline btn-sm" style="margin-top: var(--space-4);">Reset Filter</a>
                    </div>
                <?php else: ?>
                    <div class="courses-grid">
                        <?php foreach ($courses as $course_item):
                            $is_enrolled = isset($user_enrollments[$course_item['id']]);
                            $enrollment_data = $is_enrolled ? $user_enrollments[$course_item['id']] : null;
                            $logoUrl = getCourseLogo($course_item['judul_course']);
                            $level = strtolower($course_item['level']);
                        ?>
                            <article class="course-card card card-elevated reveal">
                                <div class="course-cover">
                                    <div class="course-cover-bg"></div>
                                    <?php if ($logoUrl): ?>
                                        <img src="<?php echo $logoUrl; ?>" alt="" class="course-cover-logo" loading="lazy">
                                    <?php else: ?>
                                        <div class="course-cover-fallback"><?php icon('book', 32); ?></div>
                                    <?php endif; ?>
                                    <span class="course-level-badge badge badge-<?php echo $level; ?>">
                                        <?php echo ucfirst($course_item['level']); ?>
                                    </span>
                                </div>
                                <div class="course-body">
                                    <div class="course-category"><?php echo htmlspecialchars($course_item['nama_kategori'] ?? 'Umum'); ?></div>
                                    <h3 class="course-title"><?php echo htmlspecialchars($course_item['judul_course']); ?></h3>
                                    <p class="course-description"><?php echo htmlspecialchars($course_item['deskripsi']); ?></p>

                                    <div class="course-stats">
                                        <span class="course-stat">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                            </svg>
                                            <?php echo (int)($course_item['total_lessons'] ?? 0); ?> Lesson
                                        </span>
                                        <span class="course-stat">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            <?php echo (int)($course_item['durasi_jam'] ?? 0); ?> jam
                                        </span>
                                    </div>

                                    <?php if ($is_enrolled && $enrollment_data['progress_percent'] > 0): ?>
                                        <div class="course-progress">
                                            <div class="progress progress-sm">
                                                <div class="progress-bar" style="width: <?php echo (float)$enrollment_data['progress_percent']; ?>%"></div>
                                            </div>
                                            <div class="course-progress-info">
                                                <span class="progress-percent"><?php echo number_format($enrollment_data['progress_percent'], 0); ?>% selesai</span>
                                                <a href="course.php?id=<?php echo $course_item['id']; ?>" class="btn btn-sm btn-primary">
                                                    Lanjutkan →
                                                </a>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="course-footer">
                                            <span class="course-status"><?php echo $is_enrolled ? '📚 Belum dimulai' : '🆓 Gratis'; ?></span>
                                            <a href="course.php?id=<?php echo $course_item['id']; ?>" class="btn btn-sm <?php echo $is_enrolled ? 'btn-outline' : 'btn-primary'; ?>">
                                                <?php echo $is_enrolled ? 'Mulai' : 'Pelajari'; ?> →
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, i) => {
                    if (entry.isIntersecting) {
                        const delay = parseInt(entry.target.dataset.revealDelay || '0', 10);
                        setTimeout(() => entry.target.classList.add('is-revealed'), i * 60 + delay);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.05 });

            document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
        });
    </script>
</body>
</html>

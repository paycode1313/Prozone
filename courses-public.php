<?php
/**
 * Prozone - Courses (Public)
 * Halaman katalog kursus untuk user terdaftar
 */

if (!defined('PROZONE_ACCESS')) {
    define('PROZONE_ACCESS', true);
}

require_once __DIR__ . '/config/config.php';

$page_title = 'Kursus - ' . APP_NAME;
$page_description = 'Jelajahi katalog kursus Prozone: HTML, CSS, JavaScript, Python, dan lainnya. Belajar coding interaktif dengan code editor langsung di browser.';
$page_css = ['components/card.css', 'components/button.css', 'components/badge.css', 'components/avatar.css', 'components/form.css', 'components/progress.css', 'components/pagination.css', 'components/dropdown.css', 'components/tooltip.css', 'components/layout.css', 'pages/landing.css', 'pages/courses.css'];
$body_class = getThemeClass();
$current_page = 'courses-public.php';
$nav_active   = function ($href) use ($current_page) {
    return str_contains($href, $current_page) ? ' aria-current="page"' : '';
};

$database = new Database();
$db = $database->getConnection();

// Ambil semua kategori
try {
    $stmt = $db->query("SELECT id, nama_kategori FROM course_categories ORDER BY nama_kategori ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Filter berdasarkan kategori
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$searchQuery = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

$query = "SELECT c.*, cat.nama_kategori as category_name, cat.slug as category_slug FROM courses c LEFT JOIN course_categories cat ON c.kategori_id = cat.id WHERE c.is_published = 1";

if ($categoryFilter > 0) {
    $query .= " AND c.kategori_id = " . $categoryFilter;
}
if ($searchQuery !== '') {
    $query .= " AND (c.judul_course LIKE '%". $searchQuery . "%' OR c.deskripsi LIKE '%". $searchQuery . "%')";
}
$query .= " ORDER BY c.created_at DESC";

try {
    $stmt = $db->query($query);
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $courses = [];
}

// Kursus populer (berdasarkan enrolled paling banyak)
$popularCourses = [];
try {
    $stmt = $db->query("SELECT c.*, cat.nama_kategori as category_name, COUNT(e.user_id) as enrolled_count
                         FROM courses c
                         LEFT JOIN course_categories cat ON c.kategori_id = cat.id
                         LEFT JOIN enrollments e ON c.id = e.course_id
                         WHERE c.is_published = 1
                         GROUP BY c.id
                         ORDER BY enrolled_count DESC
                         LIMIT 3");
    $popularCourses = $stmt->fetchAll();
} catch (PDOException $e) {
    $popularCourses = [];
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

$is_logged_in = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require_once 'includes/head.php'; ?>
</head>
<body class="<?php echo $body_class; ?> landing-page page-courses">
    <!-- Public navbar (glass) — same as index.php -->
    <nav class="landing-nav" id="landingNav" aria-label="Navigasi utama">
        <div class="nav-inner">
            <a href="index.php" class="brand" aria-label="Prozone Home">
                <svg class="brand-logo" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <defs>
                        <linearGradient id="brandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#6366F1"/>
                            <stop offset="100%" stop-color="#10B981"/>
                        </linearGradient>
                    </defs>
                    <path d="M 25 20 L 25 75 Q 25 80 30 80 L 35 80 Q 40 80 40 75 L 40 20 Q 40 15 35 15 L 30 15 Q 25 15 25 20 Z" fill="url(#brandGrad)"/>
                    <path d="M 40 20 Q 40 15 45 15 L 60 15 Q 70 15 70 25 L 70 35 Q 70 45 60 45 L 45 45 Q 40 45 40 40 L 40 30 Q 40 25 45 25 L 60 25 Q 65 25 65 30 L 65 35 Q 65 40 60 40 L 45 40 Q 40 40 40 35 Z" fill="url(#brandGrad)"/>
                </svg>
                <span>Prozone</span>
            </a>
            <div class="nav-menu" id="navMenu">
                <a href="features.php" class="nav-link"<?php echo $nav_active('features.php'); ?>>Fitur</a>
                <a href="courses-public.php" class="nav-link"<?php echo $nav_active('courses-public.php'); ?>>Kursus</a>
                <a href="about.php" class="nav-link"<?php echo $nav_active('about.php'); ?>>Tentang</a>
                <?php if ($is_logged_in): ?>
                <a href="dashboard.php" class="btn btn-primary btn-sm nav-cta">Dashboard</a>
                <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm nav-cta">Masuk</a>
                <?php endif; ?>
            </div>
            <button class="nav-mobile-toggle" id="navMobileToggle" aria-label="Menu" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
        </div>
    </nav>

    <!-- Hero — matching index.php hero style -->
    <header class="hero">
        <div class="hero-inner">
            <div class="hero-content">
                <span class="hero-eyebrow reveal">
                    <span class="pulse-dot" aria-hidden="true"></span>
                    Katalog Kursus
                </span>
                <h1 class="hero-title reveal">
                    Pelajari <span class="text-gradient">Coding</span> dari Dasar
                </h1>
                <p class="hero-lead reveal">
                    Temukan kursus yang sesuai dengan minat dan tujuanmu. Setiap kursus dilengkapi
                    code editor interaktif dan latihan praktis.
                </p>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <svg viewBox="0 0 600 500" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="coursesGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#6366F1"/>
                            <stop offset="100%" stop-color="#10B981"/>
                        </linearGradient>
                    </defs>
                    <!-- Background circles -->
                    <circle cx="120" cy="100" r="80" fill="url(#coursesGrad1)" opacity="0.12"/>
                    <circle cx="480" cy="380" r="100" fill="url(#coursesGrad1)" opacity="0.10"/>
                    <circle cx="420" cy="80" r="50" fill="url(#coursesGrad1)" opacity="0.14"/>

                    <!-- Book / Course stack -->
                    <g transform="translate(140, 200)">
                        <rect x="0" y="0" width="120" height="160" rx="12" fill="url(#coursesGrad1)" opacity="0.2" stroke="url(#coursesGrad1)" stroke-width="3"/>
                        <line x1="20" y1="40" x2="100" y2="40" stroke="url(#coursesGrad1)" stroke-width="3" opacity="0.6"/>
                        <line x1="20" y1="60" x2="80" y2="60" stroke="url(#coursesGrad1)" stroke-width="3" opacity="0.5"/>
                        <line x1="20" y1="80" x2="90" y2="80" stroke="url(#coursesGrad1)" stroke-width="3" opacity="0.4"/>
                        <text x="20" y="30" font-family="monospace" font-size="14" font-weight="bold" fill="url(#coursesGrad1)" opacity="0.8">HTML</text>
                    </g>
                    <g transform="translate(300, 160)">
                        <rect x="0" y="0" width="120" height="160" rx="12" fill="url(#coursesGrad1)" opacity="0.25" stroke="url(#coursesGrad1)" stroke-width="3"/>
                        <line x1="20" y1="40" x2="100" y2="40" stroke="url(#coursesGrad1)" stroke-width="3" opacity="0.6"/>
                        <line x1="20" y1="60" x2="80" y2="60" stroke="url(#coursesGrad1)" stroke-width="3" opacity="0.5"/>
                        <line x1="20" y1="80" x2="90" y2="80" stroke="url(#coursesGrad1)" stroke-width="3" opacity="0.4"/>
                        <text x="20" y="30" font-family="monospace" font-size="14" font-weight="bold" fill="url(#coursesGrad1)" opacity="0.8">Python</text>
                    </g>
                    <g transform="translate(460, 220)">
                        <rect x="0" y="0" width="100" height="140" rx="12" fill="url(#coursesGrad1)" opacity="0.15" stroke="url(#coursesGrad1)" stroke-width="3"/>
                        <line x1="15" y1="35" x2="85" y2="35" stroke="url(#coursesGrad1)" stroke-width="3" opacity="0.5"/>
                        <line x1="15" y1="55" x2="70" y2="55" stroke="url(#coursesGrad1)" stroke-width="3" opacity="0.4"/>
                        <text x="15" y="25" font-family="monospace" font-size="12" font-weight="bold" fill="url(#coursesGrad1)" opacity="0.7">JS</text>
                    </g>
                </svg>
            </div>
        </div>
    </header>

    <!-- Popular Courses -->
    <?php if (!empty($popularCourses)): ?>
    <section class="section section-features" id="popular">
        <div class="container">
            <div class="section-heading reveal">
                <span class="eyebrow">Populer</span>
                <h2>Kursus Populer</h2>
                <p>Paling banyak dipilih oleh anggota Prozone</p>
            </div>
            <div class="grid grid-3">
                <?php foreach ($popularCourses as $course): ?>
                <div class="card card-course card-course-popular card-elevated card-hover-lift reveal">
                    <?php
                        $logoUrl = getCourseLogo($course['judul_course']);
                        $level = strtolower($course['level']);
                    ?>
                    <div class="card-course-thumb">
                        <?php if ($logoUrl): ?>
                            <img src="<?php echo $logoUrl; ?>" alt="" class="course-logo" loading="lazy">
                        <?php else: ?>
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.7"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        <?php endif; ?>
                        <span class="card-course-level"><?php echo ucfirst($course['level']); ?></span>
                    </div>
                    <div class="card-course-body">
                        <?php if (!empty($course['category_name'])): ?>
                        <span class="badge badge-primary badge-xs">
                            <?php echo htmlspecialchars($course['category_name']); ?>
                        </span>
                        <?php endif; ?>
                        <h3 class="card-course-title">
                            <a href="course.php?id=<?php echo (int)$course['id']; ?>"
                               class="card-course-link">
                                <?php echo htmlspecialchars($course['judul_course']); ?>
                            </a>
                        </h3>
                        <p class="card-course-desc">
                            <?php echo htmlspecialchars(substr($course['deskripsi'], 0, 120)) . '...'; ?>
                        </p>
                        <div class="card-course-meta">
                            <div class="card-course-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                </svg>
                                <span><?php echo htmlspecialchars($course['level']); ?></span>
                            </div>
                            <div class="card-course-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span><?php echo (int)$course['durasi_jam']; ?> jam</span>
                            </div>
                        </div>
                        <div class="card-course-footer">
                            <span class="card-course-enrolled">
                                <?php echo (int)$course['enrolled_count']; ?> pelajar
                            </span>
                            <a href="course.php?id=<?php echo (int)$course['id']; ?>" class="btn btn-primary btn-sm">
                                Mulai Belajar
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Browse Section -->
    <section class="section" id="browse">
        <div class="container">
            <div class="section-heading reveal">
                <span class="eyebrow">Katalog</span>
                <h2>Jelajahi Semua Kursus</h2>
                <p>Temukan kursus sesuai minat dan tujuan belajar Anda</p>
            </div>

            <!-- Filters -->
            <div class="browse-filters reveal">
                <form class="filter-form" method="GET" action="">
                    <div class="form-search-group">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="form-search-icon">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="M21 21l-4.35-4.35"></path>
                        </svg>
                        <input type="text"
                               name="search"
                               class="form-input form-search-input"
                               placeholder="Cari kursus..."
                               value="<?php echo htmlspecialchars($searchQuery); ?>"
                               id="course-search">
                        <button type="submit" class="btn btn-primary btn-search" aria-label="Cari">
                            Cari
                        </button>
                    </div>

                    <div class="filter-chips">
                        <a href="courses-public.php"
                           class="filter-chip <?php echo $categoryFilter === 0 ? 'is-active' : ''; ?>">
                            Semua Kursus
                        </a>
                        <?php foreach ($categories as $cat): ?>
                        <a href="courses-public.php?category=<?php echo (int)$cat['id']; ?>"
                           class="filter-chip <?php echo $categoryFilter === (int)$cat['id'] ? 'is-active' : ''; ?>">
                            <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>

            <!-- Results count -->
            <div class="results-info">
                <span class="results-count">Ditemukan <?php echo count($courses); ?> kursus</span>
            </div>

            <!-- Course grid -->
            <?php if (count($courses) > 0): ?>
            <div class="grid grid-3">
                <?php foreach ($courses as $course): ?>
                <div class="card card-course card-elevated card-hover-lift reveal">
                    <?php
                        $logoUrl = getCourseLogo($course['judul_course']);
                        $level = strtolower($course['level']);
                    ?>
                    <div class="card-course-thumb">
                        <?php if ($logoUrl): ?>
                            <img src="<?php echo $logoUrl; ?>" alt="" class="course-logo" loading="lazy">
                        <?php else: ?>
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.7"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        <?php endif; ?>
                        <span class="card-course-level"><?php echo ucfirst($course['level']); ?></span>
                    </div>
                    <div class="card-course-body">
                        <?php if (!empty($course['category_name'])): ?>
                        <span class="badge badge-xs">
                            <?php echo htmlspecialchars($course['category_name']); ?>
                        </span>
                        <?php endif; ?>
                        <h3 class="card-course-title">
                            <a href="course.php?id=<?php echo (int)$course['id']; ?>"
                               class="card-course-link">
                                <?php echo htmlspecialchars($course['judul_course']); ?>
                            </a>
                        </h3>
                        <p class="card-course-desc">
                            <?php echo htmlspecialchars(substr($course['deskripsi'], 0, 120)) . '...'; ?>
                        </p>
                        <div class="card-course-meta">
                            <div class="card-course-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                </svg>
                                <span><?php echo htmlspecialchars($course['level']); ?></span>
                            </div>
                            <div class="card-course-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span><?php echo (int)$course['durasi_jam']; ?> jam</span>
                            </div>
                        </div>
                        <div class="card-course-footer">
                            <a href="course.php?id=<?php echo (int)$course['id']; ?>" class="btn btn-primary btn-sm">
                                Mulai Belajar
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                <h3 class="empty-state-title">Tidak Ada Kursus Ditemukan</h3>
                <p class="empty-state-desc">
                    Coba ubah filter atau kata kunci pencarian Anda.
                </p>
                <a href="courses-public.php" class="btn btn-primary">Lihat Semua Kursus</a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA -->
    <?php if (!$is_logged_in): ?>
    <section class="cta-section">
        <div class="cta-card reveal">
            <h2>Siap Memulai Perjalanan Coding Anda?</h2>
            <p>Bergabunglah dengan ribuan developer yang sudah memulai belajar coding dengan cara yang menyenangkan.</p>
            <div class="row">
                <a href="register.php" class="btn btn-xl btn-cta-primary">Mulai Belajar Gratis</a>
                <a href="login.php" class="btn btn-xl btn-cta-secondary">Sudah Punya Akun</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer — same as index.php -->
    <footer class="landing-footer">
        <div class="landing-footer-grid">
            <div class="landing-footer-brand">
                <a href="index.php" class="brand" aria-label="Prozone Home">
                    <svg class="brand-logo" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width:32px;height:32px">
                        <defs>
                            <linearGradient id="footerBrandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#6366F1"/>
                                <stop offset="100%" stop-color="#10B981"/>
                            </linearGradient>
                        </defs>
                        <path d="M 25 20 L 25 75 Q 25 80 30 80 L 35 80 Q 40 80 40 75 L 40 20 Q 40 15 35 15 L 30 15 Q 25 15 25 20 Z" fill="url(#footerBrandGrad)"/>
                        <path d="M 40 20 Q 40 15 45 15 L 60 15 Q 70 15 70 25 L 70 35 Q 70 45 60 45 L 45 45 Q 40 45 40 40 L 40 30 Q 40 25 45 25 L 60 25 Q 65 25 65 30 L 65 35 Q 65 40 60 40 L 45 40 Q 40 40 40 35 Z" fill="url(#footerBrandGrad)"/>
                    </svg>
                    <span>Prozone</span>
                </a>
                <p>Platform pembelajaran coding interaktif dengan gamifikasi. Belajar coding jadi lebih menyenangkan.</p>
                <div class="landing-footer-social">
                    <a href="#" aria-label="GitHub">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                    </a>
                    <a href="#" aria-label="Twitter">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" aria-label="Discord">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.096 18.06a.082.082 0 00.031.037 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028 14.09 14.09 0 001.226-1.994.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.456 1.216 1.987a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.095 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.095 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
                    </a>
                </div>
            </div>
            <div class="landing-footer-col">
                <h4>Platform</h4>
                <ul>
                    <li><a href="features.php">Fitur</a></li>
                    <li><a href="courses-public.php">Kursus</a></li>
                    <li><a href="leaderboard.php">Leaderboard</a></li>
                    <li><a href="about.php">Tentang</a></li>
                </ul>
            </div>
            <div class="landing-footer-col">
                <h4>Sumber Daya</h4>
                <ul>
                    <li><a href="#">Dokumentasi</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Komunitas</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
            <div class="landing-footer-col">
                <h4>Legal</h4>
                <ul>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                    <li><a href="#">Cookie Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="landing-footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            <div class="landing-footer-bottom-links">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="#">Cookies</a>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        (function() {
            const nav = document.getElementById('landingNav');
            if (!nav) return;
            const onScroll = () => {
                if (window.scrollY > 16) nav.classList.add('is-scrolled');
                else nav.classList.remove('is-scrolled');
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        })();

        // Mobile menu toggle
        (function() {
            const toggle = document.getElementById('navMobileToggle');
            const menu = document.getElementById('navMenu');
            if (!toggle || !menu) return;
            toggle.addEventListener('click', function() {
                const isOpen = menu.classList.toggle('is-mobile-open');
                toggle.setAttribute('aria-expanded', isOpen);
            });
            document.addEventListener('click', function(e) {
                if (!menu.contains(e.target) && !toggle.contains(e.target)) {
                    menu.classList.remove('is-mobile-open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        })();

        // Reveal-on-scroll
        (function() {
            const items = document.querySelectorAll('.reveal');
            if (!('IntersectionObserver' in window)) {
                items.forEach(el => el.classList.add('is-visible'));
                return;
            }
            const io = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const delay = Array.from(entry.target.parentElement.children)
                            .filter(c => c.classList.contains('reveal'))
                            .indexOf(entry.target) * 80;
                        setTimeout(() => entry.target.classList.add('is-visible'), delay);
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -50px 0px' });
            items.forEach(el => io.observe(el));
        })();
    </script>
</body>
</html>
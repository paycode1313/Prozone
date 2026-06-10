<?php
require_once 'config/config.php';
requireLogin();
require_once 'includes/icons.php';
require_once 'includes/language-icons.php';

require_once 'models/Course.php';
require_once 'models/Lesson.php';
require_once 'models/Enrollment.php';
require_once 'models/UserProgress.php';

$database = new Database();
$db = $database->getConnection();

$course = new Course($db);
$lesson = new Lesson($db);
$enrollment = new Enrollment($db);
$user_progress = new UserProgress($db);

// Get course
$course_id = $_GET['id'] ?? 0;
$course->id = $course_id;
$course_data = $course->readOne();

if (!$course_data) {
    header('Location: courses.php');
    exit();
}

// Get lessons
$lessons_stmt = $lesson->readByCourse($course_id);
$lessons = [];
while ($row = $lessons_stmt->fetch(PDO::FETCH_ASSOC)) {
    $lessons[] = $row;
}

// Check enrollment
$is_enrolled = $enrollment->isEnrolled($_SESSION['user_id'], $course_id);
$enrollment_data = null;
if ($is_enrolled) {
    $enrollment_stmt = $enrollment->getUserEnrollments($_SESSION['user_id']);
    while ($row = $enrollment_stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['course_id'] == $course_id) {
            $enrollment_data = $row;
            break;
        }
    }
}

// Get user progress for each lesson
$progress_map = [];
if ($is_enrolled) {
    $progress_stmt = $user_progress->getCourseProgress($_SESSION['user_id'], $course_id);
    while ($row = $progress_stmt->fetch(PDO::FETCH_ASSOC)) {
        $progress_map[$row['lesson_id']] = $row;
    }
}

// Handle enrollment
if ($_POST) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Sesi tidak valid (CSRF Token Error). Silakan refresh halaman.');
    }
    if (isset($_POST['enroll'])) {
        $enrollment->user_id = $_SESSION['user_id'];
        $enrollment->course_id = $course_id;
        if ($enrollment->enroll()) {
            header('Location: course.php?id=' . $course_id);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta(htmlspecialchars($course_data['judul_course']) . ' - ' . APP_NAME, htmlspecialchars($course_data['deskripsi'] ?? 'Detail kursus'), 'course, learning, ' . htmlspecialchars($course_data['judul_course'])); ?>
    <title><?php echo htmlspecialchars($course_data['judul_course']); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/ui-enhancements.css">
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <style>
        .course-header {
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 50%, #a78bfa 100%);
            color: white;
            padding: 1.5rem;
            margin-top: 70px;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2), 0 0 0 1px rgba(124, 58, 237, 0.1);
            position: relative;
            overflow: hidden;
        }
        .course-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-20px, -20px) rotate(180deg); }
        }
        .course-header-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        .course-breadcrumb {
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            opacity: 0.9;
        }
        .course-breadcrumb a {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: opacity 0.3s ease;
        }
        .course-breadcrumb a:hover {
            opacity: 0.8;
        }
        .course-breadcrumb a svg {
            width: 14px;
            height: 14px;
        }
        .course-title-large {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
        .course-meta-info {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .course-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        .course-description-full {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        .course-description-full h2 {
            color: #e0e7ff;
            margin-bottom: 0.75rem;
            background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.25rem;
        }
        .lessons-section {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        .lessons-section h2 {
            color: #e0e7ff;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.25rem;
        }
        .lessons-list {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(30, 30, 63, 0.98) 100%);
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(124, 58, 237, 0.1);
            overflow: hidden;
            border: 1px solid rgba(124, 58, 237, 0.2);
        }
        .lesson-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(124, 58, 237, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            color: #e2e8f0;
        }
        .lesson-item:hover {
            background: rgba(37, 37, 80, 0.6);
            transform: translateX(4px);
        }
        .lesson-item:last-child {
            border-bottom: none;
        }
        .lesson-number {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 50%, #a78bfa 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }
        .lesson-number.completed {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .lesson-content {
            flex: 1;
        }
        .lesson-title {
            font-weight: 600;
            margin-bottom: 0.15rem;
            color: #e2e8f0;
            font-size: 0.95rem;
        }
        .lesson-meta {
            font-size: 0.8rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .lesson-type-badge {
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .lesson-duration, .lesson-xp {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .lesson-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .lesson-badge.completed-badge {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .lesson-badge.progress-badge {
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
        }
        .lesson-action {
            margin-left: auto;
        }
        .btn-start {
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }
        .btn-start svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }
        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
            background: linear-gradient(135deg, #6d28d9 0%, #7c3aed 100%);
        }
        .btn-continue {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }
        .btn-continue svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }
        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        .course-title-with-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        .course-language-logo {
            width: 48px;
            height: 48px;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.3));
        }
        @media (max-width: 768px) {
            .course-title-with-logo {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .course-language-logo {
                width: 40px;
                height: 40px;
            }
        }
        .enroll-section {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(30, 30, 63, 0.98) 100%);
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(124, 58, 237, 0.1);
            padding: 1.5rem;
            margin: 1.5rem auto;
            max-width: 1200px;
            text-align: center;
            border: 1px solid rgba(124, 58, 237, 0.2);
            color: #e2e8f0;
        }
        .enroll-section h2 {
            color: #e0e7ff;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Enhanced Progress Section */
        .progress-section-enhanced {
            text-align: left;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .progress-badge {
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }

        .progress-bar-container {
            margin-bottom: 1.5rem;
        }

        .progress-bar-track {
            width: 100%;
            height: 16px;
            background: rgba(26, 26, 46, 0.8);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #8b5cf6 0%, #a78bfa 50%, #c4b5fd 100%);
            border-radius: 10px;
            position: relative;
            transition: width 1s ease-out;
            animation: progressGlow 2s ease-in-out infinite;
        }

        .progress-bar-glow {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.3) 50%, transparent 100%);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        @keyframes progressGlow {
            0%, 100% { box-shadow: 0 0 8px rgba(139, 92, 246, 0.4); }
            50% { box-shadow: 0 0 16px rgba(139, 92, 246, 0.6); }
        }

        .progress-stats {
            margin-top: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .progress-text {
            color: #cbd5e1;
            font-size: 0.9rem;
        }

        .progress-text strong {
            color: #a78bfa;
            font-weight: 600;
        }

        /* Statistics Cards */
        .course-statistics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(167, 139, 250, 0.05) 100%);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 0.75rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
            border-color: rgba(139, 92, 246, 0.4);
        }

        .stat-icon {
            font-size: 2rem;
            line-height: 1;
        }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #a78bfa;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }

        /* Quick Action Section */
        .quick-action-section {
            margin-top: 1.5rem;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(167, 139, 250, 0.1) 100%);
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            text-decoration: none;
            color: #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .quick-action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }

        .quick-action-btn:hover::before {
            left: 100%;
        }

        .quick-action-btn:hover {
            transform: translateX(4px);
            border-color: rgba(139, 92, 246, 0.5);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
        }

        .quick-action-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .quick-action-icon svg {
            width: 20px;
            height: 20px;
        }

        .quick-action-content {
            flex: 1;
        }

        .quick-action-title {
            font-weight: 600;
            color: #a78bfa;
            margin-bottom: 0.25rem;
        }

        .quick-action-subtitle {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .quick-action-arrow {
            font-size: 1.5rem;
            color: #a78bfa;
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
        }
        .quick-action-arrow svg {
            width: 16px;
            height: 16px;
        }

        .quick-action-btn:hover .quick-action-arrow {
            transform: translateX(4px);
        }

        /* Enhanced Lesson Items */
        .lesson-item {
            position: relative;
            cursor: pointer;
        }

        .lesson-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #8b5cf6 0%, #a78bfa 100%);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .lesson-item:hover::before {
            transform: scaleY(1);
        }

        .lesson-item.completed::before {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
        }

        .lesson-number {
            position: relative;
            overflow: visible;
        }

        .lesson-number.completed::after {
            content: '';
            position: absolute;
            top: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            background: #10b981;
            border-radius: 50%;
            border: 2px solid var(--bg-dark);
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.4);
        }

        @media (max-width: 768px) {
            .course-statistics {
                grid-template-columns: 1fr;
            }

            .progress-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .quick-action-btn {
                padding: 0.75rem 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php require_once 'navbar.php'; ?>

    <div class="dashboard-main-container">
        <div class="dashboard-content">
            <div class="course-header">
                <div class="course-header-content">
                    <div class="course-breadcrumb">
                        <a href="courses.php"><?php icon('arrow-left', 14); ?> Kembali ke Semua Kursus</a>
                    </div>
                    <div class="course-title-with-logo">
                        <?php 
                        $logo_url = getLanguageIcon($course_data['judul_course']);
                        if ($logo_url): 
                        ?>
                        <img src="<?php echo $logo_url; ?>" alt="Language Logo" class="course-language-logo">
                        <?php endif; ?>
                        <h1 class="course-title-large"><?php echo htmlspecialchars($course_data['judul_course']); ?></h1>
                    </div>
                    <div class="course-meta-info">
                        <div class="course-meta-item">
                            <span><?php icon('book', 16); ?></span>
                            <span><?php echo $course_data['total_lessons']; ?> Lessons</span>
                        </div>
                        <div class="course-meta-item">
                            <span><?php icon('clock', 16); ?></span>
                            <span><?php echo $course_data['durasi_jam']; ?> Jam</span>
                        </div>
                        <div class="course-meta-item">
                            <span><?php icon('user', 16); ?></span>
                            <span><?php echo htmlspecialchars($course_data['instructor_name']); ?></span>
                        </div>
                        <div class="course-meta-item">
                            <span><?php icon('chart', 16); ?></span>
                            <span><?php echo ucfirst($course_data['level']); ?> Level</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="course-description-full">
                <h2 style="color: #e2e8f0;">Tentang Kursus</h2>
                <p style="color: #cbd5e1; line-height: 1.8;"><?php echo nl2br(htmlspecialchars($course_data['deskripsi'])); ?></p>
            </div>

            <?php if (!$is_enrolled): ?>
                <div class="enroll-section">
                    <h2>Mulai Belajar Sekarang!</h2>
                    <p style="color: #666; margin: 1rem 0;"><?php echo $course_data['is_free'] ? 'Kursus ini gratis!' : 'Harga: Rp ' . number_format($course_data['harga'], 0, ',', '.'); ?></p>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <button type="submit" name="enroll" class="btn-start" style="border: none; cursor: pointer;">
                            <?php icon('user-plus', 16); ?>
                            Daftar Kursus
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <?php if ($enrollment_data): ?>
                    <?php
                    // Calculate statistics
                    $total_xp = 0;
                    $total_time = 0;
                    foreach ($lessons as $l) {
                        $lp = $progress_map[$l['id']] ?? null;
                        if ($lp && $lp['status'] == 'completed') {
                            $total_xp += $l['xp_reward'] ?? 10;
                        }
                        $total_time += $l['durasi_menit'] ?? 0;
                    }
                    $last_lesson = null;
                    foreach ($lessons as $l) {
                        $lp = $progress_map[$l['id']] ?? null;
                        if ($lp && ($lp['status'] == 'in_progress' || $lp['status'] == 'completed')) {
                            $last_lesson = $l;
                        }
                    }
                    // Find next incomplete lesson
                    $next_lesson = null;
                    foreach ($lessons as $l) {
                        $lp = $progress_map[$l['id']] ?? null;
                        if (!$lp || $lp['status'] != 'completed') {
                            $next_lesson = $l;
                            break;
                        }
                    }
                    ?>
                    <div class="enroll-section progress-section-enhanced">
                        <div class="progress-header">
                            <h2>📊 Progress Belajar Anda</h2>
                            <div class="progress-badge"><?php echo number_format($enrollment_data['progress_percent'], 0); ?>%</div>
                        </div>
                        
                        <div class="progress-bar-container">
                            <div class="progress-bar-track">
                                <div class="progress-bar-fill" style="width: <?php echo $enrollment_data['progress_percent']; ?>%;" data-progress="<?php echo $enrollment_data['progress_percent']; ?>">
                                    <div class="progress-bar-glow"></div>
                                </div>
                            </div>
                            <div class="progress-stats">
                                <span class="progress-text">
                                    <strong><?php echo $enrollment_data['completed_lessons']; ?></strong> dari <strong><?php echo $course_data['total_lessons']; ?></strong> lessons selesai
                                </span>
                            </div>
                        </div>

                        <div class="course-statistics">
                            <div class="stat-card">
                                <div class="stat-icon">⭐</div>
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo number_format($total_xp); ?></div>
                                    <div class="stat-label">XP Diperoleh</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">⏱️</div>
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo number_format($total_time); ?></div>
                                    <div class="stat-label">Menit</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">🎯</div>
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo $enrollment_data['completed_lessons']; ?></div>
                                    <div class="stat-label">Lessons Selesai</div>
                                </div>
                            </div>
                        </div>

                        <?php if ($next_lesson): ?>
                        <div class="quick-action-section">
                            <a href="lesson.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $next_lesson['id']; ?>" class="quick-action-btn">
                                <span class="quick-action-icon"><?php icon('play', 20); ?></span>
                                <div class="quick-action-content">
                                    <div class="quick-action-title">Lanjutkan Belajar</div>
                                    <div class="quick-action-subtitle"><?php echo htmlspecialchars($next_lesson['judul_lesson']); ?></div>
                                </div>
                                <span class="quick-action-arrow"><?php icon('arrow-right', 16); ?></span>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="lessons-section">
                <h2 style="margin-bottom: 1.5rem;">Daftar Lesson</h2>
                <div class="lessons-list">
                    <?php if (empty($lessons)): ?>
                    <div style="padding: 2rem; text-align: center; color: #94a3b8;">
                        Belum ada lesson yang tersedia.
                    </div>
                    <?php else: ?>
                        <?php foreach ($lessons as $lesson_item): ?>
                            <?php 
                            $lesson_progress = $progress_map[$lesson_item['id']] ?? null;
                            $is_completed = $lesson_progress && $lesson_progress['status'] == 'completed';
                            $is_started = $lesson_progress && $lesson_progress['status'] == 'in_progress';
                            ?>
                            <div class="lesson-item <?php echo $is_completed ? 'completed' : ''; ?>" data-lesson-id="<?php echo $lesson_item['id']; ?>">
                                <div class="lesson-number <?php echo $is_completed ? 'completed' : ''; ?>">
                                    <?php if ($is_completed): ?>
                                        <span style="font-size: 1.2rem;">✓</span>
                                    <?php else: ?>
                                        <?php echo $lesson_item['urutan']; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="lesson-content">
                                    <div class="lesson-title">
                                        <?php echo htmlspecialchars($lesson_item['judul_lesson']); ?>
                                        <?php if ($is_completed): ?>
                                            <span class="lesson-badge completed-badge">Selesai</span>
                                        <?php elseif ($is_started): ?>
                                            <span class="lesson-badge progress-badge">Sedang Dikerjakan</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="lesson-meta">
                                        <span class="lesson-type-badge"><?php echo ucfirst($lesson_item['tipe']); ?></span>
                                        <span class="lesson-duration">⏱️ <?php echo $lesson_item['durasi_menit']; ?> menit</span>
                                        <?php if ($lesson_item['xp_reward'] ?? 0): ?>
                                            <span class="lesson-xp">⭐ +<?php echo $lesson_item['xp_reward']; ?> XP</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="lesson-action">
                                    <?php if ($is_enrolled): ?>
                                        <a href="lesson.php?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $lesson_item['id']; ?>" 
                                           class="<?php echo $is_started || $is_completed ? 'btn-continue' : 'btn-start'; ?>">
                                            <?php if ($is_started): ?>
                                                <?php icon('play', 14); ?> Lanjutkan
                                            <?php elseif ($is_completed): ?>
                                                <?php icon('refresh', 14); ?> Review
                                            <?php else: ?>
                                                <?php icon('play', 14); ?> Mulai
                                            <?php endif; ?>
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #6d6d9a; font-size: 0.9rem;">Daftar untuk mengakses</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <?php include 'includes/loading.php'; ?>
    <?php include 'includes/toast.php'; ?>

    <script src="assets/js/navbar.js"></script>
    <script>
        // Animate progress bar on load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBar = document.querySelector('.progress-bar-fill');
            if (progressBar) {
                const targetWidth = progressBar.dataset.progress || 0;
                progressBar.style.width = '0%';
                setTimeout(() => {
                    progressBar.style.width = targetWidth + '%';
                }, 300);
            }

            // Add click animation to lesson items
            const lessonItems = document.querySelectorAll('.lesson-item');
            lessonItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    // Only animate if clicking on the item itself, not on links
                    if (e.target.tagName !== 'A' && !e.target.closest('a')) {
                        const link = item.querySelector('a');
                        if (link) {
                            link.click();
                        }
                    }
                });
            });

            // Add hover effects to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px) scale(1.02)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });

        // Realtime progress updates (optional - can be enabled if needed)
        <?php if ($is_enrolled && $enrollment_data): ?>
        (function() {
            const courseId = <?php echo $course_id; ?>;
            let lastProgress = <?php echo $enrollment_data['progress_percent']; ?>;
            
            async function updateProgress() {
                try {
                    const response = await fetch(`api/get-course-progress.php?course_id=${courseId}`);
                    const result = await response.json();
                    
                    if (result.success && result.data) {
                        const newProgress = result.data.progress_percent || 0;
                        if (Math.abs(newProgress - lastProgress) > 0.1) {
                            // Update progress bar
                            const progressBar = document.querySelector('.progress-bar-fill');
                            const progressText = document.querySelector('.progress-text');
                            const progressBadge = document.querySelector('.progress-badge');
                            
                            if (progressBar) {
                                progressBar.style.width = newProgress + '%';
                                progressBar.dataset.progress = newProgress;
                            }
                            
                            if (progressText) {
                                progressText.innerHTML = `<strong>${result.data.completed_lessons}</strong> dari <strong>${result.data.total_lessons}</strong> lessons selesai`;
                            }
                            
                            if (progressBadge) {
                                progressBadge.textContent = Math.round(newProgress) + '%';
                            }
                            
                            lastProgress = newProgress;
                        }
                    }
                } catch (error) {
                    console.error('Error updating progress:', error);
                }
            }
            
            // Update every 10 seconds if page is visible
            let updateInterval;
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    if (updateInterval) clearInterval(updateInterval);
                } else {
                    updateProgress();
                    updateInterval = setInterval(updateProgress, 10000);
                }
            });
            
            // Initial update after 5 seconds
            setTimeout(updateProgress, 5000);
        })();
        <?php endif; ?>
    </script>
</body>
</html>


<?php
require_once 'config/config.php';
requireLogin();
require_once 'includes/icons.php';
require_once 'includes/language-icons.php';

$page_title       = 'Dashboard';
$page_description = 'Dashboard pembelajaran coding Prozone.';
$page_css         = ['pages/dashboard.css'];
$body_class       = getThemeClass();
require_once 'models/User.php';
require_once 'models/Course.php';
require_once 'models/Enrollment.php';
require_once 'models/UserProgress.php';

$database = new Database();
$db = $database->getConnection();

// Get dashboard data based on user role
$role = $_SESSION['user_role'];

$course = new Course($db);
$enrollment = new Enrollment($db);

// Time-based greeting
$hour = date('H');
if ($hour < 12) {
    $greeting = 'Selamat Pagi';
    $greeting_icon = 'sun';
} elseif ($hour < 17) {
    $greeting = 'Selamat Siang';
    $greeting_icon = 'sun';
} else {
    $greeting = 'Selamat Malam';
    $greeting_icon = 'moon';
}

if ($role === 'student') {
    // Get student stats
    $total_courses = $course->getTotalCourses();
    
    // Get enrolled courses
    $enrollment_stmt = $enrollment->getUserEnrollments($_SESSION['user_id']);
    $enrolled_courses = [];
    $total_progress = 0;
    $completed_courses = 0;
    
    while ($row = $enrollment_stmt->fetch(PDO::FETCH_ASSOC)) {
        $enrolled_courses[] = $row;
        $total_progress += $row['progress_percent'];
        if ($row['status'] == 'completed') {
            $completed_courses++;
        }
    }
    
    $avg_progress = count($enrolled_courses) > 0 ? $total_progress / count($enrolled_courses) : 0;
    $total_enrolled = count($enrolled_courses);

    // Get user XP, Level
    $query_user = "SELECT total_xp, level, avatar FROM users WHERE id = :user_id";
    $stmt_user = $db->prepare($query_user);
    $stmt_user->bindParam(':user_id', $_SESSION['user_id']);
    $stmt_user->execute();
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
    $total_xp = $user_data['total_xp'] ?? 0;
    $level = $user_data['level'] ?? 1;
    $avatar = $user_data['avatar'] ?? null;
    
    // Calculate XP progress
    $xp_for_current_level = ($level - 1) * 100;
    $xp_for_next_level = $level * 100;
    $xp_progress = $xp_for_next_level - $xp_for_current_level;
    $xp_current = $total_xp - $xp_for_current_level;
    $xp_percent = $xp_progress > 0 ? ($xp_current / $xp_progress) * 100 : 0;
    
    // Get streak data
    $streakQuery = "SELECT COUNT(DISTINCT DATE(completed_at)) as streak_days
        FROM user_progress 
        WHERE user_id = :user_id 
        AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND status = 'completed'";
    $stmt = $db->prepare($streakQuery);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $streakData = $stmt->fetch(PDO::FETCH_ASSOC);
    $streakDays = $streakData['streak_days'] ?? 0;
    
    // Get last active course
    $lastCourseQuery = "SELECT c.*, e.progress_percent, l.judul_lesson as next_lesson, l.id as next_lesson_id
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN lessons l ON l.course_id = c.id 
            AND l.id NOT IN (SELECT lesson_id FROM user_progress WHERE user_id = :user_id AND status = 'completed')
        WHERE e.user_id = :user_id AND e.status = 'in_progress'
        ORDER BY e.enrolled_at DESC
        LIMIT 1";
    $stmt = $db->prepare($lastCourseQuery);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $lastCourse = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($role === 'admin' || $role === 'instructor') {
    // Get course stats
    $total_courses = $course->getTotalCourses();
    
    // Get total students
    $query_students = "SELECT COUNT(DISTINCT user_id) as total FROM enrollments";
    $stmt_students = $db->prepare($query_students);
    $stmt_students->execute();
    $students_data = $stmt_students->fetch(PDO::FETCH_ASSOC);
    $total_students = $students_data['total'] ?? 0;
    
    // Get total users
    $query_all_users = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
    $stmt_all_users = $db->prepare($query_all_users);
    $stmt_all_users->execute();
    $all_users_data = $stmt_all_users->fetch(PDO::FETCH_ASSOC);
    $total_users = $all_users_data['total'] ?? 0;
    
    // Get total lessons
    $query_lessons = "SELECT COUNT(*) as total FROM lessons";
    $stmt_lessons = $db->prepare($query_lessons);
    $stmt_lessons->execute();
    $lessons_data = $stmt_lessons->fetch(PDO::FETCH_ASSOC);
    $total_lessons = $lessons_data['total'] ?? 0;
    
    // Get recent enrollments
    $query_recent = "SELECT e.*, c.judul_course, u.nama_lengkap, u.avatar
                     FROM enrollments e
                     JOIN courses c ON e.course_id = c.id
                     JOIN users u ON e.user_id = u.id
                     ORDER BY e.enrolled_at DESC LIMIT 5";
    $stmt_recent = $db->prepare($query_recent);
    $stmt_recent->execute();
    $recent_enrollments = [];
    while ($row = $stmt_recent->fetch(PDO::FETCH_ASSOC)) {
        $recent_enrollments[] = $row;
    }
    
    // Get admin user data
    $query_user = "SELECT total_xp, level, avatar FROM users WHERE id = :user_id";
    $stmt_user = $db->prepare($query_user);
    $stmt_user->bindParam(':user_id', $_SESSION['user_id']);
    $stmt_user->execute();
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
    $level = $user_data['level'] ?? 1;
    $avatar = $user_data['avatar'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require_once 'includes/head.php'; ?>
</head>
<body class="<?php echo $body_class; ?>">
    <?php require_once 'navbar.php'; ?>

    <div class="dashboard-main-container">
        <div class="dashboard-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-left">
                    <div class="welcome-avatar">
                        <?php if (!empty($avatar) && file_exists('assets/uploads/avatars/' . $avatar)): ?>
                            <img src="assets/uploads/avatars/<?php echo $avatar; ?>" alt="Avatar">
                        <?php else: ?>
                            <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
                        <?php endif; ?>
                        <div class="welcome-badge"><?php echo $level ?? 1; ?></div>
                    </div>
                    <div class="welcome-text">
                        <h1><?php icon($greeting_icon, 20); ?> <?php echo $greeting; ?>, <?php echo explode(' ', $_SESSION['nama_lengkap'])[0]; ?>!</h1>
                        <p>Login sebagai <strong><?php echo ucfirst($_SESSION['user_role']); ?></strong></p>
                    </div>
                </div>
            </div>

            <?php if ($role === 'student'): ?>
            
            <!-- Quick Links -->
            <div class="quick-links">
                <a href="courses.php" class="quick-link"><?php icon('book', 16); ?> Kursus</a>
                <a href="leaderboard.php" class="quick-link"><?php icon('trophy', 16); ?> Leaderboard</a>
                <a href="achievements.php" class="quick-link"><?php icon('award', 16); ?> Achievement</a>
                <a href="certificates.php" class="quick-link"><?php icon('file-text', 16); ?> Sertifikat</a>
            </div>

            <!-- Stats Grid -->
            <div class="dash-stats-grid">
                <div class="dash-stat-card">
                    <div class="dash-stat-header">
                        <div class="dash-stat-icon icon-brand"><?php icon('star', 18); ?></div>
                    </div>
                    <div class="dash-stat-value"><?php echo number_format($total_xp ?? 0); ?></div>
                    <div class="dash-stat-label">Total XP</div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-header">
                        <div class="dash-stat-icon icon-accent"><?php icon('check-circle', 18); ?></div>
                    </div>
                    <div class="dash-stat-value"><?php echo $completed_courses ?? 0; ?>/<?php echo $total_enrolled ?? 0; ?></div>
                    <div class="dash-stat-label">Kursus Selesai</div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-header">
                        <div class="dash-stat-icon icon-warning"><?php icon('fire', 18); ?></div>
                    </div>
                    <div class="dash-stat-value"><?php echo $streakDays ?? 0; ?></div>
                    <div class="dash-stat-label">Day Streak</div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-header">
                        <div class="dash-stat-icon icon-info"><?php icon('trending-up', 18); ?></div>
                    </div>
                    <div class="dash-stat-value"><?php echo number_format($avg_progress ?? 0, 0); ?>%</div>
                    <div class="dash-stat-label">Avg Progress</div>
                </div>
            </div>

            <!-- Continue Learning -->
            <?php if (!empty($lastCourse)): ?>
            <div class="continue-card">
                <div class="continue-info">
                    <div class="continue-icon"><?php icon('play', 22); ?></div>
                    <div class="continue-text">
                        <h3><?php echo htmlspecialchars($lastCourse['judul_course']); ?></h3>
                        <p>Progress: <span><?php echo number_format($lastCourse['progress_percent'], 0); ?>%</span></p>
                    </div>
                </div>
                <a href="course.php?id=<?php echo $lastCourse['id']; ?>" class="btn-continue">
                    <?php icon('play', 16); ?> Lanjutkan
                </a>
            </div>
            <?php endif; ?>

            <?php endif; ?>

            <?php if ($role === 'admin' || $role === 'instructor'): ?>
            
            <!-- Admin Stats -->
            <div class="dash-stats-grid">
                <div class="dash-stat-card">
                    <div class="dash-stat-header">
                        <div class="dash-stat-icon icon-brand"><?php icon('book', 18); ?></div>
                    </div>
                    <div class="dash-stat-value"><?php echo $total_courses ?? 0; ?></div>
                    <div class="dash-stat-label">Total Kursus</div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-header">
                        <div class="dash-stat-icon icon-info"><?php icon('users', 18); ?></div>
                    </div>
                    <div class="dash-stat-value"><?php echo $total_students ?? 0; ?></div>
                    <div class="dash-stat-label">Siswa Aktif</div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-header">
                        <div class="dash-stat-icon icon-accent"><?php icon('user', 18); ?></div>
                    </div>
                    <div class="dash-stat-value"><?php echo $total_users ?? 0; ?></div>
                    <div class="dash-stat-label">Total User</div>
                </div>
                <div class="dash-stat-card">
                    <div class="dash-stat-header">
                        <div class="dash-stat-icon icon-warning"><?php icon('book-open', 18); ?></div>
                    </div>
                    <div class="dash-stat-value"><?php echo $total_lessons ?? 0; ?></div>
                    <div class="dash-stat-label">Total Lesson</div>
                </div>
            </div>

            <?php endif; ?>

            <!-- Courses Section -->
            <div class="courses-section">
                <div class="courses-header">
                    <h3><?php icon('book', 18); ?> <?php echo ($role === 'student') ? 'Kursus Saya' : 'Pendaftaran Terbaru'; ?></h3>
                    <a href="<?php echo ($role === 'student') ? 'courses.php' : 'admin/courses.php'; ?>">Lihat Semua →</a>
                </div>
                
                <?php if ($role === 'student'): ?>
                    <?php if (empty($enrolled_courses)): ?>
                    <div class="empty-state">
                        <?php icon('book-open', 40); ?>
                        <p>Belum ada kursus yang diikuti</p>
                        <a href="courses.php" class="btn-explore">
                            <?php icon('search', 14); ?> Jelajahi Kursus
                        </a>
                    </div>
                    <?php else: ?>
                        <?php foreach (array_slice($enrolled_courses, 0, 5) as $course_item): ?>
                        <div class="course-item">
                            <div class="course-info">
                                <div class="course-logo">
                                    <?php $logo = getLanguageIcon($course_item['judul_course']); if ($logo): ?>
                                    <img src="<?php echo $logo; ?>" alt="">
                                    <?php else: ?>
                                    <?php icon('code', 18); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="course-details">
                                    <a href="course.php?id=<?php echo $course_item['course_id']; ?>" class="course-name">
                                        <?php echo htmlspecialchars($course_item['judul_course']); ?>
                                    </a>
                                    <div class="course-meta"><?php echo $course_item['completed_lessons']; ?>/<?php echo $course_item['total_lessons']; ?> lessons</div>
                                </div>
                            </div>
                            <div class="course-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $course_item['progress_percent']; ?>%"></div>
                                </div>
                                <span class="progress-text"><?php echo number_format($course_item['progress_percent'], 0); ?>%</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($role === 'admin' || $role === 'instructor'): ?>
                    <?php if (empty($recent_enrollments)): ?>
                    <div class="empty-state">
                        <?php icon('clipboard', 40); ?>
                        <p>Belum ada pendaftaran</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($recent_enrollments as $row): ?>
                        <div class="course-item">
                            <div class="course-info">
                                <div class="course-logo" style="background: var(--gradient-brand); color: var(--text-on-primary); font-weight: bold;">
                                    <?php echo strtoupper(substr($row['nama_lengkap'], 0, 1)); ?>
                                </div>
                                <div class="course-details">
                                    <span class="course-name" style="cursor: default;"><?php echo htmlspecialchars($row['nama_lengkap']); ?></span>
                                    <div class="course-meta"><?php echo htmlspecialchars($row['judul_course']); ?></div>
                                </div>
                            </div>
                            <span class="course-meta"><?php echo date('d M Y', strtotime($row['enrolled_at'])); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <?php include 'includes/loading.php'; ?>
    <?php include 'includes/toast.php'; ?>

    <script src="assets/js/navbar.js"></script>
</body>
</html>

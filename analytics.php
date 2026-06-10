<?php
require_once 'config/config.php';
requireLogin();
requireRole(['student']);
require_once 'includes/icons.php';

require_once 'models/Course.php';
require_once 'models/Enrollment.php';
require_once 'models/UserProgress.php';
require_once 'models/Achievement.php';

$database = new Database();
$db = $database->getConnection();

$course = new Course($db);
$enrollment = new Enrollment($db);
$user_progress = new UserProgress($db);
$achievement = new Achievement($db);

// Get user stats
$user_id = $_SESSION['user_id'];

// Get enrolled courses
$enrollment_stmt = $enrollment->getUserEnrollments($user_id);
$enrolled_courses = [];
$total_progress = 0;
$completed_courses = 0;
$total_lessons_completed = 0;

while ($row = $enrollment_stmt->fetch(PDO::FETCH_ASSOC)) {
    $enrolled_courses[] = $row;
    $total_progress += $row['progress_percent'];
    if ($row['status'] == 'completed') {
        $completed_courses++;
    }
    $total_lessons_completed += $row['completed_lessons'];
}

$avg_progress = count($enrolled_courses) > 0 ? $total_progress / count($enrolled_courses) : 0;

// Get user XP and level
$query_user = "SELECT total_xp, level FROM users WHERE id = :user_id";
$stmt_user = $db->prepare($query_user);
$stmt_user->bindParam(':user_id', $user_id);
$stmt_user->execute();
$user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
$user_xp = $user_data['total_xp'] ?? 0;
$user_level = $user_data['level'] ?? 1;

// Calculate XP for next level (simple formula: level * 100)
$xp_for_current_level = ($user_level - 1) * 100;
$xp_for_next_level = $user_level * 100;
$xp_progress = $xp_for_next_level - $xp_for_current_level;
$xp_current = $user_xp - $xp_for_current_level;
$xp_percent = $xp_progress > 0 ? ($xp_current / $xp_progress) * 100 : 0;

// Get achievements
$achievements_stmt = $achievement->getUserAchievements($user_id);
$total_achievements = 0;
$earned_achievements = 0;
while ($row = $achievements_stmt->fetch(PDO::FETCH_ASSOC)) {
    $total_achievements++;
    if ($row['earned_at']) {
        $earned_achievements++;
    }
}

// Get learning streak (simplified - last 7 days)
$query_streak = "SELECT COUNT(DISTINCT DATE(completed_at)) as streak_days
                 FROM user_progress
                 WHERE user_id = :user_id 
                 AND status = 'completed'
                 AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$stmt_streak = $db->prepare($query_streak);
$stmt_streak->bindParam(':user_id', $user_id);
$stmt_streak->execute();
$streak_data = $stmt_streak->fetch(PDO::FETCH_ASSOC);
$learning_streak = $streak_data['streak_days'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('Analytics - ' . APP_NAME, 'Lihat statistik dan progress pembelajaran Anda', 'analytics, statistics, progress'); ?>
    <title>Analytics - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/ui-enhancements.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
        <link rel="stylesheet" href="assets/css/dark-theme.css">
    <style>
        .analytics-header {
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            color: white;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
        }
        .analytics-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .stat-card-large {
            background: #1e1e3f;
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid #2d2d5a;
            text-align: center;
        }
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .stat-value-large {
            font-size: 3rem;
            font-weight: 700;
            color: #a78bfa;
            margin-bottom: 0.5rem;
        }
        .stat-label-large {
            color: #94a3b8;
            font-size: 1rem;
        }
        .level-progress {
            background: #1e1e3f;
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid #2d2d5a;
            margin-bottom: 2rem;
        }
        .level-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .level-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #a78bfa;
        }
        .level-xp {
            color: #94a3b8;
            font-size: 0.9rem;
        }
        .xp-progress-bar {
            width: 100%;
            height: 30px;
            background: #252550;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        .xp-progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            transition: width 0.5s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .courses-progress {
            background: #1e1e3f;
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid #2d2d5a;
        }
        .courses-progress h3 {
            color: #e2e8f0;
            margin-bottom: 1.5rem;
        }
        .course-progress-item {
            margin-bottom: 1.5rem;
        }
        .course-progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .course-name {
            color: #e2e8f0;
            font-weight: 600;
        }
        .course-progress-percent {
            color: #a78bfa;
            font-weight: 600;
        }
        .course-progress-bar {
            width: 100%;
            height: 10px;
            background: #252550;
            border-radius: 5px;
            overflow: hidden;
        }
        .course-progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            transition: width 0.5s;
        }
    </style>
</head>
<body>
    <link rel="stylesheet" href="assets/css/navbar.css">
</head>
<body>
    <!-- Navbar -->
    <?php require_once 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="dashboard-main-container">
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h1>Analytics</h1>
                <p>Pantau progress dan statistik belajar Anda</p>
            </div>

            <div class="content">
                <div class="stats-grid">
                    <div class="stat-card-large">
                        <div class="stat-icon"><?php icon('star', 24); ?></div>
                        <div class="stat-value-large"><?php echo number_format($user_xp); ?></div>
                        <div class="stat-label-large">Total XP</div>
                    </div>
                    <div class="stat-card-large">
                        <div class="stat-icon"><?php icon('book', 24); ?></div>
                        <div class="stat-value-large"><?php echo count($enrolled_courses); ?></div>
                        <div class="stat-label-large">Kursus Terdaftar</div>
                    </div>
                    <div class="stat-card-large">
                        <div class="stat-icon"><?php icon('check-circle', 24); ?></div>
                        <div class="stat-value-large"><?php echo $completed_courses; ?></div>
                        <div class="stat-label-large">Kursus Selesai</div>
                    </div>
                    <div class="stat-card-large">
                        <div class="stat-icon"><?php icon('book-open', 24); ?></div>
                        <div class="stat-value-large"><?php echo $total_lessons_completed; ?></div>
                        <div class="stat-label-large">Lessons Selesai</div>
                    </div>
                    <div class="stat-card-large">
                        <div class="stat-icon"><?php icon('target', 24); ?></div>
                        <div class="stat-value-large"><?php echo $earned_achievements; ?>/<?php echo $total_achievements; ?></div>
                        <div class="stat-label-large">Achievements</div>
                    </div>
                    <div class="stat-card-large">
                        <div class="stat-icon"><?php icon('fire', 24); ?></div>
                        <div class="stat-value-large"><?php echo $learning_streak; ?></div>
                        <div class="stat-label-large">Day Streak</div>
                    </div>
                </div>

                <div class="level-progress">
                    <div class="level-info">
                        <div>
                            <div class="level-number">Level <?php echo $user_level; ?></div>
                            <div class="level-xp"><?php echo number_format($xp_current); ?> / <?php echo number_format($xp_progress); ?> XP menuju Level <?php echo $user_level + 1; ?></div>
                        </div>
                    </div>
                    <div class="xp-progress-bar">
                        <div class="xp-progress-fill" style="width: <?php echo $xp_percent; ?>%">
                            <?php echo number_format($xp_percent, 1); ?>%
                        </div>
                    </div>
                </div>

                <div class="courses-progress">
                    <h3><?php icon('trending-up', 16); ?> Progress per Kursus</h3>
                    <?php if (empty($enrolled_courses)): ?>
                        <p style="color: #94a3b8; text-align: center; padding: 2rem;">
                            Anda belum terdaftar di kursus manapun. <a href="courses.php" style="color: #a78bfa;">Jelajahi Kursus</a>
                        </p>
                    <?php else: ?>
                        <?php foreach ($enrolled_courses as $course_item): ?>
                            <div class="course-progress-item">
                                <div class="course-progress-header">
                                    <div class="course-name"><?php echo htmlspecialchars($course_item['judul_course']); ?></div>
                                    <div class="course-progress-percent"><?php echo number_format($course_item['progress_percent'], 1); ?>%</div>
                                </div>
                                <div class="course-progress-bar">
                                    <div class="course-progress-fill" style="width: <?php echo $course_item['progress_percent']; ?>%"></div>
                                </div>
                                <div style="font-size: 0.85rem; color: #94a3b8; margin-top: 0.25rem;">
                                    <?php echo $course_item['completed_lessons']; ?> dari <?php echo $course_item['total_lessons']; ?> lessons selesai
                                    <?php if ($course_item['status'] == 'completed'): ?>
                                        <span style="color: #10b981; margin-left: 0.5rem;">✓ Selesai</span>
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
</body>
</html>



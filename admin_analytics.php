<?php
require_once 'config/config.php';
requireLogin();
requireRole(['admin']);

require_once 'models/User.php';
require_once 'models/Course.php';

$database = new Database();
$db = $database->getConnection();

// 1. Key Metrics
$metrics = [];

// Total Users
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
$stmt = $db->prepare($query);
$stmt->execute();
$metrics['total_students'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total Instructors
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'instructor'";
$stmt = $db->prepare($query);
$stmt->execute();
$metrics['total_instructors'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total Courses
$query = "SELECT COUNT(*) as total FROM courses";
$stmt = $db->prepare($query);
$stmt->execute();
$metrics['total_courses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total Enrollments
$query = "SELECT COUNT(*) as total FROM enrollments";
$stmt = $db->prepare($query);
$stmt->execute();
$metrics['total_enrollments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// 2. User Growth (Last 30 Days)
$user_growth = [];
$query = "SELECT DATE(created_at) as date, COUNT(*) as count 
          FROM users 
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
          GROUP BY DATE(created_at) 
          ORDER BY date ASC";
$stmt = $db->prepare($query);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $user_growth[] = $row;
}

// 3. Popular Courses
$popular_courses = [];
$query = "SELECT c.judul_course, COUNT(e.id) as student_count 
          FROM courses c 
          LEFT JOIN enrollments e ON c.id = e.course_id 
          GROUP BY c.id 
          ORDER BY student_count DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $popular_courses[] = $row;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('Admin Analytics - ' . APP_NAME, 'Analitik dan statistik platform', 'admin, analytics, statistics'); ?>
    <title>Admin Analytics - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Reduced */
            gap: 1rem; /* Reduced */
            margin-bottom: 1.5rem; /* Reduced */
        }

        .stat-card {
            background: #1e1e2f;
            padding: 1rem; /* Reduced */
            border-radius: 12px;
            border: 1px solid rgba(139, 92, 246, 0.1);
        }

        .stat-value {
            font-size: 1.5rem; /* Reduced */
            font-weight: bold;
            color: #fff;
            margin: 0.25rem 0; /* Reduced */
        }

        .stat-label {
            color: #94a3b8;
            font-size: 0.8rem; /* Reduced */
        }

        .chart-container {
            background: #1e1e2f;
            padding: 1rem; /* Reduced */
            border-radius: 12px;
            border: 1px solid rgba(139, 92, 246, 0.1);
            margin-bottom: 1.5rem; /* Reduced */
        }

        .chart-header {
            margin-bottom: 1rem; /* Reduced */
        }

        .chart-title {
            color: #fff;
            font-size: 1rem; /* Reduced */
            margin: 0;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container" style="margin-top: 80px; padding: 0 1rem;">
        <h1 style="margin-bottom: 2rem; color: white;">Admin Analytics</h1>

        <div class="analytics-grid">
            <div class="stat-card">
                <div class="stat-label">Total Siswa</div>
                <div class="stat-value"><?php echo number_format($metrics['total_students']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Instruktur</div>
                <div class="stat-value"><?php echo number_format($metrics['total_instructors']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Kursus</div>
                <div class="stat-value"><?php echo number_format($metrics['total_courses']); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Pendaftaran</div>
                <div class="stat-value"><?php echo number_format($metrics['total_enrollments']); ?></div>
            </div>
        </div>

        <div class="row" style="display: flex; gap: 2rem; flex-wrap: wrap;">
            <div class="col" style="flex: 2; min-width: 300px;">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3 class="chart-title">Pertumbuhan User (30 Hari Terakhir)</h3>
                    </div>
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
            <div class="col" style="flex: 1; min-width: 300px;">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3 class="chart-title">Kursus Terpopuler</h3>
                    </div>
                    <canvas id="popularCoursesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        const userGrowthData = <?php echo json_encode($user_growth); ?>;
        
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: userGrowthData.map(d => d.date),
                datasets: [{
                    label: 'User Baru',
                    data: userGrowthData.map(d => d.count),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });

        // Popular Courses Chart
        const popularCoursesCtx = document.getElementById('popularCoursesChart').getContext('2d');
        const popularCoursesData = <?php echo json_encode($popular_courses); ?>;

        new Chart(popularCoursesCtx, {
            type: 'doughnut',
            data: {
                labels: popularCoursesData.map(d => d.judul_course),
                datasets: [{
                    data: popularCoursesData.map(d => d.student_count),
                    backgroundColor: [
                        '#8b5cf6',
                        '#ec4899',
                        '#10b981',
                        '#f59e0b',
                        '#3b82f6'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });
    </script>

    <?php include 'footer.php'; ?>
    <?php include 'includes/loading.php'; ?>
    <?php include 'includes/toast.php'; ?>
</body>
</html>

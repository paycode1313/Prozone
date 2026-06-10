<?php
// Deteksi halaman saat ini
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user_role'] ?? 'student';

// Include icon system
require_once __DIR__ . '/includes/icons.php';
?>
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2 class="sidebar-title"><?php echo APP_NAME; ?></h2>
    </div>
    
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>" data-tooltip="Dashboard">
                <i class="icon"><?php icon('dashboard', 18); ?></i> <span>Dashboard</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="courses.php" class="nav-link <?php echo ($current_page === 'courses.php') ? 'active' : ''; ?>" data-tooltip="Kursus">
                <i class="icon"><?php icon('book', 18); ?></i> <span>Kursus</span>
            </a>
        </li>
        
        <?php if ($role === 'student'): ?>
        <li class="nav-item">
            <a href="clan.php" class="nav-link <?php echo ($current_page === 'clan.php') ? 'active' : ''; ?>" data-tooltip="Clan">
                <i class="icon"><?php icon('clan', 18); ?></i> <span>Clan</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="leaderboard.php" class="nav-link <?php echo ($current_page === 'leaderboard.php') ? 'active' : ''; ?>" data-tooltip="Leaderboard">
                <i class="icon"><?php icon('trophy', 18); ?></i> <span>Leaderboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="achievements.php" class="nav-link <?php echo ($current_page === 'achievements.php') ? 'active' : ''; ?>" data-tooltip="Achievements">
                <i class="icon"><?php icon('target', 18); ?></i> <span>Achievements</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="analytics.php" class="nav-link <?php echo ($current_page === 'analytics.php') ? 'active' : ''; ?>" data-tooltip="Analytics">
                <i class="icon"><?php icon('chart', 18); ?></i> <span>Analytics</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="certificates.php" class="nav-link <?php echo ($current_page === 'certificates.php') ? 'active' : ''; ?>" data-tooltip="Sertifikat">
                <i class="icon"><?php icon('certificate', 18); ?></i> <span>Sertifikat</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="profile.php" class="nav-link <?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>" data-tooltip="Profile">
                <i class="icon"><?php icon('user', 18); ?></i> <span>Profile</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($role === 'admin' || $role === 'instructor'): ?>
        <li class="nav-item">
            <a href="manage-courses.php" class="nav-link <?php echo ($current_page === 'manage-courses.php') ? 'active' : ''; ?>" data-tooltip="Kelola Kursus">
                <i class="icon"><?php icon('settings', 18); ?></i> <span>Kelola Kursus</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="manage-lessons.php" class="nav-link <?php echo ($current_page === 'manage-lessons.php') ? 'active' : ''; ?>" data-tooltip="Kelola Lesson">
                <i class="icon"><?php icon('file', 18); ?></i> <span>Kelola Lesson</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($role === 'admin'): ?>
        <li class="nav-item">
            <a href="categories.php" class="nav-link <?php echo ($current_page === 'categories.php') ? 'active' : ''; ?>" data-tooltip="Kategori Kursus">
                <i class="icon"><?php icon('tag', 18); ?></i> <span>Kategori Kursus</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="users.php" class="nav-link <?php echo ($current_page === 'users.php') ? 'active' : ''; ?>" data-tooltip="Manajemen User">
                <i class="icon"><?php icon('users', 18); ?></i> <span>Manajemen User</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="pengaturan.php" class="nav-link <?php echo ($current_page === 'pengaturan.php') ? 'active' : ''; ?>" data-tooltip="Pengaturan">
                <i class="icon"><?php icon('settings', 18); ?></i> <span>Pengaturan</span>
            </a>
        </li>
        <?php endif; ?>
        
        <li class="nav-item">
            <a href="logout.php" class="nav-link logout-link" data-tooltip="Logout">
                <i class="icon"><?php icon('logout', 18); ?></i> <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>
<script src="assets/js/mobile-menu.js"></script>

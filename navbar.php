<?php
// Deteksi halaman saat ini
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user_role'] ?? 'student';
?>
<link rel="stylesheet" href="assets/css/notifications.css">
<nav class="dashboard-navbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <a href="dashboard.php" class="brand-link">
                <svg class="logo-img-nav" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="logoGradientNav" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#6d28d9;stop-opacity:1" />
                            <stop offset="50%" style="stop-color:#7c3aed;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    <path d="M 25 20 L 25 75 Q 25 80 30 80 L 35 80 Q 40 80 40 75 L 40 20 Q 40 15 35 15 L 30 15 Q 25 15 25 20 Z" fill="url(#logoGradientNav)"/>
                    <path d="M 40 20 Q 40 15 45 15 L 60 15 Q 70 15 70 25 L 70 35 Q 70 45 60 45 L 45 45 Q 40 45 40 40 L 40 30 Q 40 25 45 25 L 60 25 Q 65 25 65 30 L 65 35 Q 65 40 60 40 L 45 40 Q 40 40 40 35 Z" fill="url(#logoGradientNav)"/>
                </svg>
                <div class="logo-text-nav">
                    <span class="brand-text"><?php echo APP_NAME; ?></span>
                </div>
            </a>
        </div>
        
        <div class="navbar-right">
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <div class="navbar-menu" id="navbarMenu">
                <a href="dashboard.php" class="nav-menu-item <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                    Dashboard
                </a>
                
                <a href="courses.php" class="nav-menu-item <?php echo ($current_page === 'courses.php') ? 'active' : ''; ?>">
                    Kursus
                </a>
                
                <?php if ($role === 'student'): ?>
                <a href="clan.php" class="nav-menu-item <?php echo ($current_page === 'clan.php') ? 'active' : ''; ?>">
                    Clan
                </a>
                <a href="friends.php" class="nav-menu-item <?php echo ($current_page === 'friends.php') ? 'active' : ''; ?>">
                    Teman
                </a>
                <a href="leaderboard.php" class="nav-menu-item <?php echo ($current_page === 'leaderboard.php') ? 'active' : ''; ?>">
                    Leaderboard
                </a>
                <a href="playground.php" class="nav-menu-item <?php echo ($current_page === 'playground.php') ? 'active' : ''; ?>">
                    Playground
                </a>
                <a href="profile.php" class="nav-menu-item <?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>">
                    Profile
                </a>
                <?php endif; ?>
                
                <?php if ($role === 'admin' || $role === 'instructor'): ?>
                <a href="manage-courses.php" class="nav-menu-item <?php echo ($current_page === 'manage-courses.php') ? 'active' : ''; ?>">
                    Kelola Kursus
                </a>
                <a href="manage-lessons.php" class="nav-menu-item <?php echo ($current_page === 'manage-lessons.php') ? 'active' : ''; ?>">
                    Kelola Lesson
                </a>
                <?php endif; ?>
                
                <?php if ($role === 'admin'): ?>
                <a href="categories.php" class="nav-menu-item <?php echo ($current_page === 'categories.php') ? 'active' : ''; ?>">
                    Kategori Kursus
                </a>
                <a href="users.php" class="nav-menu-item <?php echo ($current_page === 'users.php') ? 'active' : ''; ?>">
                    Manajemen User
                </a>
                <a href="admin_analytics.php" class="nav-menu-item <?php echo ($current_page === 'admin_analytics.php') ? 'active' : ''; ?>">
                    Analytics
                </a>
                <a href="pengaturan.php" class="nav-menu-item <?php echo ($current_page === 'pengaturan.php') ? 'active' : ''; ?>">
                    Pengaturan
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Notification System -->
            <div class="notification-wrapper">
                <button class="notification-btn" id="notificationBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="notification-badge" id="notificationBadge">0</span>
                </button>
                
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h3>Notifikasi</h3>
                        <button class="mark-all-read" id="markAllRead">Tandai semua dibaca</button>
                    </div>
                    <ul class="notification-list" id="notificationList">
                        <!-- Notifications will be loaded here -->
                        <div class="notification-empty">Loading...</div>
                    </ul>
                </div>
            </div>

            <div class="navbar-user">
                <div class="user-dropdown">
                    <div class="user-avatar-nav">
                        <?php
                            $nama = $_SESSION['nama_lengkap'] ?? ($_SESSION['username'] ?? 'U');
                            echo strtoupper(substr($nama, 0, 1));
                        ?>
                    </div>
                    <div class="user-info-nav">
                        <div class="user-name-nav">
                            <?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? ($_SESSION['username'] ?? 'User')); ?>
                        </div>
                        <div class="user-role-nav">
                            <?php echo ucfirst($_SESSION['user_role'] ?? 'student'); ?>
                        </div>
                    </div>
                    <div class="user-dropdown-menu">
                        <a href="profile.php" class="dropdown-item">Profile</a>
                        <a href="logout.php" class="dropdown-item logout-item">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
<script src="assets/js/notifications.js"></script>


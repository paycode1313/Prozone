<?php
require_once 'config/config.php';
requireLogin();
require_once 'config/language.php';
require_once 'includes/icons.php';
require_once 'includes/FileUpload.php';

require_once 'models/User.php';
require_once 'models/Achievement.php';
require_once 'models/Course.php';
require_once 'models/Enrollment.php';
require_once 'models/UserProgress.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Get active tab from URL
$active_tab = $_GET['tab'] ?? 'edit';

// Get flash message from session (PRG pattern)
$message = '';
$message_type = '';
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// CSRF Check for all POST requests
if ($_POST) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = 'Sesi tidak valid (CSRF Token Error). Silakan refresh halaman.';
        $_SESSION['flash_type'] = 'error';
        header('Location: profile.php?tab=' . $active_tab);
        exit;
    }
}

// Handle update profile
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $user->id = $_SESSION['user_id'];
    $user->nama_lengkap = sanitizeInput($_POST['nama_lengkap']);
    $user->email = sanitizeInput($_POST['email']);
    
    $upload_error = false;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload = FileUpload::uploadAvatar($_FILES['avatar'], $_SESSION['user_id']);
        
        if ($upload['success']) {
            $user->avatar = $upload['filename'];
            $_SESSION['avatar'] = $upload['filename'];
        } else {
            $_SESSION['flash_message'] = $upload['error'];
            $_SESSION['flash_type'] = 'error';
            $upload_error = true;
        }
    }
    
    if (!$upload_error) {
        if ($user->update()) {
            $_SESSION['nama_lengkap'] = $user->nama_lengkap;
            $_SESSION['email'] = $user->email;
            $_SESSION['flash_message'] = 'Profile berhasil diperbarui!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Gagal memperbarui profile!';
            $_SESSION['flash_type'] = 'error';
        }
    }
    header('Location: profile.php?tab=' . $active_tab);
    exit;
}

// Handle change password
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    
    if ($user->login($_SESSION['username'], $old_password)) {
        if ($user->changePassword($new_password)) {
            $_SESSION['flash_message'] = 'Password berhasil diubah!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Gagal mengubah password!';
            $_SESSION['flash_type'] = 'error';
        }
    } else {
        $_SESSION['flash_message'] = 'Password lama salah!';
        $_SESSION['flash_type'] = 'error';
    }
    header('Location: profile.php?tab=' . $active_tab);
    exit;
}

// Handle change language
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'change_language') {
    $language = sanitizeInput($_POST['language']);
    setLanguage($language);
    $_SESSION['flash_message'] = 'Bahasa berhasil diubah!';
    $_SESSION['flash_type'] = 'success';
    header('Location: profile.php?tab=settings');
    exit;
}

// Handle change theme
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'change_theme') {
    $theme = sanitizeInput($_POST['theme']);
    $_SESSION['theme'] = $theme;
    
    $query = "UPDATE users SET theme_preference = :theme WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':theme', $theme);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    $_SESSION['flash_message'] = 'Tema berhasil diubah!';
    $_SESSION['flash_type'] = 'success';
    header('Location: profile.php?tab=settings');
    exit;
}

// Get current preferences
$query_pref = "SELECT language_preference, theme_preference, created_at FROM users WHERE id = :user_id";
$stmt_pref = $db->prepare($query_pref);
$stmt_pref->bindParam(':user_id', $_SESSION['user_id']);
$stmt_pref->execute();
$preferences = $stmt_pref->fetch(PDO::FETCH_ASSOC);
$current_language = $preferences['language_preference'] ?? 'id';
$current_theme = $preferences['theme_preference'] ?? 'dark';
$join_date = $preferences['created_at'] ?? date('Y-m-d');

// Get data for Achievements tab
$achievement = new Achievement($db);
$achievements_stmt = $achievement->getUserAchievements($_SESSION['user_id']);
$achievements = [];
while ($row = $achievements_stmt->fetch(PDO::FETCH_ASSOC)) {
    $achievements[] = $row;
}
$total_achievements = count($achievements);
$earned_count = 0;
foreach ($achievements as $ach) {
    if ($ach['earned_at']) {
        $earned_count++;
    }
}
$progress_percent = $total_achievements > 0 ? ($earned_count / $total_achievements) * 100 : 0;

// Get data for Analytics tab
$course = new Course($db);
$enrollment = new Enrollment($db);
$user_progress = new UserProgress($db);
$user_id = $_SESSION['user_id'];

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

$query_user = "SELECT total_xp, level, coins FROM users WHERE id = :user_id";
$stmt_user = $db->prepare($query_user);
$stmt_user->bindParam(':user_id', $user_id);
$stmt_user->execute();
$user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
$user_xp = $user_data['total_xp'] ?? 0;
$user_level = $user_data['level'] ?? 1;
$user_coins = $user_data['coins'] ?? 0;

$xp_for_current_level = ($user_level - 1) * 100;
$xp_for_next_level = $user_level * 100;
$xp_progress = $xp_for_next_level - $xp_for_current_level;
$xp_current = $user_xp - $xp_for_current_level;
$xp_percent = $xp_progress > 0 ? ($xp_current / $xp_progress) * 100 : 0;

$achievements_stmt2 = $achievement->getUserAchievements($user_id);
$total_achievements_analytics = 0;
$earned_achievements = 0;
while ($row = $achievements_stmt2->fetch(PDO::FETCH_ASSOC)) {
    $total_achievements_analytics++;
    if ($row['earned_at']) {
        $earned_achievements++;
    }
}

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

// Get leaderboard rank
$query_rank = "SELECT COUNT(*) + 1 as user_rank FROM users WHERE total_xp > (SELECT total_xp FROM users WHERE id = :user_id) AND role = 'student'";
$stmt_rank = $db->prepare($query_rank);
$stmt_rank->bindParam(':user_id', $user_id);
$stmt_rank->execute();
$rank_data = $stmt_rank->fetch(PDO::FETCH_ASSOC);
$user_rank = $rank_data['user_rank'] ?? 1;

// Get data for Certificates tab
$query_cert = "SELECT c.*, e.completed_at, e.progress_percent
              FROM enrollments e
              JOIN courses c ON e.course_id = c.id
              WHERE e.user_id = :user_id 
              AND e.status = 'completed'
              ORDER BY e.completed_at DESC";
$stmt_cert = $db->prepare($query_cert);
$stmt_cert->bindParam(':user_id', $_SESSION['user_id']);
$stmt_cert->execute();
$certificates = [];
while ($row = $stmt_cert->fetch(PDO::FETCH_ASSOC)) {
    $certificates[] = $row;
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('Profile - ' . APP_NAME, 'Kelola profil dan lihat progress belajar Anda', 'profile, user, settings'); ?>
    <title>Profile - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <link rel="stylesheet" href="assets/css/ui-enhancements.css">
    <link rel="stylesheet" href="assets/css/glassmorphism.css">
    <style>
        /* Profile Hero Card */
        .profile-hero {
            background: linear-gradient(135deg, 
                rgba(139, 92, 246, 0.15) 0%, 
                rgba(59, 130, 246, 0.1) 50%,
                rgba(236, 72, 153, 0.08) 100%);
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }
        .profile-hero::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        .profile-hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 2rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .profile-avatar-section {
            position: relative;
        }
        .profile-avatar-large {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3.5rem;
            font-weight: bold;
            position: relative;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.3), 0 10px 40px rgba(139, 92, 246, 0.3);
            animation: avatar-glow 3s ease-in-out infinite;
        }
        .profile-avatar-large img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        @keyframes avatar-glow {
            0%, 100% { box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.3), 0 10px 40px rgba(139, 92, 246, 0.3); }
            50% { box-shadow: 0 0 0 6px rgba(139, 92, 246, 0.4), 0 10px 50px rgba(139, 92, 246, 0.4); }
        }
        .level-badge {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1rem;
            color: #1a1a2e;
            border: 3px solid #1a1a2e;
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);
        }
        .profile-info-section {
            flex: 1;
            min-width: 250px;
        }
        .profile-name {
            font-size: 2rem;
            font-weight: 800;
            color: #e2e8f0;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .verified-badge {
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .profile-username {
            color: #8b5cf6;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        .profile-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #94a3b8;
            font-size: 0.875rem;
        }
        .profile-meta-item svg {
            color: #64748b;
        }
        .profile-xp-bar {
            background: rgba(30, 30, 55, 0.5);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            max-width: 400px;
        }
        .profile-xp-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .profile-xp-label {
            color: #94a3b8;
            font-size: 0.8rem;
        }
        .profile-xp-value {
            color: #a78bfa;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .profile-xp-progress {
            height: 8px;
            background: rgba(139, 92, 246, 0.2);
            border-radius: 4px;
            overflow: hidden;
        }
        .profile-xp-fill {
            height: 100%;
            background: linear-gradient(90deg, #8b5cf6, #a78bfa);
            border-radius: 4px;
            transition: width 1s ease-out;
        }
        .profile-quick-stats {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .quick-stat {
            background: rgba(30, 30, 55, 0.5);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            text-align: center;
            min-width: 100px;
            transition: all 0.3s ease;
        }
        .quick-stat:hover {
            border-color: rgba(139, 92, 246, 0.4);
            transform: translateY(-2px);
        }
        .quick-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .quick-stat-label {
            color: #64748b;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 0.25rem;
        }

        /* Tab Navigation */
        .profile-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            background: rgba(30, 30, 55, 0.5);
            padding: 0.5rem;
            border-radius: 16px;
            border: 1px solid rgba(139, 92, 246, 0.1);
            overflow-x: auto;
            scrollbar-width: none;
        }
        .profile-tabs::-webkit-scrollbar {
            display: none;
        }
        .profile-tab {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: transparent;
            border: none;
            color: #94a3b8;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .profile-tab:hover {
            color: #e2e8f0;
            background: rgba(139, 92, 246, 0.1);
        }
        .profile-tab.active {
            color: white;
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }
        .profile-tab svg {
            width: 18px;
            height: 18px;
        }

        .tab-content {
            display: none;
            animation: fadeSlideIn 0.4s ease;
        }
        .tab-content.active {
            display: block;
        }
        @keyframes fadeSlideIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Profile Section Card */
        .profile-section {
            background: rgba(30, 30, 55, 0.5);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(139, 92, 246, 0.15);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .profile-section h2 {
            color: #e2e8f0;
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .profile-section h2 svg {
            color: #8b5cf6;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #e2e8f0;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .form-label svg {
            color: #8b5cf6;
            width: 16px;
            height: 16px;
        }
        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 12px;
            background: rgba(15, 15, 35, 0.6);
            color: #e2e8f0;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: rgba(139, 92, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        .form-input::placeholder {
            color: #64748b;
        }
        .form-hint {
            color: #64748b;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        /* Avatar Upload */
        .avatar-upload-wrapper {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .avatar-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 2rem;
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.3);
            flex-shrink: 0;
        }
        .avatar-preview img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .avatar-upload-area {
            flex: 1;
            min-width: 200px;
            padding: 1.25rem;
            border: 2px dashed rgba(139, 92, 246, 0.3);
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(139, 92, 246, 0.05);
        }
        .avatar-upload-area:hover {
            border-color: rgba(139, 92, 246, 0.5);
            background: rgba(139, 92, 246, 0.1);
        }
        .avatar-upload-area input[type="file"] {
            display: none;
        }
        .upload-icon {
            color: #8b5cf6;
            margin-bottom: 0.5rem;
        }
        .upload-text {
            color: #94a3b8;
            font-size: 0.85rem;
        }
        .upload-text span {
            color: #8b5cf6;
            font-weight: 600;
        }

        /* Password Strength */
        .password-strength {
            margin-top: 0.5rem;
        }
        .strength-bar {
            height: 4px;
            background: rgba(139, 92, 246, 0.2);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 0.375rem;
        }
        .strength-fill {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        .strength-fill.weak { width: 25%; background: #ef4444; }
        .strength-fill.fair { width: 50%; background: #f59e0b; }
        .strength-fill.good { width: 75%; background: #3b82f6; }
        .strength-fill.strong { width: 100%; background: #10b981; }
        .strength-text {
            font-size: 0.75rem;
            font-weight: 500;
        }
        .strength-text.weak { color: #ef4444; }
        .strength-text.fair { color: #f59e0b; }
        .strength-text.good { color: #3b82f6; }
        .strength-text.strong { color: #10b981; }

        /* Setting Item */
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem;
            background: rgba(15, 15, 35, 0.5);
            border-radius: 14px;
            border: 1px solid rgba(139, 92, 246, 0.1);
            transition: all 0.3s ease;
        }
        .setting-item:hover {
            border-color: rgba(139, 92, 246, 0.3);
        }
        .setting-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .setting-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .setting-icon.lang { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .setting-icon.theme { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        .setting-label {
            color: #e2e8f0;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .setting-desc {
            color: #64748b;
            font-size: 0.8rem;
        }
        .toggle-group {
            display: flex;
            gap: 0.375rem;
            background: rgba(15, 15, 35, 0.8);
            padding: 0.25rem;
            border-radius: 10px;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }
        .toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 1rem;
            border: none;
            background: transparent;
            color: #94a3b8;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .toggle-btn svg {
            flex-shrink: 0;
        }
        .toggle-btn.active {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            color: white;
            box-shadow: 0 2px 10px rgba(139, 92, 246, 0.3);
        }

        /* Submit Button */
        .btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(139, 92, 246, 0.4);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: rgba(15, 15, 35, 0.5);
            border: 1px solid rgba(139, 92, 246, 0.15);
            border-radius: 16px;
            padding: 1.25rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            border-color: rgba(139, 92, 246, 0.4);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.15);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            color: white;
        }
        .stat-icon.xp { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        .stat-icon.courses { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .stat-icon.lessons { background: linear-gradient(135deg, #10b981, #34d399); }
        .stat-icon.streak { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .stat-icon.achievements { background: linear-gradient(135deg, #ec4899, #f472b6); }
        .stat-icon.rank { background: linear-gradient(135deg, #6366f1, #818cf8); }
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, #e2e8f0 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.25rem;
        }
        .stat-label {
            color: #64748b;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Level Progress Card */
        .level-card {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .level-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .level-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .level-circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 30px rgba(139, 92, 246, 0.4);
        }
        .level-circle span {
            color: white;
            font-size: 1.75rem;
            font-weight: 800;
        }
        .level-text h3 {
            color: #e2e8f0;
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        .level-text p {
            color: #64748b;
            font-size: 0.85rem;
        }
        .level-xp-needed {
            text-align: right;
        }
        .level-xp-needed .xp-value {
            color: #a78bfa;
            font-size: 1.25rem;
            font-weight: 700;
        }
        .level-xp-needed .xp-label {
            color: #64748b;
            font-size: 0.8rem;
        }
        .level-progress-bar {
            height: 12px;
            background: rgba(139, 92, 246, 0.2);
            border-radius: 6px;
            overflow: hidden;
        }
        .level-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #8b5cf6, #a78bfa, #c4b5fd);
            border-radius: 6px;
            transition: width 1s ease-out;
            position: relative;
        }
        .level-progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Course Progress */
        .courses-progress-section {
            background: rgba(15, 15, 35, 0.5);
            border: 1px solid rgba(139, 92, 246, 0.15);
            border-radius: 16px;
            padding: 1.5rem;
        }
        .courses-progress-section h3 {
            color: #e2e8f0;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .course-item {
            padding: 1rem;
            background: rgba(139, 92, 246, 0.05);
            border-radius: 12px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .course-item:hover {
            background: rgba(139, 92, 246, 0.1);
        }
        .course-item:last-child {
            margin-bottom: 0;
        }
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .course-name {
            color: #e2e8f0;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .course-percent {
            color: #a78bfa;
            font-weight: 700;
            font-size: 0.95rem;
        }
        .course-progress-bar {
            height: 8px;
            background: rgba(139, 92, 246, 0.2);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        .course-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #8b5cf6, #a78bfa);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #64748b;
        }
        .course-completed-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            color: #10b981;
            font-weight: 500;
        }

        /* Achievement Grid */
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        .achievement-card {
            background: rgba(15, 15, 35, 0.5);
            border: 1px solid rgba(139, 92, 246, 0.15);
            border-radius: 16px;
            padding: 1.25rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .achievement-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #8b5cf6, #a78bfa);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .achievement-card:hover {
            transform: translateY(-5px);
            border-color: rgba(139, 92, 246, 0.4);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.2);
        }
        .achievement-card:hover::before {
            transform: scaleX(1);
        }
        .achievement-card.earned {
            border-color: rgba(139, 92, 246, 0.4);
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(15, 15, 35, 0.5) 100%);
        }
        .achievement-card.earned::before {
            transform: scaleX(1);
        }
        .achievement-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            filter: grayscale(1);
            transition: filter 0.3s;
            line-height: 1;
        }
        .achievement-icon svg {
            width: 40px;
            height: 40px;
        }
        .achievement-card.earned .achievement-icon {
            filter: grayscale(0);
        }
        .achievement-name {
            color: #e2e8f0;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.375rem;
        }
        .achievement-desc {
            color: #64748b;
            font-size: 0.75rem;
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }
        .achievement-xp {
            display: inline-block;
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .achievement-badge {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background: #10b981;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 8px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .achievement-card:not(.earned) .achievement-badge {
            display: none;
        }

        /* Certificate Grid */
        .certificates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }
        .certificate-card {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(15, 15, 35, 0.5) 100%);
            border: 1px solid rgba(251, 191, 36, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .certificate-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #fbbf24, #f59e0b);
        }
        .certificate-card:hover {
            transform: translateY(-5px);
            border-color: rgba(251, 191, 36, 0.4);
            box-shadow: 0 10px 30px rgba(251, 191, 36, 0.15);
        }
        .certificate-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            color: #fbbf24;
        }
        .certificate-icon svg {
            width: 36px;
            height: 36px;
        }
        .certificate-title {
            color: #e2e8f0;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .certificate-info {
            color: #64748b;
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }
        .certificate-date {
            color: #fbbf24;
            font-weight: 600;
            font-size: 0.85rem;
            margin: 0.75rem 0;
        }
        .btn-cert-download {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #1a1a2e;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-cert-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #94a3b8;
        }
        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
            color: #8b5cf6;
        }
        .empty-icon svg {
            width: 48px;
            height: 48px;
        }
        .empty-title {
            color: #e2e8f0;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .empty-text {
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        /* Alert */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-hero {
                padding: 1.5rem;
            }
            .profile-hero-content {
                flex-direction: column;
                text-align: center;
            }
            .profile-avatar-large {
                width: 120px;
                height: 120px;
                font-size: 3rem;
            }
            .profile-name {
                justify-content: center;
                font-size: 1.5rem;
            }
            .profile-meta {
                justify-content: center;
            }
            .profile-quick-stats {
                justify-content: center;
            }
            .profile-xp-bar {
                max-width: 100%;
                margin: 0 auto 1rem;
            }
            .profile-tabs {
                padding: 0.375rem;
            }
            .profile-tab {
                padding: 0.625rem 1rem;
                font-size: 0.8rem;
            }
            .profile-tab span {
                display: none;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .achievements-grid,
            .certificates-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'navbar.php'; ?>

    <div class="dashboard-main-container">
        <div class="dashboard-content">
            <div class="page-wrapper">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php if ($message_type === 'success'): ?>
                            <?php icon('check-circle', 20); ?>
                        <?php else: ?>
                            <?php icon('alert-circle', 20); ?>
                        <?php endif; ?>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Hero Card -->
                <div class="profile-hero">
                    <div class="profile-hero-content">
                        <div class="profile-avatar-section">
                            <div class="profile-avatar-large">
                                <?php if (!empty($user->avatar) && file_exists('assets/uploads/avatars/' . $user->avatar)): ?>
                                    <img src="assets/uploads/avatars/<?php echo $user->avatar; ?>" alt="Avatar">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)); ?>
                                <?php endif; ?>
                                <div class="level-badge"><?php echo $user_level; ?></div>
                            </div>
                        </div>
                        <div class="profile-info-section">
                            <h1 class="profile-name">
                                <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
                                <span class="verified-badge"><?php icon('check', 10); ?> Verified</span>
                            </h1>
                            <div class="profile-username">@<?php echo htmlspecialchars($_SESSION['username']); ?></div>
                            <div class="profile-meta">
                                <div class="profile-meta-item">
                                    <?php icon('mail', 14); ?>
                                    <?php echo htmlspecialchars($_SESSION['email'] ?? 'No email'); ?>
                                </div>
                                <div class="profile-meta-item">
                                    <?php icon('calendar', 14); ?>
                                    Joined <?php echo date('M Y', strtotime($join_date)); ?>
                                </div>
                            </div>
                            <div class="profile-xp-bar">
                                <div class="profile-xp-header">
                                    <span class="profile-xp-label">XP Progress to Level <?php echo $user_level + 1; ?></span>
                                    <span class="profile-xp-value"><?php echo number_format($xp_current); ?> / <?php echo number_format($xp_progress); ?></span>
                                </div>
                                <div class="profile-xp-progress">
                                    <div class="profile-xp-fill" style="width: <?php echo $xp_percent; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="profile-quick-stats">
                            <div class="quick-stat">
                                <div class="quick-stat-value"><?php echo number_format($user_xp); ?></div>
                                <div class="quick-stat-label">Total XP</div>
                            </div>
                            <div class="quick-stat">
                                <div class="quick-stat-value">#<?php echo $user_rank; ?></div>
                                <div class="quick-stat-label">Rank</div>
                            </div>
                            <div class="quick-stat">
                                <div class="quick-stat-value"><?php echo $learning_streak; ?></div>
                                <div class="quick-stat-label">Day Streak</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="profile-tabs">
                    <button class="profile-tab <?php echo $active_tab === 'edit' ? 'active' : ''; ?>" onclick="switchTab('edit')">
                        <?php icon('user', 18); ?>
                        <span>Edit Profil</span>
                    </button>
                    <button class="profile-tab <?php echo $active_tab === 'analytics' ? 'active' : ''; ?>" onclick="switchTab('analytics')">
                        <?php icon('bar-chart-2', 18); ?>
                        <span>Analytics</span>
                    </button>
                    <button class="profile-tab <?php echo $active_tab === 'achievements' ? 'active' : ''; ?>" onclick="switchTab('achievements')">
                        <?php icon('award', 18); ?>
                        <span>Achievement</span>
                    </button>
                    <button class="profile-tab <?php echo $active_tab === 'certificates' ? 'active' : ''; ?>" onclick="switchTab('certificates')">
                        <?php icon('file-text', 18); ?>
                        <span>Sertifikat</span>
                    </button>
                    <button class="profile-tab <?php echo $active_tab === 'settings' ? 'active' : ''; ?>" onclick="switchTab('settings')">
                        <?php icon('settings', 18); ?>
                        <span>Pengaturan</span>
                    </button>
                </div>

                <!-- Tab: Edit Profile -->
                <div id="tab-edit" class="tab-content <?php echo $active_tab === 'edit' ? 'active' : ''; ?>">
                    <div class="profile-section">
                        <h2><?php icon('user', 20); ?> Edit Profile</h2>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-group">
                                <label class="form-label"><?php icon('image', 16); ?> Foto Profil</label>
                                <div class="avatar-upload-wrapper">
                                    <div class="avatar-preview">
                                        <?php if (!empty($user->avatar) && file_exists('assets/uploads/avatars/' . $user->avatar)): ?>
                                            <img src="assets/uploads/avatars/<?php echo $user->avatar; ?>" alt="Avatar" id="avatarPreview">
                                        <?php else: ?>
                                            <span id="avatarInitial"><?php echo strtoupper(substr($user->nama_lengkap, 0, 1)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <label class="avatar-upload-area" for="avatarInput">
                                        <div class="upload-icon"><?php icon('upload', 24); ?></div>
                                        <div class="upload-text">
                                            <span>Klik untuk upload</span> atau drag & drop
                                        </div>
                                        <input type="file" name="avatar" accept="image/*" id="avatarInput">
                                    </label>
                                </div>
                                <div class="form-hint"><?php icon('info', 12); ?> Format: JPG, PNG, GIF. Max: 2MB</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><?php icon('user', 16); ?> Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>" required class="form-input" placeholder="Masukkan nama lengkap">
                            </div>

                            <div class="form-group">
                                <label class="form-label"><?php icon('mail', 16); ?> Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" class="form-input" placeholder="Masukkan email">
                            </div>

                            <button type="submit" class="btn-submit">
                                <?php icon('save', 18); ?>
                                Simpan Perubahan
                            </button>
                        </form>
                    </div>

                    <div class="profile-section">
                        <h2><?php icon('lock', 20); ?> Ubah Password</h2>
                        <form method="POST" id="passwordForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="change_password">

                            <div class="form-group">
                                <label class="form-label"><?php icon('key', 16); ?> Password Lama</label>
                                <input type="password" name="old_password" required class="form-input" placeholder="Masukkan password lama">
                            </div>

                            <div class="form-group">
                                <label class="form-label"><?php icon('lock', 16); ?> Password Baru</label>
                                <input type="password" name="new_password" id="newPassword" required minlength="6" class="form-input" placeholder="Masukkan password baru">
                                <div class="password-strength" id="passwordStrength" style="display: none;">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                    <span class="strength-text" id="strengthText"></span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><?php icon('check-circle', 16); ?> Konfirmasi Password</label>
                                <input type="password" name="confirm_password" required minlength="6" class="form-input" placeholder="Konfirmasi password baru">
                            </div>

                            <button type="submit" class="btn-submit">
                                <?php icon('refresh-cw', 18); ?>
                                Ubah Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tab: Analytics -->
                <div id="tab-analytics" class="tab-content <?php echo $active_tab === 'analytics' ? 'active' : ''; ?>">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon xp"><?php icon('star', 22); ?></div>
                            <div class="stat-value"><?php echo number_format($user_xp); ?></div>
                            <div class="stat-label">Total XP</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon courses"><?php icon('book', 22); ?></div>
                            <div class="stat-value"><?php echo count($enrolled_courses); ?></div>
                            <div class="stat-label">Enrolled</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon lessons"><?php icon('check-circle', 22); ?></div>
                            <div class="stat-value"><?php echo $total_lessons_completed; ?></div>
                            <div class="stat-label">Lessons</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon streak"><?php icon('fire', 22); ?></div>
                            <div class="stat-value"><?php echo $learning_streak; ?></div>
                            <div class="stat-label">Day Streak</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon achievements"><?php icon('award', 22); ?></div>
                            <div class="stat-value"><?php echo $earned_achievements; ?>/<?php echo $total_achievements_analytics; ?></div>
                            <div class="stat-label">Achievements</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon rank"><?php icon('trending-up', 22); ?></div>
                            <div class="stat-value">#<?php echo $user_rank; ?></div>
                            <div class="stat-label">Rank</div>
                        </div>
                    </div>

                    <div class="level-card">
                        <div class="level-header">
                            <div class="level-info">
                                <div class="level-circle">
                                    <span><?php echo $user_level; ?></span>
                                </div>
                                <div class="level-text">
                                    <h3>Level <?php echo $user_level; ?></h3>
                                    <p><?php echo number_format($user_xp); ?> Total XP</p>
                                </div>
                            </div>
                            <div class="level-xp-needed">
                                <div class="xp-value"><?php echo number_format($xp_progress - $xp_current); ?></div>
                                <div class="xp-label">XP to Level <?php echo $user_level + 1; ?></div>
                            </div>
                        </div>
                        <div class="level-progress-bar">
                            <div class="level-progress-fill" style="width: <?php echo $xp_percent; ?>%"></div>
                        </div>
                    </div>

                    <div class="courses-progress-section">
                        <h3><?php icon('book-open', 18); ?> Progress Kursus</h3>
                        <?php if (empty($enrolled_courses)): ?>
                            <div class="empty-state">
                                <div class="empty-icon"><?php icon('book-open', 48); ?></div>
                                <div class="empty-title">Belum ada kursus</div>
                                <div class="empty-text">Mulai belajar dengan mendaftar ke kursus pertama Anda!</div>
                                <a href="courses.php" class="btn-submit" style="text-decoration: none;">
                                    <?php icon('search', 18); ?>
                                    Jelajahi Kursus
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($enrolled_courses as $course_item): ?>
                                <div class="course-item">
                                    <div class="course-header">
                                        <div class="course-name"><?php echo htmlspecialchars($course_item['judul_course']); ?></div>
                                        <div class="course-percent"><?php echo number_format($course_item['progress_percent'], 0); ?>%</div>
                                    </div>
                                    <div class="course-progress-bar">
                                        <div class="course-progress-fill" style="width: <?php echo $course_item['progress_percent']; ?>%"></div>
                                    </div>
                                    <div class="course-meta">
                                        <span><?php echo $course_item['completed_lessons']; ?> / <?php echo $course_item['total_lessons']; ?> lessons</span>
                                        <?php if ($course_item['status'] == 'completed'): ?>
                                            <span class="course-completed-badge"><?php icon('check-circle', 14); ?> Selesai</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab: Achievements -->
                <div id="tab-achievements" class="tab-content <?php echo $active_tab === 'achievements' ? 'active' : ''; ?>">
                    <div class="profile-section">
                        <h2><?php icon('award', 20); ?> Achievement</h2>
                        <div class="stats-grid" style="margin-bottom: 1.5rem;">
                            <div class="stat-card">
                                <div class="stat-icon achievements"><?php icon('award', 22); ?></div>
                                <div class="stat-value"><?php echo $earned_count; ?></div>
                                <div class="stat-label">Diperoleh</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon courses"><?php icon('target', 22); ?></div>
                                <div class="stat-value"><?php echo $total_achievements; ?></div>
                                <div class="stat-label">Total</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon streak"><?php icon('percent', 22); ?></div>
                                <div class="stat-value"><?php echo number_format($progress_percent, 0); ?>%</div>
                                <div class="stat-label">Progress</div>
                            </div>
                        </div>

                        <div class="achievements-grid">
                            <?php if (empty($achievements)): ?>
                                <div class="empty-state" style="grid-column: 1 / -1;">
                                    <div class="empty-icon"><?php icon('trophy', 48); ?></div>
                                    <div class="empty-title">Belum ada achievement</div>
                                    <div class="empty-text">Selesaikan tantangan untuk mendapatkan achievement!</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($achievements as $ach): ?>
                                    <?php $is_earned = !empty($ach['earned_at']); ?>
                                    <div class="achievement-card <?php echo $is_earned ? 'earned' : ''; ?>">
                                        <?php if ($is_earned): ?>
                                            <div class="achievement-badge">✓</div>
                                        <?php endif; ?>
                                        <div class="achievement-icon"><?php echo htmlspecialchars($ach['icon']); ?></div>
                                        <div class="achievement-name"><?php echo htmlspecialchars($ach['nama_achievement']); ?></div>
                                        <div class="achievement-desc"><?php echo htmlspecialchars($ach['deskripsi']); ?></div>
                                        <div class="achievement-xp">+<?php echo $ach['xp_reward']; ?> XP</div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tab: Certificates -->
                <div id="tab-certificates" class="tab-content <?php echo $active_tab === 'certificates' ? 'active' : ''; ?>">
                    <div class="profile-section">
                        <h2><?php icon('file-text', 20); ?> Sertifikat</h2>
                        <div class="certificates-grid">
                            <?php if (empty($certificates)): ?>
                                <div class="empty-state" style="grid-column: 1 / -1;">
                                    <div class="empty-icon"><?php icon('scroll', 48); ?></div>
                                    <div class="empty-title">Belum ada sertifikat</div>
                                    <div class="empty-text">Selesaikan kursus untuk mendapatkan sertifikat!</div>
                                    <a href="courses.php" class="btn-submit" style="text-decoration: none;">
                                        <?php icon('search', 18); ?>
                                        Jelajahi Kursus
                                    </a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($certificates as $cert): ?>
                                    <div class="certificate-card">
                                        <div class="certificate-icon"><?php icon('award', 36); ?></div>
                                        <div class="certificate-title"><?php echo htmlspecialchars($cert['judul_course']); ?></div>
                                        <div class="certificate-info">Level: <?php echo ucfirst($cert['level']); ?></div>
                                        <div class="certificate-date"><?php echo date('d M Y', strtotime($cert['completed_at'])); ?></div>
                                        <a href="certificates.php" class="btn-cert-download">
                                            <?php icon('download', 16); ?>
                                            Download
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tab: Settings -->
                <div id="tab-settings" class="tab-content <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">
                    <div class="profile-section">
                        <h2><?php icon('globe', 20); ?> Bahasa / Language</h2>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="change_language">
                            <input type="hidden" name="language" id="selectedLanguage" value="<?php echo $current_language; ?>">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-icon lang"><?php icon('globe', 20); ?></div>
                                    <div>
                                        <div class="setting-label">Bahasa Aplikasi</div>
                                        <div class="setting-desc">Pilih bahasa yang ingin digunakan</div>
                                    </div>
                                </div>
                                <div class="toggle-group">
                                    <button type="button" class="toggle-btn <?php echo $current_language === 'id' ? 'active' : ''; ?>" onclick="selectLanguage('id')"><?php icon('globe', 14); ?> ID</button>
                                    <button type="button" class="toggle-btn <?php echo $current_language === 'en' ? 'active' : ''; ?>" onclick="selectLanguage('en')"><?php icon('globe', 14); ?> EN</button>
                                </div>
                            </div>
                            <button type="submit" class="btn-submit" style="margin-top: 1rem;">
                                <?php icon('save', 18); ?>
                                Simpan Bahasa
                            </button>
                        </form>
                    </div>

                    <div class="profile-section">
                        <h2><?php icon('moon', 20); ?> Tema / Theme</h2>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="action" value="change_theme">
                            <input type="hidden" name="theme" id="selectedTheme" value="<?php echo $current_theme; ?>">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-icon theme"><?php icon('moon', 20); ?></div>
                                    <div>
                                        <div class="setting-label">Tema Aplikasi</div>
                                        <div class="setting-desc">Pilih tampilan light atau dark mode</div>
                                    </div>
                                </div>
                                <div class="toggle-group">
                                    <button type="button" class="toggle-btn <?php echo $current_theme === 'dark' ? 'active' : ''; ?>" onclick="selectTheme('dark')"><?php icon('moon', 14); ?> Dark</button>
                                    <button type="button" class="toggle-btn <?php echo $current_theme === 'light' ? 'active' : ''; ?>" onclick="selectTheme('light')"><?php icon('sun', 14); ?> Light</button>
                                </div>
                            </div>
                            <button type="submit" class="btn-submit" style="margin-top: 1rem;">
                                <?php icon('save', 18); ?>
                                Simpan Tema
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <?php include 'includes/loading.php'; ?>
    <?php include 'includes/toast.php'; ?>

    <script src="assets/js/navbar.js"></script>
    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            document.querySelectorAll('.profile-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById('tab-' + tab).classList.add('active');
            event.target.closest('.profile-tab').classList.add('active');
            
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
        }

        function selectLanguage(lang) {
            document.getElementById('selectedLanguage').value = lang;
            document.querySelectorAll('.toggle-group .toggle-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        function selectTheme(theme) {
            document.getElementById('selectedTheme').value = theme;
            document.querySelectorAll('.toggle-group .toggle-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Avatar preview
        document.getElementById('avatarInput')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.avatar-preview');
                    const initial = document.getElementById('avatarInitial');
                    if (initial) initial.style.display = 'none';
                    
                    let img = preview.querySelector('img');
                    if (!img) {
                        img = document.createElement('img');
                        img.id = 'avatarPreview';
                        preview.appendChild(img);
                    }
                    img.src = e.target.result;
                    img.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Password strength indicator
        document.getElementById('newPassword')?.addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthDiv = document.getElementById('passwordStrength');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            if (password.length === 0) {
                strengthDiv.style.display = 'none';
                return;
            }
            
            strengthDiv.style.display = 'block';
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            const levels = ['', 'weak', 'fair', 'good', 'strong', 'strong'];
            const texts = ['', 'Lemah', 'Cukup', 'Bagus', 'Kuat', 'Sangat Kuat'];
            
            strengthFill.className = 'strength-fill ' + levels[strength];
            strengthText.className = 'strength-text ' + levels[strength];
            strengthText.textContent = texts[strength];
        });

        // Password confirmation validation
        document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                if (typeof showToast === 'function') {
                    showToast('Password baru dan konfirmasi tidak cocok!', 'error');
                } else {
                    alert('Password baru dan konfirmasi tidak cocok!');
                }
            }
        });

        // Animate XP bar on load
        document.addEventListener('DOMContentLoaded', function() {
            const xpFill = document.querySelector('.profile-xp-fill');
            const levelFill = document.querySelector('.level-progress-fill');
            
            if (xpFill) {
                const targetWidth = xpFill.style.width;
                xpFill.style.width = '0%';
                setTimeout(() => {
                    xpFill.style.width = targetWidth;
                }, 300);
            }
            
            if (levelFill) {
                const targetWidth = levelFill.style.width;
                levelFill.style.width = '0%';
                setTimeout(() => {
                    levelFill.style.width = targetWidth;
                }, 500);
            }
        });
    </script>
</body>
</html>

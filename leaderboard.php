<?php
require_once 'config/config.php';
requireLogin();
requireRole(['student']);
require_once 'includes/icons.php';

require_once 'models/Leaderboard.php';

$database = new Database();
$db = $database->getConnection();

$leaderboard = new Leaderboard($db);

// Get tab (solo or clan)
$tab = $_GET['tab'] ?? 'solo';

// Get time period filter
$period = $_GET['period'] ?? 'all';

// Get solo leaderboard
$solo_leaderboard = [];
$solo_stmt = $leaderboard->getSoloLeaderboard(100);
while ($row = $solo_stmt->fetch(PDO::FETCH_ASSOC)) {
    $solo_leaderboard[] = $row;
}

// Get user rank and stats
$user_rank = 0;
$user_data = null;
$prev_user_xp = 0;
$next_user_xp = 0;
foreach ($solo_leaderboard as $index => $user) {
    if ($user['id'] == $_SESSION['user_id']) {
        $user_rank = $index + 1;
        $user_data = $user;
        // Get previous user XP (for progress to next rank)
        if ($index > 0) {
            $prev_user_xp = $solo_leaderboard[$index - 1]['total_xp'];
        }
        // Get next user XP (user behind you)
        if ($index < count($solo_leaderboard) - 1) {
            $next_user_xp = $solo_leaderboard[$index + 1]['total_xp'];
        }
        break;
    }
}

// Get streak data for current user
$streakQuery = "SELECT COUNT(DISTINCT DATE(completed_at)) as streak_days
    FROM user_progress 
    WHERE user_id = :user_id 
    AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND status = 'completed'";
$streakStmt = $db->prepare($streakQuery);
$streakStmt->bindParam(':user_id', $_SESSION['user_id']);
$streakStmt->execute();
$streakData = $streakStmt->fetch(PDO::FETCH_ASSOC);
$user_streak = $streakData['streak_days'] ?? 0;

// Get clan leaderboard
$clan_leaderboard = [];
$clan_stmt = $leaderboard->getClanLeaderboard(50);
while ($row = $clan_stmt->fetch(PDO::FETCH_ASSOC)) {
    $clan_leaderboard[] = $row;
}

// Calculate XP needed for next rank
$xp_to_next_rank = $user_rank > 1 ? $prev_user_xp - ($user_data['total_xp'] ?? 0) : 0;
$xp_lead = ($user_data['total_xp'] ?? 0) - $next_user_xp;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('Leaderboard - ' . APP_NAME, 'Lihat peringkat dan kompetisi antar siswa', 'leaderboard, ranking, competition'); ?>
    <title>Leaderboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/ui-enhancements.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <link rel="stylesheet" href="assets/css/glassmorphism.css">
    <style>
        /* Leaderboard Hero Section */
        .leaderboard-hero {
            background: linear-gradient(135deg, 
                rgba(139, 92, 246, 0.15) 0%, 
                rgba(59, 130, 246, 0.1) 50%,
                rgba(236, 72, 153, 0.1) 100%);
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }
        .leaderboard-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%238b5cf6' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }
        .hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .hero-rank-display {
            text-align: center;
            min-width: 140px;
        }
        .hero-rank-badge {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            position: relative;
            box-shadow: 0 0 40px rgba(139, 92, 246, 0.4);
            animation: pulse-glow 2s ease-in-out infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 40px rgba(139, 92, 246, 0.4); }
            50% { box-shadow: 0 0 60px rgba(139, 92, 246, 0.6); }
        }
        .hero-rank-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
        }
        .hero-rank-label {
            color: #a78bfa;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
        }
        .hero-user-info {
            flex: 1;
        }
        .hero-user-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 0.5rem;
        }
        .hero-user-stats {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .hero-stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .hero-stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .hero-stat-icon.xp { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        .hero-stat-icon.level { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
        .hero-stat-icon.streak { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        .hero-stat-value {
            font-size: 1.125rem;
            font-weight: 700;
            color: #e2e8f0;
        }
        .hero-stat-label {
            font-size: 0.75rem;
            color: #64748b;
        }
        .hero-progress {
            margin-top: 1.5rem;
            background: rgba(30, 30, 55, 0.5);
            border-radius: 12px;
            padding: 1rem 1.25rem;
        }
        .hero-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .hero-progress-label {
            color: #94a3b8;
            font-size: 0.875rem;
        }
        .hero-progress-value {
            color: #a78bfa;
            font-weight: 600;
            font-size: 0.875rem;
        }
        .hero-progress-bar {
            height: 8px;
            background: rgba(139, 92, 246, 0.2);
            border-radius: 4px;
            overflow: hidden;
        }
        .hero-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #8b5cf6, #a78bfa);
            border-radius: 4px;
            transition: width 1s ease-out;
        }

        /* Top 3 Podium */
        .podium-section {
            margin-bottom: 2rem;
        }
        .podium-title {
            text-align: center;
            color: #e2e8f0;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        .podium-container {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 1rem;
            padding: 1rem;
        }
        .podium-player {
            text-align: center;
            animation: fadeInUp 0.6s ease-out backwards;
        }
        .podium-player:nth-child(1) { animation-delay: 0.2s; }
        .podium-player:nth-child(2) { animation-delay: 0s; }
        .podium-player:nth-child(3) { animation-delay: 0.4s; }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .podium-avatar-wrapper {
            position: relative;
            margin-bottom: 0.75rem;
        }
        .podium-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        .podium-player.first .podium-avatar {
            width: 90px;
            height: 90px;
            font-size: 2rem;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            box-shadow: 0 0 30px rgba(251, 191, 36, 0.5);
        }
        .podium-player.second .podium-avatar {
            background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
            box-shadow: 0 0 20px rgba(148, 163, 184, 0.4);
        }
        .podium-player.third .podium-avatar {
            background: linear-gradient(135deg, #cd7f32 0%, #a0522d 100%);
            box-shadow: 0 0 20px rgba(205, 127, 50, 0.4);
        }
        .podium-crown {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.75rem;
            animation: float 2s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(-5px); }
        }
        .podium-name {
            color: #e2e8f0;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            max-width: 120px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .podium-xp {
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .podium-player.first .podium-xp { color: #fbbf24; }
        .podium-player.second .podium-xp { color: #94a3b8; }
        .podium-player.third .podium-xp { color: #cd7f32; }
        .podium-stand {
            width: 100px;
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.3);
        }
        .podium-player.first .podium-stand {
            height: 100px;
            background: linear-gradient(180deg, rgba(251, 191, 36, 0.3) 0%, rgba(251, 191, 36, 0.1) 100%);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-bottom: none;
        }
        .podium-player.second .podium-stand {
            height: 70px;
            background: linear-gradient(180deg, rgba(148, 163, 184, 0.3) 0%, rgba(148, 163, 184, 0.1) 100%);
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-bottom: none;
        }
        .podium-player.third .podium-stand {
            height: 50px;
            background: linear-gradient(180deg, rgba(205, 127, 50, 0.3) 0%, rgba(205, 127, 50, 0.1) 100%);
            border: 1px solid rgba(205, 127, 50, 0.3);
            border-bottom: none;
        }

        /* Filter & Search Bar */
        .leaderboard-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .period-filters {
            display: flex;
            gap: 0.5rem;
            background: rgba(30, 30, 55, 0.5);
            padding: 0.375rem;
            border-radius: 12px;
            border: 1px solid rgba(139, 92, 246, 0.1);
        }
        .period-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: #94a3b8;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .period-btn:hover {
            color: #e2e8f0;
        }
        .period-btn.active {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            color: white;
        }
        .search-box {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(30, 30, 55, 0.5);
            padding: 0.625rem 1rem;
            border-radius: 12px;
            border: 1px solid rgba(139, 92, 246, 0.1);
            transition: all 0.3s ease;
        }
        .search-box:focus-within {
            border-color: rgba(139, 92, 246, 0.4);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        .search-box input {
            background: transparent;
            border: none;
            outline: none;
            color: #e2e8f0;
            font-size: 0.875rem;
            width: 200px;
        }
        .search-box input::placeholder {
            color: #64748b;
        }
        .search-box svg {
            color: #64748b;
        }

        /* Leaderboard Table */
        .leaderboard-table {
            background: rgba(30, 30, 55, 0.5);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        .leaderboard-header {
            display: flex;
            align-items: center;
            padding: 1rem 1.25rem;
            background: rgba(139, 92, 246, 0.05);
            border-bottom: 1px solid rgba(139, 92, 246, 0.1);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: 600;
        }
        .leaderboard-header .rank-col { width: 60px; }
        .leaderboard-header .player-col { flex: 1; }
        .leaderboard-header .stats-col { width: 300px; text-align: right; }
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
            animation: slideIn 0.4s ease-out backwards;
        }
        .leaderboard-item:nth-child(1) { animation-delay: 0.05s; }
        .leaderboard-item:nth-child(2) { animation-delay: 0.1s; }
        .leaderboard-item:nth-child(3) { animation-delay: 0.15s; }
        .leaderboard-item:nth-child(4) { animation-delay: 0.2s; }
        .leaderboard-item:nth-child(5) { animation-delay: 0.25s; }
        .leaderboard-item:nth-child(6) { animation-delay: 0.3s; }
        .leaderboard-item:nth-child(7) { animation-delay: 0.35s; }
        .leaderboard-item:nth-child(8) { animation-delay: 0.4s; }
        .leaderboard-item:nth-child(9) { animation-delay: 0.45s; }
        .leaderboard-item:nth-child(10) { animation-delay: 0.5s; }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        .leaderboard-item:hover {
            background: rgba(139, 92, 246, 0.08);
            transform: translateX(4px);
        }
        .leaderboard-item:last-child {
            border-bottom: none;
        }
        .leaderboard-item.current-user {
            background: linear-gradient(135deg, 
                rgba(139, 92, 246, 0.15) 0%, 
                rgba(167, 139, 250, 0.1) 100%);
            border-left: 3px solid #8b5cf6;
        }
        .rank-number {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        .rank-number.top-1 {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #1a1a2e;
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3);
        }
        .rank-number.top-2 {
            background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(148, 163, 184, 0.2);
        }
        .rank-number.top-3 {
            background: linear-gradient(135deg, #cd7f32 0%, #a0522d 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(205, 127, 50, 0.2);
        }
        .rank-number.other {
            background: rgba(139, 92, 246, 0.1);
            color: #a78bfa;
            border: 1px solid rgba(139, 92, 246, 0.2);
            font-size: 0.9rem;
        }
        .user-info {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 50%, #a78bfa 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.125rem;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
            position: relative;
        }
        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        .user-level-badge {
            position: absolute;
            bottom: -4px;
            right: -4px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #60a5fa);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 700;
            color: white;
            border: 2px solid #1a1a2e;
        }
        .user-details {
            flex: 1;
        }
        .user-name {
            color: #e2e8f0;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .you-badge {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            color: white;
            font-size: 0.65rem;
            padding: 0.125rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
        }
        .user-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .user-level {
            color: #8b5cf6;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .user-streak {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: #f59e0b;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .stats {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        .stat-item {
            text-align: center;
            min-width: 70px;
        }
        .stat-value {
            color: #fff;
            font-size: 1.125rem;
            font-weight: 700;
            background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-label {
            color: #64748b;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Clan Items */
        .clan-item {
            display: flex;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(139, 92, 246, 0.08);
            transition: all 0.3s ease;
            animation: slideIn 0.4s ease-out backwards;
        }
        .clan-item:hover {
            background: rgba(139, 92, 246, 0.08);
            transform: translateX(4px);
        }
        .clan-avatar {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-right: 1.25rem;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
        }
        .clan-info {
            flex: 1;
        }
        .clan-name {
            color: #e2e8f0;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        .clan-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #64748b;
            font-size: 0.8rem;
        }
        .clan-meta-item {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }
        .clan-meta-item svg {
            color: #8b5cf6;
        }

        /* Empty State */
        .leaderboard-empty {
            padding: 4rem 2rem;
            text-align: center;
        }
        .leaderboard-empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 1s ease-in-out infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .leaderboard-empty-title {
            color: #e2e8f0;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .leaderboard-empty-text {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Motivational Message */
        .motivation-banner {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(139, 92, 246, 0.15));
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .motivation-icon {
            font-size: 2rem;
        }
        .motivation-text {
            flex: 1;
        }
        .motivation-title {
            color: #e2e8f0;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        .motivation-subtitle {
            color: #94a3b8;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .leaderboard-hero {
                padding: 1.5rem;
            }
            .hero-content {
                flex-direction: column;
                text-align: center;
            }
            .hero-user-stats {
                justify-content: center;
            }
            .hero-progress {
                margin-top: 1rem;
            }
            .podium-container {
                gap: 0.5rem;
            }
            .podium-stand {
                width: 80px;
            }
            .podium-player.first .podium-avatar {
                width: 70px;
                height: 70px;
            }
            .podium-avatar {
                width: 55px;
                height: 55px;
            }
            .leaderboard-controls {
                flex-direction: column;
            }
            .search-box {
                width: 100%;
            }
            .search-box input {
                width: 100%;
            }
            .leaderboard-item {
                padding: 0.875rem 1rem;
                flex-wrap: wrap;
            }
            .leaderboard-header {
                display: none;
            }
            .user-avatar {
                width: 40px;
                height: 40px;
                font-size: 0.9rem;
            }
            .rank-number {
                width: 36px;
                height: 36px;
                font-size: 0.8rem;
                border-radius: 10px;
            }
            .stats {
                width: 100%;
                margin-top: 0.75rem;
                justify-content: flex-end;
                gap: 1rem;
            }
            .stat-value {
                font-size: 0.9rem;
            }
            .stat-item {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php require_once 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="dashboard-main-container">
        <div class="dashboard-content">
            <div class="page-wrapper">
                <div class="glass-header">
                    <h1>🏅 Leaderboard</h1>
                    <p>Bersaing dengan developer lainnya dan raih posisi teratas!</p>
                </div>

                <!-- Your Rank Hero Section -->
                <?php if ($tab === 'solo' && $user_data): ?>
                <div class="leaderboard-hero">
                    <div class="hero-content">
                        <div class="hero-rank-display">
                            <div class="hero-rank-badge">
                                <span class="hero-rank-number">#<?php echo $user_rank; ?></span>
                            </div>
                            <div class="hero-rank-label">Rank Anda</div>
                        </div>
                        <div class="hero-user-info">
                            <div class="hero-user-name"><?php echo htmlspecialchars($user_data['nama_lengkap']); ?></div>
                            <div class="hero-user-stats">
                                <div class="hero-stat">
                                    <div class="hero-stat-icon xp"><?php icon('star', 18); ?></div>
                                    <div>
                                        <div class="hero-stat-value"><?php echo number_format($user_data['total_xp']); ?></div>
                                        <div class="hero-stat-label">Total XP</div>
                                    </div>
                                </div>
                                <div class="hero-stat">
                                    <div class="hero-stat-icon level"><?php icon('trophy', 18); ?></div>
                                    <div>
                                        <div class="hero-stat-value">Level <?php echo $user_data['level']; ?></div>
                                        <div class="hero-stat-label">Level</div>
                                    </div>
                                </div>
                                <div class="hero-stat">
                                    <div class="hero-stat-icon streak"><?php icon('fire', 18); ?></div>
                                    <div>
                                        <div class="hero-stat-value"><?php echo $user_streak; ?></div>
                                        <div class="hero-stat-label">Streak</div>
                                    </div>
                                </div>
                            </div>
                            <?php if ($user_rank > 1 && $xp_to_next_rank > 0): ?>
                            <div class="hero-progress">
                                <div class="hero-progress-header">
                                    <span class="hero-progress-label">XP menuju Rank #<?php echo $user_rank - 1; ?></span>
                                    <span class="hero-progress-value"><?php echo number_format($xp_to_next_rank); ?> XP lagi</span>
                                </div>
                                <div class="hero-progress-bar">
                                    <?php 
                                    $progress = 100 - (($xp_to_next_rank / ($xp_to_next_rank + $xp_lead)) * 100);
                                    $progress = max(5, min(95, $progress));
                                    ?>
                                    <div class="hero-progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                            </div>
                            <?php elseif ($user_rank === 1): ?>
                            <div class="hero-progress" style="text-align: center;">
                                <span style="color: #fbbf24; font-weight: 600;">🏆 Selamat! Anda berada di posisi #1!</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Motivational Banner -->
                <?php if ($tab === 'solo' && $user_rank > 10): ?>
                <div class="motivation-banner">
                    <div class="motivation-icon">💪</div>
                    <div class="motivation-text">
                        <div class="motivation-title">Terus Belajar!</div>
                        <div class="motivation-subtitle">Selesaikan lebih banyak lesson untuk naik ke Top 10</div>
                    </div>
                </div>
                <?php elseif ($tab === 'solo' && $user_rank > 3 && $user_rank <= 10): ?>
                <div class="motivation-banner">
                    <div class="motivation-icon">🔥</div>
                    <div class="motivation-text">
                        <div class="motivation-title">Hampir Sampai!</div>
                        <div class="motivation-subtitle">Sedikit lagi untuk masuk podium Top 3!</div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="glass-tabs">
                    <button class="glass-tab <?php echo $tab === 'solo' ? 'active' : ''; ?>" onclick="window.location.href='?tab=solo'">
                        👤 Solo
                    </button>
                    <button class="glass-tab <?php echo $tab === 'clan' ? 'active' : ''; ?>" onclick="window.location.href='?tab=clan'">
                        💜 Clan
                    </button>
                </div>

                <?php if ($tab === 'solo'): ?>
                    <!-- Top 3 Podium -->
                    <?php if (count($solo_leaderboard) >= 3): ?>
                    <div class="podium-section">
                        <div class="podium-title">
                            <?php icon('trophy', 24); ?> Top 3 Developer
                        </div>
                        <div class="podium-container">
                            <!-- Second Place -->
                            <div class="podium-player second">
                                <div class="podium-avatar-wrapper">
                                    <div class="podium-avatar">
                                        <?php echo strtoupper(substr($solo_leaderboard[1]['nama_lengkap'], 0, 1)); ?>
                                    </div>
                                </div>
                                <div class="podium-name"><?php echo htmlspecialchars($solo_leaderboard[1]['nama_lengkap']); ?></div>
                                <div class="podium-xp"><?php echo number_format($solo_leaderboard[1]['total_xp']); ?> XP</div>
                                <div class="podium-stand">2</div>
                            </div>
                            <!-- First Place -->
                            <div class="podium-player first">
                                <div class="podium-avatar-wrapper">
                                    <div class="podium-crown">👑</div>
                                    <div class="podium-avatar">
                                        <?php echo strtoupper(substr($solo_leaderboard[0]['nama_lengkap'], 0, 1)); ?>
                                    </div>
                                </div>
                                <div class="podium-name"><?php echo htmlspecialchars($solo_leaderboard[0]['nama_lengkap']); ?></div>
                                <div class="podium-xp"><?php echo number_format($solo_leaderboard[0]['total_xp']); ?> XP</div>
                                <div class="podium-stand">1</div>
                            </div>
                            <!-- Third Place -->
                            <div class="podium-player third">
                                <div class="podium-avatar-wrapper">
                                    <div class="podium-avatar">
                                        <?php echo strtoupper(substr($solo_leaderboard[2]['nama_lengkap'], 0, 1)); ?>
                                    </div>
                                </div>
                                <div class="podium-name"><?php echo htmlspecialchars($solo_leaderboard[2]['nama_lengkap']); ?></div>
                                <div class="podium-xp"><?php echo number_format($solo_leaderboard[2]['total_xp']); ?> XP</div>
                                <div class="podium-stand">3</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Search & Filters -->
                    <div class="leaderboard-controls">
                        <div class="period-filters">
                            <button class="period-btn <?php echo $period === 'weekly' ? 'active' : ''; ?>" onclick="window.location.href='?tab=solo&period=weekly'">Mingguan</button>
                            <button class="period-btn <?php echo $period === 'monthly' ? 'active' : ''; ?>" onclick="window.location.href='?tab=solo&period=monthly'">Bulanan</button>
                            <button class="period-btn <?php echo $period === 'all' ? 'active' : ''; ?>" onclick="window.location.href='?tab=solo&period=all'">Semua</button>
                        </div>
                        <div class="search-box">
                            <?php icon('search', 18); ?>
                            <input type="text" id="searchUser" placeholder="Cari nama..." oninput="filterLeaderboard(this.value)">
                        </div>
                    </div>

                    <div class="leaderboard-table" id="leaderboardTable">
                        <?php if (empty($solo_leaderboard)): ?>
                            <div class="leaderboard-empty">
                                <div class="leaderboard-empty-icon">🏆</div>
                                <div class="leaderboard-empty-title">Belum ada data leaderboard</div>
                                <div class="leaderboard-empty-text">Mulai belajar untuk tampil di leaderboard!</div>
                            </div>
                        <?php else: ?>
                            <div class="leaderboard-header">
                                <div class="rank-col">Rank</div>
                                <div class="player-col">Player</div>
                                <div class="stats-col">Stats</div>
                            </div>
                            <?php foreach ($solo_leaderboard as $index => $user): ?>
                                <?php 
                                $rank = $index + 1;
                                // Skip top 3 in main list if podium is shown
                                if ($rank <= 3 && count($solo_leaderboard) >= 3) continue;
                                $is_current_user = $user['id'] == $_SESSION['user_id'];
                                $rank_class = $rank <= 3 ? 'top-' . $rank : 'other';
                                ?>
                                <div class="leaderboard-item <?php echo $is_current_user ? 'current-user' : ''; ?>" data-name="<?php echo strtolower(htmlspecialchars($user['nama_lengkap'])); ?>">
                                    <div class="rank-number <?php echo $rank_class; ?>">
                                        <?php if ($rank <= 3): ?>
                                            <?php icon('trophy', 20); ?>
                                        <?php else: ?>
                                            <?php echo $rank; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php if (!empty($user['avatar'])): ?>
                                                <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($user['nama_lengkap'], 0, 1)); ?>
                                            <?php endif; ?>
                                            <div class="user-level-badge"><?php echo $user['level']; ?></div>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name">
                                                <?php echo htmlspecialchars($user['nama_lengkap']); ?>
                                                <?php if ($is_current_user): ?>
                                                    <span class="you-badge">ANDA</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="user-meta">
                                                <div class="user-level">Level <?php echo $user['level']; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="stats">
                                        <div class="stat-item">
                                            <div class="stat-value"><?php echo number_format($user['total_xp']); ?></div>
                                            <div class="stat-label">XP</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value"><?php echo $user['completed_courses']; ?></div>
                                            <div class="stat-label">Course</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value"><?php echo $user['completed_lessons']; ?></div>
                                            <div class="stat-label">Lesson</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Clan Leaderboard -->
                    <?php if (count($clan_leaderboard) >= 3): ?>
                    <div class="podium-section">
                        <div class="podium-title">
                            <?php icon('users', 24); ?> Top 3 Clan
                        </div>
                        <div class="podium-container">
                            <!-- Second Place -->
                            <div class="podium-player second">
                                <div class="podium-avatar-wrapper">
                                    <div class="podium-avatar" style="border-radius: 16px;">
                                        <?php icon('users', 24); ?>
                                    </div>
                                </div>
                                <div class="podium-name"><?php echo htmlspecialchars($clan_leaderboard[1]['nama_clan']); ?></div>
                                <div class="podium-xp"><?php echo number_format($clan_leaderboard[1]['total_xp']); ?> XP</div>
                                <div class="podium-stand">2</div>
                            </div>
                            <!-- First Place -->
                            <div class="podium-player first">
                                <div class="podium-avatar-wrapper">
                                    <div class="podium-crown">👑</div>
                                    <div class="podium-avatar" style="border-radius: 20px;">
                                        <?php icon('users', 32); ?>
                                    </div>
                                </div>
                                <div class="podium-name"><?php echo htmlspecialchars($clan_leaderboard[0]['nama_clan']); ?></div>
                                <div class="podium-xp"><?php echo number_format($clan_leaderboard[0]['total_xp']); ?> XP</div>
                                <div class="podium-stand">1</div>
                            </div>
                            <!-- Third Place -->
                            <div class="podium-player third">
                                <div class="podium-avatar-wrapper">
                                    <div class="podium-avatar" style="border-radius: 16px;">
                                        <?php icon('users', 24); ?>
                                    </div>
                                </div>
                                <div class="podium-name"><?php echo htmlspecialchars($clan_leaderboard[2]['nama_clan']); ?></div>
                                <div class="podium-xp"><?php echo number_format($clan_leaderboard[2]['total_xp']); ?> XP</div>
                                <div class="podium-stand">3</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Search -->
                    <div class="leaderboard-controls">
                        <div></div>
                        <div class="search-box">
                            <?php icon('search', 18); ?>
                            <input type="text" id="searchClan" placeholder="Cari clan..." oninput="filterClanLeaderboard(this.value)">
                        </div>
                    </div>

                    <div class="leaderboard-table" id="clanLeaderboardTable">
                        <?php if (empty($clan_leaderboard)): ?>
                            <div class="leaderboard-empty">
                                <div class="leaderboard-empty-icon">💜</div>
                                <div class="leaderboard-empty-title">Belum ada clan</div>
                                <div class="leaderboard-empty-text">Buat atau bergabung dengan clan untuk berkompetisi!</div>
                            </div>
                        <?php else: ?>
                            <div class="leaderboard-header">
                                <div class="rank-col">Rank</div>
                                <div class="player-col">Clan</div>
                                <div class="stats-col">Stats</div>
                            </div>
                            <?php foreach ($clan_leaderboard as $index => $clan_item): ?>
                                <?php 
                                $rank = $index + 1;
                                // Skip top 3 in main list if podium is shown
                                if ($rank <= 3 && count($clan_leaderboard) >= 3) continue;
                                ?>
                                <div class="clan-item" data-name="<?php echo strtolower(htmlspecialchars($clan_item['nama_clan'])); ?>">
                                    <div class="rank-number <?php echo $rank <= 3 ? 'top-' . $rank : 'other'; ?>">
                                        <?php if ($rank <= 3): ?>
                                            <?php icon('trophy', 20); ?>
                                        <?php else: ?>
                                            <?php echo $rank; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="clan-avatar"><?php icon('users', 24); ?></div>
                                    <div class="clan-info">
                                        <div class="clan-name"><?php echo htmlspecialchars($clan_item['nama_clan']); ?></div>
                                        <div class="clan-meta">
                                            <div class="clan-meta-item">
                                                <?php icon('users', 14); ?>
                                                <?php echo $clan_item['total_members']; ?> Members
                                            </div>
                                        </div>
                                    </div>
                                    <div class="stats">
                                        <div class="stat-item">
                                            <div class="stat-value"><?php echo number_format($clan_item['total_xp']); ?></div>
                                            <div class="stat-label">Total XP</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value"><?php echo number_format($clan_item['average_xp'], 0); ?></div>
                                            <div class="stat-label">Avg XP</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <?php include 'includes/loading.php'; ?>
    <?php include 'includes/toast.php'; ?>

    <script src="assets/js/navbar.js"></script>
    <script>
        // Search/Filter functionality
        function filterLeaderboard(searchTerm) {
            const items = document.querySelectorAll('#leaderboardTable .leaderboard-item');
            const term = searchTerm.toLowerCase().trim();
            
            items.forEach(item => {
                const name = item.getAttribute('data-name') || '';
                if (term === '' || name.includes(term)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function filterClanLeaderboard(searchTerm) {
            const items = document.querySelectorAll('#clanLeaderboardTable .clan-item');
            const term = searchTerm.toLowerCase().trim();
            
            items.forEach(item => {
                const name = item.getAttribute('data-name') || '';
                if (term === '' || name.includes(term)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Animate progress bar on load
        document.addEventListener('DOMContentLoaded', function() {
            const progressFill = document.querySelector('.hero-progress-fill');
            if (progressFill) {
                const targetWidth = progressFill.style.width;
                progressFill.style.width = '0%';
                setTimeout(() => {
                    progressFill.style.width = targetWidth;
                }, 300);
            }
        });
    </script>
</body>
</html>



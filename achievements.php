<?php
require_once 'config/config.php';
requireLogin();
requireRole(['student']);

require_once 'models/Achievement.php';

$database = new Database();
$db = $database->getConnection();

$achievement = new Achievement($db);

// Get user achievements
$achievements_stmt = $achievement->getUserAchievements($_SESSION['user_id']);
$achievements = [];
while ($row = $achievements_stmt->fetch(PDO::FETCH_ASSOC)) {
    $achievements[] = $row;
}

// Calculate stats
$total_achievements = count($achievements);
$earned_count = 0;
foreach ($achievements as $ach) {
    if ($ach['earned_at']) {
        $earned_count++;
    }
}
$progress_percent = $total_achievements > 0 ? ($earned_count / $total_achievements) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('Achievements - ' . APP_NAME, 'Lihat pencapaian dan badge yang telah Anda raih', 'achievements, badges, rewards'); ?>
    <title>Achievements - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <link rel="stylesheet" href="assets/css/glassmorphism.css">
    <style>
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
            padding: 0;
        }
        .achievement-card {
            background: rgba(30, 30, 55, 0.5);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 14px;
            padding: 1.25rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        .achievement-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            transform: scaleX(0);
            transition: transform 0.3s;
        }
        .achievement-card:hover {
            transform: translateY(-3px);
            border-color: rgba(139, 92, 246, 0.3);
            box-shadow: 0 8px 30px rgba(139, 92, 246, 0.15);
        }
        .achievement-card:hover::before {
            transform: scaleX(1);
        }
        .achievement-card.earned {
            border-color: rgba(139, 92, 246, 0.4);
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(30, 30, 55, 0.5) 100%);
        }
        .achievement-card.earned::before {
            transform: scaleX(1);
        }
        .achievement-icon {
            font-size: 2.25rem;
            margin-bottom: 0.625rem;
            filter: grayscale(1);
            transition: filter 0.3s;
        }
        .achievement-card.earned .achievement-icon {
            filter: grayscale(0);
        }
        .achievement-name {
            color: #e2e8f0;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .achievement-description {
            color: rgba(148, 163, 184, 0.9);
            font-size: 0.8rem;
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }
        .achievement-xp {
            display: inline-block;
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .achievement-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: #10b981;
            color: white;
            padding: 0.15rem 0.5rem;
            border-radius: 8px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .achievement-card:not(.earned) .achievement-badge {
            display: none;
        }
        .btn-share-achievement {
            margin-top: 0.5rem;
            background: rgba(139, 92, 246, 0.15);
            color: #a78bfa;
            padding: 0.35rem 0.75rem;
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-share-achievement:hover {
            background: rgba(139, 92, 246, 0.25);
            border-color: rgba(139, 92, 246, 0.5);
        }
        /* Share Modal */
        .share-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .share-modal.active {
            display: flex;
        }
        .share-modal-content {
            background: #1e1e32;
            border-radius: 1rem;
            padding: 1.5rem;
            max-width: 400px;
            width: 90%;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }
        .share-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .share-modal-header h3 {
            color: #e2e8f0;
            margin: 0;
        }
        .share-modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .share-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }
        .share-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
            color: #e2e8f0;
        }
        .share-option:hover {
            background: rgba(139, 92, 246, 0.2);
            transform: translateY(-2px);
        }
        .share-option svg {
            width: 24px;
            height: 24px;
        }
        .share-option span {
            font-size: 0.75rem;
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
                    <h1>🏆 Achievements</h1>
                    <p>Kumpulkan achievement dan dapatkan bonus XP!</p>
                </div>

                <div class="glass-stats-grid">
                    <div class="glass-stat-card">
                        <div class="glass-stat-value"><?php echo $earned_count; ?></div>
                        <div class="glass-stat-label">Achievement Diperoleh</div>
                    </div>
                    <div class="glass-stat-card">
                        <div class="glass-stat-value"><?php echo $total_achievements; ?></div>
                        <div class="glass-stat-label">Total Achievement</div>
                    </div>
                    <div class="glass-stat-card">
                        <div class="glass-stat-value"><?php echo number_format($progress_percent, 1); ?>%</div>
                        <div class="glass-stat-label">Progress</div>
                    </div>
                </div>

                <div class="glass-section" style="margin-bottom: 1.5rem;">
                    <div class="glass-progress glass-progress-lg">
                        <div class="glass-progress-bar" style="width: <?php echo $progress_percent; ?>%"></div>
                    </div>
                </div>

                <div class="achievements-grid">
                    <?php if (empty($achievements)): ?>
                        <div class="glass-empty-state" style="grid-column: 1 / -1;">
                            <div class="glass-empty-icon">🏆</div>
                            <p class="glass-empty-title">Belum ada achievement yang tersedia</p>
                            <p class="glass-empty-text">Selesaikan berbagai tantangan untuk mendapatkan achievement!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($achievements as $ach): ?>
                            <?php $is_earned = !empty($ach['earned_at']); ?>
                            <div class="achievement-card <?php echo $is_earned ? 'earned' : ''; ?>">
                                <?php if ($is_earned): ?>
                                    <div class="achievement-badge">✓ Diperoleh</div>
                                <?php endif; ?>
                                <div class="achievement-icon"><?php echo htmlspecialchars($ach['icon']); ?></div>
                                <div class="achievement-name"><?php echo htmlspecialchars($ach['nama_achievement']); ?></div>
                                <div class="achievement-description"><?php echo htmlspecialchars($ach['deskripsi']); ?></div>
                                <div class="achievement-xp">+<?php echo $ach['xp_reward']; ?> XP</div>
                                <?php if ($is_earned): ?>
                                    <div style="margin-top: 0.75rem; font-size: 0.7rem; color: #10b981;">
                                        Diperoleh: <?php echo date('d/m/Y', strtotime($ach['earned_at'])); ?>
                                    </div>
                                    <button class="btn-share-achievement" onclick="shareAchievement('<?php echo htmlspecialchars($ach['nama_achievement']); ?>', '<?php echo htmlspecialchars($ach['icon']); ?>')">
                                        🔗 Bagikan
                                    </button>
                                <?php else: ?>
                                    <div style="margin-top: 0.75rem; font-size: 0.7rem; color: rgba(148, 163, 184, 0.7);">
                                        Belum diperoleh
                                    </div>
                                <?php endif; ?>
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

    <!-- Share Modal -->
    <div class="share-modal" id="shareModal">
        <div class="share-modal-content">
            <div class="share-modal-header">
                <h3>🔗 Bagikan Achievement</h3>
                <button class="share-modal-close" onclick="closeShareModal()">×</button>
            </div>
            <p style="color: #94a3b8; margin-bottom: 1rem; font-size: 0.9rem;">Bagikan pencapaian Anda ke:</p>
            <div class="share-options">
                <button class="share-option" onclick="shareToTwitter()">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    <span>Twitter</span>
                </button>
                <button class="share-option" onclick="shareToFacebook()">
                    <svg viewBox="0 0 24 24" fill="#1877f2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    <span>Facebook</span>
                </button>
                <button class="share-option" onclick="shareToWhatsApp()">
                    <svg viewBox="0 0 24 24" fill="#25d366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                    <span>WhatsApp</span>
                </button>
                <button class="share-option" onclick="shareToTelegram()">
                    <svg viewBox="0 0 24 24" fill="#0088cc"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    <span>Telegram</span>
                </button>
                <button class="share-option" onclick="shareToLinkedIn()">
                    <svg viewBox="0 0 24 24" fill="#0a66c2"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    <span>LinkedIn</span>
                </button>
                <button class="share-option" onclick="copyShareLink()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                    <span>Salin Link</span>
                </button>
            </div>
        </div>
    </div>

    <script src="assets/js/navbar.js"></script>
    <script>
        let currentAchievementName = '';
        let currentAchievementIcon = '';

        function shareAchievement(name, icon) {
            currentAchievementName = name;
            currentAchievementIcon = icon;
            document.getElementById('shareModal').classList.add('active');
        }

        function closeShareModal() {
            document.getElementById('shareModal').classList.remove('active');
        }

        document.getElementById('shareModal').addEventListener('click', function(e) {
            if (e.target === this) closeShareModal();
        });

        function getShareText() {
            return `${currentAchievementIcon} Saya baru saja mendapatkan achievement "${currentAchievementName}" di <?php echo APP_NAME; ?>! #Achievement #Learning`;
        }

        function getShareUrl() {
            return window.location.origin + '/achievements.php';
        }

        function shareToTwitter() {
            const text = encodeURIComponent(getShareText());
            const url = encodeURIComponent(getShareUrl());
            window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank', 'width=550,height=420');
            closeShareModal();
        }

        function shareToFacebook() {
            const url = encodeURIComponent(getShareUrl());
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=550,height=420');
            closeShareModal();
        }

        function shareToLinkedIn() {
            const url = encodeURIComponent(getShareUrl());
            window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank', 'width=550,height=420');
            closeShareModal();
        }

        function shareToWhatsApp() {
            const text = encodeURIComponent(getShareText() + ' ' + getShareUrl());
            window.open(`https://wa.me/?text=${text}`, '_blank');
            closeShareModal();
        }

        function shareToTelegram() {
            const text = encodeURIComponent(getShareText());
            const url = encodeURIComponent(getShareUrl());
            window.open(`https://t.me/share/url?url=${url}&text=${text}`, '_blank');
            closeShareModal();
        }

        function copyShareLink() {
            const text = getShareText() + ' ' + getShareUrl();
            navigator.clipboard.writeText(text).then(() => {
                alert('Link berhasil disalin!');
                closeShareModal();
            });
        }
    </script>
</body>
</html>



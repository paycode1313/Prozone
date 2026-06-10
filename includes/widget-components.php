<?php
// Include icon system
if (!function_exists('getIcon')) {
    require_once __DIR__ . '/icons.php';
}

/**
 * Daily Challenge Widget Component
 */
function renderDailyChallengeWidget() {
    ?>
    <div class="daily-challenge-widget">
        <div class="challenge-header">
            <div class="challenge-icon"><?php icon('target', 24); ?></div>
            <div class="challenge-title">
                <h4>Tantangan Hari Ini</h4>
                <span>Selesaikan untuk bonus XP!</span>
            </div>
            <div class="challenge-timer" id="challengeTimer">23:45:12</div>
        </div>
        <div class="challenge-content">
            <div class="challenge-task">
                <p>Selesaikan 3 lesson hari ini dan dapatkan bonus XP ekstra!</p>
            </div>
            <div class="challenge-reward">
                <div class="reward-info">
                    <span><?php icon('gift', 16); ?> Hadiah:</span>
                    <span class="reward-xp">+50 XP Bonus</span>
                </div>
                <a href="courses.php" class="btn-challenge">Mulai Belajar</a>
            </div>
        </div>
    </div>
    <script>
    // Daily challenge timer
    function updateChallengeTimer() {
        const now = new Date();
        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(0, 0, 0, 0);
        
        const diff = tomorrow - now;
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        const timer = document.getElementById('challengeTimer');
        if (timer) {
            timer.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }
    }
    if (!window.challengeTimerStarted) {
        window.challengeTimerStarted = true;
        setInterval(updateChallengeTimer, 1000);
        updateChallengeTimer();
    }
    </script>
    <?php
}

/**
 * Streak Widget Component
 */
function renderStreakWidget($streakDays = 0) {
    $days = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    $today = date('N') - 1; // 0-6
    ?>
    <div class="streak-widget">
        <div class="streak-flame"><?php icon('fire', 48); ?></div>
        <p class="streak-count"><?= $streakDays ?></p>
        <p class="streak-label">Hari Berturut-turut</p>
        <div class="streak-calendar">
            <?php for ($i = 0; $i < 7; $i++): 
                $isActive = $i <= $today && $streakDays > ($today - $i);
                $isToday = $i === $today;
            ?>
            <div class="streak-day <?= $isToday ? 'today' : ($isActive ? 'active' : 'inactive') ?>">
                <?= $days[$i] ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    <?php
}

/**
 * Activity Feed Widget Component
 */
function renderActivityFeedWidget($activities = []) {
    ?>
    <div class="activity-feed-widget">
        <div class="widget-header">
            <h4><?php icon('zap', 16); ?> Aktivitas Terbaru</h4>
            <a href="leaderboard.php">Lihat Semua</a>
        </div>
        <div class="activity-list">
            <?php if (empty($activities)): ?>
            <div class="activity-item">
                <div class="activity-avatar"><?php icon('wave', 20); ?></div>
                <div class="activity-content">
                    <p class="activity-text">Belum ada aktivitas. Mulai belajar sekarang!</p>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($activities as $activity): 
                $timeAgo = timeAgo($activity['completed_at'] ?? date('Y-m-d H:i:s'));
                $avatar = !empty($activity['avatar']) ? 'assets/uploads/avatars/' . $activity['avatar'] : null;
                $initial = strtoupper(substr($activity['nama_lengkap'] ?? 'U', 0, 1));
            ?>
            <div class="activity-item">
                <div class="activity-avatar">
                    <?php if ($avatar): ?>
                    <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar">
                    <?php else: ?>
                    <?= $initial ?>
                    <?php endif; ?>
                </div>
                <div class="activity-content">
                    <p class="activity-text">
                        <strong><?= htmlspecialchars($activity['nama_lengkap'] ?? 'User') ?></strong>
                        menyelesaikan 
                        <span class="highlight"><?= htmlspecialchars($activity['judul_lesson'] ?? 'Lesson') ?></span>
                    </p>
                    <p class="activity-time"><?= $timeAgo ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Leaderboard Preview Widget Component
 */
function renderLeaderboardWidget($users = [], $currentUserId = null) {
    ?>
    <div class="leaderboard-preview-widget">
        <div class="widget-header">
            <h4><?php icon('trophy', 16); ?> Top Learners</h4>
            <a href="leaderboard.php">Lihat Semua</a>
        </div>
        <div class="leaderboard-list">
            <?php if (empty($users)): ?>
            <div class="leaderboard-item">
                <div class="leaderboard-rank normal">-</div>
                <div class="leaderboard-user">
                    <span class="leaderboard-user-name">Belum ada data</span>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($users as $index => $user): 
                $rank = $index + 1;
                $rankClass = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : 'normal'));
                $isCurrentUser = ($user['id'] ?? 0) == $currentUserId;
                $avatar = !empty($user['avatar']) ? 'assets/uploads/avatars/' . $user['avatar'] : null;
                $initial = strtoupper(substr($user['nama_lengkap'] ?? 'U', 0, 1));
            ?>
            <div class="leaderboard-item <?= $isCurrentUser ? 'highlighted' : '' ?>">
                <div class="leaderboard-rank <?= $rankClass ?>"><?= $rank ?></div>
                <div class="leaderboard-user">
                    <div class="leaderboard-user-avatar">
                        <?php if ($avatar): ?>
                        <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar">
                        <?php else: ?>
                        <?= $initial ?>
                        <?php endif; ?>
                    </div>
                    <span class="leaderboard-user-name"><?= htmlspecialchars($user['nama_lengkap'] ?? 'User') ?></span>
                </div>
                <span class="leaderboard-xp"><?= number_format($user['total_xp'] ?? 0) ?> XP</span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Quick Actions Widget Component
 */
function renderQuickActionsWidget() {
    ?>
    <div class="quick-actions-widget">
        <a href="courses.php" class="quick-action">
            <div class="quick-action-icon purple"><?php icon('book', 20); ?></div>
            <span>Kursus Saya</span>
        </a>
        <a href="achievements.php" class="quick-action">
            <div class="quick-action-icon green"><?php icon('medal', 20); ?></div>
            <span>Achievements</span>
        </a>
        <a href="leaderboard.php" class="quick-action">
            <div class="quick-action-icon blue"><?php icon('chart', 20); ?></div>
            <span>Leaderboard</span>
        </a>
        <a href="certificates.php" class="quick-action">
            <div class="quick-action-icon yellow"><?php icon('certificate', 20); ?></div>
            <span>Sertifikat</span>
        </a>
    </div>
    <?php
}

/**
 * Quote Widget Component
 */
function renderQuoteWidget($quote = null) {
    $defaultQuotes = [
        ['text' => 'Setiap ahli pernah menjadi pemula.', 'author' => 'Helen Hayes'],
        ['text' => 'Kode adalah puisi yang bisa dipahami mesin.', 'author' => 'Anonymous'],
        ['text' => 'Kesuksesan adalah hasil dari persiapan, kerja keras, dan belajar dari kegagalan.', 'author' => 'Colin Powell'],
        ['text' => 'Jangan takut membuat kesalahan. Itulah cara terbaik untuk belajar.', 'author' => 'Anonymous'],
        ['text' => 'Belajar coding bukan tentang menjadi sempurna, tapi tentang menjadi lebih baik setiap hari.', 'author' => 'Anonymous']
    ];
    
    if (!$quote) {
        $quote = $defaultQuotes[date('d') % count($defaultQuotes)];
    }
    ?>
    <div class="quote-widget">
        <span class="quote-icon"><?php icon('quote', 24); ?></span>
        <p class="quote-text"><?= htmlspecialchars($quote['text']) ?></p>
        <p class="quote-author">— <?= htmlspecialchars($quote['author']) ?></p>
    </div>
    <?php
}

/**
 * Skill Progress Widget Component
 */
function renderSkillProgressWidget($skills = []) {
    $skillColors = ['html', 'css', 'js', 'python', 'php'];
    ?>
    <div class="skill-progress-widget">
        <div class="widget-header">
            <h4><?php icon('trending-up', 16); ?> Progress Skill</h4>
        </div>
        <div class="skill-list">
            <?php if (empty($skills)): ?>
            <div class="skill-item">
                <div class="skill-icon" style="background: rgba(100, 116, 139, 0.2);"><?php icon('book', 20); ?></div>
                <div class="skill-info">
                    <p class="skill-name" style="color: #94a3b8;">Belum ada kursus yang diikuti</p>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($skills as $index => $skill): 
                $colorClass = $skillColors[$index % count($skillColors)];
                $total = $skill['total_lessons'] ?? 1;
                $completed = $skill['completed_lessons'] ?? 0;
                $percent = $total > 0 ? round(($completed / $total) * 100) : 0;
            ?>
            <div class="skill-item">
                <div class="skill-icon" style="background: rgba(139, 92, 246, 0.2);"><?php icon('code', 18); ?></div>
                <div class="skill-info">
                    <div class="skill-header">
                        <span class="skill-name"><?= htmlspecialchars(substr($skill['judul_course'] ?? 'Course', 0, 25)) ?></span>
                        <span class="skill-level"><?= $percent ?>%</span>
                    </div>
                    <div class="skill-bar">
                        <div class="skill-progress <?= $colorClass ?>" style="width: <?= $percent ?>%"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Recommended Courses Widget Component
 */
function renderRecommendedCoursesWidget($courses = []) {
    ?>
    <div class="activity-feed-widget">
        <div class="widget-header">
            <h4><?php icon('star', 16); ?> Rekomendasi Untukmu</h4>
            <a href="courses-public.php">Lihat Semua</a>
        </div>
        <div class="activity-list">
            <?php if (empty($courses)): ?>
            <div class="activity-item">
                <div class="activity-avatar"><?php icon('book', 24); ?></div>
                <div class="activity-content">
                    <p class="activity-text">Jelajahi kursus menarik lainnya!</p>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($courses as $course): 
                $thumbnail = !empty($course['thumbnail']) ? 'assets/uploads/thumbnails/' . $course['thumbnail'] : null;
            ?>
            <a href="course.php?id=<?= $course['id'] ?>" class="activity-item" style="text-decoration: none;">
                <div class="activity-avatar" style="border-radius: 8px;">
                    <?php if ($thumbnail): ?>
                    <img src="<?= htmlspecialchars($thumbnail) ?>" alt="Thumbnail" style="border-radius: 6px;">
                    <?php else: ?>
                    <?php icon('book-open', 24); ?>
                    <?php endif; ?>
                </div>
                <div class="activity-content">
                    <p class="activity-text">
                        <strong><?= htmlspecialchars($course['judul_course'] ?? 'Course') ?></strong>
                    </p>
                    <p class="activity-time">
                        <?= $course['total_lessons'] ?? 0 ?> Lessons • 
                        <span style="color: #a78bfa;">+<?= $course['total_xp'] ?? 0 ?> XP</span>
                    </p>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Helper function for time ago
 */
function timeAgo($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    $string = array(
        'y' => 'tahun',
        'm' => 'bulan',
        'w' => 'minggu',
        'd' => 'hari',
        'h' => 'jam',
        'i' => 'menit',
        's' => 'detik',
    );
    
    $parts = [];
    if ($diff->y > 0) $parts[] = $diff->y . ' tahun';
    if ($diff->m > 0) $parts[] = $diff->m . ' bulan';
    if ($weeks > 0) $parts[] = $weeks . ' minggu';
    if ($days > 0) $parts[] = $days . ' hari';
    if ($diff->h > 0) $parts[] = $diff->h . ' jam';
    if ($diff->i > 0) $parts[] = $diff->i . ' menit';
    if ($diff->s > 0) $parts[] = $diff->s . ' detik';

    if (!$full) $parts = array_slice($parts, 0, 1);
    return $parts ? implode(', ', $parts) . ' lalu' : 'baru saja';
}
?>

<?php
/**
 * Prozone - Features Page
 * Halaman showcase lengkap fitur-fitur Prozone
 */

if (!defined('PROZONE_ACCESS')) {
    define('PROZONE_ACCESS', true);
}

require_once __DIR__ . '/config/config.php';

$page_title       = 'Fitur - ' . APP_NAME;
$page_description = 'Temukan semua fitur Prozone: code editor interaktif, gamifikasi, clans, leaderboard, achievements, certificates, dan masih banyak lagi.';
$page_css         = ['components/card.css', 'components/button.css', 'components/badge.css', 'components/avatar.css', 'pages/landing.css', 'pages/features.css'];
$body_class       = getThemeClass();
$current_page     = 'features.php';
$nav_active       = function ($href) use ($current_page) {
    return str_contains($href, $current_page) ? ' aria-current="page"' : '';
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require_once 'includes/head.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta($page_title, $page_description, 'fitur prozone, code editor, gamifikasi, clan, leaderboard, achievement, sertifikat'); ?>
</head>
<body class="<?php echo $body_class; ?> landing-page page-features">
    <!-- Public navbar (Glass) -->
    <nav class="landing-nav" id="landingNav" aria-label="Navigasi utama">
        <div class="nav-inner">
            <a href="index.php" class="brand" aria-label="Prozone Home">
                <svg class="brand-logo" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <defs>
                        <linearGradient id="brandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#6366F1"/>
                            <stop offset="100%" stop-color="#10B981"/>
                        </linearGradient>
                    </defs>
                    <path d="M 25 20 L 25 75 Q 25 80 30 80 L 35 80 Q 40 80 40 75 L 40 20 Q 40 15 35 15 L 30 15 Q 25 15 25 20 Z" fill="url(#brandGrad)"/>
                    <path d="M 40 20 Q 40 15 45 15 L 60 15 Q 70 15 70 25 L 70 35 Q 70 45 60 45 L 45 45 Q 40 45 40 40 L 40 30 Q 40 25 45 25 L 60 25 Q 65 25 65 30 L 65 35 Q 65 40 60 40 L 45 40 Q 40 40 40 35 Z" fill="url(#brandGrad)"/>
                </svg>
                <span>Prozone</span>
            </a>
            <div class="nav-menu" id="navMenu">
                <a href="features.php" class="nav-link"<?php echo $nav_active('features.php'); ?>>Fitur</a>
                <a href="courses-public.php" class="nav-link"<?php echo $nav_active('courses-public.php'); ?>>Kursus</a>
                <a href="about.php" class="nav-link"<?php echo $nav_active('about.php'); ?>>Tentang</a>
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="btn btn-primary btn-sm nav-cta">Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-sm nav-cta">Masuk</a>
                <?php endif; ?>
            </div>
            <button class="nav-mobile-toggle" id="navMobileToggle" aria-label="Menu" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
        </div>
    </nav>

    <!-- Hero -->
    <header class="hero">
        <div class="hero-inner">
            <div class="hero-content">
                <span class="hero-eyebrow reveal">
                    <span class="pulse-dot" aria-hidden="true"></span>
                    Platform Coding Interaktif
                </span>
                <h1 class="hero-title reveal">
                    Semua yang Anda Butuhkan untuk <span class="text-gradient">Belajar Coding</span>
                </h1>
                <p class="hero-lead reveal">
                    Prozone menyediakan tools lengkap untuk menjadi developer. Dari code editor interaktif,
                    gamifikasi yang seru, hingga komunitas yang supportive — semua dalam satu platform.
                </p>
                <div class="hero-ctas reveal">
                    <?php if (isLoggedIn()): ?>
                        <a href="dashboard.php" class="btn btn-primary btn-lg">Buka Dashboard</a>
                        <a href="courses-public.php" class="btn btn-secondary btn-lg">Jelajahi Kursus</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary btn-lg">
                            Mulai Belajar Gratis
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <a href="login.php" class="btn btn-secondary btn-lg">Sudah Punya Akun?</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <svg viewBox="0 0 600 500" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="featGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#6366F1"/>
                            <stop offset="100%" stop-color="#10B981"/>
                        </linearGradient>
                    </defs>
                    <!-- Background circles -->
                    <circle cx="100" cy="100" r="80" fill="url(#featGrad1)" opacity="0.15"/>
                    <circle cx="500" cy="400" r="100" fill="url(#featGrad1)" opacity="0.1"/>
                    <circle cx="450" cy="100" r="60" fill="url(#featGrad1)" opacity="0.12"/>

                    <!-- Feature icons cluster -->
                    <g transform="translate(120, 80)">
                        <!-- Code editor window -->
                        <rect class="hero-terminal-bg" x="0" y="0" width="220" height="160" rx="12" stroke="url(#featGrad1)" stroke-width="2"/>
                        <circle cx="15" cy="15" r="5" fill="#F43F5E"/>
                        <circle cx="35" cy="15" r="5" fill="#F59E0B"/>
                        <circle cx="55" cy="15" r="5" fill="#10B981"/>
                        <line class="hero-terminal-divider" x1="15" y1="35" x2="205" y2="35" stroke-width="2" opacity="0.5"/>
                        <text class="hero-terminal-cmd" x="15" y="60" font-family="monospace" font-size="13" font-weight="bold">&gt; learn coding</text>
                        <text class="hero-terminal-ok" x="15" y="85" font-family="monospace" font-size="11">✓ Interactive editor</text>
                        <text class="hero-terminal-ok" x="15" y="110" font-family="monospace" font-size="11">✓ Gamification</text>
                        <text class="hero-terminal-dim" x="15" y="135" font-family="monospace" font-size="11">✓ Community</text>
                    </g>

                    <!-- XP badge -->
                    <g transform="translate(400, 200)">
                        <rect x="0" y="0" width="140" height="80" rx="10" fill="url(#featGrad1)" opacity="0.2" stroke="url(#featGrad1)" stroke-width="1.5"/>
                        <text x="70" y="35" text-anchor="middle" font-family="sans-serif" font-size="28" font-weight="800" fill="url(#featGrad1)">12K</text>
                        <text x="70" y="60" text-anchor="middle" font-family="sans-serif" font-size="10" fill="url(#featGrad1)" opacity="0.7">XP POINTS</text>
                    </g>

                    <!-- Trophy icon -->
                    <g transform="translate(380, 340)">
                        <path d="M20 6 L20 2 L40 2 L40 6 L35 6 L35 10 L25 10 L25 6 Z" fill="url(#featGrad1)" opacity="0.3"/>
                        <rect x="24" y="10" width="12" height="3" fill="url(#featGrad1)" opacity="0.3"/>
                        <rect x="22" y="13" width="16" height="3" rx="1" fill="url(#featGrad1)" opacity="0.4"/>
                    </g>

                    <!-- Achievement star -->
                    <g transform="translate(160, 300)">
                        <path d="M12 2 L15.09 8.26 L22 9.27 L17 14.14 L18.18 21.02 L12 17.77 L5.82 21.02 L7 14.14 L2 9.27 L8.91 8.26 Z" fill="url(#featGrad1)" opacity="0.25"/>
                    </g>
                </svg>
            </div>
        </div>
    </header>

    <!-- Feature Detail: Interactive Learning -->
    <section class="section section-feature-detail">
        <div class="container">
            <div class="feature-detail">
                <div class="feature-detail-content reveal">
                    <span class="section-detail-eyebrow">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="2" y="3" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 21H16M12 17V21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Interactive Learning
                    </span>
                    <h2 class="feature-detail-title">Code Editor Langsung di Browser</h2>
                    <p class="feature-detail-desc">
                        Tidak perlu install software apapun. Tulis dan jalankan kode langsung di browser
                        dengan editor modern kami. Mendukung JavaScript, Python, HTML, CSS, dan masih banyak lagi.
                    </p>
                    <ul class="feature-detail-list">
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Multi-language support (JavaScript, Python, Java, C++, dll)
                        </li>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Syntax highlighting & auto-complete
                        </li>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Real-time error detection
                        </li>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Save & share kode dengan komunitas
                        </li>
                    </ul>
                </div>
                <div class="feature-detail-visual reveal">
                    <div class="code-preview">
                        <div class="code-preview-header">
                            <div class="code-preview-dots">
                                <span></span><span></span><span></span>
                            </div>
                            <span class="code-preview-filename">hello.js</span>
                        </div>
                        <div class="code-preview-body">
                            <pre><code><span class="code-line"><span class="code-keyword">function</span> <span class="code-function">greet</span>(<span class="code-var">name</span>) {</span><span class="code-line">  <span class="code-keyword">return</span> <span class="code-string">`Hello, <span class="code-interp">${name}</span>!`</span>;</span><span class="code-line">}</span><span class="code-line"></span><span class="code-line"><span class="code-function">console</span>.<span class="code-function">log</span>(<span class="code-function">greet</span>(<span class="code-string">'Prozone'</span>));</span></code></pre>
                        </div>
                        <div class="code-preview-output">
                            <span class="code-output-label">Output:</span>
                            <span class="code-output-text">Hello, Prozone!</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature Detail: Gamification -->
    <section class="section section-feature-detail section-feature-reverse">
        <div class="container">
            <div class="feature-detail">
                <div class="feature-detail-visual reveal">
                    <div class="gamification-preview">
                        <div class="xp-card">
                            <div class="xp-card-header">
                                <span class="xp-card-label">Total XP</span>
                                <span class="xp-card-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                </span>
                            </div>
                            <div class="xp-card-value">12,450</div>
                            <div class="xp-progress">
                                <div class="xp-progress-bar" style="width: 65%"></div>
                            </div>
                            <div class="xp-progress-label">Level 12 → 13</div>
                        </div>
                        <div class="achievement-strip">
                            <div class="achievement-item">
                                <div class="achievement-item-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 15c2.21 0 4-1.79 4-4V5a4 4 0 0 0-8 0v6c0 2.21 1.79 4 4 4z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                                </div>
                                <div class="achievement-item-name">First Code</div>
                            </div>
                            <div class="achievement-item achievement-item-locked">
                                <div class="achievement-item-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                </div>
                                <div class="achievement-item-name">Locked</div>
                            </div>
                            <div class="achievement-item achievement-item-locked">
                                <div class="achievement-item-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M16 16s-1.5-2-4-2-4 2-4 2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                                </div>
                                <div class="achievement-item-name">Locked</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="feature-detail-content reveal">
                    <span class="section-detail-eyebrow">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        Gamification
                    </span>
                    <h2 class="feature-detail-title">Belajar Sambil Bermain</h2>
                    <p class="feature-detail-desc">
                        Sistem XP, level, dan achievement membuat belajar terasa seperti bermain game.
                        Setiap baris kode yang Anda tulis memberikan poin dan membuka achievement baru.
                    </p>
                    <ul class="feature-detail-list">
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            XP & level system yang progression
                        </li>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            50+ achievement untuk di-unlock
                        </li>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Daily streak & bonus rewards
                        </li>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Coin & shop system untuk customization
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature Detail: Clan System -->
    <section class="section section-feature-detail">
        <div class="container">
            <div class="feature-detail">
                <div class="feature-detail-content reveal">
                    <span class="section-detail-eyebrow">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 11C11.2091 11 13 9.20914 13 7C13 4.79086 11.2091 3 9 3C6.79086 3 5 4.79086 5 7C5 9.20914 6.79086 11 9 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Clan System
                    </span>
                    <h2 class="feature-detail-title">Berkompetisi Bersama Clan</h2>
                    <p class="feature-detail-desc">
                        Bergabung dengan clan atau buat clan Anda sendiri. Berkompetisi di leaderboard clan,
                        kolaborasi menyelesaikan challenge, dan bangun komunitas belajar yang solid.
                    </p>
                    <ul class="feature-detail-list">
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Buat atau join clan dengan teman
                        </li>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Clan leaderboard & weekly challenges
                        </li>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Clan chat & collaborative learning
                        </li>
                        <li>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            Clan XP pool & bonus rewards
                        </li>
                    </ul>
                </div>
                <div class="feature-detail-visual reveal">
                    <div class="clan-preview">
                        <!-- Clan Card -->
                        <div class="clan-card">
                            <div class="clan-card-header">
                                <div class="clan-card-emblem">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="clan-card-info">
                                    <div class="clan-card-name">Code Warriors</div>
                                    <div class="clan-card-rank">#3 di Leaderboard</div>
                                </div>
                            </div>
                            <div class="clan-card-stats">
                                <div class="clan-stat">
                                    <div class="clan-stat-value">8</div>
                                    <div class="clan-stat-label">Anggota</div>
                                </div>
                                <div class="clan-stat">
                                    <div class="clan-stat-value">42K</div>
                                    <div class="clan-stat-label">Total XP</div>
                                </div>
                                <div class="clan-stat">
                                    <div class="clan-stat-value">5</div>
                                    <div class="clan-stat-label">Challenges</div>
                                </div>
                            </div>
                            <div class="clan-members-strip">
                                <div class="clan-member-avatar" style="--avatar-color: var(--brand)">A</div>
                                <div class="clan-member-avatar" style="--avatar-color: var(--accent)">S</div>
                                <div class="clan-member-avatar" style="--avatar-color: var(--color-info)">R</div>
                                <div class="clan-member-avatar" style="--avatar-color: var(--color-warning)">D</div>
                                <div class="clan-member-avatar" style="--avatar-color: var(--color-error)">M</div>
                                <div class="clan-member-avatar clan-member-more">+3</div>
                            </div>
                        </div>
                        <!-- Clan Leaderboard Mini -->
                        <div class="clan-leaderboard-mini">
                            <div class="clan-lb-row clan-lb-row-top">
                                <span class="clan-lb-rank">#1</span>
                                <span class="clan-lb-name">Pro Legends</span>
                                <span class="clan-lb-xp">68K XP</span>
                            </div>
                            <div class="clan-lb-row clan-lb-row-top">
                                <span class="clan-lb-rank">#2</span>
                                <span class="clan-lb-name">Byte Squad</span>
                                <span class="clan-lb-xp">55K XP</span>
                            </div>
                            <div class="clan-lb-row clan-lb-row-you">
                                <span class="clan-lb-rank">#3</span>
                                <span class="clan-lb-name">Code Warriors</span>
                                <span class="clan-lb-xp">42K XP</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="cta-card reveal">
            <h2>Siap Memulai Perjalanan Coding Anda?</h2>
            <p>Bergabung dengan ribuan developer yang sudah memulai belajar coding dengan cara yang menyenangkan. Dapatkan XP, kumpulkan achievement, dan bersaing di leaderboard.</p>
            <div class="row">
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="btn btn-xl btn-cta-primary">Buka Dashboard</a>
                    <a href="courses-public.php" class="btn btn-xl btn-cta-secondary">Jelajahi Kursus</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-xl btn-cta-primary">Mulai Belajar Gratis</a>
                    <a href="login.php" class="btn btn-xl btn-cta-secondary">Sudah Punya Akun</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div class="landing-footer-grid">
            <div class="landing-footer-brand">
                <a href="index.php" class="brand" aria-label="Prozone Home">
                    <svg class="brand-logo" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width:32px;height:32px">
                        <defs>
                            <linearGradient id="footerBrandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#6366F1"/>
                                <stop offset="100%" stop-color="#10B981"/>
                            </linearGradient>
                        </defs>
                        <path d="M 25 20 L 25 75 Q 25 80 30 80 L 35 80 Q 40 80 40 75 L 40 20 Q 40 15 35 15 L 30 15 Q 25 15 25 20 Z" fill="url(#footerBrandGrad)"/>
                        <path d="M 40 20 Q 40 15 45 15 L 60 15 Q 70 15 70 25 L 70 35 Q 70 45 60 45 L 45 45 Q 40 45 40 40 L 40 30 Q 40 25 45 25 L 60 25 Q 65 25 65 30 L 65 35 Q 65 40 60 40 L 45 40 Q 40 40 40 35 Z" fill="url(#footerBrandGrad)"/>
                    </svg>
                    <span>Prozone</span>
                </a>
                <p>Platform pembelajaran coding interaktif dengan gamifikasi. Belajar coding jadi lebih menyenangkan.</p>
                <div class="landing-footer-social">
                    <a href="#" aria-label="GitHub">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                    </a>
                    <a href="#" aria-label="Twitter">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" aria-label="Discord">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.096 18.06a.082.082 0 00.031.037 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028 14.09 14.09 0 001.226-1.994.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.456 1.216 1.987a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.095 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.095 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
                    </a>
                </div>
            </div>
            <div class="landing-footer-col">
                <h4>Platform</h4>
                <ul>
                    <li><a href="features.php">Fitur</a></li>
                    <li><a href="courses-public.php">Kursus</a></li>
                    <li><a href="leaderboard.php">Leaderboard</a></li>
                    <li><a href="about.php">Tentang</a></li>
                </ul>
            </div>
            <div class="landing-footer-col">
                <h4>Sumber Daya</h4>
                <ul>
                    <li><a href="#">Dokumentasi</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Komunitas</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
            <div class="landing-footer-col">
                <h4>Legal</h4>
                <ul>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                    <li><a href="#">Cookie Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="landing-footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            <div class="landing-footer-bottom-links">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="#">Cookies</a>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        (function() {
            const nav = document.getElementById('landingNav');
            if (!nav) return;
            const onScroll = () => {
                if (window.scrollY > 16) nav.classList.add('is-scrolled');
                else nav.classList.remove('is-scrolled');
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        })();

        // Mobile menu toggle
        (function() {
            const toggle = document.getElementById('navMobileToggle');
            const menu = document.getElementById('navMenu');
            if (!toggle || !menu) return;
            toggle.addEventListener('click', function() {
                const isOpen = menu.classList.toggle('is-mobile-open');
                toggle.setAttribute('aria-expanded', isOpen);
            });
            document.addEventListener('click', function(e) {
                if (!menu.contains(e.target) && !toggle.contains(e.target)) {
                    menu.classList.remove('is-mobile-open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        })();

        // Reveal-on-scroll
        (function() {
            const items = document.querySelectorAll('.reveal');
            if (!('IntersectionObserver' in window)) {
                items.forEach(el => el.classList.add('is-visible'));
                return;
            }
            const io = new IntersectionObserver((entries) => {
                entries.forEach((entry, i) => {
                    if (entry.isIntersecting) {
                        const delay = Array.from(entry.target.parentElement.children)
                            .filter(c => c.classList.contains('reveal'))
                            .indexOf(entry.target) * 80;
                        setTimeout(() => entry.target.classList.add('is-visible'), delay);
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
            items.forEach(el => io.observe(el));
        })();
    </script>
</body>
</html>
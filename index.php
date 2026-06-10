<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$page_title       = 'Belajar Coding dengan Mudah';
$page_description = 'Platform pembelajaran coding interaktif terbaik untuk pemula hingga ahli. Pelajari HTML, CSS, JavaScript, Python, dan banyak lagi.';
$page_css         = ['components/button.css', 'components/badge.css', 'pages/landing.css'];
$body_class       = getThemeClass();
$current_page     = 'index.php';
$nav_active       = function ($href) use ($current_page) {
    return str_contains($href, $current_page) ? ' aria-current="page"' : '';
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require_once 'includes/head.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta($page_title, $page_description, 'belajar coding, programming, web development, html, css, javascript, python'); ?>
</head>
<body class="<?php echo $body_class; ?> landing-page">

    <!-- Public navbar (glass) -->
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
                <a href="login.php" class="btn btn-primary btn-sm nav-cta">Masuk</a>
            </div>
            <button class="nav-mobile-toggle" id="navMobileToggle" aria-label="Menu" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
        </div>
    </nav>

    <!-- Hero -->
    <header class="hero">
        <div class="hero-bg-grid" aria-hidden="true"></div>
        <div class="hero-particles" aria-hidden="true">
            <span class="particle" style="--delay:0s;--x:10%;--y:20%;--size:4px;--color:var(--brand)"></span>
            <span class="particle" style="--delay:1.2s;--x:85%;--y:15%;--size:3px;--color:var(--accent)"></span>
            <span class="particle" style="--delay:2.4s;--x:70%;--y:60%;--size:5px;--color:var(--brand)"></span>
            <span class="particle" style="--delay:0.8s;--x:25%;--y:75%;--size:3px;--color:var(--accent)"></span>
            <span class="particle" style="--delay:3.6s;--x:90%;--y:80%;--size:4px;--color:var(--color-info)"></span>
            <span class="particle" style="--delay:1.6s;--x:40%;--y:30%;--size:2px;--color:var(--brand)"></span>
            <span class="particle" style="--delay:2.8s;--x:60%;--y:90%;--size:3px;--color:var(--color-xp)"></span>
            <span class="particle" style="--delay:0.4s;--x:15%;--y:50%;--size:4px;--color:var(--accent)"></span>
        </div>
        <div class="hero-inner">
            <div class="hero-content">
                <span class="hero-eyebrow reveal">
                    <span class="pulse-dot" aria-hidden="true"></span>
                    Platform Coding #1 untuk Pemula
                </span>
                <h1 class="hero-title reveal">
                    Belajar Coding dengan Cara <span class="text-gradient">Menyenangkan</span>
                </h1>
                <p class="hero-lead reveal">
                    Platform pembelajaran coding interaktif dengan clan, leaderboard, achievement,
                    dan code editor langsung di browser. Tingkatkan skill programming Anda
                    sambil bersenang-senang!
                </p>
                <div class="hero-ctas reveal">
                    <a href="register.php" class="btn btn-primary btn-lg hero-btn-primary">
                        <span>Mulai Belajar Gratis</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <a href="login.php" class="btn btn-secondary btn-lg hero-btn-secondary">Sudah Punya Akun?</a>
                </div>
                <dl class="hero-trust reveal">
                    <div class="hero-trust-item">
                        <dt class="hero-trust-label">Kursus</dt>
                        <dd class="hero-trust-num" data-count="100">0+</dd>
                    </div>
                    <div class="hero-trust-item">
                        <dt class="hero-trust-label">Students</dt>
                        <dd class="hero-trust-num" data-count="1000">0+</dd>
                    </div>
                    <div class="hero-trust-item">
                        <dt class="hero-trust-label">Instructors</dt>
                        <dd class="hero-trust-num" data-count="50">0+</dd>
                    </div>
                </dl>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <svg viewBox="0 0 600 500" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="heroGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#6366F1"/>
                            <stop offset="100%" stop-color="#10B981"/>
                        </linearGradient>
                        <linearGradient id="heroGrad2" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#6366F1" stop-opacity="0.6"/>
                            <stop offset="100%" stop-color="#10B981" stop-opacity="0.6"/>
                        </linearGradient>
                        <filter id="heroGlow">
                            <feGaussianBlur stdDeviation="12" result="blur"/>
                            <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                    </defs>
                    <!-- Ambient glow -->
                    <circle cx="300" cy="250" r="220" fill="url(#heroGrad2)" opacity="0.08"/>
                    <!-- Background circles -->
                    <circle cx="100" cy="100" r="80" fill="url(#heroGrad1)" opacity="0.12"/>
                    <circle cx="500" cy="400" r="100" fill="url(#heroGrad1)" opacity="0.08"/>
                    <circle cx="450" cy="100" r="60" fill="url(#heroGrad1)" opacity="0.10"/>
                    <!-- Code brackets -->
                    <g transform="translate(150, 200)" filter="url(#heroGlow)">
                        <path d="M 0 0 L 0 150 Q 0 180 30 180 L 60 180" stroke="url(#heroGrad1)" stroke-width="8" fill="none" stroke-linecap="round"/>
                        <path d="M 200 0 L 200 150 Q 200 180 170 180 L 140 180" stroke="url(#heroGrad1)" stroke-width="8" fill="none" stroke-linecap="round"/>
                    </g>
                    <!-- Code lines -->
                    <g transform="translate(200, 180)" opacity="0.85">
                        <line x1="0" y1="0"  x2="150" y2="0"  stroke="url(#heroGrad1)" stroke-width="4" class="code-line" style="animation-delay: 0.3s"/>
                        <line x1="0" y1="30" x2="120" y2="30" stroke="url(#heroGrad1)" stroke-width="4" class="code-line" style="animation-delay: 0.6s"/>
                        <line x1="0" y1="60" x2="180" y2="60" stroke="url(#heroGrad1)" stroke-width="4" class="code-line" style="animation-delay: 0.9s"/>
                        <line x1="0" y1="90" x2="100" y2="90" stroke="url(#heroGrad1)" stroke-width="4" class="code-line" style="animation-delay: 1.2s"/>
                    </g>
                    <!-- Terminal -->
                    <g transform="translate(300, 300)">
                        <rect class="hero-terminal-bg" x="0" y="0" width="200" height="140" rx="12" stroke="url(#heroGrad1)" stroke-width="2"/>
                        <circle cx="15" cy="15" r="5" fill="#F43F5E"/>
                        <circle cx="35" cy="15" r="5" fill="#F59E0B"/>
                        <circle cx="55" cy="15" r="5" fill="#10B981"/>
                        <line class="hero-terminal-divider" x1="20" y1="40" x2="180" y2="40" stroke-width="2" opacity="0.5"/>
                        <text class="hero-terminal-cmd" x="20" y="65"  font-family="monospace" font-size="14" font-weight="bold">&gt; npm start</text>
                        <text class="hero-terminal-ok"  x="20" y="90"  font-family="monospace" font-size="12">✓ Server running...</text>
                        <text class="hero-terminal-dim" x="20" y="115" font-family="monospace" font-size="12">✓ Ready to code!</text>
                    </g>
                    <!-- XP badge -->
                    <g transform="translate(480, 160)" filter="url(#heroGlow)">
                        <circle cx="30" cy="30" r="28" fill="var(--color-xp)" opacity="0.9"/>
                        <text x="30" y="34" text-anchor="middle" font-family="sans-serif" font-size="14" font-weight="bold" fill="#1E293B">XP</text>
                    </g>
                    <!-- Trophy icon -->
                    <g transform="translate(80, 340)">
                        <path d="M20 4H4C3 4 2 5 2 6V10C2 14 5 16 8 16H10V18H6V20H18V18H14V16H16C19 16 22 14 22 10V6C22 5 21 4 20 4Z" fill="url(#heroGrad1)" opacity="0.7"/>
                    </g>
                </svg>
            </div>
        </div>
    </header>

    <!-- How It Works -->
    <section class="section section-how" id="how-it-works">
        <div class="container">
            <div class="section-heading reveal">
                <span class="eyebrow">Cara Kerja</span>
                <h2>Mulai coding dalam 3 langkah mudah</h2>
                <p>Tidak perlu pengalaman sebelumnya. Cukup daftar dan langsung mulai perjalanan coding Anda.</p>
            </div>

            <div class="how-steps-wrapper reveal">
                <div class="how-connector" aria-hidden="true">
                    <span class="how-connector-dot how-connector-dot-1"></span>
                    <span class="how-connector-dot how-connector-dot-2"></span>
                    <span class="how-connector-dot how-connector-dot-3"></span>
                </div>
                <div class="grid grid-auto how-grid">
                    <div class="card how-step reveal">
                        <div class="how-step-num-badge">01</div>
                        <div class="how-icon">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M16 21V19C16 17.9391 15.5786 16.9217 14.8284 16.1716C14.0783 15.4214 13.0609 15 12 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M8.5 11C10.7091 11 12.5 9.20914 12.5 7C12.5 4.79086 10.7091 3 8.5 3C6.29086 3 4.5 4.79086 4.5 7C4.5 9.20914 6.29086 11 8.5 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M20 8V14M23 11H17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3>Buat Akun Gratis</h3>
                        <p>Daftar dalam 30 detik dengan email Anda. Tidak perlu kartu kredit, langsung bisa mulai belajar.</p>
                    </div>

                    <div class="card how-step reveal">
                        <div class="how-step-num-badge">02</div>
                        <div class="how-icon">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M2 3H6C6.53043 3 7.03914 3.21071 7.41421 3.58579C7.78929 3.96086 8 4.46957 8 5V19C8 19.5304 7.78929 20.0391 7.41421 20.4142C7.03914 20.7893 6.53043 21 6 21H2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M22 3H18C17.4696 3 16.9609 3.21071 16.5858 3.58579C16.2107 3.96086 16 4.46957 16 5V19C16 19.5304 16.2107 20.0391 16.5858 20.4142C16.9609 20.7893 17.4696 21 18 21H22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 3V21M22 3V21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M12 8V16M8 12H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3>Pilih Bahasa & Kursus</h3>
                        <p>HTML, CSS, JavaScript, Python, dan banyak lagi. Pilih jalur belajar yang sesuai dengan goal Anda.</p>
                    </div>

                    <div class="card how-step reveal">
                        <div class="how-step-num-badge">03</div>
                        <div class="how-icon">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3>Tulis & Jalankan Code</h3>
                        <p>Gunakan editor interaktif untuk menulis kode, lihat hasilnya secara real-time, dan kumpulkan XP.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="section section-stats">
        <div class="container">
            <div class="section-heading reveal">
                <span class="eyebrow">Angka & Fakta</span>
                <h2>Prozone dalam angka</h2>
                <p>Platform yang terus bertumbuh bersama komunitas developer Indonesia.</p>
            </div>
            <div class="grid grid-auto-sm stats-grid">
                <div class="card stat-block reveal">
                    <div class="stat-glow" aria-hidden="true"></div>
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div class="stat-value" data-count="100">0+</div>
                    <div class="stat-label">Kursus Tersedia</div>
                </div>
                <div class="card stat-block reveal">
                    <div class="stat-glow" aria-hidden="true"></div>
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div class="stat-value" data-count="1000">0+</div>
                    <div class="stat-label">Student Aktif</div>
                </div>
                <div class="card stat-block reveal">
                    <div class="stat-glow" aria-hidden="true"></div>
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div class="stat-value" data-count="50">0+</div>
                    <div class="stat-label">Instruktur</div>
                </div>
                <div class="card stat-block reveal">
                    <div class="stat-glow" aria-hidden="true"></div>
                    <div class="stat-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div class="stat-value">24/7</div>
                    <div class="stat-label">Akses Belajar</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="section section-testimonials" id="testimonials">
        <div class="container">
            <div class="section-heading reveal">
                <span class="eyebrow">Testimoni</span>
                <h2>Dengarkan dari para learner</h2>
                <p>Ribuan developer sudah memulai perjalanan coding mereka bersama Prozone.</p>
            </div>

            <div class="testimonials-grid">
                <div class="testimonial-card reveal">
                    <div class="testimonial-quote-mark" aria-hidden="true">"</div>
                    <div class="testimonial-stars" aria-label="5 dari 5 bintang">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                    </div>
                    <p class="testimonial-text">Prozone benar-benar mengubah cara saya belajar coding. Sistem XP dan achievement bikin saya selalu semangat untuk lanjut belajar setiap hari!</p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar"><span>A</span></div>
                        <div>
                            <div class="testimonial-name">Andi Pratama</div>
                            <div class="testimonial-role">Mahasiswa Informatika</div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card reveal">
                    <div class="testimonial-quote-mark" aria-hidden="true">"</div>
                    <div class="testimonial-stars" aria-label="5 dari 5 bintang">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                    </div>
                    <p class="testimonial-text">Code editor langsung di browser itu game changer! Saya bisa langsung praktek tanpa perlu install apapun. Clan system-nya juga seru banget.</p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar"><span>S</span></div>
                        <div>
                            <div class="testimonial-name">Sari Dewi</div>
                            <div class="testimonial-role">Career Switcher</div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card reveal">
                    <div class="testimonial-quote-mark" aria-hidden="true">"</div>
                    <div class="testimonial-stars" aria-label="5 dari 5 bintang">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                    </div>
                    <p class="testimonial-text">Sebagai instructor, Prozone memudahkan saya membuat materi interaktif. Dashboard analytics-nya juga sangat membantu memantau progress siswa.</p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar"><span>R</span></div>
                        <div>
                            <div class="testimonial-name">Rizki Ramadhan</div>
                            <div class="testimonial-role">Instructor & Senior Dev</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="cta-card reveal">
            <div class="cta-mesh" aria-hidden="true">
                <span class="cta-shape cta-shape-1"></span>
                <span class="cta-shape cta-shape-2"></span>
                <span class="cta-shape cta-shape-3"></span>
            </div>
            <h2>Siap Memulai Perjalanan Coding Anda?</h2>
            <p>Bergabunglah dengan ribuan developer yang sudah memulai belajar coding dengan cara yang menyenangkan. Dapatkan XP, kumpulkan achievement, dan bersaing di leaderboard.</p>
            <div class="row">
                <a href="register.php" class="btn btn-xl btn-cta-primary">
                    Mulai Belajar Gratis
                </a>
                <a href="login.php" class="btn btn-xl btn-cta-secondary">Sudah Punya Akun</a>
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
                entries.forEach((entry) => {
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

        // Animated counter
        (function() {
            const counters = document.querySelectorAll('[data-count]');
            if (!counters.length) return;
            const io = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const el = entry.target;
                        const target = parseInt(el.dataset.count);
                        const suffix = '+';
                        const duration = 1500;
                        const start = 0;
                        const startTime = performance.now();
                        function update(now) {
                            const elapsed = now - startTime;
                            const progress = Math.min(elapsed / duration, 1);
                            // easeOutExpo
                            const eased = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
                            const current = Math.round(start + (target - start) * eased);
                            el.textContent = current.toLocaleString('id-ID') + suffix;
                            if (progress < 1) requestAnimationFrame(update);
                        }
                        requestAnimationFrame(update);
                        io.unobserve(el);
                    }
                });
            }, { threshold: 0.3 });
            counters.forEach(el => io.observe(el));
        })();
    </script>
</body>
</html>
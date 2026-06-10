<?php
/**
 * Prozone - About Us Page
 * Halaman tentang kami dengan design system yang konsisten
 */

if (!defined('PROZONE_ACCESS')) {
    define('PROZONE_ACCESS', true);
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$page_title       = 'Tentang Kami - ' . APP_NAME;
$page_description = 'Pelajari misi Prozone dalam membuat pembelajaran coding interaktif untuk developer Indonesia. Bergabunglah dengan komunitas pembelajar kami.';
$page_css         = ['components/card.css', 'components/button.css', 'components/badge.css', 'components/avatar.css', 'components/alert.css', 'components/layout.css', 'pages/landing.css', 'pages/about.css'];
$body_class       = getThemeClass();
$current_page     = 'about.php';
$nav_active       = function ($href) use ($current_page) {
    return str_contains($href, $current_page) ? ' aria-current="page"' : '';
};

$is_logged_in = isLoggedIn();
$user = $is_logged_in ? getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require_once 'includes/head.php'; ?>
</head>
<body class="<?php echo $body_class; ?> landing-page page-about">
    <!-- Public navbar (glass) — same as index.php -->
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
                <?php if ($is_logged_in): ?>
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

    <!-- Hero — matching index.php hero style -->
    <header class="hero">
        <div class="hero-inner">
            <div class="hero-content">
                <span class="hero-eyebrow reveal">
                    <span class="pulse-dot" aria-hidden="true"></span>
                    Tentang Kami
                </span>
                <h1 class="hero-title reveal">
                    Membangun <span class="text-gradient">Developer Indonesia</span> yang Berdaya Saing Global
                </h1>
                <p class="hero-lead reveal">
                    Prozone adalah platform pembelajaran coding interaktif yang dirancang untuk
                    membekali generasi muda Indonesia dengan keterampilan teknologi yang relevan,
                    melalui pendekatan gamifikasi yang menyenangkan.
                </p>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <svg viewBox="0 0 600 500" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="aboutGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#6366F1"/>
                            <stop offset="100%" stop-color="#10B981"/>
                        </linearGradient>
                    </defs>
                    <!-- Background circles -->
                    <circle cx="120" cy="100" r="80" fill="url(#aboutGrad1)" opacity="0.12"/>
                    <circle cx="480" cy="380" r="100" fill="url(#aboutGrad1)" opacity="0.10"/>
                    <circle cx="420" cy="80" r="50" fill="url(#aboutGrad1)" opacity="0.14"/>

                    <!-- People icons -->
                    <g transform="translate(100, 180)">
                        <circle cx="30" cy="30" r="20" fill="url(#aboutGrad1)" opacity="0.8"/>
                        <path d="M 10 70 Q 10 50 30 50 Q 50 50 50 70" fill="url(#aboutGrad1)" opacity="0.6"/>
                    </g>
                    <g transform="translate(250, 150)">
                        <circle cx="30" cy="30" r="20" fill="url(#aboutGrad1)" opacity="0.7"/>
                        <path d="M 10 70 Q 10 50 30 50 Q 50 50 50 70" fill="url(#aboutGrad1)" opacity="0.5"/>
                    </g>
                    <g transform="translate(170, 260)">
                        <circle cx="30" cy="30" r="20" fill="url(#aboutGrad1)" opacity="0.9"/>
                        <path d="M 10 70 Q 10 50 30 50 Q 50 50 50 70" fill="url(#aboutGrad1)" opacity="0.7"/>
                    </g>

                    <!-- Connection lines -->
                    <line x1="130" y1="210" x2="280" y2="180" stroke="url(#aboutGrad1)" stroke-width="2" opacity="0.4"/>
                    <line x1="200" y1="290" x2="280" y2="180" stroke="url(#aboutGrad1)" stroke-width="2" opacity="0.4"/>
                    <line x1="130" y1="210" x2="200" y2="290" stroke="url(#aboutGrad1)" stroke-width="2" opacity="0.4"/>

                    <!-- Globe -->
                    <g transform="translate(350, 200)">
                        <circle cx="80" cy="80" r="60" fill="url(#aboutGrad1)" opacity="0.15" stroke="url(#aboutGrad1)" stroke-width="3"/>
                        <ellipse cx="80" cy="80" rx="60" ry="30" fill="none" stroke="url(#aboutGrad1)" stroke-width="2" opacity="0.5"/>
                        <line x1="80" y1="20" x2="80" y2="140" stroke="url(#aboutGrad1)" stroke-width="2" opacity="0.5"/>
                    </g>

                    <!-- Heart / Passion -->
                    <g transform="translate(480, 260)">
                        <path d="M 30 60 C 30 40, 0 20, 0 10 C 0 0, 10 0, 15 5 L 30 20 L 45 5 C 50 0, 60 0, 60 10 C 60 20, 30 40, 30 60 Z" fill="url(#aboutGrad1)" opacity="0.25"/>
                    </g>
                </svg>
            </div>
        </div>
    </header>

    <!-- Mission & Vision -->
    <section class="section section-mv" id="mission-vision">
        <div class="container">
            <div class="section-heading reveal">
                <span class="eyebrow">Misi & Visi</span>
                <h2>Kami Ada untuk Mengubah Cara Indonesia Belajar Coding</h2>
                <p>Dari misi yang kuat hingga visi yang jelas, Prozone dibangun dengan prinsip yang memberdayakan.</p>
            </div>

            <div class="grid grid-2" style="--grid-gap: var(--space-6);">
                <div class="card card-elevated card-hover-lift mv-card reveal">
                    <div class="mv-icon-wrapper">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"></circle>
                            <circle cx="12" cy="12" r="6"></circle>
                            <circle cx="12" cy="12" r="2"></circle>
                        </svg>
                    </div>
                    <h2 class="mv-title">Misi Kami</h2>
                    <p class="mv-description">
                        Mendemokratisasi pendidikan coding berkualitas tinggi di Indonesia melalui
                        platform yang interaktif, terstruktur, dan berbasis gamifikasi. Kami percaya
                        setiap orang Indonesia berhak mendapatkan akses ke keterampilan digital
                        yang membuka peluang karier global.
                    </p>
                    <ul class="mv-list">
                        <li>
                            <span class="mv-check" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span>Pembelajaran interaktif dengan code editor di browser</span>
                        </li>
                        <li>
                            <span class="mv-check" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span>Kurikulum terstruktur dari dasar hingga mahir</span>
                        </li>
                        <li>
                            <span class="mv-check" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span>Sistem gamifikasi yang memotivasi belajar konsisten</span>
                        </li>
                        <li>
                            <span class="mv-check" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span>Komunitas suportif untuk belajar bersama</span>
                        </li>
                    </ul>
                </div>

                <div class="card card-elevated card-hover-lift mv-card reveal">
                    <div class="mv-icon-wrapper mv-icon-accent">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </div>
                    <h2 class="mv-title">Visi Kami</h2>
                    <p class="mv-description">
                        Menjadi platform pembelajaran coding #1 di Indonesia yang menghasilkan
                        developer berkualitas dunia pada tahun 2030. Kami membayangkan ekosistem
                        di mana setiap siswa dapat belajar, berlatih, dan berkarya dengan tools
                        profesional yang selama ini hanya tersedia di bootcamp premium.
                    </p>
                    <ul class="mv-list">
                        <li>
                            <span class="mv-check mv-check-accent" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span>1 juta developer Indonesia aktif di platform</span>
                        </li>
                        <li>
                            <span class="mv-check mv-check-accent" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span>1000+ kurikulum teknologi yang selalu up-to-date</span>
                        </li>
                        <li>
                            <span class="mv-check mv-check-accent" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span>Jaringan employer yang siap menerima talenta lokal</span>
                        </li>
                        <li>
                            <span class="mv-check mv-check-accent" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                            <span>Kontribusi nyata untuk ekonomi digital nasional</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="section section-stats">
        <div class="container">
            <div class="section-heading reveal">
                <span class="eyebrow">Statistik</span>
                <h2>Prozone dalam Angka</h2>
                <p>Komitmen kami terlihat dari hasil nyata</p>
            </div>
            <div class="grid grid-auto-sm stats-grid">
                <div class="card stat-block reveal">
                    <div class="stat-value">15K+</div>
                    <div class="stat-label">Pengguna Aktif</div>
                </div>
                <div class="card stat-block reveal">
                    <div class="stat-value">500+</div>
                    <div class="stat-label">Lessons</div>
                </div>
                <div class="card stat-block reveal">
                    <div class="stat-value">2M+</div>
                    <div class="stat-label">Kode Dieksekusi</div>
                </div>
                <div class="card stat-block reveal">
                    <div class="stat-value">3.5K+</div>
                    <div class="stat-label">Sertifikat</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values -->
    <section class="section section-features" id="values">
        <div class="container">
            <div class="section-heading reveal">
                <span class="eyebrow">Nilai Kami</span>
                <h2>Nilai yang Kami Pegang</h2>
                <p>Prinsip yang membentuk setiap aspek Prozone</p>
            </div>

            <div class="grid grid-auto features-grid">
                <article class="card card-elevated card-hover-lift feature-card reveal">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 2L2 7l10 5 10-5-10-5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 17l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Aksesibilitas</h3>
                    <p>Platform dapat diakses kapan saja, di mana saja, tanpa biaya mahal. Kami percaya pendidikan berkualitas adalah hak, bukan privilege.</p>
                </article>

                <article class="card card-elevated card-hover-lift feature-card reveal">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 3c0 1 1 3 3 3s3 2 3 3-1 3-3 3-3-2-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 21c0-1 1-3 3-3s3 2 3 3-1 3-3 3-3-2-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Kualitas</h3>
                    <p>Kurikulum dirancang oleh praktisi industri, konten selalu di-update, dan tools yang digunakan relevan dengan standar profesional.</p>
                </article>

                <article class="card card-elevated card-hover-lift feature-card reveal">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>Komunitas</h3>
                    <p>Belajar lebih efektif bersama. Forum diskusi, clan, dan sistem friends membantu Anda tumbuh bersama ribuan learner lainnya.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- Story / Timeline -->
    <section class="section section-how" id="timeline">
        <div class="container">
            <div class="section-heading reveal">
                <span class="eyebrow">Perjalanan</span>
                <h2>Dari Ide Kecil Menjadi Platform yang Memberdayakan</h2>
                <p>Setiap langkah membawa kami lebih dekat ke visi 2030</p>
            </div>

            <div class="grid grid-auto how-grid">
                <div class="card how-step reveal">
                    <div class="how-num">01</div>
                    <div class="how-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 2L2 7l10 5 10-5-10-5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>2024 — Awal Mula</h3>
                    <p>Prozone lahir dari keprihatinan terhadap kurangnya platform belajar coding yang menarik dan terstruktur untuk pelajar Indonesia.</p>
                </div>

                <div class="card how-step reveal">
                    <div class="how-num">02</div>
                    <div class="how-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>2025 — Beta Launch</h3>
                    <p>Peluncuran beta dengan 100+ lessons dan 1000+ pengguna pertama. Sistem gamifikasi diperkenalkan untuk meningkatkan motivasi belajar.</p>
                </div>

                <div class="card how-step reveal">
                    <div class="how-num">03</div>
                    <div class="how-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3>2026 — Ekspansi Fitur</h3>
                    <p>Menambahkan clan, leaderboard, achievements, dan sistem sertifikat. Code editor multi-bahasa dan lesson interaktif resmi dirilis.</p>
                </div>

                <div class="card how-step reveal">
                    <div class="how-num">04</div>
                    <div class="how-icon">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                            <path d="M2 12H22M12 2C14.5013 4.73835 15.9228 8.29203 16 12C15.9228 15.708 14.5013 19.2616 12 22C9.49872 19.2616 8.07725 15.708 8 12C8.07725 8.29203 9.49872 4.73835 12 2Z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3>Visi 2030</h3>
                    <p>Target 1 juta developer Indonesia aktif, dengan kurikulum yang selalu relevan dan jaringan employer yang siap menerima talenta lokal.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="cta-card reveal">
            <h2>Siap Memulai Perjalanan Coding Anda?</h2>
            <p>Bergabunglah dengan ribuan developer yang sudah memulai belajar coding dengan cara yang menyenangkan. Dapatkan XP, kumpulkan achievement, dan bersaing di leaderboard.</p>
            <div class="row">
                <?php if ($is_logged_in): ?>
                <a href="dashboard.php" class="btn btn-xl btn-cta-primary">Buka Dashboard</a>
                <a href="courses-public.php" class="btn btn-xl btn-cta-secondary">Jelajahi Kursus</a>
                <?php else: ?>
                <a href="register.php" class="btn btn-xl btn-cta-primary">Daftar Gratis</a>
                <a href="login.php" class="btn btn-xl btn-cta-secondary">Sudah Punya Akun</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer — same as index.php -->
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
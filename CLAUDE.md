# CLAUDE.md

File ini memberikan panduan untuk Claude Code (claude.ai/code) saat bekerja dengan kode di repositori ini.

## Apa Ini

**Prozone** adalah platform pembelajaran coding interaktif berbasis PHP (web app single-codebase) dengan fitur gamifikasi: courses, lessons dengan eksekusi kode di browser, XP/level, achievements, clans, leaderboards, friends, chat, shop, dan certificates. UI berbahasa Indonesia (`lang="id"`), dibuat untuk berjalan di Laragon (Windows/XAMPP-style) dan bisa di-deploy ke shared hosting cPanel.

## Tech Stack

- **Backend:** PHP 7.4+ (vanilla, MVC-ish), MySQL/MariaDB via PDO
- **Frontend:** Server-rendered HTML + vanilla JS + modular CSS (no React/Vue, no build step, no npm)
- **Auth:** Session-based (`$_SESSION`), CSRF tokens (`$_SESSION['csrf_token']`), `password_hash`/`password_verify`
- **Email:** PHPMailer (composer)
- **Server:** Apache dengan `mod_rewrite` (lihat `.htaccess`); semua route yang tidak dikenal fall through ke `index.php`

## Menjalankan Project

Ini adalah project Laragon-hosted. Tidak ada build, test runner, atau linter.

**Local dev (Laragon):**
1. Start Laragon (Apache + MySQL auto-start)
2. Kunjungi `http://localhost/ProzoneWeb/`
3. Database `prozone` dibuat otomatis via `install.php`; schema SQL ada di `database/*.sql` (apply berurutan: `schema.sql` → `complete_database.sql` → `update_schema_v*.sql`)

**Kredensial database** hardcoded di `config/database.php` (root / tanpa password, `localhost`, db `prozone`). Update sebelum deploy.

**Deploy:** Lihat `DEPLOY_GUIDE.md` untuk workflow cPanel. Dua file yang selalu perlu di-update: `config/config.php` (BASE_URL, EMAIL settings) dan `config/database.php` (kredensial hosting).

**Composer:** Hanya PHPMailer yang di-pull. Jalankan `composer install` jika `vendor/` belum ada.

## Arsitektur

### Request Lifecycle
1. `.htaccess` mengirim semua request yang tidak cocok ke `index.php` (kecuali `api/`, `assets/`, `config/`, `database/`, `models/`)
2. Halaman PHP di root (mis. `dashboard.php`, `course.php`) biasanya handle routing+view sekaligus — setiap file adalah self-contained page yang include `config/config.php` lalu render HTML
3. AJAX/JSON calls masuk ke `api/*.php` — file-file ini independen, return JSON, dan handle their own auth/CSRF

### Struktur Direktori
- `config/` — Koneksi database, env config (BASE_URL, SMTP), language system, helper functions (`isLoggedIn`, `requireLogin`, `requireRole`, `sanitizeInput`, `formatCurrency`, CSRF helpers, XP/level calculation)
- `models/` — Class per entity (User, Course, Lesson, Clan, Chat, Comment, Friend, Shop, Notification, dll). Loaded via `spl_autoload_register` di `config/config.php`
- `classes/` — Class non-model (saat ini hanya `EmailService.php`)
- `includes/` — Partial yang dapat digunakan ulang (navbar, footer, sidebar, favicon, SEO, social share, widget components, WYSIWYG editor, toast, loading spinner, search, discussion, language-icons)
- `api/` — JSON endpoints (lihat daftar di bawah)
- `database/` — Schema SQL + migration files (perubahan incremental, apply secara kumulatif)
- `assets/css/` — Foundation (`tokens.css`, `base.css`, `light.css`, `dark.css`, `animations.css`) + `components/` (button, card, form, badge, avatar, progress, modal, layout, table, alert, tabs, accordion, tooltip, dropdown, breadcrumb, pagination, toast) + specialized (code-editor, chat, notification, leaderboard, achievement, clan, certificate)
- `assets/js/` — Vanilla JS (navbar, mobile-menu, notifications, search-enhancements)
- `cron/`, `hosting/`, `logs/`, `vendor/`

### Halaman dengan Peran Spesifik
- `login.php`, `register.php`, `forgot-password.php`, `reset-password.php`, `verify-email.php` — Auth flow (hanya butuh koneksi DB + PHPMailer; biasanya tidak require login)
- `dashboard.php` — Halaman utama student setelah login (paling kompleks, ~29KB)
- `lesson.php` — Editor kode interaktif (terbesar di codebase, ~294KB; mengandung banyak inline HTML/JS untuk Monaco-style editor + multi-language support)
- `manage-*.php`, `admin_*.php`, `categories.php`, `users.php` — Admin pages (gate via `requireRole(['admin'])`)
- `clan.php`, `leaderboard.php`, `achievements.php`, `friends.php`, `shop.php`, `certificates.php` — Social/gamification pages
- `index.php` — Landing/marketing page publik (~64KB)
- `404.php`, `unauthorized.php` — Error pages

### API Endpoints (`api/*.php`)
- `run-code.php` — Eksekusi kode via subprocess (PHP via CLI; bahasa lain mungkin via juri eksternal — periksa file untuk protokol eksekusi spesifik bahasa)
- `validate-code.php` — Validasi jawaban untuk lesson challenges
- `save-progress.php`, `complete-lesson.php`, `get-course-progress.php` — Lesson progress tracking
- `get-lesson-data.php` — Lazy-load lesson content
- `comments.php` — Lesson/discussion comments
- `send-chat-message.php`, `get-chat-messages.php` — Real-time chat (polling-based, bukan WebSocket)
- `friends.php` — Friend request/accept/list
- `notifications.php` — Notification list/mark-read
- `shop.php`, `topup.php` — Pembelian item (integrasi currency/XP)
- `check-achievements.php` — Trigger achievement unlock
- `test-api.php` — Endpoint testing (jangan dipanggil di production)

## Design System

Prozone punya **CSS design system lengkap dengan token-based theming** (lihat `assets/css/`). Sebelum menambahkan inline style atau class baru, periksa apakah class yang ada sudah mencakup kasus penggunaan.

- **Selalu** import foundation (`tokens.css` + `base.css` + theme) sebelum component CSS
- **Selalu** set `data-theme="dark"` atau `data-theme="light"` di `<html>` (default: dark, lihat `config/config.php`)
- **Token referensi:** `var(--brand)`, `var(--accent)`, `var(--bg-*)`, `var(--text-*)`, `var(--space-*)`, `var(--radius-*)`, `var(--text-*)` — lihat `tokens.css` untuk daftar lengkap
- **Component classes** untuk buttons (`.btn`, `.btn-primary`, `.btn-outline`, `.btn-sm`, `.btn-icon`), cards (`.card`, `.card-elevated`, `.card-interactive`), forms (`.form-input`, `.form-select`, `.form-checkbox`, `.form-switch`), badges (`.badge`, `.badge-primary`), avatars (`.avatar`, `.avatar-md`), progress (`.progress`, `.progress-bar`), alerts (`.alert`, `.alert-success`), modal (`.modal`, `.modal-overlay`), dan lain-lain
- **Jangan** tambahkan inline `style="color: ..."` atau hardcoded hex value — gunakan token atau component class
- Test page untuk verifikasi visual: `test-components.php` (load semua component CSS, berguna untuk regression check)

## Catatan Penting

- **Tidak ada framework PHP, tidak ada ORM** — query ditulis langsung di models sebagai PDO prepared statements
- **Tidak ada test suite** — verifikasi via browser atau `test-components.php` (visual regression) + `api/test-api.php` (endpoint smoke test)
- **Tidak ada build pipeline** — edit file langsung, refresh browser
- **Bahasa Indonesia di-hardcode** di banyak tempat (`Bahasa`, `Mata Pelajaran`, `Kelas`, `Poin`, `Papan Peringkat`, `Pencapaian`); English hanya untuk kode/identifier
- **In-browser code execution** ada di `api/run-code.php` — TELITI jika memodifikasi (security implications: subprocess call)
- **HTML special chars:** Selalu jalankan user input melalui `sanitizeInput()` sebelum echo ke HTML, atau `htmlspecialchars()` inline
- **CSRF:** Form yang melakukan state-changing POST harus include `<?= generateCsrfToken() ?>` di hidden field dan verify via `verifyCsrfToken($_POST['csrf_token'])` (lihat `config/config.php`)
- **Session tema & bahasa** dimuat dari DB saat login (`config/config.php` line 57-69) — user preference take precedence over default

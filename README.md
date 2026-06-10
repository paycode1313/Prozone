# Prozone Web

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Apache](https://img.shields.io/badge/Apache-mod_rewrite-D22128?logo=apache&logoColor=white)](https://httpd.apache.org/)

> Platform pembelajaran coding interaktif dengan fitur gamifikasi: courses, lessons dengan eksekusi kode di browser, XP/level, achievements, clans, leaderboards, friends, chat, shop, dan certificates.
>
> *Interactive coding learning platform with gamification: courses, in-browser code execution, XP/leveling, achievements, clans, leaderboards, friends, chat, shop, and certificates.*

🇮🇩 Bahasa Indonesia (default) · 🇬🇧 English (code & identifiers)

---

## ✨ Features / Fitur

| Feature                    | Description (EN)                                                    | Deskripsi (ID)                                                  |
| -------------------------- | ------------------------------------------------------------------- | --------------------------------------------------------------- |
| 📚 **Courses & Lessons**   | Theory + practice lessons with starter code & solutions             | Lesson teori + praktik dengan kode contoh & solusi             |
| ▶️ **In-browser Execution** | Run code (Python, JavaScript, PHP, etc.) directly in the browser    | Eksekusi kode (Python, JS, PHP, dll) langsung di browser        |
| 🎮 **Gamification**         | XP, levels, achievements, streaks                                   | XP, level, achievement, streak harian                          |
| 👥 **Clans**               | Community groups with chat, announcements, leaderboard              | Grup komunitas dengan chat, pengumuman, leaderboard             |
| 🏆 **Leaderboards**         | Solo & clan rankings                                                | Peringkat solo & clan                                          |
| 🤝 **Friends & DM**         | Friend requests + private messaging                                 | Permintaan pertemanan + pesan privat                           |
| 💬 **Real-time Chat**       | Clan chat (polling-based, no WebSocket required)                    | Chat clan (polling, tanpa WebSocket)                           |
| 🛒 **Shop**                | Buy titles, avatar frames, themes with in-game coins                | Beli gelar, frame avatar, tema dengan coin                     |
| 📜 **Certificates**         | Auto-generated certificates for completed courses                   | Sertifikat otomatis untuk course yang selesai                  |
| 🌐 **i18n + Theme**         | Indonesian / English, Light / Dark mode                             | Bahasa Indonesia / Inggris, mode Terang / Gelap                |
| 🔒 **Security**             | Session auth, CSRF tokens, `password_hash`/`password_verify`        | Session auth, CSRF token, hash password                        |

---

## 🛠️ Tech Stack

- **Backend:** PHP 7.4+ (vanilla, MVC-ish) + MySQL/MariaDB via PDO
- **Frontend:** Server-rendered HTML + vanilla JS + modular CSS (no React/Vue, no build step, no npm)
- **Auth:** Session-based (`$_SESSION`), CSRF tokens, `password_hash`/`password_verify`
- **Email:** PHPMailer (via Composer)
- **Server:** Apache with `mod_rewrite` (see `.htaccess`)

---

## 🚀 Quick Start (Local — Laragon / XAMPP)

### 1. Requirements / Persyaratan
- PHP **7.4+** (8.x recommended)
- MySQL **5.7+** atau MariaDB
- Apache with `mod_rewrite`
- Composer (optional, only for PHPMailer)

### 2. Clone & Install / Clone & Pasang

```bash
git clone https://github.com/<your-username>/ProzoneWeb.git
cd ProzoneWeb
composer install
```

### 3. Create Database / Buat Database

Import the single-file schema (recommended):
```bash
mysql -u root -p < database/prozone_complete.sql
```

Or use phpMyAdmin → Import → pilih `database/prozone_complete.sql`.

### 4. Configure / Konfigurasi

Copy the example config files and edit with your credentials:
```bash
cp config/database.example.php config/database.php
cp config/config.example.php config/config.php
```

Edit `config/database.php`:
```php
private $host     = 'localhost';
private $db_name  = 'prozone';
private $username = 'root';
private $password = '';
```

Edit `config/config.php`:
```php
define('BASE_URL', 'http://localhost/ProzoneWeb/');
```

### 5. Run / Jalankan

Buka browser: `http://localhost/ProzoneWeb/`

### 6. Default Login / Login Default

| Username       | Password   | Role        |
| -------------- | ---------- | ----------- |
| `admin`        | `password` | admin       |
| `instructor1`  | `password` | instructor  |
| `student1`     | `password` | student     |

> ⚠️ **Change these passwords immediately in production!**
> ⚠️ **Ganti password default segera di production!**

---

## 📂 Project Structure / Struktur Proyek

```
ProzoneWeb/
├── api/                  # JSON API endpoints
├── assets/
│   ├── css/              # Design system (tokens, components)
│   └── js/               # Vanilla JS (navbar, notifications, etc.)
├── classes/              # Non-model classes (EmailService)
├── config/               # Database, app config, helpers
│   ├── database.example.php   # 👈 Template (copy to database.php)
│   ├── config.example.php     # 👈 Template (copy to config.php)
│   └── language.php
├── cron/                 # Scheduled tasks
├── database/
│   └── prozone_complete.sql   # Single-file database setup
├── includes/             # Reusable partials (navbar, footer, etc.)
├── logs/                 # Application logs (gitignored)
├── models/               # Entity classes (User, Course, etc.)
├── index.php             # Landing page
├── dashboard.php         # Student dashboard
├── lesson.php            # In-browser code editor
├── install.php           # Auto-installer
└── .htaccess             # Apache rewrite rules
```

---

## 🌐 Deployment / Deployment

For shared hosting (cPanel) deployment, see [`DEPLOY_GUIDE.md`](DEPLOY_GUIDE.md).

Two files always need updating:
- `config/config.php` → `BASE_URL`, SMTP settings
- `config/database.php` → hosting DB credentials

---

## 🧪 Testing / Pengujian

Tidak ada test suite otomatis. Verifikasi via:
- **Visual:** `test-components.php` (component regression)
- **API smoke test:** `api/test-api.php` (jangan dipanggil di production)
- **Browser:** golden path manual test (register → enroll → code → submit)

---

## 🛡️ Security Notes / Catatan Keamanan

- ✅ **Always** sanitize user input via `sanitizeInput()` or `htmlspecialchars()` before echoing to HTML
- ✅ All state-changing POST forms must include `<?= generateCsrfToken() ?>` in hidden field
- ✅ Verify CSRF via `verifyCsrfToken($_POST['csrf_token'])`
- ⚠️ `api/run-code.php` performs **subprocess calls** — review carefully before modifying
- ⚠️ Never commit `config/database.php` or `config/config.php` to version control

---

## 🤝 Contributing / Kontribusi

1. Fork the repo
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License / Lisensi

Distributed under the **MIT License**. See [`LICENSE`](LICENSE) for the full text.

---

## 👥 Credits / Kredit

Built with ❤️ by Prozone contributors.
PHPMailer by the [PHPMailer](https://github.com/PHPMailer/PHPMailer) team.

---

<p align="center">
  Made with ☕ in Indonesia 🇮🇩<br>
  Dibuat dengan ☕ di Indonesia 🇮🇩
</p>

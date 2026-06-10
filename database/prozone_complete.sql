-- =====================================================
-- PROZONE - COMPLETE DATABASE SETUP (SINGLE FILE)
-- =====================================================
-- File: database/prozone_complete.sql
-- Deskripsi: Satu file SQL lengkap untuk setup database
--            Prozone Platform dari nol sampai siap pakai.
--
-- Dibuat dengan menggabungkan:
--   - schema.sql            (tabel utama + seed awal)
--   - update_schema_v2.sql  (coins + shop items)
--   - update_schema_v3.sql  (coins column)
--   - update_schema_v4.sql  (password reset & email verification)
--   - update_schema_v5.sql  (expected_output untuk lesson)
--   - clan_features.sql     (clan announcements & activity log)
--   - friends_system.sql    (friends, private messages, online status)
--   - update_emoji_support.sql (utf8mb4 untuk emoji)
--   - new_courses.sql       (Python, JavaScript, PHP, Java, C++)
--   - course_content.sql    (5 courses x 15 lessons)
--   - add_lessons.sql       (C++ intermediate lessons)
--   - add_intermediate_lessons.sql (JS102, PHP102, PY102, JAVA102)
--   - php101_lessons.sql    (PHP101 lessons)
--
-- Cara pakai:
--   1. Buat database kosong bernama `prozone` (opsional, di-handle di sini)
--   2. Import file ini lewat phpMyAdmin / HeidiSQL / CLI:
--        mysql -u root -p < prozone_complete.sql
--   3. Update `config/database.php` dengan kredensial MySQL Anda
--
-- Default Login (password: "password"):
--   - admin        (role: admin)
--   - instructor1  (role: instructor)
--   - student1     (role: student)
--   - system       (role: admin, untuk seeding clan default)
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- =====================================================
-- 1. CREATE DATABASE
-- =====================================================
CREATE DATABASE IF NOT EXISTS `prozone`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;
USE `prozone`;

-- =====================================================
-- 2. CORE TABLES
-- =====================================================

-- ---------- USERS ----------
DROP TABLE IF EXISTS `user_achievements`;
DROP TABLE IF EXISTS `user_items`;
DROP TABLE IF EXISTS `private_messages`;
DROP TABLE IF EXISTS `friends`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `certificates`;
DROP TABLE IF EXISTS `user_progress`;
DROP TABLE IF EXISTS `enrollments`;
DROP TABLE IF EXISTS `clan_activity_log`;
DROP TABLE IF EXISTS `clan_announcements`;
DROP TABLE IF EXISTS `clan_members`;
DROP TABLE IF EXISTS `clan_chat_messages`;
DROP TABLE IF EXISTS `chat_messages`;
DROP TABLE IF EXISTS `leaderboard_solo`;
DROP TABLE IF EXISTS `leaderboard_clan`;
DROP TABLE IF EXISTS `clans`;
DROP TABLE IF EXISTS `lessons`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `course_categories`;
DROP TABLE IF EXISTS `achievements`;
DROP TABLE IF EXISTS `shop_items`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `pengaturan`;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password` VARCHAR(255) NULL,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NULL,
    `role` ENUM('admin', 'instructor', 'student') NOT NULL DEFAULT 'student',
    `avatar` VARCHAR(255) NULL,
    `bio` TEXT,
    `total_xp` INT DEFAULT 0,
    `level` INT DEFAULT 1,
    `coins` INT DEFAULT 0,
    `language_preference` ENUM('id', 'en') DEFAULT 'id',
    `theme_preference` ENUM('light', 'dark') DEFAULT 'dark',
    `is_online` TINYINT(1) DEFAULT 0,
    `last_seen` DATETIME DEFAULT NULL,
    `reset_token` VARCHAR(255) NULL,
    `reset_token_expiry` DATETIME NULL,
    `email_verification_token` VARCHAR(255) NULL,
    `email_verification_expires` DATETIME NULL,
    `email_verified_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_online_status` (`is_online`, `last_seen`),
    INDEX `idx_reset_token` (`reset_token`),
    INDEX `idx_email_verification_token` (`email_verification_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- COURSE CATEGORIES ----------
CREATE TABLE `course_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kategori` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) UNIQUE NOT NULL,
    `deskripsi` TEXT,
    `icon` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- COURSES ----------
CREATE TABLE `courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_course` VARCHAR(20) UNIQUE NOT NULL,
    `judul_course` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) UNIQUE NOT NULL,
    `kategori_id` INT,
    `instructor_id` INT,
    `deskripsi` TEXT,
    `thumbnail` VARCHAR(255) NULL,
    `level` ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    `durasi_jam` INT DEFAULT 0,
    `harga` DECIMAL(10,2) DEFAULT 0.00,
    `is_free` BOOLEAN DEFAULT TRUE,
    `is_published` BOOLEAN DEFAULT FALSE,
    `total_lessons` INT DEFAULT 0,
    `total_students` INT DEFAULT 0,
    `rating` DECIMAL(3,2) DEFAULT 0.00,
    `xp_reward` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`kategori_id`) REFERENCES `course_categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- LESSONS ----------
CREATE TABLE `lessons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `judul_lesson` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) NOT NULL,
    `urutan` INT NOT NULL,
    `konten` TEXT,
    `kode_contoh` TEXT,
    `kode_solusi` TEXT,
    `expected_output` TEXT DEFAULT NULL,
    `hints` TEXT,
    `instruksi` TEXT,
    `tipe` ENUM('theory', 'practice', 'quiz') DEFAULT 'theory',
    `durasi_menit` INT DEFAULT 0,
    `is_free` BOOLEAN DEFAULT TRUE,
    `xp_reward` INT DEFAULT 10,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_course_lesson` (`course_id`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ENROLLMENTS ----------
CREATE TABLE `enrollments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `progress_percent` DECIMAL(5,2) DEFAULT 0.00,
    `completed_lessons` INT DEFAULT 0,
    `status` ENUM('enrolled', 'completed', 'dropped') DEFAULT 'enrolled',
    `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_enrollment` (`user_id`, `course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- USER PROGRESS ----------
CREATE TABLE `user_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `lesson_id` INT NOT NULL,
    `status` ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    `kode_user` TEXT,
    `skor` INT DEFAULT 0,
    `waktu_pengerjaan` INT DEFAULT 0,
    `xp_earned` INT DEFAULT 0,
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_progress` (`user_id`, `lesson_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- COMMENTS ----------
CREATE TABLE `comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lesson_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `parent_id` INT DEFAULT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- NOTIFICATIONS ----------
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- CLANS ----------
CREATE TABLE `clans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_clan` VARCHAR(100) UNIQUE NOT NULL,
    `slug` VARCHAR(100) UNIQUE NOT NULL,
    `deskripsi` TEXT,
    `avatar` VARCHAR(255) NULL,
    `leader_id` INT NOT NULL,
    `total_members` INT DEFAULT 1,
    `total_xp` INT DEFAULT 0,
    `is_public` BOOLEAN DEFAULT TRUE,
    `max_members` INT DEFAULT 50,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`leader_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- CLAN MEMBERS ----------
CREATE TABLE `clan_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clan_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `role` ENUM('leader', 'co_leader', 'member') DEFAULT 'member',
    `xp_contribution` INT DEFAULT 0,
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`clan_id`) REFERENCES `clans`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_clan_member` (`clan_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- CLAN ANNOUNCEMENTS ----------
CREATE TABLE `clan_announcements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clan_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `content` TEXT NOT NULL,
    `is_pinned` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`clan_id`) REFERENCES `clans`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- CLAN ACTIVITY LOG ----------
CREATE TABLE `clan_activity_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clan_id` INT NOT NULL,
    `user_id` INT DEFAULT NULL,
    `action_type` ENUM('join', 'leave', 'kick', 'promote', 'demote', 'transfer_leader', 'create', 'update_settings', 'announcement') NOT NULL,
    `description` TEXT,
    `target_user_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`clan_id`) REFERENCES `clans`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`target_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- CHAT MESSAGES (Clan chat) ----------
CREATE TABLE `chat_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clan_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`clan_id`) REFERENCES `clans`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_clan_created` (`clan_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- LEADERBOARD (SOLO) ----------
CREATE TABLE `leaderboard_solo` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `total_xp` INT DEFAULT 0,
    `completed_courses` INT DEFAULT 0,
    `completed_lessons` INT DEFAULT 0,
    `rank` INT DEFAULT 0,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_leaderboard` (`user_id`),
    INDEX `idx_xp_rank` (`total_xp` DESC, `rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- LEADERBOARD (CLAN) ----------
CREATE TABLE `leaderboard_clan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clan_id` INT NOT NULL,
    `total_xp` INT DEFAULT 0,
    `total_members` INT DEFAULT 0,
    `average_xp` DECIMAL(10,2) DEFAULT 0.00,
    `rank` INT DEFAULT 0,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`clan_id`) REFERENCES `clans`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_clan_leaderboard` (`clan_id`),
    INDEX `idx_clan_xp_rank` (`total_xp` DESC, `rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ACHIEVEMENTS ----------
CREATE TABLE `achievements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_achievement` VARCHAR(50) UNIQUE NOT NULL,
    `nama_achievement` VARCHAR(200) NOT NULL,
    `deskripsi` TEXT,
    `icon` VARCHAR(50),
    `xp_reward` INT DEFAULT 0,
    `tipe` ENUM('course_complete', 'lesson_complete', 'streak', 'clan', 'special') NOT NULL,
    `requirement_value` INT DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- USER ACHIEVEMENTS ----------
CREATE TABLE `user_achievements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `achievement_id` INT NOT NULL,
    `earned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`achievement_id`) REFERENCES `achievements`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_achievement` (`user_id`, `achievement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- SHOP ITEMS ----------
CREATE TABLE `shop_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `cost` INT NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `value` VARCHAR(255) NOT NULL,
    `icon` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- USER ITEMS (Inventory) ----------
CREATE TABLE `user_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `is_equipped` TINYINT(1) DEFAULT 0,
    `purchased_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `shop_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- CERTIFICATES ----------
CREATE TABLE `certificates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `certificate_code` VARCHAR(50) UNIQUE NOT NULL,
    `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_certificate` (`user_id`, `course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- FRIENDS ----------
CREATE TABLE `friends` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `friend_id` INT NOT NULL,
    `status` ENUM('pending', 'accepted', 'blocked') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`friend_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_friendship` (`user_id`, `friend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- PRIVATE MESSAGES ----------
CREATE TABLE `private_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT NOT NULL,
    `receiver_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_conversation` (`sender_id`, `receiver_id`),
    INDEX `idx_unread` (`receiver_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- PENGATURAN ----------
CREATE TABLE `pengaturan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kunci` VARCHAR(100) UNIQUE NOT NULL,
    `nilai` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. CLAN ACTIVITY INDEXES
-- =====================================================
CREATE INDEX `idx_clan_activity_clan_id` ON `clan_activity_log`(`clan_id`);
CREATE INDEX `idx_clan_activity_created` ON `clan_activity_log`(`created_at`);
CREATE INDEX `idx_clan_announcements_clan_id` ON `clan_announcements`(`clan_id`);

-- =====================================================
-- 4. SEED DATA - USERS
-- =====================================================
-- Password default "password" (bcrypt hash yang valid)
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `email`, `role`, `total_xp`, `level`) VALUES
('admin',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@prozone.com',      'admin',      1000, 10),
('instructor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Instructor Pro','instructor@prozone.com', 'instructor',  500,  5),
('student1',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student Demo',  'student@prozone.com',    'student',     250,  3),
('system',      '$2y$10$abcdefghijklmnopqrstuuabcdefghijklmnopqrstuu',           'System Admin',  'system@prozone.com',     'admin',         0,  1);

-- =====================================================
-- 5. SEED DATA - COURSE CATEGORIES
-- =====================================================
INSERT INTO `course_categories` (`nama_kategori`, `slug`, `deskripsi`, `icon`) VALUES
('HTML & CSS',           'html-css',  'Pelajari dasar-dasar HTML dan CSS untuk membuat website yang menarik', '🌐'),
('JavaScript',           'javascript','Pelajari JavaScript dari dasar hingga advanced untuk membuat website interaktif', '⚡'),
('PHP',                  'php',       'Pelajari PHP untuk membuat aplikasi web dinamis dan backend', '🐘'),
('Python',               'python',    'Pelajari Python untuk data science, web development, dan automation', '🐍'),
('Database',             'database',  'Pelajari MySQL dan database management untuk aplikasi web', '💾'),
('Framework',            'framework', 'Pelajari framework modern seperti Laravel, React, dan Vue.js', '⚙️'),
('Backend Development',  'backend',   'Server-side programming dan database', 'server'),
('Python Programming',   'python-pro','Belajar Python dari dasar hingga mahir', 'terminal'),
('PHP Development',      'php-dev',   'Web development dengan PHP', 'code'),
('Java Programming',     'java-pro',  'Object-oriented programming dengan Java', 'code'),
('C++ Programming',      'cpp-pro',   'Pemrograman sistem dengan C++', 'cpu');

-- =====================================================
-- 6. SEED DATA - COURSES
-- =====================================================
INSERT INTO `courses`
  (`kode_course`, `judul_course`, `slug`, `kategori_id`, `instructor_id`, `deskripsi`, `level`, `durasi_jam`, `is_free`, `is_published`, `total_lessons`, `xp_reward`)
VALUES
-- Course 1: HTML & CSS Fundamentals
('HTML001', 'HTML & CSS Fundamentals', 'html-css-fundamentals', 1, 2,
    'Pelajari dasar-dasar HTML dan CSS untuk membuat website yang menarik dan responsif. Cocok untuk pemula yang ingin menjadi Web Developer.',
    'beginner', 15, TRUE, TRUE, 15, 500),

-- Course 2: Python Programming
('PY001', 'Python Programming', 'python-programming', 4, 2,
    'Kuasai bahasa pemrograman paling populer di dunia. Dari syntax dasar hingga Object Oriented Programming.',
    'beginner', 20, TRUE, TRUE, 15, 600),

-- Course 3: PHP Web Development
('PHP001', 'PHP Web Development', 'php-web-development', 3, 2,
    'Belajar bahasa server-side paling populer untuk membuat website dinamis dan berinteraksi dengan database.',
    'beginner', 20, TRUE, TRUE, 15, 600),

-- Course 4: JavaScript Programming
('JS001', 'JavaScript Programming', 'javascript-programming', 2, 2,
    'Bahasa pemrograman wajib untuk Front-End Developer. Membuat website menjadi interaktif dan hidup.',
    'beginner', 20, TRUE, TRUE, 15, 600),

-- Course 5: Java Programming Basics
('JAVA001', 'Java Programming Basics', 'java-programming-basics', 10, 2,
    'Pelajari Java, bahasa pemrograman berorientasi objek yang kuat, aman, dan portabel.',
    'beginner', 20, TRUE, TRUE, 15, 600),

-- Course 6: Python untuk Pemula
('PY101', 'Python untuk Pemula', 'python-untuk-pemula', 8, 2,
    'Pelajari dasar-dasar Python programming dari variabel, tipe data, hingga function dan class.',
    'beginner', 10, TRUE, TRUE, 8, 200),

-- Course 7: Python Intermediate
('PY102', 'Python Intermediate', 'python-intermediate', 8, 2,
    'Tingkatkan skill Python dengan OOP, file handling, error handling, dan module.',
    'intermediate', 15, TRUE, TRUE, 8, 300),

-- Course 8: JavaScript Fundamentals
('JS101', 'JavaScript Fundamentals', 'javascript-fundamentals', 2, 2,
    'Kuasai dasar JavaScript: variabel, function, array, object, dan DOM manipulation.',
    'beginner', 12, TRUE, TRUE, 5, 250),

-- Course 9: JavaScript Modern (ES6+)
('JS102', 'JavaScript Modern (ES6+)', 'javascript-modern-es6', 2, 2,
    'Pelajari fitur modern JavaScript: arrow functions, destructuring, promises, async/await, dan modules.',
    'intermediate', 10, TRUE, TRUE, 10, 280),

-- Course 10: PHP untuk Web Development
('PHP101', 'PHP untuk Web Development', 'php-web-development-course', 9, 2,
    'Belajar PHP dari dasar: syntax, variabel, array, function, dan form handling.',
    'beginner', 12, TRUE, TRUE, 4, 250),

-- Course 11: PHP & MySQL Database
('PHP102', 'PHP & MySQL Database', 'php-mysql-database', 9, 2,
    'Kuasai koneksi database, CRUD operations, prepared statements, dan best practices keamanan.',
    'intermediate', 14, TRUE, TRUE, 8, 300),

-- Course 12: Java OOP
('JAVA102', 'Java Object-Oriented Programming', 'java-oop', 10, 2,
    'Deep dive ke OOP: classes, inheritance, polymorphism, interfaces, dan exception handling.',
    'intermediate', 18, TRUE, TRUE, 8, 350),

-- Course 13: Java Programming Basics (advanced)
('JAVA101', 'Java Programming Basics', 'java-programming-basics-advanced', 10, 2,
    'Mulai perjalanan programming dengan Java: variabel, control flow, methods, dan introduction to OOP.',
    'beginner', 15, TRUE, TRUE, 10, 280),

-- Course 14: C++ untuk Pemula
('CPP101', 'C++ untuk Pemula', 'cpp-untuk-pemula', 11, 2,
    'Pelajari dasar C++: syntax, variabel, pointers, dan memory management.',
    'beginner', 14, TRUE, TRUE, 10, 260);

-- =====================================================
-- 7. SEED DATA - LESSONS
-- =====================================================

-- ----- COURSE 1: HTML & CSS Fundamentals (15 lessons) -----
INSERT INTO `lessons` (`course_id`, `judul_lesson`, `slug`, `tipe`, `konten`, `urutan`, `durasi_menit`, `kode_contoh`, `kode_solusi`, `xp_reward`) VALUES
(1, 'Pengenalan HTML', 'pengenalan-html', 'theory',
 '<h2>Apa itu HTML?</h2><p>HTML (HyperText Markup Language) adalah tulang punggung dari setiap halaman web. Ia memberikan struktur pada konten Anda.</p><h3>Struktur Dasar</h3><pre>&lt;!DOCTYPE html&gt;\n&lt;html&gt;\n  &lt;body&gt;\n    &lt;h1&gt;Hello World&lt;/h1&gt;\n  &lt;/body&gt;\n&lt;/html&gt;</pre>', 1, 15, NULL, NULL, 10),

(1, 'Struktur Halaman Web', 'struktur-halaman-web', 'practice',
 'Lengkapi struktur dasar HTML berikut agar menjadi halaman yang valid.', 2, 20,
 '<!DOCTYPE html>\n<html>\n<head>\n  <title>My Page</title>\n</head>\n<!-- Tambahkan body di sini -->\n</html>',
 '<!DOCTYPE html>\n<html>\n<head>\n  <title>My Page</title>\n</head>\n<body>\n  <h1>Halaman Utama</h1>\n</body>\n</html>', 20),

(1, 'Heading dan Paragraf', 'heading-dan-paragraf', 'theory',
 '<h2>Heading</h2><p>HTML memiliki 6 level heading, dari <code>&lt;h1&gt;</code> (paling besar) sampai <code>&lt;h6&gt;</code>.</p><h2>Paragraf</h2><p>Gunakan tag <code>&lt;p&gt;</code> untuk membuat paragraf teks.</p>', 3, 15, NULL, NULL, 10),

(1, 'Membuat Artikel Sederhana', 'membuat-artikel-sederhana', 'practice',
 'Buatlah sebuah artikel dengan satu Judul Utama (h1), satu Sub-judul (h2), dan dua paragraf.', 4, 25,
 '<!-- Tulis kodemu di sini -->',
 '<h1>Berita Hari Ini</h1>\n<h2>Teknologi Baru</h2>\n<p>Paragraf pertama tentang teknologi.</p>\n<p>Paragraf kedua penjelasan lebih lanjut.</p>', 25),

(1, 'Lists (Daftar)', 'lists-daftar', 'theory',
 '<h2>Unordered List</h2><p>Gunakan <code>&lt;ul&gt;</code> untuk poin-poin (bullet).</p><h2>Ordered List</h2><p>Gunakan <code>&lt;ol&gt;</code> untuk daftar bernomor.</p>', 5, 15, NULL, NULL, 10),

(1, 'Membuat Resep Masakan', 'membuat-resep-masakan', 'practice',
 'Buat daftar bahan makanan menggunakan Unordered List, dan langkah memasak menggunakan Ordered List.', 6, 30,
 '<h3>Bahan:</h3>\n<!-- UL di sini -->\n<h3>Langkah:</h3>\n<!-- OL di sini -->',
 '<h3>Bahan:</h3>\n<ul><li>Tepung</li><li>Gula</li></ul>\n<h3>Langkah:</h3>\n<ol><li>Campur bahan</li><li>Goreng</li></ol>', 25),

(1, 'Hyperlinks dan Navigasi', 'hyperlinks-dan-navigasi', 'theory',
 '<h2>Tag Anchor</h2><p>Gunakan tag <code>&lt;a&gt;</code> untuk membuat link.</p><pre>&lt;a href="https://google.com"&gt;Ke Google&lt;/a&gt;</pre>', 7, 20, NULL, NULL, 10),

(1, 'Menambahkan Gambar', 'menambahkan-gambar', 'practice',
 'Tambahkan gambar ke halaman web menggunakan tag img. Jangan lupa atribut alt!', 8, 20,
 '<!-- Tambahkan gambar kucing.jpg di sini -->',
 '<img src="kucing.jpg" alt="Gambar Kucing Lucu">', 20),

(1, 'Pengenalan CSS', 'pengenalan-css', 'theory',
 '<h2>Apa itu CSS?</h2><p>CSS (Cascading Style Sheets) digunakan untuk menghias HTML.</p><h3>Syntax</h3><pre>selector { property: value; }</pre>', 9, 20, NULL, NULL, 10),

(1, 'Mewarnai Halaman', 'mewarnai-halaman', 'practice',
 'Ubah warna teks heading menjadi biru dan background halaman menjadi abu-abu muda (#f0f0f0).', 10, 25,
 '<style>/* Tulis CSS di sini */</style>\n<h1>Judul Biru</h1>',
 '<style>body { background-color: #f0f0f0; } h1 { color: blue; }</style>\n<h1>Judul Biru</h1>', 25),

(1, 'CSS Box Model', 'css-box-model', 'theory',
 '<h2>Box Model</h2><p>Setiap elemen HTML adalah kotak (Content, Padding, Border, Margin).</p>', 11, 25, NULL, NULL, 10),

(1, 'Mengatur Layout Card', 'mengatur-layout-card', 'practice',
 'Buat class .card dengan padding 20px, border 1px solid black, dan margin 10px.', 12, 30,
 '<style>.card { /* CSS di sini */ }</style>\n<div class="card">Konten</div>',
 '<style>.card { padding: 20px; border: 1px solid black; margin: 10px; }</style>\n<div class="card">Konten</div>', 25),

(1, 'Flexbox Layout', 'flexbox-layout', 'theory',
 '<h2>Flexbox</h2><p>Cara modern mengatur layout.</p><pre>.container { display: flex; justify-content: center; align-items: center; }</pre>', 13, 30, NULL, NULL, 10),

(1, 'Membuat Navbar', 'membuat-navbar', 'practice',
 'Buat navigasi horizontal menggunakan Flexbox. Berikan jarak antar menu.', 14, 35,
 '<style>.nav { /* Gunakan flex */ }</style>\n<div class="nav"><div>Home</div><div>About</div></div>',
 '<style>.nav { display: flex; gap: 20px; background: #333; color: white; padding: 10px; }</style>\n<div class="nav"><div>Home</div><div>About</div></div>', 25),

(1, 'Final Project: Landing Page', 'final-project-landing-page', 'practice',
 'Buat landing page sederhana yang menggabungkan Header, Hero Section (Gambar & Teks), dan Footer.', 15, 60, '<!-- Buat struktur lengkap -->', '<html><body><header>Brand</header><section><h1>Hero Title</h1><p>Subtitle.</p></section><footer>&copy; 2025</footer></body></html>', 50);

-- ----- COURSE 2: Python Programming (15 lessons) -----
INSERT INTO `lessons` (`course_id`, `judul_lesson`, `slug`, `tipe`, `konten`, `urutan`, `durasi_menit`, `kode_contoh`, `kode_solusi`, `xp_reward`) VALUES
(2, 'Kenapa Python?', 'kenapa-python', 'theory',
 '<h2>Python itu Mudah & Powerful</h2><p>Python memiliki syntax yang mirip bahasa Inggris, membuatnya mudah dibaca. Digunakan di Google, NASA, dan Netflix.</p><pre>print("Hello World")</pre>', 1, 15, NULL, NULL, 10),

(2, 'Variabel dan Tipe Data', 'variabel-dan-tipe-data', 'practice',
 'Buat variabel `nama` berisi string nama Anda, dan `umur` berisi integer umur Anda. Lalu print keduanya.', 2, 20,
 '# Tulis kodemu di sini',
 'nama = "Budi"\numur = 20\nprint(nama)\nprint(umur)', 20),

(2, 'Operasi Matematika', 'operasi-matematika', 'theory',
 '<h2>Operator Aritmatika</h2><ul><li>Tambah: <code>+</code></li><li>Kurang: <code>-</code></li><li>Kali: <code>*</code></li><li>Bagi: <code>/</code></li><li>Pangkat: <code>**</code></li><li>Modulus: <code>%</code></li></ul>', 3, 20, NULL, NULL, 10),

(2, 'Kalkulator Sederhana', 'kalkulator-sederhana', 'practice',
 'Hitung luas persegi panjang dengan panjang 10 dan lebar 5. Simpan di variabel `luas` dan print.', 4, 20,
 'panjang = 10\nlebar = 5\n# Hitung luas',
 'panjang = 10\nlebar = 5\nluas = panjang * lebar\nprint(luas)', 20),

(2, 'String Manipulation', 'string-manipulation', 'theory',
 '<h2>F-Strings</h2><pre>nama = "Andi"\nprint(f"Halo {nama}")</pre>', 5, 25, NULL, NULL, 10),

(2, 'Memperbaiki Format Teks', 'memperbaiki-format-teks', 'practice',
 'Diberikan variabel `teks = "  python keren  "`. Bersihkan spasi di awal/akhir dan ubah menjadi huruf besar semua.', 6, 25,
 'teks = "  python keren  "\n# Olah teks ini',
 'teks = "  python keren  "\nbersih = teks.strip().upper()\nprint(bersih)', 25),

(2, 'List dan Tuple', 'list-dan-tuple', 'theory',
 '<h2>List</h2><p>Kumpulan data yang bisa diubah. <code>data = [1, 2, 3]</code></p><h2>Tuple</h2><p>Kumpulan data yang TIDAK bisa diubah. <code>data = (1, 2, 3)</code></p>', 7, 25, NULL, NULL, 10),

(2, 'Mengelola Daftar Belanja', 'mengelola-daftar-belanja', 'practice',
 'Buat list `belanja`. Tambahkan "Susu" ke dalamnya. Print item pertama dari list.', 8, 30,
 'belanja = ["Roti", "Telur"]\n# Tambahkan Susu\n# Print item pertama',
 'belanja = ["Roti", "Telur"]\nbelanja.append("Susu")\nprint(belanja[0])', 25),

(2, 'Percabangan (If/Else)', 'percabangan-if-else', 'theory',
 '<h2>Logika Kondisional</h2><pre>nilai = 80\nif nilai >= 75:\n    print("Lulus")\nelse:\n    print("Remidi")</pre>', 9, 25, NULL, NULL, 10),

(2, 'Cek Ganjil Genap', 'cek-ganjil-genap', 'practice',
 'Buat logika untuk mengecek apakah variabel `angka` adalah ganjil atau genap.', 10, 30,
 'angka = 7\n# Tulis if else di sini',
 'angka = 7\nif angka % 2 == 0:\n    print("Genap")\nelse:\n    print("Ganjil")', 20),

(2, 'Perulangan (Loops)', 'perulangan-loops', 'theory',
 '<h2>For Loop</h2><p>Untuk iterasi list atau range.</p><pre>for i in range(5):\n    print(i)</pre>', 11, 30, NULL, NULL, 10),

(2, 'Mencetak Angka', 'mencetak-angka', 'practice',
 'Gunakan for loop untuk mencetak angka dari 1 sampai 10.', 12, 30,
 '# Tulis loop di sini',
 'for i in range(1, 11):\n    print(i)', 20),

(2, 'Functions', 'functions', 'theory',
 '<h2>Fungsi</h2><p>Blok kode yang bisa dipanggil berulang kali.</p><pre>def sapa(nama):\n    return f"Halo {nama}"</pre>', 13, 30, NULL, NULL, 10),

(2, 'Konversi Suhu', 'konversi-suhu', 'practice',
 'Buat fungsi `celcius_ke_fahrenheit(c)` yang mengembalikan nilai Fahrenheit. Rumus: (C * 9/5) + 32', 14, 40,
 'def celcius_ke_fahrenheit(c):\n    # Tulis rumus\n    pass',
 'def celcius_ke_fahrenheit(c):\n    return (c * 9/5) + 32', 30),

(2, 'Final Project: Tebak Angka', 'final-project-tebak-angka', 'practice',
 'Buat game sederhana di mana komputer memilih angka acak 1-10, dan user harus menebaknya (simulasi logika).', 15, 60,
 'import random\nangka_rahasia = random.randint(1, 10)\n# Logika tebakan',
 'import random\nangka_rahasia = random.randint(1, 10)\ntebakan = 5\nif tebakan == angka_rahasia:\n    print("Benar!")\nelse:\n    print("Salah")', 50);

-- ----- COURSE 3: PHP Web Development (15 lessons) -----
INSERT INTO `lessons` (`course_id`, `judul_lesson`, `slug`, `tipe`, `konten`, `urutan`, `durasi_menit`, `kode_contoh`, `kode_solusi`, `xp_reward`) VALUES
(3, 'Pengenalan PHP', 'pengenalan-php', 'theory',
 '<h2>PHP: Hypertext Preprocessor</h2><p>Bahasa script yang berjalan di server. Kode PHP disisipkan dalam HTML.</p><pre>&lt;?php echo "Hello World"; ?&gt;</pre>', 1, 15, NULL, NULL, 10),

(3, 'Echo dan Print', 'echo-dan-print', 'practice',
 'Gunakan `echo` untuk menampilkan teks "Belajar PHP itu Asik" ke layar.', 2, 15,
 '<?php // Tulis kodemu ?>',
 '<?php echo "Belajar PHP itu Asik"; ?>', 20),

(3, 'Variabel PHP', 'variabel-php', 'theory',
 '<h2>Aturan Variabel</h2><ul><li>Diawali tanda dollar <code>$</code></li><li>Case sensitive</li><li>Tidak boleh diawali angka</li></ul>', 3, 20, NULL, NULL, 10),

(3, 'Operasi String PHP', 'operasi-string-php', 'practice',
 'Gabungkan variabel `$depan = "Web"` dan `$belakang = "Dev"` menjadi "Web Dev" menggunakan titik (.).', 4, 20,
 '<?php\n$depan = "Web"; $belakang = "Dev";\n// Gabungkan\n?>',
 '<?php $depan = "Web"; $belakang = "Dev"; echo $depan . " " . $belakang; ?>', 20),

(3, 'Array Dasar', 'array-dasar', 'theory',
 '<h2>Indexed Array</h2><pre>$buah = ["Apel", "Jeruk"];</pre><h2>Associative Array</h2><pre>$user = ["nama" => "Andi", "umur" => 20];</pre>', 5, 25, NULL, NULL, 10),

(3, 'Akses Array', 'akses-array', 'practice',
 'Ambil dan tampilkan nilai "umur" dari array associative berikut: `$siswa = ["nama"=>"Siti", "umur"=>17];`', 6, 25,
 '<?php\n$siswa = ["nama"=>"Siti", "umur"=>17];\n// Tampilkan umur\n?>',
 '<?php $siswa = ["nama"=>"Siti", "umur"=>17]; echo $siswa["umur"]; ?>', 20),

(3, 'Struktur Kendali (If)', 'struktur-kendali-if', 'theory',
 '<h2>Kondisi</h2><pre>if ($nilai > 70) { echo "Lulus"; }</pre>', 7, 20, NULL, NULL, 10),

(3, 'Looping (Foreach)', 'looping-foreach', 'theory',
 '<h2>Foreach</h2><pre>foreach ($colors as $color) { echo $color; }</pre>', 8, 25, NULL, NULL, 10),

(3, 'Menampilkan List Buah', 'menampilkan-list-buah', 'practice',
 'Gunakan foreach untuk menampilkan semua item dalam array `$buah = ["Mangga", "Pisang", "Jambu"];`', 9, 30,
 '<?php $buah = ["Mangga", "Pisang", "Jambu"]; ?>',
 '<?php foreach($buah as $b) { echo $b . "<br>"; } ?>', 20),

(3, 'Function PHP', 'function-php-2', 'theory',
 '<h2>User Defined Function</h2><pre>function sapa($nama) { return "Halo $nama"; }</pre>', 10, 25, NULL, NULL, 10),

(3, 'Superglobals ($_GET)', 'superglobals-get', 'theory',
 '<h2>$_GET</h2><p>Mengambil data dari URL parameter.</p><pre>$id = $_GET["id"];</pre>', 11, 30, NULL, NULL, 10),

(3, 'Form Handling ($_POST)', 'form-handling-post', 'practice',
 'Simulasikan pengambilan data form. Ambil nilai "email" dari variabel superglobal $_POST dan tampilkan.', 12, 35,
 '<?php // Ambil $_POST["email"] ?>',
 '<?php $email = $_POST["email"]; echo "Email anda: " . $email; ?>', 20),

(3, 'Koneksi Database', 'koneksi-database', 'theory',
 '<h2>PDO (PHP Data Objects)</h2><pre>$pdo = new PDO("mysql:host=localhost;dbname=test", "root", "");</pre>', 13, 30, NULL, NULL, 10),

(3, 'Session Management', 'session-management', 'theory',
 '<h2>Session</h2><pre>session_start(); $_SESSION["user"] = "admin";</pre>', 14, 30, NULL, NULL, 10),

(3, 'Project: Login Checker', 'project-login-checker', 'practice',
 'Buat logika login sederhana. Jika username="admin" dan password="123", tampilkan "Login Sukses", jika tidak "Gagal".', 15, 60,
 '<?php $user = "admin"; $pass = "salah"; ?>',
 '<?php\nif($user == "admin" && $pass == "123") { echo "Login Sukses"; } else { echo "Gagal"; }\n?>', 30);

-- ----- COURSE 4: JavaScript Programming (15 lessons) -----
INSERT INTO `lessons` (`course_id`, `judul_lesson`, `slug`, `tipe`, `konten`, `urutan`, `durasi_menit`, `kode_contoh`, `kode_solusi`, `xp_reward`) VALUES
(4, 'Pengenalan JavaScript', 'pengenalan-javascript', 'theory',
 '<h2>JS di Browser</h2><p>JavaScript berjalan di sisi klien (browser). Bisa mengubah HTML dan CSS secara dinamis.</p><pre>&lt;script&gt; alert("Hello"); &lt;/script&gt;</pre>', 1, 15, NULL, NULL, 10),

(4, 'Variabel (let & const)', 'variabel-let-const', 'theory',
 '<h2>Modern JS (ES6)</h2><ul><li><code>let</code>: bisa berubah</li><li><code>const</code>: konstanta</li><li>Hindari <code>var</code></li></ul>', 2, 20, NULL, NULL, 10),

(4, 'Deklarasi Variabel', 'deklarasi-variabel', 'practice',
 'Buat konstanta `PI` dengan nilai 3.14 dan variabel `jari_jari` dengan nilai 10.', 3, 20,
 '// Tulis kodemu',
 'const PI = 3.14;\nlet jari_jari = 10;', 20),

(4, 'Tipe Data JS', 'tipe-data-js', 'theory',
 '<h2>Primitif</h2><p>String, Number, Boolean, Undefined, Null.</p>', 4, 20, NULL, NULL, 10),

(4, 'Functions & Arrow Func', 'functions-arrow-func', 'theory',
 '<h2>Arrow Function</h2><pre>const sum = (a, b) => a + b;</pre>', 5, 25, NULL, NULL, 10),

(4, 'Membuat Fungsi Sapaan', 'membuat-fungsi-sapaan', 'practice',
 'Buat arrow function bernama `greet` yang menerima parameter `name` dan mengembalikan string "Hello [name]".', 6, 25,
 '// Buat arrow function',
 'const greet = (name) => `Hello ${name}`;', 20),

(4, 'DOM Manipulation', 'dom-manipulation', 'theory',
 '<h2>Document Object Model</h2><pre>const btn = document.getElementById("myBtn");\nbtn.style.color = "red";</pre>', 7, 30, NULL, NULL, 10),

(4, 'Mengubah Teks Elemen', 'mengubah-teks-elemen', 'practice',
 'Ubah teks elemen dengan id="judul" menjadi "Berubah!" menggunakan JS.', 8, 30,
 '// Ubah teks elemen #judul',
 'document.getElementById("judul").innerText = "Berubah!";', 20),

(4, 'Events (Click)', 'events-click', 'theory',
 '<h2>Event Listener</h2><pre>button.addEventListener("click", () => { alert("Tombol ditekan!"); });</pre>', 9, 30, NULL, NULL, 10),

(4, 'Array Methods', 'array-methods', 'theory',
 '<h2>Map, Filter, Reduce</h2><pre>const doubled = nums.map(n => n * 2);</pre>', 10, 35, NULL, NULL, 10),

(4, 'Filter Angka Genap', 'filter-angka-genap', 'practice',
 'Gunakan `.filter()` untuk mengambil hanya angka genap dari array `[1, 2, 3, 4, 5, 6]`.', 11, 35,
 'const nums = [1, 2, 3, 4, 5, 6];\n// Filter genap',
 'const nums = [1, 2, 3, 4, 5, 6];\nconst genap = nums.filter(n => n % 2 === 0);', 20),

(4, 'Async & Await', 'async-await', 'theory',
 '<h2>Asynchronous JS</h2><pre>async function getData() { const res = await fetch(url); }</pre>', 12, 40, NULL, NULL, 10),

(4, 'JSON Handling', 'json-handling', 'theory',
 '<h2>JSON</h2><pre>const obj = JSON.parse(jsonString); const str = JSON.stringify(obj);</pre>', 13, 25, NULL, NULL, 10),

(4, 'Local Storage', 'local-storage', 'theory',
 '<h2>Menyimpan Data di Browser</h2><pre>localStorage.setItem("username", "andi");</pre>', 14, 30, NULL, NULL, 10),

(4, 'Project: Counter App', 'project-counter-app', 'practice',
 'Buat logika counter sederhana. Ada variabel `count = 0`. Buat fungsi `increment` yang menambah nilai count.', 15, 45,
 'let count = 0;\n// Buat fungsi increment',
 'let count = 0;\nconst increment = () => { count++; console.log(count); };', 30);

-- ----- COURSE 5: Java Programming Basics (15 lessons) -----
INSERT INTO `lessons` (`course_id`, `judul_lesson`, `slug`, `tipe`, `konten`, `urutan`, `durasi_menit`, `kode_contoh`, `kode_solusi`, `xp_reward`) VALUES
(5, 'Pengenalan Java', 'pengenalan-java', 'theory',
 '<h2>Write Once, Run Anywhere</h2><p>Java berjalan di JVM. Kode dikompilasi menjadi Bytecode.</p><pre>public class Main { public static void main(String[] args) { System.out.println("Hello"); } }</pre>', 1, 20, NULL, NULL, 10),

(5, 'Tipe Data Primitif', 'tipe-data-primitif', 'theory',
 '<h2>8 Tipe Primitif</h2><ul><li>int, long, short, byte</li><li>float, double</li><li>boolean</li><li>char</li></ul>', 2, 20, NULL, NULL, 10),

(5, 'Deklarasi Variabel', 'deklarasi-variabel-java', 'practice',
 'Deklarasikan variabel integer `umur` dengan nilai 21 dan boolean `isStudent` dengan nilai true.', 3, 20,
 '// Tulis kodemu',
 'int umur = 21;\nboolean isStudent = true;', 20),

(5, 'String Java', 'string-java', 'theory',
 '<h2>String adalah Object</h2><p>Di Java, String bukan primitif.</p>', 4, 20, NULL, NULL, 10),

(5, 'Input Output', 'input-output', 'theory',
 '<h2>Scanner Class</h2><pre>Scanner scanner = new Scanner(System.in);</pre>', 5, 25, NULL, NULL, 10),

(5, 'Kondisional (If-Else)', 'kondisional-if-else', 'practice',
 'Buat logika: Jika `nilai` > 75 print "Lulus", jika tidak print "Gagal".', 6, 25,
 'int nilai = 80;\n// Logika if',
 'int nilai = 80;\nif (nilai > 75) { System.out.println("Lulus"); } else { System.out.println("Gagal"); }', 20),

(5, 'Loops (For & While)', 'loops-for-while', 'theory',
 '<h2>Perulangan</h2><pre>for(int i=0; i&lt;5; i++) { System.out.println(i); }</pre>', 7, 25, NULL, NULL, 10),

(5, 'Mencetak Deret Angka', 'mencetak-deret-angka', 'practice',
 'Gunakan for loop untuk mencetak angka 1 sampai 5.', 8, 25,
 '// Loop di sini',
 'for(int i=1; i&lt;=5; i++) { System.out.println(i); }', 20),

(5, 'Array Java', 'array-java', 'theory',
 '<h2>Array Statis</h2><pre>int[] angka = {1, 2, 3};</pre>', 9, 30, NULL, NULL, 10),

(5, 'Method (Fungsi)', 'method-fungsi', 'theory',
 '<h2>Method Structure</h2><pre>public static int tambah(int a, int b) { return a + b; }</pre>', 10, 30, NULL, NULL, 10),

(5, 'Membuat Method Luas', 'membuat-method-luas', 'practice',
 'Buat method `hitungLuas` yang menerima panjang dan lebar (int), lalu mengembalikan hasil perkaliannya.', 11, 35,
 '// Buat method di sini',
 'public static int hitungLuas(int p, int l) { return p * l; }', 20),

(5, 'OOP: Class & Object', 'oop-class-object', 'theory',
 '<h2>Blueprint & Instance</h2><pre>class Mobil { String warna; } Mobil m = new Mobil();</pre>', 12, 40, NULL, NULL, 10),

(5, 'OOP: Constructor', 'oop-constructor', 'theory',
 '<h2>Constructor</h2><pre>public Mobil(String w) { this.warna = w; }</pre>', 13, 35, NULL, NULL, 10),

(5, 'OOP: Inheritance', 'oop-inheritance', 'theory',
 '<h2>Pewarisan</h2><pre>class Kucing extends Hewan { }</pre>', 14, 40, NULL, NULL, 10),

(5, 'Project: Student Data', 'project-student-data', 'practice',
 'Buat class `Student` dengan atribut `name` dan `gpa`. Buat method `display()` untuk menampilkan data.', 15, 60,
 'class Student { }',
 'class Student { String name; double gpa; void display() { System.out.println(name + " : " + gpa); } }', 30);

-- ----- COURSE 6: Python untuk Pemula (8 lessons) -----
INSERT INTO `lessons` (`course_id`, `judul_lesson`, `slug`, `tipe`, `konten`, `urutan`, `durasi_menit`, `kode_contoh`, `kode_solusi`, `xp_reward`) VALUES
(6, 'Pengenalan Python', 'pengenalan-python', 'theory',
 '# Selamat Datang di Python! 🐍\n\nPython adalah bahasa pemrograman yang mudah dipelajari dan sangat powerful.\n\n```python\nprint("Hello, World!")\n```', 1, 15,
 '# Hello World in Python\nprint("Hello, World!")',
 'print("Hello, World!")\nprint("Selamat belajar Python!")', 15),

(6, 'Variabel dan Tipe Data', 'variabel-python', 'practice',
 '# Variabel dan Tipe Data\n\n```python\nnama = "John"\numur = 25\ntinggi = 175.5\nis_student = True\n```', 2, 20,
 'nama = "Budi"\numur = 20',
 'nama = "Budi"\numur = 20\nprint(nama, umur)', 20),

(6, 'Operasi Matematika', 'operasi-matematika-python', 'practice',
 'Python mendukung operasi matematika dasar (+, -, *, /, //, %, **).', 3, 15,
 'a = 10\nb = 3\n# Print semua operasi',
 'a = 10\nb = 3\nprint(a+b, a-b, a*b, a/b)', 15),

(6, 'Kondisi If-Else', 'kondisi-if-else-python', 'practice',
 'Membuat keputusan dalam program dengan if-elif-else.', 4, 20,
 'nilai = 78',
 'nilai = 78\nif nilai >= 80:\n    print("A")\nelif nilai >= 70:\n    print("B")\nelse:\n    print("C")', 20),

(6, 'Loop - For dan While', 'loop-python', 'practice',
 'Iterasi dengan for loop dan while loop.', 5, 25,
 'for i in range(5): print(i)',
 'for i in range(1, 6):\n    print(i)', 25),

(6, 'List dan Tuple', 'list-tuple-python', 'practice',
 'List (mutable) dan Tuple (immutable) untuk kumpulan data.', 6, 25,
 'numbers = [1, 2, 3]',
 'numbers = [1, 2, 3]\nnumbers.append(4)\nprint(numbers)', 25),

(6, 'Function (Fungsi)', 'function-python', 'practice',
 'Fungsi adalah blok kode yang dapat digunakan kembali.', 7, 25,
 'def greet(name):\n    pass',
 'def greet(name):\n    return f"Hello, {name}!"\nprint(greet("World"))', 25),

(6, 'Dictionary', 'dictionary-python', 'practice',
 'Dictionary menyimpan data dalam format key-value.', 8, 25,
 'student = {"nama": "Budi"}',
 'student = {"nama": "Budi", "umur": 20}\nprint(student["nama"])', 25);

-- ----- COURSE 7: Python Intermediate (8 lessons) -----
INSERT INTO `lessons` (`course_id`, `judul_lesson`, `slug`, `tipe`, `konten`, `urutan`, `durasi_menit`, `kode_contoh`, `xp_reward`) VALUES
(7, 'List Comprehension', 'list-comprehension', 'practice',
 'Cara singkat membuat list baru dari list yang ada.', 1, 15,
 'numbers = [1, 2, 3, 4, 5]\nsquares = [x**2 for x in numbers]\nprint(squares)', 25),

(7, 'Lambda Functions', 'lambda-functions', 'practice',
 'Fungsi anonim yang singkat.', 2, 15,
 'square = lambda x: x ** 2\nprint(square(5))', 25),

(7, 'Decorators', 'decorators', 'practice',
 'Fungsi yang memodifikasi perilaku fungsi lain.', 3, 20,
 'def timer(func):\n    def wrapper():\n        return func()\n    return wrapper', 30),

(7, 'Generators', 'generators', 'practice',
 'Menghasilkan nilai satu per satu, hemat memori.', 4, 15,
 'def countdown(n):\n    while n > 0:\n        yield n\n        n -= 1', 25),

(7, 'Error Handling Advanced', 'error-handling-advanced', 'practice',
 'Penanganan error yang lebih kompleks.', 5, 20,
 'try:\n    value = int("abc")\nexcept ValueError as e:\n    print(e)', 30),

(7, 'File Handling', 'file-handling', 'practice',
 'Membaca dan menulis file di Python.', 6, 15,
 'with open("data.txt", "w") as f:\n    f.write("Hello")', 25),

(7, 'OOP Advanced', 'oop-advanced', 'practice',
 'Inheritance, polymorphism, encapsulation.', 7, 25,
 'class Dog(Animal):\n    def speak(self):\n        return "Woof"', 35),

(7, 'Regular Expressions', 'regular-expressions', 'practice',
 'Regex untuk pencarian dan manipulasi string.', 8, 20,
 'import re\nre.findall(r"\\d+", "abc123")', 30);

-- ----- COURSE 8: JavaScript Fundamentals (5 lessons) -----
INSERT INTO `lessons` (`course_id`, `judul_lesson`, `slug`, `tipe`, `konten`, `urutan`, `durasi_menit`, `kode_contoh`, `kode_solusi`, `xp_reward`) VALUES
(8, 'Pengenalan JavaScript', 'pengenalan-js101', 'theory',
 'JavaScript adalah bahasa pemrograman untuk web interaktif.', 1, 15,
 'console.log("Hello");',
 'console.log("Hello, World!");', 15),

(8, 'Variabel: let, const, var', 'variabel-js101', 'practice',
 'Tiga cara deklarasi variabel: let, const, var.', 2, 20,
 'let nama = "Budi";',
 'let nama = "Budi";\nconst umur = 20;\nconsole.log(nama, umur);', 20),

(8, 'Array dan Object', 'array-object-js101', 'practice',
 'Array untuk list, Object untuk key-value.', 3, 25,
 'const arr = [1, 2, 3];',
 'const arr = [1, 2, 3];\nconst obj = {name: "Test"};\nconsole.log(arr, obj);', 25),

(8, 'Function JavaScript', 'function-js101', 'practice',
 'Regular function dan arrow function.', 4, 25,
 'function add(a, b) { return a + b; }',
 'const add = (a, b) => a + b;\nconsole.log(add(2, 3));', 25),

(8, 'DOM Manipulation', 'dom-manipulation-js101', 'practice',
 'Mengakses dan memodifikasi elemen HTML.', 5, 30,
 'document.getElementById("title").textContent = "Hello!";',
 'document.getElementById("title").textContent = "Hello!";', 30);

-- ----- COURSE 9: JavaScript Modern ES6+ (10 lessons) -----
INSERT INTO `lessons` (`course_id`, `judul_lesson`, `slug`, `tipe`, `konten`, `urutan`, `durasi_menit`, `kode_contoh`, `xp_reward`) VALUES
(9, 'Let dan Const', 'let-dan-const', 'practice',
 'ES6 memperkenalkan let dan const sebagai pengganti var.', 1, 15,
 'let nama = "John";\nconst PI = 3.14159;\nconsole.log(nama, PI);', 20),

(9, 'Arrow Functions', 'arrow-functions', 'practice',
 'Sintaks singkat untuk menulis function.', 2, 15,
 'const add = (a, b) => a + b;\nconsole.log(add(5, 3));', 20),

(9, 'Template Literals', 'template-literals', 'practice',
 'Memudahkan penulisan string dengan variabel.', 3, 15,
 'const name = "John";\nconsole.log(`Hello, ${name}!`);', 20),

(9, 'Destructuring', 'destructuring', 'practice',
 'Ekstraksi nilai dari array dan object.', 4, 15,
 'const [a, b] = [1, 2];\nconsole.log(a, b);', 25),

(9, 'Spread Operator', 'spread-operator', 'practice',
 'Menyebarkan elemen array atau object.', 5, 15,
 'const arr = [...[1, 2], 3];\nconsole.log(arr);', 25),

(9, 'Rest Parameters', 'rest-parameters', 'practice',
 'Mengumpulkan sisa argumen menjadi array.', 6, 10,
 'const sum = (...nums) => nums.reduce((a, b) => a + b, 0);\nconsole.log(sum(1, 2, 3));', 20),

(9, 'Promises', 'promises', 'practice',
 'Cara modern menangani operasi asinkron.', 7, 20,
 'new Promise((resolve) => resolve(42)).then(v => console.log(v));', 30),

(9, 'Async/Await', 'async-await-modern', 'practice',
 'Sintaks yang lebih bersih untuk Promise.', 8, 20,
 'const getData = async () => {\n    return await fetch("/api");\n};', 30),

(9, 'Array Methods Modern', 'array-methods-modern', 'practice',
 'Map, filter, reduce, find, some, every.', 9, 15,
 'const nums = [1, 2, 3];\nconsole.log(nums.map(n => n * 2));', 25),

(9, 'Modules ES6', 'modules-es6', 'theory',
 'Organisir kode dalam file terpisah.', 10, 15,
 'import { add } from "./math.js";\nconsole.log(add(2, 3));', 25);

-- ----- COURSE 10: PHP untuk Web Development (4 lessons) -----
INSERT INTO `lessons` (`course_id`, `judul_lesson`, `slug`, `tipe`, `konten`, `urutan`, `durasi_menit`, `kode_contoh`, `kode_solusi`, `xp_reward`) VALUES
(10, 'Pengenalan PHP', 'pengenalan-php101', 'theory',
 'PHP adalah bahasa server-side populer untuk web development.', 1, 15,
 '<?php echo "Hello, World!"; ?>',
 '<?php echo "Hello, World!"; ?>', 15),

(10, 'Variabel dan Tipe Data', 'variabel-php101', 'practice',
 'Variabel PHP dimulai dengan tanda $.', 2, 20,
 '<?php $nama = "Budi"; ?>',
 '<?php $nama = "Budi"; $umur = 20; echo "$nama, $umur tahun"; ?>', 20),

(10, 'Array PHP', 'array-php101', 'practice',
 'Indexed dan Associative Array di PHP.', 3, 25,
 '<?php $arr = ["A", "B"]; ?>',
 '<?php $arr = ["A", "B", "C"]; print_r($arr); ?>', 25),

(10, 'Function PHP', 'function-php101', 'practice',
 'Mendefinisikan dan memanggil function.', 4, 25,
 '<?php function greet($n) { return "Hello $n"; } ?>',
 '<?php function add($a, $b) { return $a + $b; } echo add(5, 3); ?>', 25);

-- ----- COURSE 11: PHP & MySQL Database (8 lessons) -----
INSERT INTO `lessons` (`course_id`, `judul_lesson`, `slug`, `tipe`, `konten`, `urutan`, `durasi_menit`, `kode_contoh`, `xp_reward`) VALUES
(11, 'Koneksi MySQL', 'koneksi-mysql', 'practice',
 'PDO dan MySQLi untuk koneksi database.', 1, 15,
 '<?php\n$pdo = new PDO("mysql:host=localhost;dbname=test", "root", "");\necho "OK";\n?>', 25),

(11, 'Query SELECT', 'query-select', 'practice',
 'Mengambil data dengan SELECT.', 2, 15,
 '<?php\n$stmt = $pdo->query("SELECT * FROM users");\n$users = $stmt->fetchAll();\n?>', 25),

(11, 'Query INSERT', 'query-insert', 'practice', 'Menambah data baru dengan INSERT.', 3, 15,
 '<?php\n$stmt = $pdo->prepare("INSERT INTO users (name) VALUES (?)");\n$stmt->execute(["John"]);\n?>', 25),

(11, 'Query UPDATE dan DELETE', 'query-update-delete', 'practice', 'Mengubah dan menghapus data.', 4, 15,
 '<?php\n$stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");\n$stmt->execute(["Jane", 1]);\n?>', 25),

(11, 'JOIN Tables', 'join-tables', 'practice', 'Menggabungkan data dari beberapa tabel.', 5, 20,
 '<?php\n$stmt = $pdo->query("SELECT u.name, o.total FROM users u JOIN orders o ON u.id = o.user_id");\n?>', 30),

(11, 'Transactions', 'transactions', 'practice', 'Serangkaian operasi yang atomik.', 6, 20,
 '<?php\n$pdo->beginTransaction();\n// ...\n$pdo->commit();\n?>', 30),

(11, 'CRUD Lengkap', 'crud-lengkap', 'practice', 'Class untuk operasi CRUD.', 7, 25,
 '<?php\nclass User { /* ... */ }\n?>', 35),

(11, 'Pagination', 'pagination', 'practice', 'Menampilkan data dalam halaman.', 8, 15,
 '<?php\n$page = $_GET["page"] ?? 1;\n?>', 25);

-- ----- COURSE 12: Java OOP (8 lessons) -----
INSERT INTO `lessons` (`course_id`, `judul_lesson`, `slug`, `tipe`, `konten`, `urutan`, `durasi_menit`, `kode_contoh`, `xp_reward`) VALUES
(12, 'Classes dan Objects', 'classes-objects', 'practice',
 'Class adalah blueprint untuk membuat object.', 1, 20,
 'public class Person {\n    private String name;\n    public Person(String name) { this.name = name; }\n}', 25),

(12, 'Encapsulation', 'encapsulation', 'practice',
 'Menyembunyikan detail implementasi.', 2, 15,
 'public class BankAccount {\n    private double balance;\n    public void deposit(double amount) { balance += amount; }\n}', 25),

(12, 'Inheritance', 'inheritance', 'practice',
 'Class mewarisi class lain.', 3, 20,
 'public class Dog extends Animal {\n    public void bark() { System.out.println("Woof"); }\n}', 30),

(12, 'Polymorphism', 'polymorphism', 'practice',
 'Object dapat digunakan dalam berbagai bentuk.', 4, 20,
 'Shape s = new Circle(5);\ns.getArea();', 30),

(12, 'Interfaces', 'interfaces', 'practice',
 'Mendefinisikan kontrak untuk class.', 5, 20,
 'public interface Drawable { void draw(); }', 30),

(12, 'Abstract Classes', 'abstract-classes', 'practice',
 'Class yang tidak bisa diinstansiasi langsung.', 6, 20,
 'public abstract class Vehicle {\n    public abstract void start();\n}', 30),

(12, 'Exception Handling', 'exception-handling', 'practice',
 'Menangani error dan exception.', 7, 20,
 'try { /* ... */ } catch (Exception e) { /* ... } }', 30),

(12, 'Collections', 'collections', 'practice',
 'Collections Framework Java.', 8, 20,
 'List<String> list = new ArrayList<>();\nlist.add("a");', 30);

-- ----- COURSE 13: JAVA101 (10 lessons) -----
INSERT INTO `lessons` (`course_id`, `judul_lesson`, `slug`, `tipe`, `konten`, `urutan`, `durasi_menit`, `kode_contoh`, `xp_reward`) VALUES
(13, 'Pengenalan Java', 'pengenalan-java101', 'theory',
 'Java berjalan di JVM dengan konsep Write Once Run Anywhere.', 1, 20,
 'public class Main {\n    public static void main(String[] args) {\n        System.out.println("Hello");\n    }\n}', 15),

(13, 'Variabel dan Tipe Data', 'variabel-java101', 'practice',
 'Deklarasi variabel dan tipe data primitif.', 2, 20,
 'int umur = 21;', 20),

(13, 'Operator Java', 'operator-java101', 'practice',
 'Operator aritmatika, perbandingan, logika.', 3, 20,
 'int a = 10, b = 3;\nint sum = a + b;', 20),

(13, 'Input Scanner', 'input-scanner', 'practice',
 'Mengambil input user dengan Scanner.', 4, 20,
 'Scanner sc = new Scanner(System.in);\nString name = sc.nextLine();', 20),

(13, 'Percabangan If-Else', 'percabangan-java101', 'practice',
 'Kontrol alur dengan if-else.', 5, 25,
 'if (nilai > 75) { lulus = true; }', 25),

(13, 'Switch Case', 'switch-case', 'practice',
 'Percabangan dengan switch-case.', 6, 20,
 'switch (day) { case 1: ... }', 20),

(13, 'Loop For', 'loop-for-java', 'practice', 'Perulangan dengan for.', 7, 20, 'for (int i = 0; i < 5; i++) {}', 20),

(13, 'Loop While', 'loop-while-java', 'practice', 'Perulangan dengan while.', 8, 20, 'while (i < 5) { i++; }', 20),

(13, 'Array Java', 'array-java101', 'practice', 'Array di Java bersifat statis.', 9, 25, 'int[] arr = {1, 2, 3};', 25),

(13, 'Method Java', 'method-java101', 'practice', 'Mendefinisikan method.', 10, 25, 'public static int add(int a, int b) { return a + b; }', 25);

-- ----- COURSE 14: C++ untuk Pemula (10 lessons) -----
INSERT INTO `lessons` (`course_id`, `judul_lesson`, `slug`, `tipe`, `konten`, `urutan`, `durasi_menit`, `kode_contoh`, `xp_reward`) VALUES
(14, 'Pengenalan C++', 'pengenalan-cpp', 'theory',
 'C++ adalah bahasa tingkat menengah dengan OOP, dikembangkan oleh Bjarne Stroustrup.', 1, 10,
 '#include <iostream>\nusing namespace std;\nint main() {\n    cout << "Hello, C++!" << endl;\n    return 0;\n}', 15),

(14, 'Variabel dan Tipe Data', 'variabel-cpp', 'practice',
 'Tipe data: int, float, double, char, string, bool.', 2, 15,
 'int umur = 25;', 20),

(14, 'Input dan Output', 'io-cpp', 'practice', 'Gunakan cin dan cout.', 3, 15,
 'cin >> nama;', 20),

(14, 'Operator C++', 'operator-cpp', 'practice', 'Operator aritmatika dan logika.', 4, 10,
 'int a = 10, b = 3;', 15),

(14, 'Percabangan If-Else', 'percabangan-cpp', 'practice', 'Kontrol alur dengan if-else.', 5, 15,
 'if (nilai >= 80) { /* A */ }', 20),

(14, 'Perulangan For', 'for-cpp', 'practice', 'Loop dengan for.', 6, 15, 'for (int i = 1; i <= 10; i++) {}', 20),

(14, 'Perulangan While', 'while-cpp', 'practice', 'Loop dengan while.', 7, 10, 'while (count <= 5) {}', 15),

(14, 'Array di C++', 'array-cpp', 'practice', 'Array di C++.', 8, 15, 'int numbers[5] = {1, 2, 3, 4, 5};', 20),

(14, 'Function di C++', 'function-cpp', 'practice', 'Mendefinisikan function.', 9, 15,
 'int add(int a, int b) { return a + b; }', 25),

(14, 'Pointer Dasar', 'pointer-dasar', 'theory',
 'Pointer menyimpan alamat memori.', 10, 20,
 'int* ptr = &num;', 25);

-- =====================================================
-- 8. SEED DATA - DEFAULT CLANS
-- =====================================================
INSERT INTO `clans` (`nama_clan`, `slug`, `deskripsi`, `leader_id`, `total_members`, `total_xp`, `is_public`, `max_members`) VALUES
('Code Warriors',       'code-warriors',
    'Komunitas developer yang passionate untuk coding dan berbagi ilmu.',
    (SELECT id FROM users WHERE username = 'admin' LIMIT 1), 1, 0, TRUE, 100),
('Python Enthusiasts',  'python-enthusiasts',
    'Clan khusus untuk pecinta Python. Data science, automation, web dev - semua ada di sini!',
    (SELECT id FROM users WHERE username = 'admin' LIMIT 1), 1, 0, TRUE, 50),
('JavaScript Ninjas',   'javascript-ninjas',
    'Master JavaScript bersama! Frontend, backend, fullstack - kita explore semuanya.',
    (SELECT id FROM users WHERE username = 'admin' LIMIT 1), 1, 0, TRUE, 50),
('Backend Masters',     'backend-masters',
    'Fokus pada server-side development. PHP, Node.js, database, dan API development.',
    (SELECT id FROM users WHERE username = 'admin' LIMIT 1), 1, 0, TRUE, 50),
('Web Dev Indonesia',   'webdev-indonesia',
    'Komunitas web developer Indonesia. Sharing, diskusi, dan kolaborasi project!',
    (SELECT id FROM users WHERE username = 'admin' LIMIT 1), 1, 0, TRUE, 100);

-- =====================================================
-- 9. SEED DATA - ACHIEVEMENTS
-- =====================================================
INSERT INTO `achievements` (`kode_achievement`, `nama_achievement`, `deskripsi`, `icon`, `xp_reward`, `tipe`, `requirement_value`) VALUES
('first_lesson',   'First Steps',         'Selesaikan lesson pertama Anda',         '🎯', 50,  'lesson_complete', 1),
('ten_lessons',    'Getting Started',     'Selesaikan 10 lesson',                    '⭐', 100, 'lesson_complete', 10),
('fifty_lessons',  'Half Century',        'Selesaikan 50 lesson',                    '🌟', 300, 'lesson_complete', 50),
('hundred_lessons','Century Master',      'Selesaikan 100 lesson',                   '💫', 500, 'lesson_complete', 100),
('first_course',   'Course Master',       'Selesaikan 1 kursus lengkap',             '🏆', 200, 'course_complete', 1),
('five_courses',   'Multi-Course Expert', 'Selesaikan 5 kursus lengkap',             '🎓', 400, 'course_complete', 5),
('ten_courses',    'Ultimate Learner',    'Selesaikan 10 kursus lengkap',            '👑', 750, 'course_complete', 10),
('level_5',        'Level 5 Achiever',    'Capai level 5',                           '📈', 150, 'special', 5),
('level_10',       'Level 10 Master',     'Capai level 10',                          '📊', 300, 'special', 10),
('level_20',       'Level 20 Legend',     'Capai level 20',                          '🌟', 600, 'special', 20),
('xp_1000',        'XP Collector',        'Kumpulkan 1000 XP',                       '💎', 200, 'special', 1000),
('xp_5000',        'XP Master',           'Kumpulkan 5000 XP',                       '💍', 500, 'special', 5000),
('xp_10000',       'XP Legend',           'Kumpulkan 10000 XP',                      '👑', 1000,'special', 10000),
('week_warrior',   'Week Warrior',        'Belajar 7 hari berturut-turut',           '🔥', 150, 'streak', 7),
('clan_leader',    'Clan Leader',         'Buat clan pertama Anda',                  '💜', 100, 'clan', 1),
('code_ninja',     'Code Ninja',          'Selesaikan 50 lesson',                    '🥷', 500, 'lesson_complete', 50);

-- =====================================================
-- 10. SEED DATA - SHOP ITEMS
-- =====================================================
INSERT INTO `shop_items` (`name`, `description`, `cost`, `type`, `value`, `icon`) VALUES
('Novice Coder',    'Gelar untuk pemula yang bersemangat',            100,  'title', 'Novice Coder',                                '🌱'),
('Bug Hunter',      'Pemburu bug yang handal',                          500,  'title', 'Bug Hunter',                                  '🐛'),
('Code Wizard',     'Penyihir kode legendaris',                        2000, 'title', 'Code Wizard',                                 '🧙‍♂️'),
('Neon Frame',      'Bingkai avatar neon yang keren',                  1000, 'frame', 'border: 3px solid #00ff00; box-shadow: 0 0 10px #00ff00;', '🖼️'),
('Gold Frame',      'Bingkai emas mewah',                              5000, 'frame', 'border: 3px solid #ffd700; box-shadow: 0 0 10px #ffd700;', '👑'),
('Novice Title',    'Title for beginners',                             100,  'title', 'Novice',                                      '🏅'),
('Golden Frame',    'A shiny golden frame for your avatar',            500,  'frame', 'border-gold',                                 '🖼️'),
('Dark Blue Theme', 'A cool dark blue theme',                          1000, 'theme', 'theme-dark-blue',                             '🌙');

-- =====================================================
-- 11. SEED DATA - PENGATURAN
-- =====================================================
INSERT INTO `pengaturan` (`kunci`, `nilai`) VALUES
('nama_platform',     'Prozone'),
('deskripsi_platform','Platform pembelajaran coding interaktif dengan fitur clan, leaderboard, dan achievement'),
('email_platform',    'info@prozone.com'),
('warna_primary',     '#8b5cf6'),
('warna_secondary',   '#a78bfa'),
('warna_success',     '#10b981'),
('warna_danger',      '#ef4444'),
('warna_warning',     '#f59e0b'),
('warna_info',        '#3b82f6');

-- =====================================================
-- 12. UPDATE TOTAL LESSONS COUNT
-- =====================================================
UPDATE `courses` c
SET `total_lessons` = (SELECT COUNT(*) FROM `lessons` WHERE `course_id` = c.id);

-- =====================================================
-- DONE
-- =====================================================
SET FOREIGN_KEY_CHECKS = 1;

SELECT '✅ Database Prozone berhasil di-setup!' AS status;
SELECT CONCAT('Total courses: ', COUNT(*)) AS info FROM courses;
SELECT CONCAT('Total lessons: ', COUNT(*)) AS info FROM lessons;
SELECT CONCAT('Total users:   ', COUNT(*)) AS info FROM users;
SELECT CONCAT('Total clans:    ', COUNT(*)) AS info FROM clans;
SELECT CONCAT('Total achievements: ', COUNT(*)) AS info FROM achievements;

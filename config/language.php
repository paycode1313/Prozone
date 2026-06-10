<?php
// Multi-Language System untuk Prozone

$lang = $_SESSION['language'] ?? 'id';

$translations = [
    'id' => [
        'app_name' => 'Prozone',
        'dashboard' => 'Dashboard',
        'courses' => 'Kursus',
        'clan' => 'Clan',
        'leaderboard' => 'Leaderboard',
        'achievements' => 'Achievements',
        'analytics' => 'Analytics',
        'profile' => 'Profile',
        'logout' => 'Logout',
        'welcome' => 'Selamat Datang',
        'total_xp' => 'Total XP',
        'level' => 'Level',
        'progress' => 'Progress',
        'completed' => 'Selesai',
        'in_progress' => 'Sedang Dikerjakan',
        'not_started' => 'Belum Dimulai',
        'start_lesson' => 'Mulai Lesson',
        'continue_lesson' => 'Lanjutkan',
        'hint' => 'Hint',
        'instruction' => 'Instruksi',
        'run_code' => 'Jalankan Kode',
        'save_progress' => 'Simpan Progress',
        'complete_lesson' => 'Selesaikan Lesson',
        'preview' => 'Preview',
        'code_editor' => 'Editor Kode',
        'chat' => 'Chat',
        'send_message' => 'Kirim Pesan',
        'join_clan' => 'Bergabung Clan',
        'create_clan' => 'Buat Clan',
        'solo' => 'Solo',
        'clan_leaderboard' => 'Leaderboard Clan',
        'solo_leaderboard' => 'Leaderboard Solo',
        'rank' => 'Peringkat',
        'xp' => 'XP',
        'earned' => 'Diperoleh',
        'not_earned' => 'Belum Diperoleh',
        'certificates' => 'Sertifikat',
        'language' => 'Bahasa',
        'theme' => 'Tema',
        'dark_mode' => 'Dark Mode',
        'light_mode' => 'Light Mode',
        'settings' => 'Pengaturan',
        'save' => 'Simpan',
        'cancel' => 'Batal',
    ],
    'en' => [
        'app_name' => 'Prozone',
        'dashboard' => 'Dashboard',
        'courses' => 'Courses',
        'clan' => 'Clan',
        'leaderboard' => 'Leaderboard',
        'achievements' => 'Achievements',
        'analytics' => 'Analytics',
        'profile' => 'Profile',
        'logout' => 'Logout',
        'welcome' => 'Welcome',
        'total_xp' => 'Total XP',
        'level' => 'Level',
        'progress' => 'Progress',
        'completed' => 'Completed',
        'in_progress' => 'In Progress',
        'not_started' => 'Not Started',
        'start_lesson' => 'Start Lesson',
        'continue_lesson' => 'Continue',
        'hint' => 'Hint',
        'instruction' => 'Instruction',
        'run_code' => 'Run Code',
        'save_progress' => 'Save Progress',
        'complete_lesson' => 'Complete Lesson',
        'preview' => 'Preview',
        'code_editor' => 'Code Editor',
        'chat' => 'Chat',
        'send_message' => 'Send Message',
        'join_clan' => 'Join Clan',
        'create_clan' => 'Create Clan',
        'solo' => 'Solo',
        'clan_leaderboard' => 'Clan Leaderboard',
        'solo_leaderboard' => 'Solo Leaderboard',
        'rank' => 'Rank',
        'xp' => 'XP',
        'earned' => 'Earned',
        'not_earned' => 'Not Earned',
        'certificates' => 'Certificates',
        'language' => 'Language',
        'theme' => 'Theme',
        'dark_mode' => 'Dark Mode',
        'light_mode' => 'Light Mode',
        'settings' => 'Settings',
        'save' => 'Save',
        'cancel' => 'Cancel',
    ]
];

function t($key) {
    global $translations, $lang;
    return $translations[$lang][$key] ?? $key;
}

function setLanguage($language) {
    $_SESSION['language'] = $language;
    // Update database
    if (isset($_SESSION['user_id'])) {
        $database = new Database();
        $db = $database->getConnection();
        $query = "UPDATE users SET language_preference = :lang WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':lang', $language);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
    }
}

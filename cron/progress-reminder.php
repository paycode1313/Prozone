<?php
/**
 * Progress Reminder Cron Job
 * Run this daily: php cron/progress-reminder.php
 * Or via cron: 0 9 * * * php /path/to/cron/progress-reminder.php
 */

// Change to root directory
chdir(dirname(__DIR__));

require_once 'config/config.php';
require_once 'classes/EmailService.php';

$database = new Database();
$db = $database->getConnection();

$emailService = new EmailService($db);

// Get users who haven't been active in 3+ days
$query = "SELECT DISTINCT u.id, u.email, u.nama_lengkap,
          (SELECT MAX(up.completed_at) FROM user_progress up WHERE up.user_id = u.id) as last_activity,
          (SELECT c.judul_course FROM enrollments e 
           JOIN courses c ON e.course_id = c.id 
           WHERE e.user_id = u.id AND e.status = 'active' 
           ORDER BY e.enrolled_at DESC LIMIT 1) as current_course
          FROM users u
          WHERE u.role = 'student'
          AND u.email_verified = 1
          AND u.id IN (SELECT DISTINCT user_id FROM enrollments WHERE status = 'active')
          HAVING last_activity < DATE_SUB(NOW(), INTERVAL 3 DAY)
          OR last_activity IS NULL";

try {
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $count = 0;
    while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (empty($user['email'])) continue;
        
        $lastActivity = $user['last_activity'] 
            ? floor((time() - strtotime($user['last_activity'])) / 86400) . ' hari'
            : 'beberapa waktu';
        
        $courseName = $user['current_course'] ?? 'kursus Anda';
        
        $emailService->sendProgressReminder(
            $user['id'],
            $user['email'],
            $user['nama_lengkap'],
            $lastActivity,
            $courseName
        );
        
        $count++;
        
        // Rate limit: 1 email per second
        sleep(1);
    }
    
    echo "Progress reminder sent to {$count} users.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

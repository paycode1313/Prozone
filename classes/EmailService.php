<?php
/**
 * Email Service for Prozone
 * Handles email verification, password reset, and notifications
 * Uses PHPMailer for SMTP support
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

class EmailService {
    private $from_email;
    private $from_name;
    private $db;
    
    public function __construct($db = null) {
        $this->db = $db;
        $this->from_email = defined('EMAIL_FROM') ? EMAIL_FROM : 'noreply@prozone.com';
        $this->from_name = defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : APP_NAME;
    }
    
    /**
     * Send email using PHPMailer with SMTP
     */
    public function send($to, $subject, $body, $isHtml = true) {
        // For development, log email instead of sending
        if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
            $this->logEmail($to, $subject, $body);
            return true;
        }
        
        // Check if SMTP is configured
        if (defined('SMTP_HOST') && SMTP_HOST) {
            return $this->sendViaSMTP($to, $subject, $body, $isHtml);
        }
        
        // Fallback to PHP mail()
        return $this->sendViaMail($to, $subject, $body, $isHtml);
    }
    
    /**
     * Send email via SMTP using PHPMailer
     */
    private function sendViaSMTP($to, $subject, $body, $isHtml = true) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;
            $mail->CharSet    = 'UTF-8';
            
            // Recipients
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to);
            $mail->addReplyTo($this->from_email, $this->from_name);
            
            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            
            if ($isHtml) {
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
            }
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log error
            $this->logEmail($to, "[ERROR] " . $subject, "Mailer Error: " . $mail->ErrorInfo . "\n\nOriginal Body:\n" . $body);
            return false;
        }
    }
    
    /**
     * Send email via PHP mail() function
     */
    private function sendViaMail($to, $subject, $body, $isHtml = true) {
        $headers = [];
        $headers[] = "From: {$this->from_name} <{$this->from_email}>";
        $headers[] = "Reply-To: {$this->from_email}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        
        if ($isHtml) {
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        }
        
        return mail($to, $subject, $body, implode("\r\n", $headers));
    }
    
    /**
     * Log email to file (for development)
     */
    private function logEmail($to, $subject, $body) {
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/emails.log';
        $log = "\n=== EMAIL LOG: " . date('Y-m-d H:i:s') . " ===\n";
        $log .= "To: {$to}\n";
        $log .= "Subject: {$subject}\n";
        $log .= "Body:\n{$body}\n";
        $log .= "=== END EMAIL ===\n";
        
        file_put_contents($logFile, $log, FILE_APPEND);
    }
    
    /**
     * Generate verification token
     */
    public function generateToken($length = 64) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Send email verification
     */
    public function sendVerificationEmail($userId, $email, $name) {
        $token = $this->generateToken();
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Save token to database
        $query = "UPDATE users SET 
                  email_verification_token = :token,
                  email_verification_expires = :expiry
                  WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expiry', $expiry);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $verifyLink = rtrim($baseUrl, '/') . "/verify-email.php?token={$token}";
        
        $subject = "Verifikasi Email Anda - " . APP_NAME;
        $body = $this->getEmailTemplate('verification', [
            'name' => $name,
            'verify_link' => $verifyLink,
            'expiry_hours' => 24
        ]);
        
        return $this->send($email, $subject, $body);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($userId, $email, $name) {
        $token = $this->generateToken();
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Save token to database
        $query = "UPDATE users SET 
                  reset_token = :token,
                  reset_token_expiry = :expiry
                  WHERE id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expiry', $expiry);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $resetLink = rtrim($baseUrl, '/') . "/reset-password.php?token={$token}";
        
        // Store reset link for debug mode access
        $this->lastResetLink = $resetLink;
        
        $subject = "Reset Password - " . APP_NAME;
        $body = $this->getEmailTemplate('password_reset', [
            'name' => $name,
            'reset_link' => $resetLink,
            'expiry_minutes' => 60
        ]);
        
        return $this->send($email, $subject, $body);
    }
    
    /**
     * Get last generated reset link (for debug mode)
     */
    public function getLastResetLink() {
        return $this->lastResetLink ?? null;
    }
    
    private $lastResetLink = null;
    
    /**
     * Send progress reminder
     */
    public function sendProgressReminder($userId, $email, $name, $lastActivity, $courseName) {
        $subject = "Ayo lanjutkan belajar! - " . APP_NAME;
        $body = $this->getEmailTemplate('progress_reminder', [
            'name' => $name,
            'course_name' => $courseName,
            'last_activity' => $lastActivity,
            'dashboard_link' => BASE_URL . 'dashboard.php'
        ]);
        
        return $this->send($email, $subject, $body);
    }
    
    /**
     * Send course update notification
     */
    public function sendCourseUpdateNotification($email, $name, $courseName, $updateType) {
        $subject = "Update Kursus: {$courseName} - " . APP_NAME;
        $body = $this->getEmailTemplate('course_update', [
            'name' => $name,
            'course_name' => $courseName,
            'update_type' => $updateType
        ]);
        
        return $this->send($email, $subject, $body);
    }
    
    /**
     * Send achievement notification
     */
    public function sendAchievementNotification($email, $name, $achievementName, $xpEarned) {
        $subject = "Selamat! Anda mendapatkan achievement baru! - " . APP_NAME;
        $body = $this->getEmailTemplate('achievement', [
            'name' => $name,
            'achievement_name' => $achievementName,
            'xp_earned' => $xpEarned
        ]);
        
        return $this->send($email, $subject, $body);
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($template, $data) {
        $templates = [
            'verification' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 100%); color: #e2e8f0; padding: 40px; border-radius: 12px;">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h1 style="color: #8b5cf6; margin: 0;">PROZONE</h1>
                        <p style="color: #94a3b8; margin: 5px 0;">Learning Management System</p>
                    </div>
                    <h2 style="color: #e2e8f0;">Halo, {name}!</h2>
                    <p style="color: #cbd5e1; line-height: 1.6;">Terima kasih telah mendaftar di Prozone. Untuk menyelesaikan pendaftaran, silakan verifikasi email Anda dengan mengklik tombol di bawah ini:</p>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{verify_link}" style="background: linear-gradient(135deg, #7c3aed, #8b5cf6); color: white; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Verifikasi Email</a>
                    </div>
                    <p style="color: #94a3b8; font-size: 14px;">Link ini akan kedaluwarsa dalam {expiry_hours} jam.</p>
                    <p style="color: #64748b; font-size: 12px; margin-top: 30px; border-top: 1px solid #2d2d5a; padding-top: 20px;">Jika Anda tidak mendaftar di Prozone, abaikan email ini.</p>
                </div>
            ',
            'password_reset' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 100%); color: #e2e8f0; padding: 40px; border-radius: 12px;">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h1 style="color: #8b5cf6; margin: 0;">PROZONE</h1>
                    </div>
                    <h2 style="color: #e2e8f0;">Reset Password</h2>
                    <p style="color: #cbd5e1; line-height: 1.6;">Halo {name}, Anda menerima email ini karena ada permintaan reset password untuk akun Anda.</p>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{reset_link}" style="background: linear-gradient(135deg, #7c3aed, #8b5cf6); color: white; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Reset Password</a>
                    </div>
                    <p style="color: #94a3b8; font-size: 14px;">Link ini akan kedaluwarsa dalam {expiry_minutes} menit.</p>
                    <p style="color: #64748b; font-size: 12px; margin-top: 30px; border-top: 1px solid #2d2d5a; padding-top: 20px;">Jika Anda tidak meminta reset password, abaikan email ini.</p>
                </div>
            ',
            'progress_reminder' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 100%); color: #e2e8f0; padding: 40px; border-radius: 12px;">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h1 style="color: #8b5cf6; margin: 0;">PROZONE</h1>
                    </div>
                    <h2 style="color: #e2e8f0;">Kami merindukanmu, {name}!</h2>
                    <p style="color: #cbd5e1; line-height: 1.6;">Sudah {last_activity} sejak aktivitas terakhir Anda. Jangan biarkan progress belajar Anda terhenti!</p>
                    <div style="background: rgba(139, 92, 246, 0.1); padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #8b5cf6;">
                        <p style="margin: 0; color: #a78bfa;">Lanjutkan kursus: <strong>{course_name}</strong></p>
                    </div>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{dashboard_link}" style="background: linear-gradient(135deg, #7c3aed, #8b5cf6); color: white; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Lanjutkan Belajar</a>
                    </div>
                </div>
            ',
            'achievement' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 100%); color: #e2e8f0; padding: 40px; border-radius: 12px;">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h1 style="color: #8b5cf6; margin: 0;">PROZONE</h1>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 64px; margin: 20px 0;">🏆</div>
                        <h2 style="color: #fbbf24;">Selamat, {name}!</h2>
                        <p style="color: #cbd5e1; font-size: 18px;">Anda mendapatkan achievement baru:</p>
                        <div style="background: rgba(251, 191, 36, 0.1); padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid rgba(251, 191, 36, 0.3);">
                            <h3 style="color: #fbbf24; margin: 0;">{achievement_name}</h3>
                            <p style="color: #10b981; margin: 10px 0 0 0;">+{xp_earned} XP</p>
                        </div>
                    </div>
                </div>
            ',
            'course_update' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 100%); color: #e2e8f0; padding: 40px; border-radius: 12px;">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h1 style="color: #8b5cf6; margin: 0;">PROZONE</h1>
                    </div>
                    <h2 style="color: #e2e8f0;">Halo {name}!</h2>
                    <p style="color: #cbd5e1; line-height: 1.6;">Ada update baru pada kursus yang Anda ikuti:</p>
                    <div style="background: rgba(139, 92, 246, 0.1); padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #8b5cf6;">
                        <p style="margin: 0; color: #a78bfa;"><strong>{course_name}</strong></p>
                        <p style="margin: 10px 0 0 0; color: #94a3b8;">{update_type}</p>
                    </div>
                </div>
            '
        ];
        
        $html = $templates[$template] ?? '';
        
        foreach ($data as $key => $value) {
            $html = str_replace('{' . $key . '}', htmlspecialchars($value), $html);
        }
        
        return $html;
    }
}

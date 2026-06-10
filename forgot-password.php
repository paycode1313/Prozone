<?php
require_once 'config/config.php';
require_once 'classes/EmailService.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_POST) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Sesi tidak valid (CSRF Token Error). Silakan refresh halaman.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');

        if (empty($email)) {
            $error_message = 'Email harus diisi!';
        } else {
            $database = new Database();
            $db = $database->getConnection();

            // Check if email exists
            $query = "SELECT id, username, nama_lengkap, email FROM users WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                try {
                    // Use EmailService to send password reset email
                    $emailService = new EmailService($db);
                    $result = $emailService->sendPasswordResetEmail(
                        $user['id'],
                        $user['email'],
                        $user['nama_lengkap']
                    );

                    if ($result) {
                        // Check if in debug mode - show link directly
                        if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
                            $resetLink = $emailService->getLastResetLink();
                            $success_message = 'Link reset password telah dibuat!<br><br>
                                <div style="background: rgba(139, 92, 246, 0.1); padding: 1rem; border-radius: 8px; margin-top: 0.5rem;">
                                    <strong style="color: #a78bfa;">&nbsp;&#x1F527;&nbsp; Mode Development</strong><br>
                                    <small style="color: #94a3b8;">Email dilog ke /logs/emails.log</small><br><br>
                                    <a href="' . htmlspecialchars($resetLink) . '" style="color: #8b5cf6; word-break: break-all;">Klik di sini untuk reset password</a>
                                </div>';
                        } else {
                            $success_message = 'Link reset password telah dikirim ke email Anda. Silakan cek inbox atau folder spam.';
                        }
                    } else {
                        $error_message = 'Gagal mengirim email. Silakan coba lagi atau hubungi administrator.';
                    }
                } catch (PDOException $e) {
                    // If database columns don't exist, try to add them
                    try {
                        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL");
                        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token_expiry DATETIME NULL");

                        // Retry sending email
                        $emailService = new EmailService($db);
                        $result = $emailService->sendPasswordResetEmail(
                            $user['id'],
                            $user['email'],
                            $user['nama_lengkap']
                        );

                        if ($result) {
                            if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
                                $resetLink = $emailService->getLastResetLink();
                                $success_message = 'Link reset: <a href="' . htmlspecialchars($resetLink) . '" style="color: #8b5cf6;">Klik di sini</a>';
                            } else {
                                $success_message = 'Link reset password telah dikirim ke email Anda.';
                            }
                        } else {
                            $error_message = 'Gagal mengirim email reset password.';
                        }
                    } catch (Exception $ex) {
                        $error_message = 'Terjadi kesalahan sistem. Silakan hubungi administrator.';
                    }
                } catch (Exception $e) {
                    $error_message = 'Terjadi kesalahan saat mengirim email: ' . $e->getMessage();
                }
            } else {
                // Security: Don't reveal if email exists or not
                $success_message = 'Jika email terdaftar, link reset password akan dikirim ke email Anda.';
            }
        }
    }
}

$page_title       = 'Lupa Password - ' . APP_NAME;
$page_description = 'Reset password akun Prozone Anda';
$page_css         = ['components/button.css', 'components/card.css', 'components/form.css', 'components/alert.css', 'components/badge.css', 'components/auth.css'];
$body_class       = getThemeClass();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require_once 'includes/head.php'; ?>
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="<?php echo $body_class; ?> auth-body">
    <div class="auth-wrapper">
        <!-- Decorative circles -->
        <div class="auth-deco-circle auth-deco-circle--tl"></div>
        <div class="auth-deco-circle auth-deco-circle--br"></div>

        <!-- LEFT: Forgot Password Form -->
        <div class="auth-form-panel">
            <a href="login.php" class="auth-back-link-top">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                <span>Back to Login</span>
            </a>

            <div class="auth-form-title">Forgot Password?</div>
            <span class="auth-form-title-underline"></span>

            <p style="font-size:0.875rem; color:#6b7280; margin-bottom:1.5rem; line-height:1.6;">
                Enter your email address and we'll send you a link to reset your password.
            </p>

            <?php if ($error_message): ?>
                <div class="alert alert-error" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success" role="status">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <div class="auth-field">
                    <label for="email" class="auth-field-label">Email Address</label>
                    <div class="auth-field-wrap">
                        <span class="auth-field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg>
                        </span>
                        <input type="email" id="email" name="email"
                               class="auth-field-input"
                               placeholder="nama@email.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required autocomplete="email">
                    </div>
                </div>

                <button type="submit" class="auth-btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/></svg>
                    <span class="btn-label">SEND RESET LINK</span>
                </button>
            </form>

            <div class="auth-divider-sm"><span>or</span></div>

            <div class="auth-form-footer">
                Remember your password? <a href="login.php">Log in</a>
            </div>

            <div style="text-align:center">
                <a href="index.php" class="auth-home-link">&larr; Back to Home</a>
            </div>
        </div>

        <!-- RIGHT: Welcome Panel -->
        <div class="auth-welcome-panel">
            <button class="auth-welcome-close" onclick="window.location.href='index.php'" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

            <div class="auth-welcome-watermark"></div>

            <div class="auth-welcome-content">
                <a href="index.php" class="auth-welcome-brand">
                    <svg class="auth-welcome-brand-logo" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="logoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#ffffff" stop-opacity="0.9"/>
                                <stop offset="100%" stop-color="#c7d2fe" stop-opacity="0.7"/>
                            </linearGradient>
                        </defs>
                        <path d="M 25 20 L 25 75 Q 25 80 30 80 L 35 80 Q 40 80 40 75 L 40 20 Q 40 15 35 15 L 30 15 Q 25 15 25 20 Z" fill="url(#logoGrad)"/>
                        <path d="M 40 20 Q 40 15 45 15 L 60 15 Q 70 15 70 25 L 70 35 Q 70 45 60 45 L 45 45 Q 40 45 40 40 L 40 30 Q 40 25 45 25 L 60 25 Q 65 25 65 30 L 65 35 Q 65 40 60 40 L 45 40 Q 40 40 40 35 Z" fill="url(#logoGrad)"/>
                    </svg>
                    <span class="auth-welcome-brand-name"><?php echo APP_NAME; ?></span>
                </a>

                <h1 class="auth-welcome-heading" style="font-size:1.75rem;">DON'T WORRY!</h1>
                <p class="auth-welcome-text">It happens to the best of us. We'll help you get back to your account safely.</p>

                <div class="auth-welcome-features">
                    <div class="auth-welcome-feature">
                        <div class="auth-welcome-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <span class="auth-welcome-feature-text">Secure & Encrypted</span>
                    </div>
                    <div class="auth-welcome-feature">
                        <div class="auth-welcome-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <span class="auth-welcome-feature-text">Fast Reset Process</span>
                    </div>
                    <div class="auth-welcome-feature">
                        <div class="auth-welcome-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <span class="auth-welcome-feature-text">New Password Works Instantly</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
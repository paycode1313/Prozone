<?php
/**
 * Reset Password Page
 */
require_once 'config/config.php';

$token = sanitizeInput($_GET['token'] ?? '');
$step = 'verify'; // verify, reset, complete, error
$message = '';
$user_id = null;

if (empty($token)) {
    $step = 'error';
    $message = 'Token reset password tidak ditemukan.';
} else {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify token
    $query = "SELECT id, email, nama_lengkap, reset_token_expiry 
              FROM users 
              WHERE reset_token = :token";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (strtotime($user['reset_token_expiry']) < time()) {
            $step = 'error';
            $message = 'Link reset password sudah kedaluwarsa. Silakan minta link baru.';
        } else {
            $user_id = $user['id'];
            $step = 'reset';
            
            // Handle password reset form submission
            if ($_POST && isset($_POST['new_password'])) {
                if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                    $message = 'Token tidak valid. Silakan coba lagi.';
                } else {
                    $new_password = $_POST['new_password'];
                    $confirm_password = $_POST['confirm_password'];
                    
                    if (strlen($new_password) < 6) {
                        $message = 'Password minimal 6 karakter.';
                    } elseif ($new_password !== $confirm_password) {
                        $message = 'Password dan konfirmasi tidak cocok.';
                    } else {
                        // Update password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update = "UPDATE users SET 
                                   password = :password,
                                   reset_token = NULL,
                                   reset_token_expiry = NULL
                                   WHERE id = :id";
                        $stmt = $db->prepare($update);
                        $stmt->bindParam(':password', $hashed_password);
                        $stmt->bindParam(':id', $user_id);
                        
                        if ($stmt->execute()) {
                            $step = 'complete';
                            $message = 'Password berhasil diubah!';
                        } else {
                            $message = 'Gagal mengubah password. Silakan coba lagi.';
                        }
                    }
                }
            }
        }
    } else {
        $step = 'error';
        $message = 'Token tidak valid atau sudah digunakan.';
    }
}
?>
<?php
$page_title       = 'Reset Password - ' . APP_NAME;
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

        <!-- LEFT: Reset Password Form -->
        <div class="auth-form-panel">
            <a href="login.php" class="auth-back-link-top">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                <span>Back to Login</span>
            </a>

            <?php if ($step === 'error'): ?>
                <div class="auth-form-title">Reset Failed</div>
                <span class="auth-form-title-underline" style="background:linear-gradient(90deg,#ef4444,#f87171)"></span>
                <div class="alert alert-error" style="margin-top:1rem">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <div style="text-align:center; margin-top:1rem">
                    <a href="forgot-password.php" class="auth-forgot-link">&larr; Request new reset link</a>
                </div>

            <?php elseif ($step === 'complete'): ?>
                <div class="auth-form-title">Password Changed!</div>
                <span class="auth-form-title-underline" style="background:linear-gradient(90deg,#10b981,#34d399)"></span>
                <div class="alert alert-success" style="margin-top:1rem">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <a href="login.php" class="auth-btn-primary" style="text-decoration:none; margin-top:1rem;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span class="btn-label">LOG IN NOW</span>
                </a>

            <?php else: ?>
                <div class="auth-form-title">Reset Password</div>
                <span class="auth-form-title-underline"></span>

                <?php if ($message): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="resetForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                    <div class="auth-field">
                        <label for="new_password" class="auth-field-label">New Password</label>
                        <div class="auth-field-wrap">
                            <span class="auth-field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input type="password" id="new_password" name="new_password"
                                   class="auth-field-input"
                                   placeholder="Minimum 8 characters"
                                   required minlength="8" autocomplete="new-password">
                            <button type="button" class="auth-field-toggle" id="togglePassword" aria-label="Show password">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-7-10-7a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        <div class="auth-strength-bar" aria-hidden="true">
                            <div class="auth-strength-fill" id="strengthBar" style="width: 0%"></div>
                        </div>
                        <span class="auth-strength-text" id="strengthLabel"></span>
                        <ul class="auth-req-list" id="requirementList">
                            <li data-req="length">Min 8 characters</li>
                            <li data-req="case">Upper & lowercase</li>
                            <li data-req="number">Contains number</li>
                            <li data-req="special">Special character</li>
                        </ul>
                    </div>

                    <div class="auth-field">
                        <label for="confirm_password" class="auth-field-label">Confirm Password</label>
                        <div class="auth-field-wrap">
                            <span class="auth-field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </span>
                            <input type="password" id="confirm_password" name="confirm_password"
                                   class="auth-field-input"
                                   placeholder="Repeat password"
                                   required minlength="8" autocomplete="new-password">
                        </div>
                        <p class="auth-field-error" id="matchFeedback" style="display:none">Passwords don't match</p>
                    </div>

                    <button type="submit" class="auth-btn-primary" id="submitBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <span class="btn-label">CHANGE PASSWORD</span>
                        <span class="btn-spinner" aria-hidden="true"></span>
                    </button>
                </form>
            <?php endif; ?>

            <div class="auth-divider-sm"><span>or</span></div>

            <div class="auth-form-footer">
                Remember password? <a href="login.php">Log in</a>
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

                <h1 class="auth-welcome-heading" style="font-size:1.75rem;">ALMOST THERE!</h1>
                <p class="auth-welcome-text">Create a strong password to keep your account safe and secure.</p>

                <div class="auth-welcome-features">
                    <div class="auth-welcome-feature">
                        <div class="auth-welcome-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <span class="auth-welcome-feature-text">Fully Encrypted</span>
                    </div>
                    <div class="auth-welcome-feature">
                        <div class="auth-welcome-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <span class="auth-welcome-feature-text">Strong Bcrypt Hashing</span>
                    </div>
                    <div class="auth-welcome-feature">
                        <div class="auth-welcome-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <span class="auth-welcome-feature-text">Instant Activation</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($step === 'reset'): ?>
    <script>
    (function() {
        'use strict';
        var password = document.getElementById('new_password');
        var confirm = document.getElementById('confirm_password');
        var toggle = document.getElementById('togglePassword');
        var strengthBar = document.getElementById('strengthBar');
        var strengthLabel = document.getElementById('strengthLabel');
        var matchFeedback = document.getElementById('matchFeedback');
        var submitBtn = document.getElementById('submitBtn');
        var form = document.getElementById('resetForm');
        var requirements = {
            length: document.querySelector('[data-req="length"]'),
            case: document.querySelector('[data-req="case"]'),
            number: document.querySelector('[data-req="number"]'),
            special: document.querySelector('[data-req="special"]')
        };

        if (toggle) {
            toggle.addEventListener('click', function() {
                var isPw = password.type === 'password';
                password.type = isPw ? 'text' : 'password';
                confirm.type = isPw ? 'text' : 'password';
                toggle.querySelector('.eye-open').style.display = isPw ? 'none' : '';
                toggle.querySelector('.eye-closed').style.display = isPw ? '' : 'none';
            });
        }

        function updateStrength() {
            var v = password.value;
            var checks = {
                length: v.length >= 8,
                case: /[A-Z]/.test(v) && /[a-z]/.test(v),
                number: /\d/.test(v),
                special: /[^A-Za-z0-9]/.test(v)
            };
            var score = Object.values(checks).filter(Boolean).length;
            var labels = ['Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];
            var classes = ['weak', 'weak', 'fair', 'good', 'strong'];
            var percents = [0, 25, 50, 75, 100];

            Object.keys(checks).forEach(function(k) {
                requirements[k].classList.toggle('met', checks[k]);
            });

            if (v.length === 0) {
                strengthBar.style.width = '0%';
                strengthBar.className = 'auth-strength-fill';
                strengthLabel.textContent = '';
            } else {
                strengthBar.style.width = percents[score] + '%';
                strengthBar.className = 'auth-strength-fill ' + classes[score];
                strengthLabel.textContent = 'Strength: ' + labels[score];
            }
        }

        function updateMatch() {
            if (confirm.value.length === 0) {
                matchFeedback.style.display = 'none';
                confirm.classList.remove('is-invalid');
                return;
            }
            if (password.value !== confirm.value) {
                matchFeedback.style.display = '';
                confirm.classList.add('is-invalid');
            } else {
                matchFeedback.style.display = 'none';
                confirm.classList.remove('is-invalid');
            }
        }

        password.addEventListener('input', function() { updateStrength(); updateMatch(); });
        confirm.addEventListener('input', updateMatch);

        if (form) {
            form.addEventListener('submit', function(e) {
                if (password.value !== confirm.value) {
                    e.preventDefault();
                    matchFeedback.style.display = '';
                    confirm.classList.add('is-invalid');
                    confirm.focus();
                    return;
                }
                submitBtn.classList.add('btn-loading');
                submitBtn.disabled = true;
            });
        }
    })();
    </script>
    <?php endif; ?>
</body>
</html>

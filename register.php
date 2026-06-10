<?php
require_once 'config/config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$page_title       = 'Daftar';
$page_description = 'Daftar di ' . APP_NAME . ' - Platform pembelajaran coding interaktif';
$page_css         = ['components/button.css', 'components/card.css', 'components/form.css', 'components/alert.css', 'components/badge.css', 'components/auth.css'];
$body_class       = getThemeClass();

$errors = $_SESSION['register_errors'] ?? [];
$old = $_SESSION['register_old'] ?? [];
$success = $_SESSION['register_success'] ?? '';
unset($_SESSION['register_errors'], $_SESSION['register_old'], $_SESSION['register-success']);

// Compute strength for repopulation (server has no zxcvbn)
$password_value = $old['password'] ?? '';
$hasMinLength = strlen($password_value) >= 8;
$hasUpperLower = preg_match('/[A-Z]/', $password_value) && preg_match('/[a-z]/', $password_value);
$hasNumber = preg_match('/\d/', $password_value);
$hasSpecial = preg_match('/[^A-Za-z0-9]/', $password_value);
$strengthScore = (int)$hasMinLength + (int)$hasUpperLower + (int)$hasNumber + (int)$hasSpecial;
$strengthPercent = ($strengthScore / 4) * 100;
$strengthLabel = ['Lemah', 'Lemah', 'Sedang', 'Kuat', 'Sangat Kuat'][$strengthScore] ?? 'Lemah';
$strengthClass = ['weak', 'weak', 'fair', 'good', 'strong'][$strengthScore] ?? 'weak';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require_once 'includes/head.php'; ?>
</head>
<body class="<?php echo $body_class; ?> auth-body">
    <div class="auth-wrapper auth-wrapper--register">
        <!-- Decorative circles -->
        <div class="auth-deco-circle auth-deco-circle--tl"></div>
        <div class="auth-deco-circle auth-deco-circle--br"></div>

        <!-- LEFT: Welcome Panel (blue gradient) -->
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

                <h1 class="auth-welcome-heading">WELCOME!</h1>
                <p class="auth-welcome-text">Already have an account? Log in and continue your coding journey.</p>

                <a href="login.php" class="auth-btn-signup">LOG IN</a>

                <div class="auth-welcome-stats">
                    <div class="auth-welcome-stat">
                        <strong>10K+</strong>
                        <span>Students</span>
                    </div>
                    <div class="auth-welcome-stat-sep"></div>
                    <div class="auth-welcome-stat">
                        <strong>50+</strong>
                        <span>Courses</span>
                    </div>
                    <div class="auth-welcome-stat-sep"></div>
                    <div class="auth-welcome-stat">
                        <strong>4.9</strong>
                        <span>Rating</span>
                    </div>
                </div>

                <div class="auth-welcome-features">
                    <div class="auth-welcome-feature">
                        <div class="auth-welcome-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        </div>
                        <span class="auth-welcome-feature-text">50+ Interactive Courses</span>
                    </div>
                    <div class="auth-welcome-feature">
                        <div class="auth-welcome-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                        </div>
                        <span class="auth-welcome-feature-text">XP, Levels & Achievements</span>
                    </div>
                    <div class="auth-welcome-feature">
                        <div class="auth-welcome-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                        </div>
                        <span class="auth-welcome-feature-text">Real-time Code Playground</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Registration Form (white card) -->
        <div class="auth-form-panel">
            <div class="auth-form-title">Create Account</div>
            <span class="auth-form-title-underline"></span>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="status">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error" role="alert">
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm" novalidate>
                <div class="auth-form-row-2">
                    <div class="auth-field">
                        <label for="nama_lengkap" class="auth-field-label">Full Name</label>
                        <div class="auth-field-wrap">
                            <span class="auth-field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </span>
                            <input type="text" id="nama_lengkap" name="nama_lengkap"
                                   class="auth-field-input <?php echo isset($errors['nama_lengkap']) ? 'is-invalid' : ''; ?>"
                                   placeholder="John Doe"
                                   value="<?php echo htmlspecialchars($old['nama_lengkap'] ?? ''); ?>"
                                   required autocomplete="name" minlength="3" maxlength="100">
                        </div>
                        <?php if (isset($errors['nama_lengkap'])): ?>
                            <p class="auth-field-error"><?php echo htmlspecialchars($errors['nama_lengkap']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="auth-field">
                        <label for="username" class="auth-field-label">Username</label>
                        <div class="auth-field-wrap">
                            <span class="auth-field-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                            </span>
                            <input type="text" id="username" name="username"
                                   class="auth-field-input <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                                   placeholder="johndoe"
                                   value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>"
                                   required autocomplete="username" pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="30">
                        </div>
                        <?php if (isset($errors['username'])): ?>
                            <p class="auth-field-error"><?php echo htmlspecialchars($errors['username']); ?></p>
                        <?php else: ?>
                            <p class="auth-field-help">3-30 chars, letters, numbers & underscore only</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="email" class="auth-field-label">Email</label>
                    <div class="auth-field-wrap">
                        <span class="auth-field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg>
                        </span>
                        <input type="email" id="email" name="email"
                               class="auth-field-input <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                               placeholder="nama@email.com"
                               value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>"
                               required autocomplete="email">
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <p class="auth-field-error"><?php echo htmlspecialchars($errors['email']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="auth-field">
                    <label for="nomor_hp" class="auth-field-label">Phone (optional)</label>
                    <div class="auth-field-wrap">
                        <span class="auth-field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>
                        </span>
                        <input type="tel" id="nomor_hp" name="nomor_hp"
                               class="auth-field-input <?php echo isset($errors['nomor_hp']) ? 'is-invalid' : ''; ?>"
                               placeholder="08xxxxxxxxxx"
                               value="<?php echo htmlspecialchars($old['nomor_hp'] ?? ''); ?>"
                               pattern="[0-9+\-\s\(\)]{8,20}" maxlength="20">
                    </div>
                    <?php if (isset($errors['nomor_hp'])): ?>
                        <p class="auth-field-error"><?php echo htmlspecialchars($errors['nomor_hp']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="auth-field">
                    <label for="password" class="auth-field-label">Password</label>
                    <div class="auth-field-wrap">
                        <span class="auth-field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input type="password" id="password" name="password"
                               class="auth-field-input <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                               placeholder="Minimum 8 characters"
                               required minlength="8" autocomplete="new-password">
                        <button type="button" class="auth-field-toggle" id="togglePassword" aria-label="Show password">
                            <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-10-7-10-7a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                    <div class="auth-strength-bar" aria-hidden="true">
                        <div class="auth-strength-fill <?php echo $password_value ? $strengthClass : ''; ?>" id="strengthBar" style="width: <?php echo $password_value ? $strengthPercent : 0; ?>%"></div>
                    </div>
                    <span class="auth-strength-text" id="strengthLabel"><?php echo $password_value ? $strengthLabel : ''; ?></span>
                    <ul class="auth-req-list" id="requirementList">
                        <li data-req="length" class="<?php echo $hasMinLength ? 'met' : ''; ?>">Min 8 characters</li>
                        <li data-req="case" class="<?php echo $hasUpperLower ? 'met' : ''; ?>">Upper & lowercase</li>
                        <li data-req="number" class="<?php echo $hasNumber ? 'met' : ''; ?>">Contains number</li>
                        <li data-req="special" class="<?php echo $hasSpecial ? 'met' : ''; ?>">Special character</li>
                    </ul>
                    <?php if (isset($errors['password'])): ?>
                        <p class="auth-field-error"><?php echo htmlspecialchars($errors['password']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="auth-field">
                    <label for="password_confirm" class="auth-field-label">Confirm Password</label>
                    <div class="auth-field-wrap">
                        <span class="auth-field-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </span>
                        <input type="password" id="password_confirm" name="password_confirm"
                               class="auth-field-input <?php echo isset($errors['password_confirm']) ? 'is-invalid' : ''; ?>"
                               placeholder="Repeat password"
                               required minlength="8" autocomplete="new-password">
                    </div>
                    <p class="auth-field-error" id="matchFeedback" style="display:none">Passwords don't match</p>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <p class="auth-field-error"><?php echo htmlspecialchars($errors['password_confirm']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="auth-field">
                    <label class="auth-checkbox-row">
                        <input type="checkbox" name="terms" id="terms" required <?php echo !empty($old['terms']) ? 'checked' : ''; ?>>
                        <span>I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a></span>
                    </label>
                    <?php if (isset($errors['terms'])): ?>
                        <p class="auth-field-error"><?php echo htmlspecialchars($errors['terms']); ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="auth-btn-primary" id="submitBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    <span class="btn-label">CREATE ACCOUNT</span>
                    <span class="btn-spinner" aria-hidden="true"></span>
                </button>
            </form>

            <div class="auth-divider-sm"><span>or</span></div>

            <div class="auth-form-footer">
                Already have an account? <a href="login.php">Log in here</a>
            </div>

            <div style="text-align:center">
                <a href="index.php" class="auth-home-link">&larr; Back to Home</a>
            </div>
        </div>
    </div>

    <script>
    (function() {
        'use strict';

        var form = document.getElementById('registerForm');
        var password = document.getElementById('password');
        var confirm = document.getElementById('password_confirm');
        var toggle = document.getElementById('togglePassword');
        var strengthBar = document.getElementById('strengthBar');
        var strengthLabel = document.getElementById('strengthLabel');
        var matchFeedback = document.getElementById('matchFeedback');
        var submitBtn = document.getElementById('submitBtn');
        var requirements = {
            length: document.querySelector('[data-req="length"]'),
            case: document.querySelector('[data-req="case"]'),
            number: document.querySelector('[data-req="number"]'),
            special: document.querySelector('[data-req="special"]')
        };

        toggle.addEventListener('click', function() {
            var isPassword = password.type === 'password';
            password.type = isPassword ? 'text' : 'password';
            confirm.type = isPassword ? 'text' : 'password';
            toggle.querySelector('.eye-open').style.display = isPassword ? 'none' : '';
            toggle.querySelector('.eye-closed').style.display = isPassword ? '' : 'none';
            toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });

        function updateStrength() {
            var v = password.value;
            var checks = {
                length: v.length >= 8,
                case: /[A-Z]/.test(v) && /[a-z]/.test(v),
                number: /\d/.test(v),
                special: /[^A-Za-z0-9]/.test(v)
            };
            var score = Object.values(checks).filter(Boolean).length;
            var labels = ['Lemah', 'Lemah', 'Sedang', 'Kuat', 'Sangat Kuat'];
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

        form.addEventListener('submit', function(e) {
            if (password.value !== confirm.value) {
                e.preventDefault();
                matchFeedback.style.display = '';
                confirm.classList.add('is-invalid');
                confirm.focus();
                return;
            }
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
                return;
            }
            submitBtn.classList.add('btn-loading');
            submitBtn.disabled = true;
        });

        updateStrength();
    })();
    </script>
</body>
</html>

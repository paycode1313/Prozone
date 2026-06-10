<?php
/**
 * Email Verification Page
 */
require_once 'config/config.php';

$message = '';
$message_type = '';

if (isset($_GET['token'])) {
    $token = sanitizeInput($_GET['token']);
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Find user with this token
    $query = "SELECT id, email, nama_lengkap, email_verification_expires 
              FROM users 
              WHERE email_verification_token = :token";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if token expired
        if (strtotime($user['email_verification_expires']) < time()) {
            $message = 'Link verifikasi sudah kedaluwarsa. Silakan minta link baru.';
            $message_type = 'error';
        } else {
            // Verify email
            $update = "UPDATE users SET 
                       email_verified = 1, 
                       email_verified_at = NOW(),
                       email_verification_token = NULL,
                       email_verification_expires = NULL
                       WHERE id = :id";
            $stmt = $db->prepare($update);
            $stmt->bindParam(':id', $user['id']);
            
            if ($stmt->execute()) {
                $message = 'Email berhasil diverifikasi! Anda sekarang bisa login.';
                $message_type = 'success';
            } else {
                $message = 'Gagal memverifikasi email. Silakan coba lagi.';
                $message_type = 'error';
            }
        }
    } else {
        $message = 'Token tidak valid atau sudah digunakan.';
        $message_type = 'error';
    }
} else {
    $message = 'Token verifikasi tidak ditemukan.';
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <title>Verifikasi Email - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0a0a1a 0%, #1a1a2e 50%, #0f0f23 100%);
            padding: 1.5rem;
        }
        .verify-container {
            max-width: 500px;
            width: 100%;
            text-align: center;
            background: rgba(30, 30, 63, 0.8);
            border-radius: 16px;
            padding: 3rem 2rem;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }
        .verify-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }
        .verify-icon.success { color: #10b981; }
        .verify-icon.error { color: #ef4444; }
        .verify-title {
            color: #e2e8f0;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .verify-message {
            color: #94a3b8;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn-login {
            display: inline-block;
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3);
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-icon <?php echo $message_type; ?>">
            <?php echo $message_type === 'success' ? '✓' : '✗'; ?>
        </div>
        <h1 class="verify-title">
            <?php echo $message_type === 'success' ? 'Email Terverifikasi!' : 'Verifikasi Gagal'; ?>
        </h1>
        <p class="verify-message"><?php echo $message; ?></p>
        <a href="login.php" class="btn-login">Ke Halaman Login</a>
    </div>
</body>
</html>

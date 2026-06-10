<?php
require_once 'config/config.php';
require_once 'includes/icons.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('Akses Ditolak - ' . APP_NAME, 'Anda tidak memiliki akses ke halaman ini', 'unauthorized, access denied'); ?>
    <title>Akses Ditolak - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <link rel="stylesheet" href="assets/css/ui-enhancements.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0a0a1a 0%, #1a1a2e 50%, #0f0f23 100%);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 30% 70%, rgba(239, 68, 68, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 70% 30%, rgba(139, 92, 246, 0.08) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .error-container {
            max-width: 600px;
            width: 100%;
            text-align: center;
            position: relative;
            z-index: 2;
            animation: fadeInUp 0.8s ease;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .error-icon {
            width: 100px;
            height: 100px;
            background: rgba(239, 68, 68, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: #ef4444;
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.3); }
            50% { transform: scale(1.05); box-shadow: 0 0 30px rgba(239, 68, 68, 0.2); }
        }
        
        .error-code {
            font-size: 5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            line-height: 1;
        }
        
        .error-title {
            font-size: 1.75rem;
            color: #f1f5f9;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .error-message {
            color: #94a3b8;
            font-size: 1rem;
            margin-bottom: 2rem;
            line-height: 1.7;
            max-width: 440px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .error-actions .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
        }
        
        .error-actions .btn-primary {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
        
        .error-info {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 0.75rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .error-info p {
            color: #f87171;
            font-size: 0.875rem;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <?php icon('user', 48); ?>
        </div>
        <div class="error-code">403</div>
        <h1 class="error-title">Akses Ditolak</h1>
        <p class="error-message">
            Maaf, Anda tidak memiliki izin untuk mengakses halaman ini. Halaman ini mungkin memerlukan hak akses khusus.
        </p>
        <div class="error-actions">
            <a href="dashboard.php" class="btn btn-primary">
                <?php icon('dashboard', 16); ?> Dashboard
            </a>
            <a href="logout.php" class="btn btn-secondary">
                <?php icon('settings', 16); ?> Logout
            </a>
        </div>
        <div class="error-info">
            <p><?php icon('clipboard', 14); ?> Jika Anda yakin seharusnya memiliki akses, silakan hubungi administrator.</p>
        </div>
    </div>
</body>
</html>

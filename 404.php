<?php
require_once 'config/config.php';
require_once 'includes/icons.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('404 - Halaman Tidak Ditemukan', 'Halaman yang Anda cari tidak ditemukan', 'error, 404, not found'); ?>
    <title>404 - Halaman Tidak Ditemukan - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/ui-enhancements.css">
    <style>
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 50%, #0f0f23 100%);
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .error-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(139, 92, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(124, 58, 237, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .error-content {
            text-align: center;
            max-width: 600px;
            position: relative;
            z-index: 2;
            animation: fadeInUp 0.8s ease;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .error-illustration {
            width: 200px;
            height: 200px;
            margin: 0 auto 2rem;
            position: relative;
        }
        
        .error-code {
            font-size: 10rem;
            font-weight: 800;
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 50%, #c4b5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            line-height: 1;
            text-shadow: 0 0 80px rgba(139, 92, 246, 0.5);
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.9; transform: scale(1.02); }
        }
        
        .error-title {
            font-size: 2rem;
            color: #f1f5f9;
            margin-bottom: 1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        
        .error-message {
            color: #94a3b8;
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
            line-height: 1.7;
            max-width: 480px;
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
            padding: 0.875rem 2rem;
            font-size: 0.95rem;
            border-radius: 0.75rem;
        }
        
        .error-actions .btn-primary {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            box-shadow: 0 8px 32px rgba(139, 92, 246, 0.35);
        }
        
        .error-actions .btn-primary:hover {
            box-shadow: 0 12px 40px rgba(139, 92, 246, 0.45);
            transform: translateY(-2px);
        }
        
        .error-actions .btn-secondary {
            background: rgba(139, 92, 246, 0.1);
            border: 2px solid rgba(139, 92, 246, 0.3);
            color: #a78bfa;
        }
        
        .error-actions .btn-secondary:hover {
            background: rgba(139, 92, 246, 0.2);
            border-color: rgba(139, 92, 246, 0.5);
        }
        
        .floating-elements {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }
        
        .floating-icon {
            position: absolute;
            color: rgba(139, 92, 246, 0.15);
            animation: float 4s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }
        
        .floating-icon:nth-child(1) { top: 10%; left: 5%; animation-delay: 0s; }
        .floating-icon:nth-child(2) { top: 15%; right: 10%; animation-delay: 1s; }
        .floating-icon:nth-child(3) { bottom: 20%; left: 8%; animation-delay: 2s; }
        .floating-icon:nth-child(4) { bottom: 25%; right: 5%; animation-delay: 0.5s; }
        .floating-icon:nth-child(5) { top: 40%; left: 3%; animation-delay: 1.5s; }
        .floating-icon:nth-child(6) { top: 35%; right: 3%; animation-delay: 2.5s; }
        
        .search-suggestion {
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(139, 92, 246, 0.15);
        }
        
        .search-suggestion p {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .quick-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .quick-links a {
            color: #8b5cf6;
            text-decoration: none;
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 9999px;
            transition: all 0.3s ease;
        }
        
        .quick-links a:hover {
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
        }
        
        @media (max-width: 640px) {
            .error-code {
                font-size: 6rem;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .error-message {
                font-size: 1rem;
            }
            
            .error-actions {
                flex-direction: column;
            }
            
            .error-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="floating-elements">
            <div class="floating-icon"><?php icon('code', 48); ?></div>
            <div class="floating-icon"><?php icon('book', 40); ?></div>
            <div class="floating-icon"><?php icon('rocket', 56); ?></div>
            <div class="floating-icon"><?php icon('terminal', 44); ?></div>
            <div class="floating-icon"><?php icon('globe', 52); ?></div>
            <div class="floating-icon"><?php icon('monitor', 48); ?></div>
        </div>
        
        <div class="error-content">
            <div class="error-code">404</div>
            <h1 class="error-title">Halaman Tidak Ditemukan</h1>
            <p class="error-message">
                Ups! Sepertinya halaman yang Anda cari tidak ada atau telah dipindahkan. 
                Jangan khawatir, mari kita bantu Anda kembali ke jalur yang benar.
            </p>
            
            <div class="error-actions">
                <a href="dashboard.php" class="btn btn-primary">
                    <?php icon('dashboard', 18); ?> Dashboard
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <?php icon('globe', 18); ?> Beranda
                </a>
            </div>
            
            <div class="search-suggestion">
                <p>Atau coba kunjungi halaman populer:</p>
                <div class="quick-links">
                    <a href="courses.php"><?php icon('book', 14); ?> Kursus</a>
                    <a href="leaderboard.php"><?php icon('trophy', 14); ?> Leaderboard</a>
                    <a href="playground.php"><?php icon('code', 14); ?> Playground</a>
                    <a href="features.php"><?php icon('star', 14); ?> Fitur</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


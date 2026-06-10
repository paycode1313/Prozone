<?php
require_once 'config/config.php';
requireLogin();
requireRole(['student']);
require_once 'includes/icons.php';

require_once 'models/Course.php';
require_once 'models/Enrollment.php';

$database = new Database();
$db = $database->getConnection();

$course = new Course($db);
$enrollment = new Enrollment($db);

// Get user's completed courses
$query = "SELECT c.*, e.completed_at, e.progress_percent
          FROM enrollments e
          JOIN courses c ON e.course_id = c.id
          WHERE e.user_id = :user_id 
          AND e.status = 'completed'
          ORDER BY e.completed_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();

$certificates = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $certificates[] = $row;
}

// Get user stats
$total_certificates = count($certificates);
$total_xp_from_certs = 0;
foreach ($certificates as $cert) {
    $total_xp_from_certs += $cert['xp_reward'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('Sertifikat - ' . APP_NAME, 'Lihat dan download sertifikat kursus yang telah diselesaikan', 'certificates, sertifikat, completion'); ?>
    <title>Sertifikat - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/ui-enhancements.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <link rel="stylesheet" href="assets/css/glassmorphism.css">
    <!-- Libraries for PDF Generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        /* Hero Section */
        .certificates-hero {
            background: linear-gradient(135deg, 
                rgba(139, 92, 246, 0.2) 0%, 
                rgba(59, 130, 246, 0.15) 50%,
                rgba(236, 72, 153, 0.1) 100%);
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }
        .certificates-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            animation: float-bg 6s ease-in-out infinite;
        }
        .certificates-hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            animation: float-bg 8s ease-in-out infinite reverse;
        }
        @keyframes float-bg {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(20px, -20px); }
        }
        .hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 2.5rem;
            flex-wrap: wrap;
        }
        .hero-icon-wrapper {
            position: relative;
        }
        .hero-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            box-shadow: 0 0 50px rgba(251, 191, 36, 0.4);
            animation: pulse-gold 2s ease-in-out infinite;
        }
        @keyframes pulse-gold {
            0%, 100% { box-shadow: 0 0 50px rgba(251, 191, 36, 0.4); }
            50% { box-shadow: 0 0 70px rgba(251, 191, 36, 0.6); }
        }
        .hero-sparkles {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
        }
        .sparkle {
            position: absolute;
            font-size: 1rem;
            animation: sparkle 2s ease-in-out infinite;
        }
        .sparkle:nth-child(1) { top: -10px; left: 10px; animation-delay: 0s; }
        .sparkle:nth-child(2) { top: 0; right: -5px; animation-delay: 0.3s; }
        .sparkle:nth-child(3) { bottom: 5px; right: 5px; animation-delay: 0.6s; }
        .sparkle:nth-child(4) { bottom: -5px; left: 20px; animation-delay: 0.9s; }
        @keyframes sparkle {
            0%, 100% { opacity: 0; transform: scale(0.5); }
            50% { opacity: 1; transform: scale(1); }
        }
        .hero-info {
            flex: 1;
        }
        .hero-title {
            font-size: 2rem;
            font-weight: 800;
            color: #e2e8f0;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #fff 0%, #e0e7ff 50%, #c4b5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-subtitle {
            color: #94a3b8;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }
        .hero-stats {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .hero-stat {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(30, 30, 55, 0.5);
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }
        .hero-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .hero-stat-icon.gold { background: linear-gradient(135deg, #fbbf24, #f59e0b); }
        .hero-stat-icon.purple { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
        .hero-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #e2e8f0;
        }
        .hero-stat-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Certificate Grid */
        .certificates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 1.5rem;
            padding: 0;
        }

        /* Certificate Card - Visual Certificate Style */
        .certificate-card {
            background: linear-gradient(145deg, rgba(30, 30, 55, 0.8) 0%, rgba(40, 40, 70, 0.6) 100%);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(139, 92, 246, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            animation: fadeInUp 0.5s ease-out backwards;
        }
        .certificate-card:nth-child(1) { animation-delay: 0.1s; }
        .certificate-card:nth-child(2) { animation-delay: 0.2s; }
        .certificate-card:nth-child(3) { animation-delay: 0.3s; }
        .certificate-card:nth-child(4) { animation-delay: 0.4s; }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .certificate-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: rgba(251, 191, 36, 0.5);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.3),
                0 0 30px rgba(251, 191, 36, 0.1);
        }

        /* Certificate Preview (Visual Cert) */
        .certificate-preview {
            background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
            padding: 1.5rem;
            position: relative;
            border-bottom: 4px solid #fbbf24;
        }
        .certificate-preview::before {
            content: '';
            position: absolute;
            top: 8px;
            left: 8px;
            right: 8px;
            bottom: 8px;
            border: 2px dashed rgba(251, 191, 36, 0.3);
            border-radius: 8px;
            pointer-events: none;
        }
        .cert-header {
            text-align: center;
            margin-bottom: 0.75rem;
        }
        .cert-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #1a1a2e;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .cert-title {
            font-size: 1rem;
            font-weight: 800;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 0.5rem;
        }
        .cert-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #fbbf24, transparent);
            margin: 0.75rem 0;
        }
        .cert-recipient {
            text-align: center;
        }
        .cert-label {
            font-size: 0.65rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .cert-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a1a2e;
            font-family: 'Georgia', serif;
            margin: 0.25rem 0;
        }
        .cert-course {
            font-size: 0.85rem;
            color: #7c3aed;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        .cert-seal {
            position: absolute;
            bottom: 10px;
            right: 15px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }
        .cert-seal::before {
            content: '✓';
            color: white;
            font-size: 1.25rem;
            font-weight: bold;
        }

        /* Certificate Info Section */
        .certificate-info-section {
            padding: 1.25rem;
        }
        .certificate-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .certificate-level {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            background: rgba(139, 92, 246, 0.15);
            color: #a78bfa;
            padding: 0.375rem 0.75rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .certificate-date {
            color: #64748b;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }
        .certificate-actions {
            display: flex;
            gap: 0.75rem;
        }
        .btn-download {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #1a1a2e;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3);
        }
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(251, 191, 36, 0.4);
        }
        .btn-share {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: rgba(139, 92, 246, 0.15);
            color: #a78bfa;
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-share:hover {
            background: rgba(139, 92, 246, 0.25);
            border-color: rgba(139, 92, 246, 0.5);
            transform: translateY(-2px);
        }

        /* Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            background: linear-gradient(145deg, rgba(30, 30, 55, 0.5) 0%, rgba(40, 40, 70, 0.3) 100%);
            border-radius: 24px;
            border: 2px dashed rgba(139, 92, 246, 0.3);
        }
        .empty-icon-wrapper {
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(167, 139, 250, 0.05) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .empty-icon {
            font-size: 4rem;
            opacity: 0.5;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .empty-title {
            color: #e2e8f0;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .empty-text {
            color: #94a3b8;
            font-size: 1rem;
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        .btn-explore {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.3);
        }
        .btn-explore:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(139, 92, 246, 0.4);
        }

        /* Share Modal */
        .share-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }
        .share-modal.active {
            display: flex;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .share-modal-content {
            background: linear-gradient(145deg, #1e1e3f 0%, #252550 100%);
            border-radius: 20px;
            padding: 2rem;
            max-width: 450px;
            width: 90%;
            border: 1px solid rgba(139, 92, 246, 0.3);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        .share-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .share-modal-header h3 {
            color: #e2e8f0;
            margin: 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .share-modal-close {
            background: rgba(239, 68, 68, 0.15);
            border: none;
            color: #f87171;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 1.25rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .share-modal-close:hover {
            background: rgba(239, 68, 68, 0.25);
            transform: rotate(90deg);
        }
        .share-modal-desc {
            color: #94a3b8;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        .share-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        .share-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 1.25rem 0.75rem;
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            color: #e2e8f0;
        }
        .share-option:hover {
            background: rgba(139, 92, 246, 0.2);
            transform: translateY(-3px);
            border-color: rgba(139, 92, 246, 0.4);
        }
        .share-option svg {
            width: 28px;
            height: 28px;
        }
        .share-option span {
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Hidden Certificate Template for PDF */
        #certificate-template {
            width: 1056px;
            height: 816px;
            padding: 0;
            background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
            color: #1a1a2e;
            position: fixed;
            top: -9999px;
            left: -9999px;
            font-family: 'Georgia', serif;
        }
        .cert-border {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 3px solid #fbbf24;
            border-radius: 10px;
        }
        .cert-inner-border {
            position: absolute;
            top: 30px;
            left: 30px;
            right: 30px;
            bottom: 30px;
            border: 1px solid rgba(251, 191, 36, 0.5);
            border-radius: 8px;
        }
        .cert-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 60px;
            text-align: center;
        }
        .cert-logo {
            font-size: 28px;
            font-weight: bold;
            color: #7c3aed;
            margin-bottom: 10px;
            letter-spacing: 3px;
        }
        .cert-main-title {
            font-size: 52px;
            font-weight: bold;
            color: #1a1a2e;
            text-transform: uppercase;
            letter-spacing: 8px;
            margin-bottom: 20px;
        }
        .cert-subtitle {
            font-size: 18px;
            color: #64748b;
            margin-bottom: 30px;
        }
        .cert-recipient-name {
            font-size: 42px;
            font-weight: bold;
            color: #1a1a2e;
            border-bottom: 3px solid #fbbf24;
            padding-bottom: 10px;
            margin-bottom: 20px;
            min-width: 500px;
            display: inline-block;
        }
        .cert-description {
            font-size: 18px;
            color: #64748b;
            margin-bottom: 15px;
        }
        .cert-course-name {
            font-size: 32px;
            font-weight: bold;
            color: #7c3aed;
            margin-bottom: 40px;
        }
        .cert-date-text {
            font-size: 16px;
            color: #64748b;
        }
        .cert-footer {
            position: absolute;
            bottom: 80px;
            left: 100px;
            right: 100px;
            display: flex;
            justify-content: space-between;
        }
        .cert-signature {
            text-align: center;
        }
        .cert-signature-line {
            width: 200px;
            border-top: 2px solid #1a1a2e;
            margin-bottom: 10px;
        }
        .cert-signature-text {
            font-size: 14px;
            color: #64748b;
        }
        .cert-seal-pdf {
            position: absolute;
            bottom: 60px;
            right: 80px;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.4);
        }
        .cert-seal-pdf span:first-child {
            font-size: 24px;
        }
        .cert-seal-pdf span:last-child {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .cert-corner {
            position: absolute;
            width: 80px;
            height: 80px;
        }
        .cert-corner-tl { top: 40px; left: 40px; border-top: 4px solid #fbbf24; border-left: 4px solid #fbbf24; }
        .cert-corner-tr { top: 40px; right: 40px; border-top: 4px solid #fbbf24; border-right: 4px solid #fbbf24; }
        .cert-corner-bl { bottom: 40px; left: 40px; border-bottom: 4px solid #fbbf24; border-left: 4px solid #fbbf24; }
        .cert-corner-br { bottom: 40px; right: 40px; border-bottom: 4px solid #fbbf24; border-right: 4px solid #fbbf24; }

        /* Responsive */
        @media (max-width: 768px) {
            .certificates-hero {
                padding: 1.5rem;
            }
            .hero-content {
                flex-direction: column;
                text-align: center;
            }
            .hero-icon {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }
            .hero-title {
                font-size: 1.5rem;
            }
            .hero-stats {
                justify-content: center;
            }
            .certificates-grid {
                grid-template-columns: 1fr;
            }
            .share-options {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php require_once 'navbar.php'; ?>

    <!-- Hidden Certificate Template for PDF -->
    <div id="certificate-template">
        <div class="cert-border"></div>
        <div class="cert-inner-border"></div>
        <div class="cert-corner cert-corner-tl"></div>
        <div class="cert-corner cert-corner-tr"></div>
        <div class="cert-corner cert-corner-bl"></div>
        <div class="cert-corner cert-corner-br"></div>
        <div class="cert-content">
            <div class="cert-logo"><?php echo APP_NAME; ?></div>
            <div class="cert-main-title">Certificate</div>
            <div class="cert-subtitle">of Completion</div>
            <div class="cert-description">This is to certify that</div>
            <div class="cert-recipient-name" id="cert-recipient"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></div>
            <div class="cert-description">has successfully completed the course</div>
            <div class="cert-course-name" id="cert-course">Course Name</div>
            <div class="cert-date-text">Completed on: <span id="cert-date">Date</span></div>
        </div>
        <div class="cert-footer">
            <div class="cert-signature">
                <div class="cert-signature-line"></div>
                <div class="cert-signature-text">Course Instructor</div>
            </div>
            <div class="cert-signature">
                <div class="cert-signature-line"></div>
                <div class="cert-signature-text"><?php echo APP_NAME; ?> Team</div>
            </div>
        </div>
        <div class="cert-seal-pdf">
            <span>✓</span>
            <span>Verified</span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="dashboard-main-container">
        <div class="dashboard-content">
            <div class="page-wrapper">
                <!-- Hero Section -->
                <div class="certificates-hero">
                    <div class="hero-content">
                        <div class="hero-icon-wrapper">
                            <div class="hero-icon">🏆</div>
                            <div class="hero-sparkles">
                                <span class="sparkle">✨</span>
                                <span class="sparkle">⭐</span>
                                <span class="sparkle">✨</span>
                                <span class="sparkle">⭐</span>
                            </div>
                        </div>
                        <div class="hero-info">
                            <h1 class="hero-title">Sertifikat Anda</h1>
                            <p class="hero-subtitle">Kumpulkan sertifikat dari setiap kursus yang telah Anda selesaikan sebagai bukti pencapaian!</p>
                            <div class="hero-stats">
                                <div class="hero-stat">
                                    <div class="hero-stat-icon gold">
                                        <?php icon('award', 20); ?>
                                    </div>
                                    <div>
                                        <div class="hero-stat-value"><?php echo $total_certificates; ?></div>
                                        <div class="hero-stat-label">Sertifikat</div>
                                    </div>
                                </div>
                                <div class="hero-stat">
                                    <div class="hero-stat-icon purple">
                                        <?php icon('star', 20); ?>
                                    </div>
                                    <div>
                                        <div class="hero-stat-value"><?php echo number_format($total_xp_from_certs); ?></div>
                                        <div class="hero-stat-label">XP Earned</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Certificates Grid -->
                <div class="certificates-grid">
                    <?php if (empty($certificates)): ?>
                        <div class="empty-state">
                            <div class="empty-icon-wrapper">
                                <div class="empty-icon">📜</div>
                            </div>
                            <h2 class="empty-title">Belum Ada Sertifikat</h2>
                            <p class="empty-text">Selesaikan kursus pertama Anda untuk mendapatkan sertifikat penyelesaian yang dapat diunduh dan dibagikan!</p>
                            <a href="courses.php" class="btn-explore">
                                <?php icon('book', 18); ?>
                                Jelajahi Kursus
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($certificates as $cert): ?>
                            <div class="certificate-card">
                                <!-- Certificate Preview -->
                                <div class="certificate-preview">
                                    <div class="cert-header">
                                        <div class="cert-badge">
                                            <?php icon('award', 12); ?>
                                            Certificate
                                        </div>
                                        <div class="cert-title">Sertifikat Kelulusan</div>
                                    </div>
                                    <div class="cert-divider"></div>
                                    <div class="cert-recipient">
                                        <div class="cert-label">Diberikan kepada</div>
                                        <div class="cert-name"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></div>
                                        <div class="cert-course"><?php echo htmlspecialchars($cert['judul_course']); ?></div>
                                    </div>
                                    <div class="cert-seal"></div>
                                </div>

                                <!-- Certificate Info -->
                                <div class="certificate-info-section">
                                    <div class="certificate-meta">
                                        <div class="certificate-level">
                                            <?php icon('bar-chart', 14); ?>
                                            <?php echo ucfirst($cert['level']); ?>
                                        </div>
                                        <div class="certificate-date">
                                            <?php icon('calendar', 14); ?>
                                            <?php echo date('d M Y', strtotime($cert['completed_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="certificate-actions">
                                        <button class="btn-download" onclick="downloadCertificate('<?php echo htmlspecialchars(addslashes($cert['judul_course'])); ?>', '<?php echo htmlspecialchars(addslashes($_SESSION['nama_lengkap'])); ?>', '<?php echo date('d F Y', strtotime($cert['completed_at'])); ?>')">
                                            <?php icon('download', 16); ?>
                                            Download PDF
                                        </button>
                                        <button class="btn-share" onclick="shareCertificate('<?php echo htmlspecialchars(addslashes($cert['judul_course'])); ?>')">
                                            <?php icon('share-2', 16); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="share-modal" id="shareModal">
        <div class="share-modal-content">
            <div class="share-modal-header">
                <h3>🔗 Bagikan Sertifikat</h3>
                <button class="share-modal-close" onclick="closeShareModal()">×</button>
            </div>
            <p class="share-modal-desc">Bagikan pencapaian Anda ke media sosial:</p>
            <div class="share-options">
                <button class="share-option" onclick="shareToTwitter()">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    <span>Twitter</span>
                </button>
                <button class="share-option" onclick="shareToFacebook()">
                    <svg viewBox="0 0 24 24" fill="#1877f2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    <span>Facebook</span>
                </button>
                <button class="share-option" onclick="shareToLinkedIn()">
                    <svg viewBox="0 0 24 24" fill="#0a66c2"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    <span>LinkedIn</span>
                </button>
                <button class="share-option" onclick="shareToWhatsApp()">
                    <svg viewBox="0 0 24 24" fill="#25d366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                    <span>WhatsApp</span>
                </button>
                <button class="share-option" onclick="shareToTelegram()">
                    <svg viewBox="0 0 24 24" fill="#0088cc"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    <span>Telegram</span>
                </button>
                <button class="share-option" onclick="copyShareLink()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                    <span>Copy Link</span>
                </button>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <?php include 'includes/loading.php'; ?>
    <?php include 'includes/toast.php'; ?>

    <script src="assets/js/navbar.js"></script>
    <script>
        window.jsPDF = window.jspdf.jsPDF;
        
        let currentShareCourse = '';

        function shareCertificate(courseName) {
            currentShareCourse = courseName;
            document.getElementById('shareModal').classList.add('active');
        }

        function closeShareModal() {
            document.getElementById('shareModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('shareModal').addEventListener('click', function(e) {
            if (e.target === this) closeShareModal();
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeShareModal();
        });

        function getShareText() {
            return `🎓 Saya baru saja menyelesaikan kursus "${currentShareCourse}" di <?php echo APP_NAME; ?>! #Learning #Achievement #Certificate`;
        }

        function getShareUrl() {
            return window.location.origin + '/courses.php';
        }

        function shareToTwitter() {
            const text = encodeURIComponent(getShareText());
            const url = encodeURIComponent(getShareUrl());
            window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank', 'width=550,height=420');
            closeShareModal();
        }

        function shareToFacebook() {
            const url = encodeURIComponent(getShareUrl());
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=550,height=420');
            closeShareModal();
        }

        function shareToLinkedIn() {
            const url = encodeURIComponent(getShareUrl());
            window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank', 'width=550,height=420');
            closeShareModal();
        }

        function shareToWhatsApp() {
            const text = encodeURIComponent(getShareText() + ' ' + getShareUrl());
            window.open(`https://wa.me/?text=${text}`, '_blank');
            closeShareModal();
        }

        function shareToTelegram() {
            const text = encodeURIComponent(getShareText());
            const url = encodeURIComponent(getShareUrl());
            window.open(`https://t.me/share/url?url=${url}&text=${text}`, '_blank');
            closeShareModal();
        }

        function copyShareLink() {
            const text = getShareText() + '\n' + getShareUrl();
            navigator.clipboard.writeText(text).then(() => {
                if (typeof showToast === 'function') {
                    showToast('Link berhasil disalin!', 'success');
                } else {
                    alert('Link berhasil disalin!');
                }
                closeShareModal();
            });
        }

        function downloadCertificate(courseName, studentName, date) {
            // Show loading toast
            if (typeof showToast === 'function') {
                showToast('Membuat sertifikat...', 'info');
            }

            // Populate template
            document.getElementById('cert-recipient').textContent = studentName;
            document.getElementById('cert-course').textContent = courseName;
            document.getElementById('cert-date').textContent = date;

            const element = document.getElementById('certificate-template');
            
            // Temporarily show the element for rendering
            element.style.position = 'absolute';
            element.style.top = '0';
            element.style.left = '0';
            element.style.zIndex = '-9999';
            
            // Use html2canvas to capture the element
            html2canvas(element, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#fefce8',
                logging: false
            }).then(canvas => {
                // Hide element again
                element.style.position = 'fixed';
                element.style.top = '-9999px';
                element.style.left = '-9999px';
                
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF({
                    orientation: 'landscape',
                    unit: 'px',
                    format: [1056, 816]
                });

                pdf.addImage(imgData, 'PNG', 0, 0, 1056, 816);
                pdf.save(`Sertifikat-${courseName.replace(/\s+/g, '-')}.pdf`);

                if (typeof showToast === 'function') {
                    showToast('Sertifikat berhasil diunduh!', 'success');
                }
            }).catch(error => {
                console.error('Error generating certificate:', error);
                if (typeof showToast === 'function') {
                    showToast('Gagal membuat sertifikat', 'error');
                }
            });
        }
    </script>
</body>
</html>

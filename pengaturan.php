<?php
require_once 'config/config.php';
requireRole(['admin']);

require_once 'models/Pengaturan.php';

$database = new Database();
$db = $database->getConnection();

$pengaturan = new Pengaturan($db);

$message = '';
$message_type = '';

// Handle form submission
if ($_POST) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Sesi tidak valid (CSRF Token Error). Silakan refresh halaman.';
        $message_type = 'error';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update') {
        $settings = [
        'nama_platform' => sanitizeInput($_POST['nama_platform'] ?? ''),
        'deskripsi_platform' => sanitizeInput($_POST['deskripsi_platform'] ?? ''),
        'email_platform' => sanitizeInput($_POST['email_platform'] ?? ''),
        'warna_primary' => sanitizeInput($_POST['warna_primary'] ?? '#8b5cf6'),
        'warna_secondary' => sanitizeInput($_POST['warna_secondary'] ?? '#a78bfa'),
        'warna_sidebar' => sanitizeInput($_POST['warna_sidebar'] ?? '#1a1a2e'),
        'warna_sidebar_header' => sanitizeInput($_POST['warna_sidebar_header'] ?? '#16213e'),
        'warna_success' => sanitizeInput($_POST['warna_success'] ?? '#27ae60'),
        'warna_danger' => sanitizeInput($_POST['warna_danger'] ?? '#e74c3c'),
        'warna_warning' => sanitizeInput($_POST['warna_warning'] ?? '#f39c12'),
        'warna_info' => sanitizeInput($_POST['warna_info'] ?? '#3498db')
    ];
    
    if ($pengaturan->updateAll($settings)) {
        $message = 'Pengaturan berhasil diperbarui!';
        $message_type = 'success';
    } else {
        $message = 'Gagal memperbarui pengaturan!';
        $message_type = 'error';
    }
    }
}

// Get all settings
$settings = $pengaturan->getAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('Pengaturan Aplikasi - ' . APP_NAME, 'Pengaturan dan konfigurasi platform', 'settings, pengaturan, admin'); ?>
    <title>Pengaturan Aplikasi - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <style>
        /* Compact Design Overrides */
        .dashboard-header {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .dashboard-header h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .form-container {
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .form-container h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            font-size: 0.85rem;
            margin-bottom: 0.35rem;
        }
        .form-group input, .form-group select, .form-group textarea {
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
            border-radius: 0.375rem;
        }
        .btn {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-radius: 0.375rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php require_once 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="dashboard-main-container">
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h1>Pengaturan Aplikasi</h1>
                <p>Kelola pengaturan aplikasi</p>
            </div>

            <!-- Content -->
            <div class="content">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Pengaturan Form -->
                <div class="form-container">
                    <h2>Pengaturan Umum</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="update">
                        
                        <div class="form-group">
                            <label for="nama_platform">Nama Platform</label>
                            <input type="text" id="nama_platform" name="nama_platform" 
                                   value="<?php echo htmlspecialchars($settings['nama_platform'] ?? APP_NAME); ?>" required>
                            <small style="color: #94a3b8; display: block; margin-top: 5px;">
                                Nama platform yang akan ditampilkan di seluruh aplikasi
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="deskripsi_platform">Deskripsi Platform</label>
                            <textarea id="deskripsi_platform" name="deskripsi_platform" rows="3" 
                                      placeholder="Deskripsi singkat tentang platform pembelajaran coding ini"><?php echo htmlspecialchars($settings['deskripsi_platform'] ?? ''); ?></textarea>
                            <small style="color: #94a3b8; display: block; margin-top: 5px;">
                                Deskripsi platform yang akan ditampilkan di halaman utama
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="email_platform">Email Platform</label>
                            <input type="email" id="email_platform" name="email_platform" 
                                   value="<?php echo htmlspecialchars($settings['email_platform'] ?? ''); ?>"
                                   placeholder="info@prozone.com">
                            <small style="color: #94a3b8; display: block; margin-top: 5px;">
                                Email kontak untuk platform
                            </small>
                        </div>

                        <hr style="margin: 30px 0; border: none; border-top: 2px solid #2d2d5a;">

                        <h3 style="margin-bottom: 20px;">Pengaturan Warna Tema</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="warna_primary">Warna Primary</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="color" id="warna_primary" name="warna_primary" 
                                           value="<?php echo htmlspecialchars($settings['warna_primary'] ?? '#667eea'); ?>" 
                                           style="width: 80px; height: 40px; border: none; border-radius: 5px; cursor: pointer;">
                                    <input type="text" value="<?php echo htmlspecialchars($settings['warna_primary'] ?? '#8b5cf6'); ?>" 
                                           onchange="document.getElementById('warna_primary').value = this.value"
                                           style="flex: 1; padding: 10px; border: 2px solid #2d2d5a; border-radius: 5px; background: #0f0f23; color: #e2e8f0;">
                                </div>
                                <small style="color: #94a3b8; display: block; margin-top: 5px;">
                                    Warna utama untuk tombol, link, dan elemen aktif
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="warna_secondary">Warna Secondary</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="color" id="warna_secondary" name="warna_secondary" 
                                           value="<?php echo htmlspecialchars($settings['warna_secondary'] ?? '#a78bfa'); ?>" 
                                           style="width: 80px; height: 40px; border: none; border-radius: 5px; cursor: pointer;">
                                    <input type="text" value="<?php echo htmlspecialchars($settings['warna_secondary'] ?? '#a78bfa'); ?>" 
                                           onchange="document.getElementById('warna_secondary').value = this.value"
                                           style="flex: 1; padding: 10px; border: 2px solid #2d2d5a; border-radius: 5px; background: #0f0f23; color: #e2e8f0;">
                                </div>
                                <small style="color: #94a3b8; display: block; margin-top: 5px;">
                                    Warna sekunder untuk gradient dan accent
                                </small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="warna_sidebar">Warna Sidebar</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="color" id="warna_sidebar" name="warna_sidebar" 
                                           value="<?php echo htmlspecialchars($settings['warna_sidebar'] ?? '#2c3e50'); ?>" 
                                           style="width: 80px; height: 40px; border: none; border-radius: 5px; cursor: pointer;">
                                    <input type="text" value="<?php echo htmlspecialchars($settings['warna_sidebar'] ?? '#1a1a2e'); ?>" 
                                           onchange="document.getElementById('warna_sidebar').value = this.value"
                                           style="flex: 1; padding: 10px; border: 2px solid #2d2d5a; border-radius: 5px; background: #0f0f23; color: #e2e8f0;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="warna_sidebar_header">Warna Header Sidebar</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="color" id="warna_sidebar_header" name="warna_sidebar_header" 
                                           value="<?php echo htmlspecialchars($settings['warna_sidebar_header'] ?? '#16213e'); ?>" 
                                           style="width: 80px; height: 40px; border: none; border-radius: 5px; cursor: pointer;">
                                    <input type="text" value="<?php echo htmlspecialchars($settings['warna_sidebar_header'] ?? '#16213e'); ?>" 
                                           onchange="document.getElementById('warna_sidebar_header').value = this.value"
                                           style="flex: 1; padding: 10px; border: 2px solid #2d2d5a; border-radius: 5px; background: #0f0f23; color: #e2e8f0;">
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="warna_success">Warna Success</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="color" id="warna_success" name="warna_success" 
                                           value="<?php echo htmlspecialchars($settings['warna_success'] ?? '#27ae60'); ?>" 
                                           style="width: 80px; height: 40px; border: none; border-radius: 5px; cursor: pointer;">
                                    <input type="text" value="<?php echo htmlspecialchars($settings['warna_success'] ?? '#27ae60'); ?>" 
                                           onchange="document.getElementById('warna_success').value = this.value"
                                           style="flex: 1; padding: 10px; border: 2px solid #2d2d5a; border-radius: 5px; background: #0f0f23; color: #e2e8f0;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="warna_danger">Warna Danger</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="color" id="warna_danger" name="warna_danger" 
                                           value="<?php echo htmlspecialchars($settings['warna_danger'] ?? '#e74c3c'); ?>" 
                                           style="width: 80px; height: 40px; border: none; border-radius: 5px; cursor: pointer;">
                                    <input type="text" value="<?php echo htmlspecialchars($settings['warna_danger'] ?? '#e74c3c'); ?>" 
                                           onchange="document.getElementById('warna_danger').value = this.value"
                                           style="flex: 1; padding: 10px; border: 2px solid #2d2d5a; border-radius: 5px; background: #0f0f23; color: #e2e8f0;">
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="warna_warning">Warna Warning</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="color" id="warna_warning" name="warna_warning" 
                                           value="<?php echo htmlspecialchars($settings['warna_warning'] ?? '#f39c12'); ?>" 
                                           style="width: 80px; height: 40px; border: none; border-radius: 5px; cursor: pointer;">
                                    <input type="text" value="<?php echo htmlspecialchars($settings['warna_warning'] ?? '#f39c12'); ?>" 
                                           onchange="document.getElementById('warna_warning').value = this.value"
                                           style="flex: 1; padding: 10px; border: 2px solid #2d2d5a; border-radius: 5px; background: #0f0f23; color: #e2e8f0;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="warna_info">Warna Info</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="color" id="warna_info" name="warna_info" 
                                           value="<?php echo htmlspecialchars($settings['warna_info'] ?? '#3498db'); ?>" 
                                           style="width: 80px; height: 40px; border: none; border-radius: 5px; cursor: pointer;">
                                    <input type="text" value="<?php echo htmlspecialchars($settings['warna_info'] ?? '#3498db'); ?>" 
                                           onchange="document.getElementById('warna_info').value = this.value"
                                           style="flex: 1; padding: 10px; border: 2px solid #2d2d5a; border-radius: 5px; background: #0f0f23; color: #e2e8f0;">
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 20px; padding: 15px; background: #252550; border-radius: 5px; border: 1px solid #2d2d5a; color: #cbd5e1;">
                            <strong style="color: #a78bfa;">💡 Tips:</strong> Gunakan color picker untuk memilih warna atau masukkan kode hex (contoh: #8b5cf6). 
                            Perubahan warna akan langsung diterapkan setelah menyimpan.
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Simpan Pengaturan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sync color picker dengan text input
        document.querySelectorAll('input[type="color"]').forEach(colorInput => {
            colorInput.addEventListener('input', function() {
                const textInput = this.parentElement.querySelector('input[type="text"]');
                if (textInput) {
                    textInput.value = this.value;
                }
            });
        });

        // Sync text input dengan color picker
        document.querySelectorAll('input[type="text"]').forEach(textInput => {
            if (textInput.previousElementSibling && textInput.previousElementSibling.type === 'color') {
                textInput.addEventListener('input', function() {
                    const colorInput = this.parentElement.querySelector('input[type="color"]');
                    if (colorInput && /^#[0-9A-F]{6}$/i.test(this.value)) {
                        colorInput.value = this.value;
                    }
                });
            }
        });
    </script>

    <?php include 'footer.php'; ?>
    <?php include 'includes/loading.php'; ?>
    <?php include 'includes/toast.php'; ?>

    <script src="assets/js/navbar.js"></script>
</body>
</html>



<?php
require_once 'config/config.php';
requireRole(['admin']);
require_once 'includes/icons.php';

require_once 'models/CourseCategory.php';

$database = new Database();
$db = $database->getConnection();

$kategori = new CourseCategory($db);

$message = '';
$message_type = '';

// Handle form submission
if ($_POST) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Sesi tidak valid (CSRF Token Error). Silakan refresh halaman.';
        $message_type = 'error';
    } elseif (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $kategori->nama_kategori = sanitizeInput($_POST['nama_kategori']);
                $kategori->deskripsi = sanitizeInput($_POST['deskripsi']);
                $kategori->icon = sanitizeInput($_POST['icon'] ?? '💻');

                if ($kategori->create()) {
                    $message = 'Kategori kursus berhasil ditambahkan!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menambahkan kategori kursus!';
                    $message_type = 'error';
                }
                break;

            case 'update':
                $kategori->id = sanitizeInput($_POST['id']);
                $kategori->nama_kategori = sanitizeInput($_POST['nama_kategori']);
                $kategori->deskripsi = sanitizeInput($_POST['deskripsi']);
                $kategori->icon = sanitizeInput($_POST['icon'] ?? '💻');

                if ($kategori->update()) {
                    $message = 'Kategori kursus berhasil diperbarui!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal memperbarui kategori kursus!';
                    $message_type = 'error';
                }
                break;

            case 'delete':
                $kategori->id = sanitizeInput($_POST['id']);
                if ($kategori->delete()) {
                    $message = 'Kategori kursus berhasil dihapus!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menghapus kategori kursus!';
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Get all kategori
$stmt = $kategori->readAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('Kategori Kursus - ' . APP_NAME, 'Kelola kategori kursus', 'admin, categories, management'); ?>
    <title>Kategori Kursus - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    </head>
<body>
    <!-- Navbar -->
    <?php require_once 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="dashboard-main-container">
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h1>Kategori Kursus</h1>
                <p>Kelola kategori kursus</p>
            </div>

            <!-- Content -->
            <div class="content">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Add Kategori Form -->
                <div style="background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(30, 30, 63, 0.98) 100%); border-radius: 1rem; padding: 2rem; margin-bottom: 2rem; border: 1px solid rgba(124, 58, 237, 0.2); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(124, 58, 237, 0.1);">
                    <h2 style="color: #e0e7ff; margin-bottom: 1.5rem; background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Tambah Kategori Baru</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="create">
                        
                        <div style="display: grid; grid-template-columns: 1fr 150px; gap: 1rem; margin-bottom: 1rem;">
                            <div>
                                <label for="nama_kategori" style="display: block; margin-bottom: 0.5rem; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.9rem;">Nama Kategori</label>
                                <input type="text" id="nama_kategori" name="nama_kategori" required
                                       style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 0.5rem; background: rgba(15, 15, 35, 0.6); color: #e0e7ff; font-size: 0.9rem; transition: all 0.3s ease;"
                                       onfocus="this.style.borderColor='rgba(124, 58, 237, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(124, 58, 237, 0.1)'"
                                       onblur="this.style.borderColor='rgba(124, 58, 237, 0.2)'; this.style.boxShadow='none'">
                            </div>
                            <div>
                                <label for="icon" style="display: block; margin-bottom: 0.5rem; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.9rem;">Icon</label>
                                <input type="text" id="icon" name="icon" placeholder="💻" maxlength="2"
                                       style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 0.5rem; background: rgba(15, 15, 35, 0.6); color: #e0e7ff; font-size: 1.2rem; text-align: center; transition: all 0.3s ease;"
                                       onfocus="this.style.borderColor='rgba(124, 58, 237, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(124, 58, 237, 0.1)'"
                                       onblur="this.style.borderColor='rgba(124, 58, 237, 0.2)'; this.style.boxShadow='none'">
                            </div>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label for="deskripsi" style="display: block; margin-bottom: 0.5rem; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.9rem;">Deskripsi</label>
                            <textarea id="deskripsi" name="deskripsi" rows="3"
                                      style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 0.5rem; background: rgba(15, 15, 35, 0.6); color: #e0e7ff; font-size: 0.9rem; resize: vertical; transition: all 0.3s ease;"
                                      onfocus="this.style.borderColor='rgba(124, 58, 237, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(124, 58, 237, 0.1)'"
                                      onblur="this.style.borderColor='rgba(124, 58, 237, 0.2)'; this.style.boxShadow='none'"></textarea>
                        </div>

                        <button type="submit" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);">Tambah Kategori</button>
                    </form>
                </div>

                <!-- Data Kategori Table -->
                <div style="background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(30, 30, 63, 0.98) 100%); border-radius: 1rem; padding: 1.75rem; border: 1px solid rgba(124, 58, 237, 0.2); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(124, 58, 237, 0.1);">
                    <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(124, 58, 237, 0.2);">
                        <h3 style="color: #e0e7ff; margin: 0; background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Daftar Kategori Kursus</h3>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid rgba(124, 58, 237, 0.2);">
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">ID</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Icon</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Nama Kategori</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Slug</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Deskripsi</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Tanggal Dibuat</th>
                                    <th style="padding: 1rem 0.75rem; text-align: left; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr style="border-bottom: 1px solid rgba(124, 58, 237, 0.1); transition: all 0.2s ease;" onmouseover="this.style.background='rgba(139, 92, 246, 0.05)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding: 0.75rem 0.5rem; color: #e0e7ff;"><?php echo $row['id']; ?></td>
                                    <td style="padding: 0.75rem 0.5rem; font-size: 1.2rem; text-align: center;"><?php echo htmlspecialchars($row['icon'] ?? '💻'); ?></td>
                                    <td style="padding: 0.75rem 0.5rem; color: #a78bfa; font-weight: 500;"><?php echo htmlspecialchars($row['nama_kategori']); ?></td>
                                    <td style="padding: 0.75rem 0.5rem; color: rgba(139, 92, 246, 0.7); font-size: 0.85rem;"><?php echo htmlspecialchars($row['slug']); ?></td>
                                    <td style="padding: 0.75rem 0.5rem; color: rgba(139, 92, 246, 0.7); font-size: 0.85rem;"><?php echo htmlspecialchars($row['deskripsi'] ?: '-'); ?></td>
                                    <td style="padding: 0.75rem 0.5rem; color: rgba(139, 92, 246, 0.7); font-size: 0.85rem;"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                    <td style="padding: 0.75rem 0.5rem;">
                                        <div style="display: flex; gap: 0.35rem;">
                                            <button onclick="editKategori(<?php echo htmlspecialchars(json_encode($row)); ?>)" 
                                                    style="padding: 0.35rem 0.75rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 0.375rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3); font-size: 0.75rem;"><?php icon('edit', 12); ?> Edit</button>
                                            <button onclick="deleteKategori(<?php echo $row['id']; ?>)" 
                                                    style="padding: 0.35rem 0.75rem; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none; border-radius: 0.375rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3); font-size: 0.75rem;"><?php icon('trash', 12); ?> Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 2000; overflow-y: auto; padding: 2rem;">
        <div style="max-width: 600px; margin: 2rem auto; background: linear-gradient(135deg, rgba(26, 26, 46, 0.98) 0%, rgba(30, 30, 63, 0.98) 100%); border-radius: 1rem; padding: 2rem; border: 1px solid rgba(124, 58, 237, 0.2); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(124, 58, 237, 0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="color: #e0e7ff; margin: 0; background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Edit Kategori</h2>
                <button onclick="closeEditModal()" style="background: none; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='#e0e7ff'" onmouseout="this.style.color='#94a3b8'">&times;</button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="edit_nama_kategori" style="display: block; margin-bottom: 0.5rem; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.9rem;">Nama Kategori</label>
                    <input type="text" id="edit_nama_kategori" name="nama_kategori" required
                           style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 0.5rem; background: rgba(15, 15, 35, 0.6); color: #e0e7ff; font-size: 0.9rem; transition: all 0.3s ease;"
                           onfocus="this.style.borderColor='rgba(124, 58, 237, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(124, 58, 237, 0.1)'"
                           onblur="this.style.borderColor='rgba(124, 58, 237, 0.2)'; this.style.boxShadow='none'">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="edit_icon" style="display: block; margin-bottom: 0.5rem; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.9rem;">Icon (Emoji)</label>
                    <input type="text" id="edit_icon" name="icon" placeholder="💻" maxlength="2"
                           style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 0.5rem; background: rgba(15, 15, 35, 0.6); color: #e0e7ff; font-size: 1.2rem; text-align: center; transition: all 0.3s ease;"
                           onfocus="this.style.borderColor='rgba(124, 58, 237, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(124, 58, 237, 0.1)'"
                           onblur="this.style.borderColor='rgba(124, 58, 237, 0.2)'; this.style.boxShadow='none'">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="edit_deskripsi" style="display: block; margin-bottom: 0.5rem; color: rgba(139, 92, 246, 0.8); font-weight: 600; font-size: 0.9rem;">Deskripsi</label>
                    <textarea id="edit_deskripsi" name="deskripsi" rows="3"
                              style="width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 0.5rem; background: rgba(15, 15, 35, 0.6); color: #e0e7ff; font-size: 0.9rem; resize: vertical; transition: all 0.3s ease;"
                              onfocus="this.style.borderColor='rgba(124, 58, 237, 0.5)'; this.style.boxShadow='0 0 0 3px rgba(124, 58, 237, 0.1)'"
                              onblur="this.style.borderColor='rgba(124, 58, 237, 0.2)'; this.style.boxShadow='none'"></textarea>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeEditModal()" style="padding: 0.75rem 1.5rem; background: rgba(139, 92, 246, 0.1); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">Batal</button>
                    <button type="submit" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editKategori(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_nama_kategori').value = data.nama_kategori;
            document.getElementById('edit_icon').value = data.icon || '💻';
            document.getElementById('edit_deskripsi').value = data.deskripsi || '';
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function deleteKategori(id) {
            if (confirm('Apakah Anda yakin ingin menghapus kategori ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('editModal').onclick = function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        }
    </script>

    <?php include 'footer.php'; ?>
    <?php include 'includes/loading.php'; ?>
    <?php include 'includes/toast.php'; ?>

    <script src="assets/js/navbar.js"></script>
</body>
</html>



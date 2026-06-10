<?php
require_once 'config/config.php';
requireLogin();
requireRole(['student']);
require_once 'includes/icons.php';

require_once 'models/Clan.php';
require_once 'models/Chat.php';

$database = new Database();
$db = $database->getConnection();

$clan = new Clan($db);
$chat = new Chat($db);

$message = '';
$message_type = '';

// Handle success messages from redirects
if (isset($_GET['joined']) && $_GET['joined'] == 1) {
    $message = 'Berhasil bergabung dengan clan!';
    $message_type = 'success';
}
if (isset($_GET['left']) && $_GET['left'] == 1) {
    $message = 'Anda telah keluar dari clan.';
    $message_type = 'success';
}

// Handle create clan
if ($_POST) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
             header('Content-Type: application/json');
             echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF Token']);
             exit;
        }
        $message = 'Sesi tidak valid (CSRF Token Error). Silakan refresh halaman.';
        $message_type = 'error';
        $_POST = []; // Stop further processing
    } elseif (isset($_POST['action']) && $_POST['action'] === 'create_clan') {
        $clan->nama_clan = sanitizeInput($_POST['nama_clan']);
    $clan->deskripsi = sanitizeInput($_POST['deskripsi']);
    $clan->leader_id = $_SESSION['user_id'];
    $clan->is_public = isset($_POST['is_public']) ? 1 : 0;
    $clan->max_members = sanitizeInput($_POST['max_members'] ?? 50);
    
    if ($clan->create()) {
        $message = 'Clan berhasil dibuat!';
        $message_type = 'success';
    } else {
        $message = 'Gagal membuat clan!';
        $message_type = 'error';
    }
}
}

// Handle join clan
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'join_clan') {
    $clan_id = sanitizeInput($_POST['clan_id']);
    
    // Check if user already in a clan
    $check_clan_query = "SELECT clan_id FROM clan_members WHERE user_id = :user_id LIMIT 1";
    $check_stmt = $db->prepare($check_clan_query);
    $check_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        $message = 'Anda sudah bergabung dengan clan lain! Silakan keluar terlebih dahulu.';
        $message_type = 'error';
    } else {
        if ($clan->addMember($clan_id, $_SESSION['user_id'])) {
            $message = 'Berhasil bergabung dengan clan!';
            $message_type = 'success';
            // Refresh page to show new clan
            header('Location: clan.php?joined=1');
            exit();
        } else {
            $message = 'Gagal bergabung dengan clan!';
            $message_type = 'error';
        }
    }
}

// Handle leave clan
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'leave_clan') {
    $clan_id = sanitizeInput($_POST['clan_id']);
    
    // Check if user is the leader
    $check_leader_query = "SELECT leader_id FROM clans WHERE id = :clan_id";
    $leader_stmt = $db->prepare($check_leader_query);
    $leader_stmt->bindParam(':clan_id', $clan_id);
    $leader_stmt->execute();
    $clan_data = $leader_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($clan_data && $clan_data['leader_id'] == $_SESSION['user_id']) {
        // Check if there are other members
        $count_query = "SELECT COUNT(*) as total FROM clan_members WHERE clan_id = :clan_id AND user_id != :user_id";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->bindParam(':clan_id', $clan_id);
        $count_stmt->bindParam(':user_id', $_SESSION['user_id']);
        $count_stmt->execute();
        $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($count_result['total'] > 0) {
            $message = 'Anda adalah leader! Transfer kepemimpinan terlebih dahulu atau keluarkan semua member.';
            $message_type = 'error';
        } else {
            // Leader is the only member, can leave and delete clan
            if ($clan->removeMember($clan_id, $_SESSION['user_id'])) {
                // Delete the clan
                $delete_clan = "DELETE FROM clans WHERE id = :clan_id";
                $del_stmt = $db->prepare($delete_clan);
                $del_stmt->bindParam(':clan_id', $clan_id);
                $del_stmt->execute();
                
                $message = 'Anda telah keluar dan clan dihapus.';
                $message_type = 'success';
                header('Location: clan.php?left=1');
                exit();
            }
        }
    } else {
        // Regular member can leave
        if ($clan->removeMember($clan_id, $_SESSION['user_id'])) {
            $message = 'Anda telah keluar dari clan.';
            $message_type = 'success';
            header('Location: clan.php?left=1');
            exit();
        } else {
            $message = 'Gagal keluar dari clan!';
            $message_type = 'error';
        }
    }
}

// Handle transfer leadership
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'transfer_leadership') {
    $clan_id = sanitizeInput($_POST['clan_id']);
    $new_leader_id = sanitizeInput($_POST['new_leader_id']);
    
    // Verify current user is leader
    $check_query = "SELECT leader_id FROM clans WHERE id = :clan_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':clan_id', $clan_id);
    $check_stmt->execute();
    $clan_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($clan_data && $clan_data['leader_id'] == $_SESSION['user_id']) {
        // Update clan leader
        $update_query = "UPDATE clans SET leader_id = :new_leader_id WHERE id = :clan_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':new_leader_id', $new_leader_id);
        $update_stmt->bindParam(':clan_id', $clan_id);
        
        if ($update_stmt->execute()) {
            // Update roles
            $db->prepare("UPDATE clan_members SET role = 'member' WHERE clan_id = :clan_id AND user_id = :user_id")
               ->execute([':clan_id' => $clan_id, ':user_id' => $_SESSION['user_id']]);
            $db->prepare("UPDATE clan_members SET role = 'leader' WHERE clan_id = :clan_id AND user_id = :user_id")
               ->execute([':clan_id' => $clan_id, ':user_id' => $new_leader_id]);
            
            $message = 'Kepemimpinan berhasil ditransfer!';
            $message_type = 'success';
        } else {
            $message = 'Gagal transfer kepemimpinan!';
            $message_type = 'error';
        }
    } else {
        $message = 'Anda bukan leader clan ini!';
        $message_type = 'error';
    }
}

// Handle kick member
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'kick_member') {
    $clan_id = sanitizeInput($_POST['clan_id']);
    $member_id = sanitizeInput($_POST['member_id']);
    
    // Verify current user is leader or co-leader
    $check_query = "SELECT c.leader_id, cm.role FROM clans c 
                    JOIN clan_members cm ON c.id = cm.clan_id 
                    WHERE c.id = :clan_id AND cm.user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':clan_id', $clan_id);
    $check_stmt->bindParam(':user_id', $_SESSION['user_id']);
    $check_stmt->execute();
    $check_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($check_data && ($check_data['leader_id'] == $_SESSION['user_id'] || $check_data['role'] === 'co_leader')) {
        // Cannot kick leader
        if ($member_id == $check_data['leader_id']) {
            $message = 'Tidak dapat mengeluarkan leader!';
            $message_type = 'error';
        } else if ($clan->removeMember($clan_id, $member_id)) {
            $message = 'Member berhasil dikeluarkan!';
            $message_type = 'success';
        } else {
            $message = 'Gagal mengeluarkan member!';
            $message_type = 'error';
        }
    } else {
        $message = 'Anda tidak memiliki izin untuk ini!';
        $message_type = 'error';
    }
}

// Handle promote/demote member
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'change_role') {
    $clan_id = sanitizeInput($_POST['clan_id']);
    $member_id = sanitizeInput($_POST['member_id']);
    $new_role = sanitizeInput($_POST['new_role']);
    
    // Verify current user is leader
    $check_query = "SELECT leader_id FROM clans WHERE id = :clan_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':clan_id', $clan_id);
    $check_stmt->execute();
    $clan_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($clan_data && $clan_data['leader_id'] == $_SESSION['user_id']) {
        if (in_array($new_role, ['member', 'co_leader'])) {
            $update_query = "UPDATE clan_members SET role = :role WHERE clan_id = :clan_id AND user_id = :user_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([':role' => $new_role, ':clan_id' => $clan_id, ':user_id' => $member_id]);
            
            $message = 'Role member berhasil diubah!';
            $message_type = 'success';
        }
    } else {
        $message = 'Anda bukan leader clan ini!';
        $message_type = 'error';
    }
}

// Handle update clan settings
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_clan') {
    $clan_id = sanitizeInput($_POST['clan_id']);
    
    // Verify current user is leader
    $check_query = "SELECT leader_id FROM clans WHERE id = :clan_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':clan_id', $clan_id);
    $check_stmt->execute();
    $clan_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($clan_data && $clan_data['leader_id'] == $_SESSION['user_id']) {
        $deskripsi = sanitizeInput($_POST['deskripsi']);
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $max_members = min(100, max(5, intval($_POST['max_members'])));
        
        $update_query = "UPDATE clans SET deskripsi = :deskripsi, is_public = :is_public, max_members = :max_members WHERE id = :clan_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute([
            ':deskripsi' => $deskripsi,
            ':is_public' => $is_public,
            ':max_members' => $max_members,
            ':clan_id' => $clan_id
        ]);
        
        $message = 'Pengaturan clan berhasil diperbarui!';
        $message_type = 'success';
    } else {
        $message = 'Anda bukan leader clan ini!';
        $message_type = 'error';
    }
}

// Handle send message
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $clan_id = sanitizeInput($_POST['clan_id']);
    $chat->clan_id = $clan_id;
    $chat->user_id = $_SESSION['user_id'];
    // Don't use htmlspecialchars for message to preserve emojis
    // We'll sanitize on output instead
    $raw_message = trim($_POST['message']);
    // Remove any dangerous HTML/script tags but keep emojis
    $chat->message = strip_tags($raw_message);
    
    if (!empty($chat->message) && $chat->create()) {
        // Success - message will be loaded via AJAX
    }
}

// Handle create announcement
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'create_announcement') {
    $clan_id = sanitizeInput($_POST['clan_id']);
    $title = sanitizeInput($_POST['announcement_title']);
    $content = strip_tags(trim($_POST['announcement_content']));
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    
    // Verify user is leader or co_leader
    $check_query = "SELECT c.leader_id, cm.role FROM clans c 
                    JOIN clan_members cm ON c.id = cm.clan_id 
                    WHERE c.id = :clan_id AND cm.user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([':clan_id' => $clan_id, ':user_id' => $_SESSION['user_id']]);
    $perm_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($perm_data && ($perm_data['leader_id'] == $_SESSION['user_id'] || $perm_data['role'] === 'co_leader')) {
        $insert_query = "INSERT INTO clan_announcements (clan_id, user_id, title, content, is_pinned) VALUES (:clan_id, :user_id, :title, :content, :is_pinned)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([
            ':clan_id' => $clan_id,
            ':user_id' => $_SESSION['user_id'],
            ':title' => $title,
            ':content' => $content,
            ':is_pinned' => $is_pinned
        ]);
        
        // Log activity
        logClanActivity($db, $clan_id, $_SESSION['user_id'], 'announcement', "Membuat pengumuman: $title");
        
        $message = 'Pengumuman berhasil dibuat!';
        $message_type = 'success';
    } else {
        $message = 'Anda tidak memiliki izin!';
        $message_type = 'error';
    }
}

// Handle delete announcement
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'delete_announcement') {
    $announcement_id = sanitizeInput($_POST['announcement_id']);
    $clan_id = sanitizeInput($_POST['clan_id']);
    
    // Verify user is leader
    $check_query = "SELECT leader_id FROM clans WHERE id = :clan_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([':clan_id' => $clan_id]);
    $clan_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($clan_data && $clan_data['leader_id'] == $_SESSION['user_id']) {
        $delete_query = "DELETE FROM clan_announcements WHERE id = :id AND clan_id = :clan_id";
        $db->prepare($delete_query)->execute([':id' => $announcement_id, ':clan_id' => $clan_id]);
        $message = 'Pengumuman dihapus!';
        $message_type = 'success';
    }
}

// Helper function to log clan activity
function logClanActivity($db, $clan_id, $user_id, $action_type, $description, $target_user_id = null) {
    $query = "INSERT INTO clan_activity_log (clan_id, user_id, action_type, description, target_user_id) 
              VALUES (:clan_id, :user_id, :action_type, :description, :target_user_id)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':clan_id' => $clan_id,
        ':user_id' => $user_id,
        ':action_type' => $action_type,
        ':description' => $description,
        ':target_user_id' => $target_user_id
    ]);
}

// Get user's clan
$user_clan = null;
$user_clan_id = null;
$clan_members = [];
$chat_messages = [];
$clan_announcements = [];
$clan_activity = [];
$clan_leaderboard = [];

$query_user_clan = "SELECT c.*, cm.role, u.nama_lengkap as leader_name
                    FROM clans c
                    JOIN clan_members cm ON c.id = cm.clan_id
                    LEFT JOIN users u ON c.leader_id = u.id
                    WHERE cm.user_id = :user_id
                    LIMIT 1";
$stmt_user_clan = $db->prepare($query_user_clan);
$stmt_user_clan->bindParam(':user_id', $_SESSION['user_id']);
$stmt_user_clan->execute();

if ($stmt_user_clan->rowCount() > 0) {
    $user_clan = $stmt_user_clan->fetch(PDO::FETCH_ASSOC);
    $user_clan_id = $user_clan['id'];
    
    // Get members
    $members_stmt = $clan->getMembers($user_clan_id);
    while ($row = $members_stmt->fetch(PDO::FETCH_ASSOC)) {
        $clan_members[] = $row;
    }
    
    // Get messages
    $messages_stmt = $chat->getMessages($user_clan_id, 50);
    while ($row = $messages_stmt->fetch(PDO::FETCH_ASSOC)) {
        $chat_messages[] = $row;
    }
    $chat_messages = array_reverse($chat_messages); // Oldest first
    
    // Get announcements
    $ann_query = "SELECT ca.*, u.nama_lengkap as author_name 
                  FROM clan_announcements ca 
                  LEFT JOIN users u ON ca.user_id = u.id 
                  WHERE ca.clan_id = :clan_id 
                  ORDER BY ca.is_pinned DESC, ca.created_at DESC 
                  LIMIT 5";
    $ann_stmt = $db->prepare($ann_query);
    $ann_stmt->execute([':clan_id' => $user_clan_id]);
    $clan_announcements = $ann_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get activity log
    $activity_query = "SELECT cal.*, u.nama_lengkap as user_name, tu.nama_lengkap as target_name 
                       FROM clan_activity_log cal 
                       LEFT JOIN users u ON cal.user_id = u.id 
                       LEFT JOIN users tu ON cal.target_user_id = tu.id 
                       WHERE cal.clan_id = :clan_id 
                       ORDER BY cal.created_at DESC 
                       LIMIT 10";
    $activity_stmt = $db->prepare($activity_query);
    $activity_stmt->execute([':clan_id' => $user_clan_id]);
    $clan_activity = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get clan leaderboard (top 10 clans by XP)
$leaderboard_query = "SELECT c.id, c.nama_clan, c.deskripsi, 
                      COUNT(DISTINCT cm.user_id) as total_members,
                      COALESCE(SUM(u.total_xp), 0) as total_xp,
                      ul.nama_lengkap as leader_name
                      FROM clans c
                      LEFT JOIN clan_members cm ON c.id = cm.clan_id
                      LEFT JOIN users u ON cm.user_id = u.id
                      LEFT JOIN users ul ON c.leader_id = ul.id
                      WHERE c.is_public = 1
                      GROUP BY c.id
                      ORDER BY total_xp DESC
                      LIMIT 10";
$leaderboard_stmt = $db->prepare($leaderboard_query);
$leaderboard_stmt->execute();
$clan_leaderboard = $leaderboard_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current clan rank
$clan_rank = 1;
if ($user_clan_id) {
    foreach ($clan_leaderboard as $index => $lb_clan) {
        if ($lb_clan['id'] == $user_clan_id) {
            $clan_rank = $index + 1;
            break;
        }
    }
}

// Get all public clans
$all_clans_stmt = $clan->readAll();
$all_clans = [];
while ($row = $all_clans_stmt->fetch(PDO::FETCH_ASSOC)) {
    $all_clans[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('Clan - ' . APP_NAME, 'Bergabung dengan clan dan belajar bersama komunitas', 'clan, community, team'); ?>
    <title>Clan - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/ui-enhancements.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
        <link rel="stylesheet" href="assets/css/dark-theme.css">
    <style>
        .clan-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 1.5rem;
        }
        .clan-main {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .clan-header {
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 50%, #a78bfa 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2), 0 0 0 1px rgba(124, 58, 237, 0.1);
            position: relative;
            overflow: hidden;
        }
        .clan-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-20px, -20px) rotate(180deg); }
        }
        .clan-header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }
        .clan-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
        }
        .btn-leave-clan {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.25rem;
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .btn-leave-clan:hover {
            background: rgba(239, 68, 68, 0.4);
            color: #fee2e2;
            border-color: rgba(239, 68, 68, 0.6);
            transform: translateY(-2px);
        }
        .clan-info {
            display: flex;
            gap: 1.5rem;
            margin-top: 0.75rem;
            font-size: 0.85rem;
            opacity: 0.9;
        }
        .chat-container {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(30, 30, 63, 0.98) 100%);
            border-radius: 0.75rem;
            border: 1px solid rgba(124, 58, 237, 0.2);
            display: flex;
            flex-direction: column;
            height: 450px;
            max-height: 450px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(124, 58, 237, 0.1);
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-height: 350px;
            scroll-behavior: smooth;
        }
        .chat-message {
            display: flex;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(37, 37, 80, 0.6);
            border-radius: 0.5rem;
            border: 1px solid rgba(124, 58, 237, 0.2);
            transition: all 0.2s ease;
        }
        .chat-message:hover {
            background: rgba(37, 37, 80, 0.8);
            border-color: rgba(124, 58, 237, 0.3);
        }
        .chat-message.own {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.25) 0%, rgba(167, 139, 250, 0.15) 100%);
            border-color: rgba(139, 92, 246, 0.4);
            margin-left: auto;
            max-width: 85%;
            box-shadow: 0 2px 6px rgba(139, 92, 246, 0.2);
        }
        .chat-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
            font-size: 0.8rem;
        }
        .chat-content {
            flex: 1;
        }
        .chat-username {
            font-weight: 600;
            color: #a78bfa;
            margin-bottom: 0.15rem;
            font-size: 0.85rem;
        }
        .chat-text {
            color: #e2e8f0;
            line-height: 1.4;
            font-size: 0.9rem;
        }
        .chat-time {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }
        .chat-input-container {
            padding: 0.75rem;
            border-top: 1px solid #2d2d5a;
            display: flex;
            gap: 0.75rem;
            background: rgba(15, 15, 35, 0.5);
        }
        .chat-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(124, 58, 237, 0.2);
            border-radius: 25px;
            background: rgba(15, 15, 35, 0.6);
            color: #e0e7ff;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .chat-input:focus {
            outline: none;
            border-color: rgba(124, 58, 237, 0.5);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
            background: rgba(15, 15, 35, 0.8);
        }
        .chat-input::placeholder {
            color: #64748b;
        }
        .btn-send {
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-send:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
            background: linear-gradient(135deg, #6d28d9 0%, #7c3aed 100%);
        }
        
        /* Clan Stats Grid */
        .clan-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .clan-stat-card {
            background: linear-gradient(145deg, rgba(37, 37, 80, 0.7) 0%, rgba(45, 45, 90, 0.5) 100%);
            border: 1px solid rgba(124, 58, 237, 0.2);
            border-radius: 1rem;
            padding: 1.25rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .clan-stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(124, 58, 237, 0.4);
            box-shadow: 0 8px 25px rgba(124, 58, 237, 0.2);
        }
        .clan-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--stat-color, #8b5cf6), transparent);
        }
        .clan-stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        .clan-stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 0.25rem;
        }
        .clan-stat-label {
            font-size: 0.75rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Clan Activity Section */
        .clan-activity {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(30, 30, 63, 0.98) 100%);
            border-radius: 1rem;
            border: 1px solid rgba(124, 58, 237, 0.2);
            overflow: hidden;
        }
        .activity-header {
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, transparent 100%);
            border-bottom: 1px solid rgba(124, 58, 237, 0.2);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .activity-header h3 {
            margin: 0;
            font-size: 1rem;
            color: #e0e7ff;
        }
        .activity-list {
            padding: 1rem;
            max-height: 200px;
            overflow-y: auto;
        }
        .activity-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(37, 37, 80, 0.4);
            border-radius: 0.75rem;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }
        .activity-item:hover {
            background: rgba(37, 37, 80, 0.6);
        }
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .activity-icon.xp { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); }
        .activity-icon.join { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .activity-icon.achievement { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
        .activity-text {
            flex: 1;
            font-size: 0.85rem;
            color: #e0e7ff;
        }
        .activity-time {
            font-size: 0.7rem;
            color: #64748b;
        }
        
        /* Enhanced Chat Header */
        .chat-header {
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, transparent 100%);
            border-bottom: 1px solid rgba(124, 58, 237, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chat-header-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: #e0e7ff;
        }
        .chat-online-count {
            font-size: 0.75rem;
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .chat-online-count::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse-dot 2s infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        
        /* Empty Chat State */
        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 2rem;
            text-align: center;
        }
        .empty-chat-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .empty-chat-text {
            color: #94a3b8;
            font-size: 0.9rem;
        }
        .clan-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .members-card {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(30, 30, 63, 0.98) 100%);
            border-radius: 0.75rem;
            padding: 1.5rem;
            border: 1px solid rgba(124, 58, 237, 0.2);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(124, 58, 237, 0.1);
        }
        .members-card h3 {
            color: #e2e8f0;
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
        }
        .member-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(37, 37, 80, 0.6);
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            border: 1px solid rgba(124, 58, 237, 0.2);
            transition: all 0.2s ease;
        }
        .member-item:hover {
            background: rgba(37, 37, 80, 0.8);
            border-color: rgba(124, 58, 237, 0.3);
            transform: translateX(4px);
        }
        .member-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            position: relative;
        }
        .member-avatar .status-indicator {
            position: absolute;
            bottom: -1px;
            right: -1px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid rgba(26, 26, 46, 1);
        }
        .member-avatar .status-indicator.online {
            background: #10b981;
        }
        .member-avatar .status-indicator.offline {
            background: #6b7280;
        }
        .member-info {
            flex: 1;
        }
        .member-name {
            color: #e2e8f0;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .member-role {
            color: #94a3b8;
            font-size: 0.75rem;
        }
        .member-xp {
            color: #a78bfa;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .clans-list {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(30, 30, 63, 0.98) 100%);
            border-radius: 1rem;
            padding: 1.75rem;
            border: 1px solid rgba(124, 58, 237, 0.2);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(124, 58, 237, 0.1);
        }
        .clans-list h3 {
            color: #e2e8f0;
            margin-bottom: 1rem;
        }
        .clan-item {
            padding: 1.25rem;
            background: rgba(37, 37, 80, 0.6);
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(124, 58, 237, 0.2);
            transition: all 0.3s ease;
        }
        .clan-item:hover {
            border-color: rgba(124, 58, 237, 0.4);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(124, 58, 237, 0.2);
            background: rgba(37, 37, 80, 0.8);
        }
        .clan-item-name {
            color: #e2e8f0;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .clan-item-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }
        .btn-join-clan {
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
            color: white;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }
        .btn-join-clan:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
            background: linear-gradient(135deg, #6d28d9 0%, #7c3aed 100%);
        }
        .create-clan-form {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(30, 30, 63, 0.98) 100%);
            border-radius: 1rem;
            padding: 2rem;
            border: 1px solid rgba(124, 58, 237, 0.2);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(124, 58, 237, 0.1);
        }
        .create-clan-form h3 {
            color: #e2e8f0;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 1024px) {
            .clan-container {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 640px) {
            .clan-header-top {
                flex-direction: column;
                gap: 1rem;
            }
            .btn-leave-clan {
                width: 100%;
                justify-content: center;
            }
            .clan-info {
                flex-wrap: wrap;
                gap: 0.75rem;
            }
        }
        
        /* Member Action Menu Styles */
        .member-menu button:hover {
            background: rgba(139, 92, 246, 0.15) !important;
        }
        
        /* Chat Input Focus Effect */
        .chat-input:focus {
            border-color: rgba(139, 92, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        
        /* Clan Card Hover Effect */
        .clan-card:hover {
            border-color: rgba(139, 92, 246, 0.4) !important;
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(124, 58, 237, 0.25);
        }
        
        /* Search Input Styles */
        #searchClan:focus {
            outline: none;
            border-color: rgba(139, 92, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        
        /* Empty State Animation */
        @keyframes float-gentle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        
        .empty-state-icon {
            animation: float-gentle 3s ease-in-out infinite;
        }
        
        /* Typing Indicator */
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 8px 12px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 12px;
            font-size: 0.8rem;
            color: #94a3b8;
        }
        
        .typing-indicator span {
            width: 6px;
            height: 6px;
            background: #a78bfa;
            border-radius: 50%;
            animation: typing-bounce 1.4s infinite;
        }
        
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typing-bounce {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-4px); }
        }
        
        /* Entrance Animations */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .clan-header { animation: slideUp 0.5s ease; }
        .clan-stats-grid { animation: slideUp 0.5s ease 0.1s both; }
        .chat-container { animation: scaleIn 0.5s ease 0.2s both; }
        .members-card { animation: fadeIn 0.5s ease 0.3s both; }
        
        /* Stat Card Number Counter Effect */
        .clan-stat-value {
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .clan-stat-card:hover .clan-stat-value {
            transform: scale(1.1);
        }
        
        .clan-stat-card:hover .clan-stat-icon {
            transform: scale(1.2) rotate(10deg);
            transition: transform 0.3s ease;
        }
        
        /* Chat Message Animation */
        .chat-message {
            animation: slideUp 0.3s ease;
        }
        
        .chat-message.own {
            animation: slideUp 0.3s ease;
        }
        
        /* Member Item Hover Glow */
        .member-item {
            transition: all 0.3s ease;
        }
        
        .member-item:hover {
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.2);
        }
        
        /* Button Shine Effect */
        .btn-send {
            position: relative;
            overflow: hidden;
        }
        
        .btn-send::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-send:hover::after {
            left: 100%;
        }
        
        /* Scrollbar Styling */
        .chat-messages::-webkit-scrollbar,
        .member-scroll::-webkit-scrollbar {
            width: 6px;
        }
        
        .chat-messages::-webkit-scrollbar-track,
        .member-scroll::-webkit-scrollbar-track {
            background: rgba(15, 15, 35, 0.5);
            border-radius: 3px;
        }
        
        .chat-messages::-webkit-scrollbar-thumb,
        .member-scroll::-webkit-scrollbar-thumb {
            background: rgba(139, 92, 246, 0.4);
            border-radius: 3px;
        }
        
        .chat-messages::-webkit-scrollbar-thumb:hover,
        .member-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(139, 92, 246, 0.6);
        }
        
        /* Top Contributor Medal Animation */
        .top-medal {
            display: inline-block;
            animation: bounce-subtle 2s ease infinite;
        }
        
        @keyframes bounce-subtle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }
        
        /* Tips Card Hover */
        .tips-card li {
            transition: all 0.3s ease;
            padding: 0.3rem 0;
        }
        
        .tips-card:hover li {
            color: #e0e7ff;
        }
        
        /* Responsive Enhancements */
        @media (max-width: 768px) {
            .clan-stats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.5rem;
            }
            
            .clan-stat-card {
                padding: 0.75rem;
            }
            
            .clan-stat-icon {
                font-size: 1.5rem;
            }
            
            .clan-stat-value {
                font-size: 1.1rem;
            }
        }
        
        /* Emoji Picker Styles */
        .emoji-picker-btn {
            background: rgba(139, 92, 246, 0.2);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .emoji-picker-btn:hover {
            background: rgba(139, 92, 246, 0.4);
            transform: scale(1.1);
        }
        
        .emoji-picker {
            position: absolute;
            bottom: 100%;
            left: 0;
            background: rgba(30, 30, 60, 0.98);
            border: 1px solid rgba(139, 92, 246, 0.3);
            border-radius: 12px;
            padding: 0.75rem;
            display: none;
            grid-template-columns: repeat(8, 1fr);
            gap: 0.25rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
            z-index: 100;
            margin-bottom: 0.5rem;
        }
        
        .emoji-picker.active {
            display: grid;
            animation: fadeIn 0.2s ease;
        }
        
        .emoji-picker button {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.375rem;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .emoji-picker button:hover {
            background: rgba(139, 92, 246, 0.3);
            transform: scale(1.2);
        }
        
        /* Invite Code Styles */
        .invite-code-box {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(26, 26, 46, 0.95) 100%);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .invite-code {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(15, 15, 35, 0.7);
            border: 1px dashed rgba(59, 130, 246, 0.4);
            border-radius: 8px;
            padding: 0.625rem 0.875rem;
            margin-top: 0.5rem;
        }
        
        .invite-code code {
            flex: 1;
            font-family: 'JetBrains Mono', monospace;
            color: #60a5fa;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }
        
        .copy-btn {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border: none;
            color: white;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .copy-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        
        .copy-btn.copied {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        /* Clan Badges Styles */
        .clan-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        
        .clan-badge {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(139, 92, 246, 0.1) 100%);
            border: 1px solid rgba(139, 92, 246, 0.3);
            padding: 0.375rem 0.625rem;
            border-radius: 20px;
            font-size: 0.7rem;
            color: #e0e7ff;
            font-weight: 500;
        }
        
        .clan-badge.gold {
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.2) 0%, rgba(245, 158, 11, 0.1) 100%);
            border-color: rgba(251, 191, 36, 0.4);
            color: #fbbf24;
        }
        
        .clan-badge.emerald {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.1) 100%);
            border-color: rgba(16, 185, 129, 0.4);
            color: #10b981;
        }
        
        .clan-badge.blue {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(37, 99, 235, 0.1) 100%);
            border-color: rgba(59, 130, 246, 0.4);
            color: #60a5fa;
        }
        
        /* New Message Notification Pulse */
        .new-message-indicator {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 12px;
            height: 12px;
            background: #ef4444;
            border-radius: 50%;
            animation: pulse-dot 1s infinite;
        }

        /* Announcements */
        .announcement-card {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(26, 26, 46, 0.95) 100%);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 0.75rem;
            padding: 1.25rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        .announcement-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .announcement-title {
            color: #e0e7ff;
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .announcement-item {
            padding: 0.85rem;
            background: rgba(37, 37, 80, 0.45);
            border: 1px solid rgba(59, 130, 246, 0.15);
            border-radius: 0.75rem;
            margin-bottom: 0.6rem;
        }
        .announcement-item.pinned {
            border-color: rgba(251, 191, 36, 0.3);
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.10) 0%, rgba(37, 37, 80, 0.45) 100%);
        }
        .announcement-item h4 {
            margin: 0 0 0.35rem 0;
            color: #e0e7ff;
            font-size: 0.95rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .announcement-item p {
            margin: 0;
            color: #b4bcd0;
            font-size: 0.85rem;
            line-height: 1.55;
        }
        .announcement-meta {
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            color: #94a3b8;
            font-size: 0.7rem;
        }
        .announcement-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.3px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .announcement-pill.pinned {
            color: #fbbf24;
            border-color: rgba(251, 191, 36, 0.4);
            background: rgba(251, 191, 36, 0.12);
        }
        .announcement-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-danger-mini {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fca5a5;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-danger-mini:hover {
            background: rgba(239, 68, 68, 0.25);
        }

        /* Leaderboard */
        .leaderboard-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.65rem 0.75rem;
            border-radius: 10px;
            border: 1px solid rgba(124, 58, 237, 0.18);
            background: rgba(37, 37, 80, 0.45);
            margin-bottom: 0.5rem;
        }
        .leaderboard-row.me {
            border-color: rgba(16, 185, 129, 0.35);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(37, 37, 80, 0.45) 100%);
        }
        .leaderboard-rank {
            width: 28px;
            text-align: center;
            font-weight: 900;
            color: #a78bfa;
        }
        .leaderboard-name {
            flex: 1;
            color: #e0e7ff;
            font-weight: 700;
            font-size: 0.85rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .leaderboard-xp {
            color: #fbbf24;
            font-weight: 800;
            font-size: 0.8rem;
            white-space: nowrap;
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
                <h1>Clan</h1>
                <p>Bergabung dengan komunitas developer</p>
            </div>

            <div class="content">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($user_clan): ?>
                    <!-- User has a clan - Show enhanced clan view -->
                    <?php 
                    $online_count = 0;
                    foreach ($clan_members as $m) {
                        if (!empty($m['is_online']) && $m['is_online']) $online_count++;
                    }
                    // Sort members by XP for top contributors
                    $sorted_members = $clan_members;
                    usort($sorted_members, function($a, $b) {
                        return ($b['total_xp'] ?? 0) - ($a['total_xp'] ?? 0);
                    });
                    ?>
                    
                    <div class="clan-container">
                        <div class="clan-main">
                            <!-- Enhanced Clan Header -->
                            <div class="clan-header" style="background: linear-gradient(135deg, #6d28d9 0%, #8b5cf6 40%, #a78bfa 100%); position: relative; overflow: hidden;">
                                <!-- Decorative Elements -->
                                <div style="position: absolute; top: -50%; right: -10%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); border-radius: 50%;"></div>
                                <div style="position: absolute; bottom: -30%; left: 10%; width: 200px; height: 200px; background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%); border-radius: 50%;"></div>
                                
                                <div class="clan-header-top" style="position: relative; z-index: 1;">
                                    <div style="display: flex; align-items: center; gap: 1.25rem;">
                                        <!-- Clan Avatar -->
                                        <div style="width: 70px; height: 70px; border-radius: 18px; background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.05) 100%); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; border: 2px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px);">
                                            <?php echo strtoupper(substr($user_clan['nama_clan'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h1 style="margin: 0 0 0.25rem 0; font-size: 1.75rem; font-weight: 800; text-shadow: 0 2px 10px rgba(0,0,0,0.2);"><?php echo htmlspecialchars($user_clan['nama_clan']); ?></h1>
                                            <p style="opacity: 0.9; margin: 0; font-size: 0.9rem;"><?php echo htmlspecialchars($user_clan['deskripsi']); ?></p>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-leave-clan" onclick="confirmLeaveClan()">
                                        <?php icon('log-out', 16); ?> Keluar
                                    </button>
                                </div>
                                
                                <div class="clan-info" style="position: relative; z-index: 1; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.15);">
                                    <span style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.1); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem;">
                                        <?php icon('users', 14); ?> <?php echo $user_clan['total_members']; ?> Members
                                    </span>
                                    <span style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.1); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem;">
                                        <?php icon('zap', 14); ?> <?php echo number_format($user_clan['total_xp']); ?> XP
                                    </span>
                                    <span style="display: flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.1); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem;">
                                        👑 <?php echo htmlspecialchars($user_clan['leader_name']); ?>
                                    </span>
                                    <span style="display: flex; align-items: center; gap: 0.5rem; background: rgba(16, 185, 129, 0.3); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; color: #a7f3d0;">
                                        <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse-dot 2s infinite;"></span>
                                        <?php echo $online_count; ?> Online
                                    </span>
                                </div>
                                
                                <!-- Clan Badges -->
                                <div class="clan-badges" style="position: relative; z-index: 1; margin-top: 0.75rem;">
                                    <?php if ($user_clan['total_xp'] >= 10000): ?>
                                        <span class="clan-badge gold">🏆 XP Master</span>
                                    <?php elseif ($user_clan['total_xp'] >= 5000): ?>
                                        <span class="clan-badge gold">⭐ Rising Star</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($user_clan['total_members'] >= 10): ?>
                                        <span class="clan-badge emerald">👥 Growing Community</span>
                                    <?php endif; ?>
                                    
                                    <?php if (count($chat_messages) >= 50): ?>
                                        <span class="clan-badge blue">💬 Active Chat</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($user_clan['is_public']): ?>
                                        <span class="clan-badge">🌐 Public Clan</span>
                                    <?php else: ?>
                                        <span class="clan-badge">🔒 Private</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Hidden form for leave clan -->
                                <form method="POST" id="leaveClanForm" style="display: none;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="leave_clan">
                                    <input type="hidden" name="clan_id" value="<?php echo $user_clan_id; ?>">
                                </form>
                            </div>

                            <!-- Clan Stats Cards -->
                            <div class="clan-stats-grid">
                                <div class="clan-stat-card" style="--stat-color: #fbbf24;">
                                    <span class="clan-stat-icon">🏆</span>
                                    <div class="clan-stat-value" style="color: #fbbf24;">#<?php echo $clan_rank; ?></div>
                                    <div class="clan-stat-label">Ranking</div>
                                </div>
                                <div class="clan-stat-card" style="--stat-color: #a78bfa;">
                                    <span class="clan-stat-icon">⚡</span>
                                    <div class="clan-stat-value" style="color: #a78bfa;"><?php echo number_format($user_clan['total_xp']); ?></div>
                                    <div class="clan-stat-label">Total XP</div>
                                </div>
                                <div class="clan-stat-card" style="--stat-color: #10b981;">
                                    <span class="clan-stat-icon">👥</span>
                                    <div class="clan-stat-value" style="color: #10b981;"><?php echo $user_clan['total_members']; ?>/<?php echo $user_clan['max_members']; ?></div>
                                    <div class="clan-stat-label">Members</div>
                                </div>
                            </div>

                            <!-- Quick Activity Strip -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                                <?php 
                                // Get newest member
                                $newest_member = null;
                                $newest_join_date = null;
                                foreach ($clan_members as $m) {
                                    if ($newest_join_date === null || (isset($m['joined_at']) && $m['joined_at'] > $newest_join_date)) {
                                        $newest_member = $m;
                                        $newest_join_date = $m['joined_at'] ?? null;
                                    }
                                }
                                
                                // Get today's active chatters (from chat_messages)
                                $today_chats = count($chat_messages);
                                ?>
                                
                                <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(26, 26, 46, 0.95) 100%); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 1rem; display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">🆕</div>
                                    <div>
                                        <div style="color: #94a3b8; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Member Terbaru</div>
                                        <div style="color: #e0e7ff; font-weight: 600; font-size: 0.9rem;"><?php echo $newest_member ? htmlspecialchars($newest_member['nama_lengkap']) : 'Belum ada'; ?></div>
                                    </div>
                                </div>
                                
                                <div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(26, 26, 46, 0.95) 100%); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 1rem; display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">💬</div>
                                    <div>
                                        <div style="color: #94a3b8; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Pesan Hari Ini</div>
                                        <div style="color: #e0e7ff; font-weight: 600; font-size: 0.9rem;"><?php echo $today_chats; ?> pesan</div>
                                    </div>
                                </div>
                                
                                <div style="background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(26, 26, 46, 0.95) 100%); border: 1px solid rgba(251, 191, 36, 0.2); border-radius: 12px; padding: 1rem; display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">⭐</div>
                                    <div>
                                        <div style="color: #94a3b8; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Top Contributor</div>
                                        <div style="color: #e0e7ff; font-weight: 600; font-size: 0.9rem;"><?php echo !empty($sorted_members) ? htmlspecialchars($sorted_members[0]['nama_lengkap']) : 'Belum ada'; ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Announcements -->
                            <div class="announcement-card" style="margin-bottom: 1rem;">
                                <div class="announcement-header">
                                    <h3 class="announcement-title">📢 Pengumuman Clan</h3>
                                    <div style="color: #94a3b8; font-size: 0.75rem;">Terbaru</div>
                                </div>

                                <?php if (empty($clan_announcements)): ?>
                                    <div style="padding: 0.9rem; border-radius: 12px; border: 1px dashed rgba(59, 130, 246, 0.25); color: #94a3b8; background: rgba(15, 15, 35, 0.35);">
                                        Belum ada pengumuman. Leader / Co-Leader bisa membuat pengumuman untuk semua member.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($clan_announcements as $ann): ?>
                                        <div class="announcement-item <?php echo !empty($ann['is_pinned']) ? 'pinned' : ''; ?>">
                                            <h4>
                                                <?php if (!empty($ann['is_pinned'])): ?>
                                                    <span class="announcement-pill pinned">📌 PIN</span>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($ann['title'], ENT_QUOTES, 'UTF-8'); ?>
                                            </h4>
                                            <p><?php echo nl2br(htmlspecialchars($ann['content'], ENT_QUOTES, 'UTF-8')); ?></p>
                                            <div class="announcement-meta">
                                                <span>
                                                    Oleh <strong><?php echo htmlspecialchars($ann['author_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></strong>
                                                    • <?php echo date('d M Y H:i', strtotime($ann['created_at'])); ?>
                                                </span>
                                                <?php if ($user_clan && $user_clan['leader_id'] == $_SESSION['user_id']): ?>
                                                    <div class="announcement-actions">
                                                        <form method="POST" style="margin: 0;">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                            <input type="hidden" name="action" value="delete_announcement">
                                                            <input type="hidden" name="clan_id" value="<?php echo $user_clan_id; ?>">
                                                            <input type="hidden" name="announcement_id" value="<?php echo (int)$ann['id']; ?>">
                                                            <button type="submit" class="btn-danger-mini" onclick="return confirm('Hapus pengumuman ini?')">Hapus</button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <?php
                                $current_user_role = $user_clan['role'] ?? 'member';
                                $can_announce = ($user_clan && ($user_clan['leader_id'] == $_SESSION['user_id'] || $current_user_role === 'co_leader'));
                                ?>
                                <?php if ($can_announce): ?>
                                    <div style="margin-top: 0.9rem; padding-top: 0.9rem; border-top: 1px solid rgba(59, 130, 246, 0.15);">
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="action" value="create_announcement">
                                            <input type="hidden" name="clan_id" value="<?php echo $user_clan_id; ?>">

                                            <div style="display: grid; grid-template-columns: 1fr; gap: 0.6rem;">
                                                <input
                                                    type="text"
                                                    name="announcement_title"
                                                    maxlength="200"
                                                    required
                                                    placeholder="Judul pengumuman..."
                                                    style="width: 100%; padding: 0.65rem 0.85rem; background: rgba(15, 15, 35, 0.7); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 10px; color: #e0e7ff; font-size: 0.9rem;"
                                                />
                                                <textarea
                                                    name="announcement_content"
                                                    rows="2"
                                                    required
                                                    placeholder="Tulis pengumuman singkat untuk semua member..."
                                                    style="width: 100%; padding: 0.65rem 0.85rem; background: rgba(15, 15, 35, 0.7); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 10px; color: #e0e7ff; font-size: 0.9rem; resize: vertical;"
                                                ></textarea>
                                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;">
                                                    <label style="display: flex; align-items: center; gap: 0.5rem; color: #b4bcd0; font-size: 0.85rem; cursor: pointer;">
                                                        <input type="checkbox" name="is_pinned" style="accent-color: #fbbf24; width: 16px; height: 16px;"> Pin
                                                    </label>
                                                    <button type="submit" style="padding: 0.65rem 1rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 10px; font-weight: 700; font-size: 0.85rem; cursor: pointer;">
                                                        Publikasikan
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Enhanced Chat Container -->
                            <div class="chat-container">
                                <div class="chat-header">
                                    <div class="chat-header-title">
                                        💬 Clan Chat
                                    </div>
                                    <div class="chat-online-count">
                                        <?php echo $online_count; ?> member online
                                    </div>
                                </div>
                                <div class="chat-messages" id="chatMessages">
                                    <?php if (empty($chat_messages)): ?>
                                        <div class="empty-chat">
                                            <div class="empty-chat-icon">💬</div>
                                            <div class="empty-chat-text">Belum ada pesan. Mulai percakapan!</div>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($chat_messages as $msg): ?>
                                            <div class="chat-message <?php echo $msg['user_id'] == $_SESSION['user_id'] ? 'own' : ''; ?>" data-id="<?php echo $msg['id']; ?>">
                                                <div class="chat-avatar" style="background: linear-gradient(135deg, <?php echo $msg['user_id'] == $_SESSION['user_id'] ? '#10b981, #059669' : '#8b5cf6, #7c3aed'; ?>);">
                                                    <?php echo strtoupper(substr($msg['nama_lengkap'], 0, 1)); ?>
                                                </div>
                                                <div class="chat-content">
                                                    <div class="chat-username"><?php echo htmlspecialchars($msg['nama_lengkap'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                    <div class="chat-text"><?php echo htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                    <div class="chat-time"><?php echo date('H:i', strtotime($msg['created_at'])); ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" class="chat-input-container" id="chatForm" style="position: relative;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="send_message">
                                    <input type="hidden" name="clan_id" value="<?php echo $user_clan_id; ?>">
                                    
                                    <!-- Emoji Picker -->
                                    <div style="position: relative;">
                                        <button type="button" class="emoji-picker-btn" onclick="toggleEmojiPicker()" title="Tambah emoji">😊</button>
                                        <div class="emoji-picker" id="emojiPicker">
                                            <button type="button" onclick="addEmoji('😀')">😀</button>
                                            <button type="button" onclick="addEmoji('😂')">😂</button>
                                            <button type="button" onclick="addEmoji('😍')">😍</button>
                                            <button type="button" onclick="addEmoji('🤔')">🤔</button>
                                            <button type="button" onclick="addEmoji('👍')">👍</button>
                                            <button type="button" onclick="addEmoji('👏')">👏</button>
                                            <button type="button" onclick="addEmoji('🎉')">🎉</button>
                                            <button type="button" onclick="addEmoji('🔥')">🔥</button>
                                            <button type="button" onclick="addEmoji('💪')">💪</button>
                                            <button type="button" onclick="addEmoji('❤️')">❤️</button>
                                            <button type="button" onclick="addEmoji('💯')">💯</button>
                                            <button type="button" onclick="addEmoji('✨')">✨</button>
                                            <button type="button" onclick="addEmoji('🚀')">🚀</button>
                                            <button type="button" onclick="addEmoji('💻')">💻</button>
                                            <button type="button" onclick="addEmoji('🤝')">🤝</button>
                                            <button type="button" onclick="addEmoji('🙏')">🙏</button>
                                        </div>
                                    </div>
                                    
                                    <input type="text" name="message" class="chat-input" placeholder="Ketik pesan..." required id="messageInput" autocomplete="off">
                                    <button type="submit" class="btn-send">
                                        <?php icon('send', 16); ?> Kirim
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="clan-sidebar">
                            <!-- Clan Leaderboard -->
                            <div class="members-card" style="background: linear-gradient(135deg, rgba(251, 191, 36, 0.06) 0%, rgba(26, 26, 46, 0.95) 100%); border: 1px solid rgba(251, 191, 36, 0.18);">
                                <h3 style="display: flex; align-items: center; justify-content: space-between;">
                                    <span>🏆 Leaderboard Clan</span>
                                    <span style="font-size: 0.75rem; color: #94a3b8; font-weight: normal;">Top 10</span>
                                </h3>
                                <?php if (empty($clan_leaderboard)): ?>
                                    <div style="color: #94a3b8; font-size: 0.85rem;">Belum ada data leaderboard.</div>
                                <?php else: ?>
                                    <?php foreach ($clan_leaderboard as $i => $lb): ?>
                                        <div class="leaderboard-row <?php echo ($user_clan_id && $lb['id'] == $user_clan_id) ? 'me' : ''; ?>">
                                            <div class="leaderboard-rank">#<?php echo $i + 1; ?></div>
                                            <div class="leaderboard-name"><?php echo htmlspecialchars($lb['nama_clan'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="leaderboard-xp"><?php echo number_format($lb['total_xp'] ?? 0); ?> XP</div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Activity Log -->
                            <div class="members-card" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.06) 0%, rgba(26, 26, 46, 0.95) 100%); border: 1px solid rgba(139, 92, 246, 0.18);">
                                <h3 style="display: flex; align-items: center; justify-content: space-between;">
                                    <span>📝 Aktivitas</span>
                                    <span style="font-size: 0.75rem; color: #94a3b8; font-weight: normal;">Terbaru</span>
                                </h3>
                                <div class="member-scroll" style="max-height: 220px; overflow-y: auto; margin: 0 -0.25rem; padding: 0 0.25rem;">
                                    <?php if (empty($clan_activity)): ?>
                                        <div style="color: #94a3b8; font-size: 0.85rem; padding: 0.5rem;">Belum ada aktivitas.</div>
                                    <?php else: ?>
                                        <?php foreach ($clan_activity as $act): ?>
                                            <div style="padding: 0.65rem 0.75rem; border-radius: 10px; background: rgba(37, 37, 80, 0.45); border: 1px solid rgba(124, 58, 237, 0.15); margin-bottom: 0.5rem;">
                                                <div style="color: #e0e7ff; font-size: 0.85rem; line-height: 1.4;">
                                                    <?php echo htmlspecialchars($act['description'] ?? 'Aktivitas', ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                                <div style="color: #94a3b8; font-size: 0.7rem; margin-top: 0.35rem;">
                                                    <?php echo date('d M Y H:i', strtotime($act['created_at'])); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Top Contributors -->
                            <div class="members-card" style="background: linear-gradient(135deg, rgba(251, 191, 36, 0.08) 0%, rgba(26, 26, 46, 0.95) 100%); border: 1px solid rgba(251, 191, 36, 0.2);">
                                <h3 style="display: flex; align-items: center; gap: 0.5rem; color: #fbbf24;">🏅 Top Contributors</h3>
                                <?php 
                                // Get top 3 (sorted_members already defined above)
                                $top_3 = array_slice($sorted_members, 0, 3);
                                $rank = 1;
                                ?>
                                <?php foreach ($top_3 as $top_member): ?>
                                    <div class="member-item" style="background: <?php echo $rank === 1 ? 'linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(37, 37, 80, 0.6) 100%)' : 'rgba(37, 37, 80, 0.6)'; ?>; border-color: <?php echo $rank === 1 ? 'rgba(251, 191, 36, 0.3)' : 'rgba(124, 58, 237, 0.2)'; ?>;">
                                        <div class="top-medal" style="font-size: 1.25rem; width: 28px; text-align: center;">
                                            <?php echo $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : '🥉'); ?>
                                        </div>
                                        <div class="member-avatar" style="background: linear-gradient(135deg, <?php echo $rank === 1 ? '#fbbf24, #f59e0b' : ($rank === 2 ? '#94a3b8, #64748b' : '#cd7f32, #a0522d'); ?>);">
                                            <?php echo strtoupper(substr($top_member['nama_lengkap'], 0, 1)); ?>
                                        </div>
                                        <div class="member-info">
                                            <div class="member-name" style="font-size: 0.85rem;"><?php echo htmlspecialchars($top_member['nama_lengkap']); ?></div>
                                        </div>
                                        <div class="member-xp" style="color: <?php echo $rank === 1 ? '#fbbf24' : '#a78bfa'; ?>;">
                                            <?php echo number_format($top_member['total_xp'] ?? 0); ?>
                                        </div>
                                    </div>
                                <?php $rank++; endforeach; ?>
                            </div>
                            
                            <!-- All Members -->
                            <div class="members-card">
                                <h3 style="display: flex; align-items: center; justify-content: space-between;">
                                    <span>👥 All Members</span>
                                    <span style="font-size: 0.75rem; color: #94a3b8; font-weight: normal;"><?php echo count($clan_members); ?>/<?php echo $user_clan['max_members']; ?></span>
                                </h3>
                                <?php 
                                $is_leader = ($user_clan['leader_id'] == $_SESSION['user_id']);
                                $current_user_role = $user_clan['role'] ?? 'member';
                                $can_manage = $is_leader || $current_user_role === 'co_leader';
                                ?>
                                <div class="member-scroll" style="max-height: 280px; overflow-y: auto; margin: 0 -0.5rem; padding: 0 0.5rem;">
                                <?php foreach ($clan_members as $member): ?>
                                    <div class="member-item" style="margin-bottom: 0.5rem;">
                                        <div class="member-avatar" style="background: linear-gradient(135deg, <?php 
                                            if ($member['role'] === 'leader') echo '#fbbf24, #f59e0b';
                                            elseif ($member['role'] === 'co_leader') echo '#60a5fa, #3b82f6';
                                            else echo '#8b5cf6, #7c3aed';
                                        ?>);">
                                            <?php echo strtoupper(substr($member['nama_lengkap'], 0, 1)); ?>
                                            <span class="status-indicator <?php echo (!empty($member['is_online']) && $member['is_online']) ? 'online' : 'offline'; ?>"></span>
                                        </div>
                                        <div class="member-info">
                                            <div class="member-name">
                                                <?php echo htmlspecialchars($member['nama_lengkap']); ?>
                                                <?php if ($member['role'] === 'leader'): ?>
                                                    <span style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #000; padding: 1px 6px; border-radius: 10px; font-size: 0.6rem; font-weight: 700;">LEADER</span>
                                                <?php elseif ($member['role'] === 'co_leader'): ?>
                                                    <span style="background: linear-gradient(135deg, #60a5fa, #3b82f6); color: #fff; padding: 1px 6px; border-radius: 10px; font-size: 0.6rem; font-weight: 700;">CO-LEADER</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="member-role" style="font-size: 0.7rem;">
                                                <?php if (!empty($member['is_online']) && $member['is_online']): ?>
                                                    <span style="color: #10b981;">● Online</span>
                                                <?php else: ?>
                                                    <span style="color: #64748b;">○ Offline</span>
                                                <?php endif; ?>
                                                <span style="color: #64748b; margin-left: 0.5rem;"><?php echo number_format($member['total_xp'] ?? 0); ?> XP</span>
                                            </div>
                                        </div>
                                        
                                        <?php if ($can_manage && $member['user_id'] != $_SESSION['user_id'] && $member['role'] !== 'leader'): ?>
                                        <div class="member-actions" style="position: relative;">
                                            <button onclick="toggleMemberMenu(<?php echo $member['user_id']; ?>)" style="background: rgba(139, 92, 246, 0.2); border: none; color: #a78bfa; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 1rem;">⋮</button>
                                            <div id="member-menu-<?php echo $member['user_id']; ?>" class="member-menu" style="display: none; position: absolute; right: 0; top: 100%; background: rgba(30, 30, 60, 0.98); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 8px; padding: 4px; z-index: 100; min-width: 140px; box-shadow: 0 8px 25px rgba(0,0,0,0.4);">
                                                <?php if ($is_leader): ?>
                                                    <?php if ($member['role'] === 'member'): ?>
                                                        <form method="POST" style="margin: 0;">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                            <input type="hidden" name="action" value="change_role">
                                                            <input type="hidden" name="clan_id" value="<?php echo $user_clan_id; ?>">
                                                            <input type="hidden" name="member_id" value="<?php echo $member['user_id']; ?>">
                                                            <input type="hidden" name="new_role" value="co_leader">
                                                            <button type="submit" style="width: 100%; padding: 8px 12px; background: transparent; border: none; color: #60a5fa; text-align: left; cursor: pointer; font-size: 0.8rem; border-radius: 4px;">⭐ Jadikan Co-Leader</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" style="margin: 0;">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                            <input type="hidden" name="action" value="change_role">
                                                            <input type="hidden" name="clan_id" value="<?php echo $user_clan_id; ?>">
                                                            <input type="hidden" name="member_id" value="<?php echo $member['user_id']; ?>">
                                                            <input type="hidden" name="new_role" value="member">
                                                            <button type="submit" style="width: 100%; padding: 8px 12px; background: transparent; border: none; color: #94a3b8; text-align: left; cursor: pointer; font-size: 0.8rem; border-radius: 4px;">↓ Turunkan ke Member</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="POST" style="margin: 0;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                        <input type="hidden" name="action" value="transfer_leadership">
                                                        <input type="hidden" name="clan_id" value="<?php echo $user_clan_id; ?>">
                                                        <input type="hidden" name="new_leader_id" value="<?php echo $member['user_id']; ?>">
                                                        <button type="submit" onclick="return confirm('Transfer kepemimpinan ke <?php echo htmlspecialchars($member['nama_lengkap']); ?>?')" style="width: 100%; padding: 8px 12px; background: transparent; border: none; color: #fbbf24; text-align: left; cursor: pointer; font-size: 0.8rem; border-radius: 4px;">👑 Transfer Leadership</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" style="margin: 0;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                    <input type="hidden" name="action" value="kick_member">
                                                    <input type="hidden" name="clan_id" value="<?php echo $user_clan_id; ?>">
                                                    <input type="hidden" name="member_id" value="<?php echo $member['user_id']; ?>">
                                                    <button type="submit" onclick="return confirm('Keluarkan <?php echo htmlspecialchars($member['nama_lengkap']); ?> dari clan?')" style="width: 100%; padding: 8px 12px; background: transparent; border: none; color: #ef4444; text-align: left; cursor: pointer; font-size: 0.8rem; border-radius: 4px;">🚫 Keluarkan</button>
                                                </form>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <?php if ($is_leader): ?>
                            <!-- Clan Settings (Leader Only) -->
                            <div class="members-card" style="margin-top: 1rem; background: linear-gradient(135deg, rgba(139, 92, 246, 0.08) 0%, rgba(26, 26, 46, 0.95) 100%); border: 1px solid rgba(139, 92, 246, 0.2);">
                                <h3 style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">⚙️ Pengaturan Clan</h3>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="update_clan">
                                    <input type="hidden" name="clan_id" value="<?php echo $user_clan_id; ?>">
                                    
                                    <div style="margin-bottom: 1rem;">
                                        <label style="display: block; margin-bottom: 0.4rem; color: #94a3b8; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Deskripsi</label>
                                        <textarea name="deskripsi" rows="2" style="width: 100%; padding: 0.625rem 0.875rem; background: rgba(15, 15, 35, 0.7); border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 8px; color: #e0e7ff; font-size: 0.85rem; resize: vertical; transition: all 0.3s;" onfocus="this.style.borderColor='rgba(139, 92, 246, 0.5)'" onblur="this.style.borderColor='rgba(124, 58, 237, 0.2)'"><?php echo htmlspecialchars($user_clan['deskripsi']); ?></textarea>
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                                        <div>
                                            <label style="display: block; margin-bottom: 0.4rem; color: #94a3b8; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Max Members</label>
                                            <input type="number" name="max_members" value="<?php echo $user_clan['max_members']; ?>" min="5" max="100" style="width: 100%; padding: 0.625rem 0.875rem; background: rgba(15, 15, 35, 0.7); border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 8px; color: #e0e7ff; font-size: 0.85rem;">
                                        </div>
                                        <div style="display: flex; align-items: flex-end;">
                                            <label style="display: flex; align-items: center; gap: 0.5rem; color: #b4bcd0; font-size: 0.85rem; cursor: pointer; padding: 0.625rem; background: rgba(15, 15, 35, 0.7); border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 8px; width: 100%;">
                                                <input type="checkbox" name="is_public" <?php echo $user_clan['is_public'] ? 'checked' : ''; ?> style="accent-color: #8b5cf6; width: 16px; height: 16px;"> 
                                                Public
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" style="width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);">
                                        <?php icon('save', 14); ?> Simpan Perubahan
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Invite Link Box -->
                            <?php 
                            // Generate simple invite code based on clan id
                            $invite_code = strtoupper(substr(md5($user_clan_id . 'prozone_clan'), 0, 8));
                            ?>
                            <div class="invite-code-box">
                                <h4 style="color: #60a5fa; font-size: 0.9rem; margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">🔗 Kode Undangan</h4>
                                <p style="color: #94a3b8; font-size: 0.75rem; margin: 0;">Bagikan ke teman untuk bergabung</p>
                                <div class="invite-code">
                                    <code id="inviteCode"><?php echo $invite_code; ?></code>
                                    <button type="button" class="copy-btn" onclick="copyInviteCode()" id="copyBtn">
                                        📋 Copy
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Quick Stats / Info Card -->
                            <div class="members-card tips-card" style="margin-top: 1rem; background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(26, 26, 46, 0.95) 100%); border: 1px solid rgba(16, 185, 129, 0.2);">
                                <h3 style="display: flex; align-items: center; gap: 0.5rem; color: #10b981; margin-bottom: 0.75rem;">💡 Tips</h3>
                                <ul style="color: #94a3b8; font-size: 0.8rem; line-height: 1.7; padding-left: 1.25rem; margin: 0;">
                                    <li>Aktif di chat untuk membangun komunitas</li>
                                    <li>XP kamu berkontribusi ke ranking clan</li>
                                    <li>Bagikan kode undangan ke teman!</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- User doesn't have a clan - Show clan list prominently -->
                    <div class="clan-container" style="grid-template-columns: 1fr 350px;">
                        <!-- Main: Join Existing Clans -->
                        <div class="clan-main">
                            <div class="clans-list" style="background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(30, 30, 63, 0.98) 100%); border-radius: 1rem; padding: 1.75rem; border: 1px solid rgba(124, 58, 237, 0.2);">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                                    <h3 style="color: #e0e7ff; font-size: 1.25rem; margin: 0;"><?php icon('users', 20); ?> Pilih Clan untuk Bergabung</h3>
                                    <span style="color: #94a3b8; font-size: 0.85rem;"><?php echo count($all_clans); ?> clan tersedia</span>
                                </div>
                                
                                <!-- Search Clan -->
                                <div style="margin-bottom: 1.5rem;">
                                    <div style="position: relative;">
                                        <input type="text" id="searchClan" placeholder="Cari clan..." 
                                            style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.75rem; background: rgba(15, 15, 35, 0.7); border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 10px; color: #e0e7ff; font-size: 0.9rem; transition: all 0.3s;">
                                        <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8;"><?php icon('search', 16); ?></span>
                                    </div>
                                </div>
                                
                                <?php if (empty($all_clans)): ?>
                                    <div style="text-align: center; padding: 3rem 2rem; background: rgba(139, 92, 246, 0.05); border-radius: 12px; border: 1px dashed rgba(139, 92, 246, 0.3);">
                                        <div style="font-size: 3rem; margin-bottom: 1rem;">🏰</div>
                                        <p style="color: #e0e7ff; font-size: 1.1rem; margin-bottom: 0.5rem;">Belum ada clan yang tersedia</p>
                                        <p style="color: #94a3b8; font-size: 0.9rem;">Jadilah yang pertama membuat clan!</p>
                                    </div>
                                <?php else: ?>
                                    <div class="clan-cards-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                                        <?php foreach ($all_clans as $clan_item): ?>
                                            <div class="clan-card" data-name="<?php echo strtolower(htmlspecialchars($clan_item['nama_clan'])); ?>" data-desc="<?php echo strtolower(htmlspecialchars($clan_item['deskripsi'] ?? '')); ?>" style="background: linear-gradient(145deg, rgba(37, 37, 80, 0.7) 0%, rgba(45, 45, 90, 0.6) 100%); border-radius: 14px; padding: 1.25rem; border: 1px solid rgba(124, 58, 237, 0.2); transition: all 0.3s ease; position: relative; overflow: hidden;">
                                                <!-- Decorative top bar -->
                                                <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #7c3aed, #a78bfa, #7c3aed);"></div>
                                                
                                                <!-- Clan Avatar & Name -->
                                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                                                    <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white; font-weight: bold;">
                                                        <?php echo strtoupper(substr($clan_item['nama_clan'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div style="color: #e0e7ff; font-weight: 700; font-size: 1.1rem;"><?php echo htmlspecialchars($clan_item['nama_clan']); ?></div>
                                                        <div style="color: #94a3b8; font-size: 0.75rem;">Leader: <?php echo htmlspecialchars($clan_item['leader_name'] ?? 'Unknown'); ?></div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Description -->
                                                <p style="color: #b4bcd0; font-size: 0.85rem; margin-bottom: 1rem; line-height: 1.5; min-height: 40px;">
                                                    <?php echo htmlspecialchars(substr($clan_item['deskripsi'] ?? 'Tidak ada deskripsi', 0, 80)); ?><?php echo strlen($clan_item['deskripsi'] ?? '') > 80 ? '...' : ''; ?>
                                                </p>
                                                
                                                <!-- Stats -->
                                                <div style="display: flex; gap: 1rem; margin-bottom: 1rem; padding: 0.75rem; background: rgba(15, 15, 35, 0.5); border-radius: 8px;">
                                                    <div style="flex: 1; text-align: center;">
                                                        <div style="color: #a78bfa; font-weight: 700; font-size: 1.1rem;"><?php echo $clan_item['total_members']; ?>/<?php echo $clan_item['max_members']; ?></div>
                                                        <div style="color: #64748b; font-size: 0.7rem; text-transform: uppercase;">Members</div>
                                                    </div>
                                                    <div style="width: 1px; background: rgba(139, 92, 246, 0.2);"></div>
                                                    <div style="flex: 1; text-align: center;">
                                                        <div style="color: #fbbf24; font-weight: 700; font-size: 1.1rem;"><?php echo number_format($clan_item['total_xp']); ?></div>
                                                        <div style="color: #64748b; font-size: 0.7rem; text-transform: uppercase;">Total XP</div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Join Button -->
                                                <?php if ($clan_item['total_members'] < $clan_item['max_members']): ?>
                                                    <form method="POST" style="margin: 0;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                        <input type="hidden" name="action" value="join_clan">
                                                        <input type="hidden" name="clan_id" value="<?php echo $clan_item['id']; ?>">
                                                        <button type="submit" style="width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%); color: white; border: none; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);">
                                                            <?php icon('user-plus', 16); ?> Bergabung
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button disabled style="width: 100%; padding: 0.75rem; background: rgba(100, 100, 120, 0.5); color: #94a3b8; border: none; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: not-allowed;">
                                                        Clan Penuh
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Sidebar: Create New Clan -->
                        <div class="clan-sidebar">
                            <div class="create-clan-form" style="background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(30, 30, 63, 0.98) 100%); border-radius: 1rem; padding: 1.5rem; border: 1px solid rgba(124, 58, 237, 0.2);">
                                <h3 style="color: #e0e7ff; margin-bottom: 1.25rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <?php icon('plus', 18); ?> Buat Clan Baru
                                </h3>
                                <p style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 1.25rem; line-height: 1.5;">
                                    Ingin memimpin komunitas sendiri? Buat clan baru dan undang teman-temanmu!
                                </p>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="action" value="create_clan">
                                    <div class="form-group" style="margin-bottom: 1rem;">
                                        <label style="display: block; margin-bottom: 0.375rem; color: #b4bcd0; font-size: 0.8rem; font-weight: 500;">Nama Clan</label>
                                        <input type="text" name="nama_clan" required placeholder="Contoh: Code Warriors" style="width: 100%; padding: 0.625rem 0.875rem; background: rgba(15, 15, 35, 0.7); border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 8px; color: #e0e7ff; font-size: 0.9rem;">
                                    </div>
                                    <div class="form-group" style="margin-bottom: 1rem;">
                                        <label style="display: block; margin-bottom: 0.375rem; color: #b4bcd0; font-size: 0.8rem; font-weight: 500;">Deskripsi</label>
                                        <textarea name="deskripsi" rows="3" placeholder="Deskripsikan clan kamu..." style="width: 100%; padding: 0.625rem 0.875rem; background: rgba(15, 15, 35, 0.7); border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 8px; color: #e0e7ff; font-size: 0.9rem; resize: vertical;"></textarea>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 1rem;">
                                        <label style="display: block; margin-bottom: 0.375rem; color: #b4bcd0; font-size: 0.8rem; font-weight: 500;">Max Members</label>
                                        <input type="number" name="max_members" value="50" min="5" max="100" style="width: 100%; padding: 0.625rem 0.875rem; background: rgba(15, 15, 35, 0.7); border: 1px solid rgba(124, 58, 237, 0.2); border-radius: 8px; color: #e0e7ff; font-size: 0.9rem;">
                                    </div>
                                    <div class="form-group" style="margin-bottom: 1.25rem;">
                                        <label style="display: flex; align-items: center; gap: 0.5rem; color: #b4bcd0; font-size: 0.85rem; cursor: pointer;">
                                            <input type="checkbox" name="is_public" checked style="width: 16px; height: 16px; accent-color: #8b5cf6;"> 
                                            Public Clan (dapat ditemukan)
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                        <?php icon('plus', 16); ?> Buat Clan
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Tips Card -->
                            <div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.05) 100%); border-radius: 1rem; padding: 1.25rem; border: 1px solid rgba(16, 185, 129, 0.2); margin-top: 1rem;">
                                <h4 style="color: #10b981; font-size: 0.95rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                                    <?php icon('lightbulb', 16); ?> Tips
                                </h4>
                                <ul style="color: #94a3b8; font-size: 0.8rem; line-height: 1.6; padding-left: 1rem; margin: 0;">
                                    <li>Bergabung dengan clan untuk belajar bersama</li>
                                    <li>Chat dengan anggota lain untuk diskusi</li>
                                    <li>XP kamu akan berkontribusi ke total XP clan</li>
                                    <li>Clan dengan XP tertinggi akan tampil di leaderboard</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Auto-scroll chat to bottom
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Toggle member action menu
        function toggleMemberMenu(userId) {
            // Close all other menus first
            document.querySelectorAll('.member-menu').forEach(menu => {
                if (menu.id !== 'member-menu-' + userId) {
                    menu.style.display = 'none';
                }
            });
            
            const menu = document.getElementById('member-menu-' + userId);
            if (menu) {
                menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
            }
        }
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.member-actions')) {
                document.querySelectorAll('.member-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });

        // Search clan functionality
        const searchInput = document.getElementById('searchClan');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                const clanCards = document.querySelectorAll('.clan-card');
                let visibleCount = 0;
                
                clanCards.forEach(card => {
                    const clanName = card.dataset.name || '';
                    const clanDesc = card.dataset.desc || '';
                    
                    if (searchTerm === '' || clanName.includes(searchTerm) || clanDesc.includes(searchTerm)) {
                        card.style.display = 'block';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Show no results message
                let noResults = document.getElementById('noSearchResults');
                if (visibleCount === 0 && searchTerm !== '') {
                    if (!noResults) {
                        noResults = document.createElement('div');
                        noResults.id = 'noSearchResults';
                        noResults.style.cssText = 'text-align: center; padding: 2rem; color: #94a3b8;';
                        noResults.innerHTML = '<p>🔍 Tidak ada clan yang cocok dengan "' + searchTerm + '"</p>';
                        document.querySelector('.clan-cards-grid').appendChild(noResults);
                    }
                } else if (noResults) {
                    noResults.remove();
                }
            });
        }

        // Auto-refresh chat every 3 seconds
        // Escape HTML to prevent XSS but preserve emojis
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        <?php if ($user_clan_id): ?>
        let lastMessageId = <?php echo !empty($chat_messages) ? max(array_column($chat_messages, 'id')) : 0; ?>;
        
        setInterval(function() {
            fetch('api/get-chat-messages.php?clan_id=<?php echo $user_clan_id; ?>&last_id=' + lastMessageId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            // Skip if message already exists (to prevent duplicates)
                            if (document.querySelector(`.chat-message[data-id="${msg.id}"]`)) {
                                lastMessageId = Math.max(lastMessageId, msg.id);
                                return;
                            }
                            
                            // Skip own messages (already added when sent)
                            if (msg.user_id == <?php echo $_SESSION['user_id']; ?>) {
                                lastMessageId = Math.max(lastMessageId, msg.id);
                                return;
                            }
                            
                            const messageDiv = document.createElement('div');
                            messageDiv.className = 'chat-message';
                            messageDiv.setAttribute('data-id', msg.id);
                            messageDiv.innerHTML = `
                                <div class="chat-avatar" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">${escapeHtml(msg.nama_lengkap.charAt(0).toUpperCase())}</div>
                                <div class="chat-content">
                                    <div class="chat-username">${escapeHtml(msg.nama_lengkap)}</div>
                                    <div class="chat-text">${escapeHtml(msg.message)}</div>
                                    <div class="chat-time">${new Date(msg.created_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}</div>
                                </div>
                            `;
                            
                            // Remove empty chat if exists
                            const emptyChat = chatMessages.querySelector('.empty-chat');
                            if (emptyChat) emptyChat.remove();
                            
                            chatMessages.appendChild(messageDiv);
                            lastMessageId = Math.max(lastMessageId, msg.id);
                        });
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                })
                .catch(error => console.error('Error fetching messages:', error));
        }, 3000);
        <?php endif; ?>

        // Submit chat form with AJAX
        const chatForm = document.getElementById('chatForm');
        if (chatForm) {
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const messageInput = document.getElementById('messageInput');
                const message = messageInput.value.trim();
                const sendBtn = this.querySelector('.btn-send');
                
                if (!message) return;
                
                // Disable button while sending
                sendBtn.disabled = true;
                sendBtn.style.opacity = '0.6';
                
                fetch('api/send-chat-message.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update lastMessageId to prevent duplicate from polling
                        if (data.message_id) {
                            lastMessageId = Math.max(lastMessageId, data.message_id);
                        }
                        
                        // Add message to chat immediately
                        const messageDiv = document.createElement('div');
                        messageDiv.className = 'chat-message own';
                        messageDiv.setAttribute('data-id', data.message_id || 0);
                        messageDiv.innerHTML = `
                            <div class="chat-avatar" style="background: linear-gradient(135deg, #10b981, #059669);"><?php echo strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1)); ?></div>
                            <div class="chat-content">
                                <div class="chat-username"><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="chat-text">${escapeHtml(message)}</div>
                                <div class="chat-time">${new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}</div>
                            </div>
                        `;
                        
                        // Remove empty chat message if exists
                        const emptyChat = chatMessages.querySelector('.empty-chat');
                        if (emptyChat) emptyChat.remove();
                        
                        chatMessages.appendChild(messageDiv);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                        messageInput.value = '';
                    } else {
                        alert(data.message || 'Gagal mengirim pesan');
                    }
                }).catch(error => {
                    console.error('Error sending message:', error);
                    alert('Gagal mengirim pesan. Silakan coba lagi.');
                }).finally(() => {
                    // Re-enable button
                    sendBtn.disabled = false;
                    sendBtn.style.opacity = '1';
                });
            });
        }
        
        // Emoji Picker Functions
        function toggleEmojiPicker() {
            const picker = document.getElementById('emojiPicker');
            picker.classList.toggle('active');
        }
        
        function addEmoji(emoji) {
            const input = document.getElementById('messageInput');
            const cursorPos = input.selectionStart;
            const textBefore = input.value.substring(0, cursorPos);
            const textAfter = input.value.substring(cursorPos);
            input.value = textBefore + emoji + textAfter;
            input.focus();
            input.setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
            toggleEmojiPicker();
        }
        
        // Close emoji picker when clicking outside
        document.addEventListener('click', function(e) {
            const picker = document.getElementById('emojiPicker');
            const btn = document.querySelector('.emoji-picker-btn');
            if (picker && !picker.contains(e.target) && e.target !== btn) {
                picker.classList.remove('active');
            }
        });
        
        // Copy Invite Code
        function copyInviteCode() {
            const code = document.getElementById('inviteCode');
            const btn = document.getElementById('copyBtn');
            
            navigator.clipboard.writeText(code.textContent).then(() => {
                btn.textContent = '✓ Copied!';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.textContent = '📋 Copy';
                    btn.classList.remove('copied');
                }, 2000);
            }).catch(() => {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = code.textContent;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                btn.textContent = '✓ Copied!';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.textContent = '📋 Copy';
                    btn.classList.remove('copied');
                }, 2000);
            });
        }
        
        // Confirm leave clan
        function confirmLeaveClan() {
            <?php if ($user_clan && $user_clan['leader_id'] == $_SESSION['user_id']): ?>
            const isLeader = true;
            const memberCount = <?php echo $user_clan['total_members'] ?? 1; ?>;
            
            if (memberCount > 1) {
                alert('Anda adalah Leader clan ini.\n\nUntuk keluar, Anda harus:\n1. Transfer kepemimpinan ke member lain, atau\n2. Keluarkan semua member terlebih dahulu.');
                return;
            } else {
                if (confirm('Anda adalah satu-satunya member.\n\nKeluar akan menghapus clan ini secara permanen.\n\nApakah Anda yakin?')) {
                    document.getElementById('leaveClanForm').submit();
                }
            }
            <?php else: ?>
            if (confirm('Apakah Anda yakin ingin keluar dari clan ini?\n\nAnda dapat bergabung kembali kapan saja.')) {
                document.getElementById('leaveClanForm').submit();
            }
            <?php endif; ?>
        }
    </script>

    <?php include 'footer.php'; ?>
    <?php include 'includes/loading.php'; ?>
    <?php include 'includes/toast.php'; ?>

    <script src="assets/js/navbar.js"></script>
</body>
</html>  

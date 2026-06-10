<?php
require_once 'config/config.php';
requireLogin();
require_once 'includes/icons.php';

require_once 'models/Friend.php';
require_once 'models/PrivateMessage.php';

$database = new Database();
$db = $database->getConnection();

$friend = new Friend($db);
$pm = new PrivateMessage($db);

// Update online status
Friend::updateOnlineStatus($db, $_SESSION['user_id'], true);

// Get friends list
$friends_list = $friend->getFriends($_SESSION['user_id']);
$friends = [];
while ($row = $friends_list->fetch(PDO::FETCH_ASSOC)) {
    $row['unread'] = $pm->getUnreadCount($_SESSION['user_id'], $row['id']);
    $friends[] = $row;
}

// Get pending requests
$pending_list = $friend->getPendingRequests($_SESSION['user_id']);
$pending_requests = [];
while ($row = $pending_list->fetch(PDO::FETCH_ASSOC)) {
    $pending_requests[] = $row;
}

// Get suggested users (all students)
$suggested_list = $friend->getSuggestedUsers($_SESSION['user_id'], 20);
$suggested_users = [];
while ($row = $suggested_list->fetch(PDO::FETCH_ASSOC)) {
    $suggested_users[] = $row;
}

// Get total unread
$total_unread = $pm->getUnreadCount($_SESSION['user_id']);

// Selected friend for chat
$selected_friend_id = isset($_GET['chat']) ? intval($_GET['chat']) : null;
$selected_friend = null;
$chat_messages = [];

if ($selected_friend_id) {
    foreach ($friends as $f) {
        if ($f['id'] == $selected_friend_id) {
            $selected_friend = $f;
            break;
        }
    }
    if ($selected_friend) {
        $messages_stmt = $pm->getConversation($_SESSION['user_id'], $selected_friend_id, 100);
        while ($row = $messages_stmt->fetch(PDO::FETCH_ASSOC)) {
            $chat_messages[] = $row;
        }
        $chat_messages = array_reverse($chat_messages);
        // Mark as read
        $pm->markAsRead($_SESSION['user_id'], $selected_friend_id);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/favicon.php'; ?>
    <?php include 'includes/seo.php'; echo seo_meta('Teman - ' . APP_NAME, 'Kelola teman dan chat', 'friends, chat, social'); ?>
    <title>Teman - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/ui-enhancements.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/dark-theme.css">
    <link rel="stylesheet" href="assets/css/glassmorphism.css">
    <style>
        .friends-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.25rem;
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 1.25rem;
            min-height: calc(100vh - 250px);
        }
        
        .friends-sidebar {
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
            overflow: hidden;
        }
        
        .sidebar-section {
            background: rgba(30, 30, 55, 0.5);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .section-header {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0, 0, 0, 0.15);
        }
        
        .section-header h3 {
            color: #e0e7ff;
            font-size: 0.9rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .badge-count {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            color: white;
            font-size: 0.65rem;
            padding: 0.15rem 0.4rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        .friends-list {
            max-height: 350px;
            overflow-y: auto;
        }
        
        .friend-item {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }
        
        .friend-item:hover, .friend-item.active {
            background: rgba(124, 58, 237, 0.1);
        }
        
        .friend-item.active {
            border-left: 3px solid #8b5cf6;
        }
        
        .friend-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
            position: relative;
            flex-shrink: 0;
        }
        
        .friend-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .status-dot {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid #1a1a2e;
        }
        
        .status-dot.online {
            background: #10b981;
        }
        
        .status-dot.offline {
            background: #6b7280;
        }
        
        .friend-info {
            flex: 1;
            min-width: 0;
        }
        
        .friend-name {
            color: #e0e7ff;
            font-weight: 600;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .friend-status {
            font-size: 0.7rem;
            color: #94a3b8;
        }
        
        .friend-status.online {
            color: #10b981;
        }
        
        .unread-badge {
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            padding: 0.15rem 0.4rem;
            border-radius: 10px;
            font-weight: 600;
        }
        
        /* Search Box */
        .search-box {
            padding: 0.75rem 1rem;
        }
        
        .search-input-wrapper {
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 0.625rem 0.875rem 0.625rem 2.5rem;
            background: rgba(15, 15, 35, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            color: #e0e7ff;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .search-input::placeholder {
            color: #94a3b8;
            opacity: 1;
        }
        
        .search-input:focus {
            outline: none;
            border-color: rgba(139, 92, 246, 0.4);
            background: rgba(15, 15, 35, 0.8);
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            display: flex;
            align-items: center;
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(26, 26, 46, 0.98);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 10px;
            margin-top: 0.5rem;
            max-height: 280px;
            overflow-y: auto;
            z-index: 100;
            display: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .search-results.show {
            display: block;
        }
        
        .search-result-item {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.625rem 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .search-result-item:hover {
            background: rgba(124, 58, 237, 0.1);
        }
        
        .btn-add-friend {
            padding: 0.4rem 0.75rem;
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .btn-add-friend:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-add-friend.pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        
        .btn-add-friend.friends {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        /* Pending Requests */
        .request-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.25rem;
            border-bottom: 1px solid rgba(124, 58, 237, 0.1);
        }
        
        .request-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-accept {
            padding: 0.4rem 0.75rem;
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-reject {
            padding: 0.4rem 0.75rem;
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* Chat Panel */
        .chat-panel {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.95) 0%, rgba(30, 30, 63, 0.98) 100%);
            border-radius: 1rem;
            border: 1px solid rgba(124, 58, 237, 0.2);
            display: flex;
            flex-direction: column;
            min-height: 500px;
        }
        
        .chat-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(124, 58, 237, 0.15);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .chat-header-info h3 {
            color: #e0e7ff;
            margin: 0;
            font-size: 1.1rem;
        }
        
        .chat-header-info span {
            font-size: 0.8rem;
            color: #94a3b8;
        }
        
        .chat-header-info span.online {
            color: #10b981;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .chat-message {
            display: flex;
            gap: 0.75rem;
            max-width: 80%;
        }
        
        .chat-message.own {
            flex-direction: row-reverse;
            margin-left: auto;
        }
        
        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        
        .message-content {
            background: rgba(124, 58, 237, 0.15);
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            border-top-left-radius: 0.25rem;
        }
        
        .chat-message.own .message-content {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            border-radius: 1rem;
            border-top-right-radius: 0.25rem;
        }
        
        .message-text {
            color: #e0e7ff;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .message-time {
            font-size: 0.7rem;
            color: rgba(148, 163, 184, 0.7);
            margin-top: 0.25rem;
        }
        
        .chat-message.own .message-time {
            color: rgba(255, 255, 255, 0.7);
            text-align: right;
        }
        
        .chat-input-container {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(124, 58, 237, 0.15);
            display: flex;
            gap: 0.75rem;
        }
        
        .chat-input {
            flex: 1;
            padding: 0.75rem 1rem;
            background: rgba(15, 15, 35, 0.6);
            border: 1px solid rgba(124, 58, 237, 0.2);
            border-radius: 0.5rem;
            color: #e0e7ff;
            font-size: 0.9rem;
        }
        
        .chat-input:focus {
            outline: none;
            border-color: rgba(124, 58, 237, 0.5);
        }
        
        .btn-send {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-send:hover {
            transform: translateY(-2px);
        }
        
        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #94a3b8;
            text-align: center;
            padding: 2rem;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: #e0e7ff;
            margin-bottom: 0.5rem;
        }
        
        /* Suggested Users Panel */
        .suggested-users-panel {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .suggested-users-grid {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            align-content: start;
        }
        
        .user-card {
            background: linear-gradient(145deg, rgba(37, 37, 80, 0.7) 0%, rgba(45, 45, 90, 0.6) 100%);
            border-radius: 1rem;
            padding: 1.25rem;
            border: 1px solid rgba(124, 58, 237, 0.2);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 0.75rem;
        }
        
        .user-card:hover {
            border-color: rgba(124, 58, 237, 0.4);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(124, 58, 237, 0.2);
        }
        
        .user-card-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            position: relative;
        }
        
        .user-card-avatar .status-dot {
            width: 14px;
            height: 14px;
            bottom: 2px;
            right: 2px;
        }
        
        .user-card-info {
            flex: 1;
        }
        
        .user-card-name {
            color: #e0e7ff;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }
        
        .user-card-username {
            color: #94a3b8;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }
        
        .user-card-stats {
            display: flex;
            justify-content: center;
            gap: 1rem;
            font-size: 0.8rem;
            color: #a78bfa;
            margin-bottom: 0.5rem;
        }
        
        .user-card-stats span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .user-card-status {
            font-size: 0.75rem;
            margin-bottom: 0.5rem;
        }
        
        .user-card-status .online-text {
            color: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
        }
        
        .user-card-status .offline-text {
            color: #6b7280;
        }
        
        .user-card-actions {
            width: 100%;
        }
        
        .btn-user-action {
            width: 100%;
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
        }
        
        .btn-user-action.btn-add {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            color: white;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }
        
        .btn-user-action.btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
        }
        
        .btn-user-action.btn-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            cursor: not-allowed;
        }
        
        .btn-user-action.btn-chat {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .btn-user-action.btn-chat:hover {
            background: rgba(16, 185, 129, 0.3);
        }
        
        @media (max-width: 900px) {
            .friends-container {
                grid-template-columns: 1fr;
                min-height: auto;
            }
            .chat-panel {
                min-height: 400px;
            }
            .suggested-users-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'navbar.php'; ?>

    <div class="dashboard-main-container">
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h1><?php icon('users', 24); ?> Teman</h1>
                <p>Kelola teman dan chat dengan mereka</p>
            </div>

            <div class="friends-container">
                <!-- Sidebar -->
                <div class="friends-sidebar">
                    <!-- Search -->
                    <div class="sidebar-section">
                        <div class="search-box">
                            <div class="search-input-wrapper">
                                <span class="search-icon"><?php icon('search', 16); ?></span>
                                <input type="text" class="search-input" id="searchInput" placeholder="Cari teman baru...">
                                <div class="search-results" id="searchResults"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Requests -->
                    <?php if (!empty($pending_requests)): ?>
                    <div class="sidebar-section">
                        <div class="section-header">
                            <h3><?php icon('user-plus', 18); ?> Permintaan</h3>
                            <span class="badge-count"><?php echo count($pending_requests); ?></span>
                        </div>
                        <div class="friends-list" id="pendingList">
                            <?php foreach ($pending_requests as $request): ?>
                            <div class="request-item" data-id="<?php echo $request['sender_id']; ?>">
                                <div class="friend-avatar">
                                    <?php echo strtoupper(substr($request['nama_lengkap'], 0, 1)); ?>
                                </div>
                                <div class="friend-info">
                                    <div class="friend-name"><?php echo htmlspecialchars($request['nama_lengkap']); ?></div>
                                    <div class="friend-status"><?php echo number_format($request['total_xp']); ?> XP</div>
                                </div>
                                <div class="request-actions">
                                    <button class="btn-accept" onclick="acceptRequest(<?php echo $request['sender_id']; ?>)"><?php icon('check', 14); ?></button>
                                    <button class="btn-reject" onclick="rejectRequest(<?php echo $request['sender_id']; ?>)"><?php icon('x', 14); ?></button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Friends List -->
                    <div class="sidebar-section" style="flex: 1; overflow: hidden;">
                        <div class="section-header">
                            <h3><?php icon('users', 18); ?> Teman</h3>
                            <span class="badge-count"><?php echo count($friends); ?></span>
                        </div>
                        <div class="friends-list" id="friendsList">
                            <?php if (empty($friends)): ?>
                            <div class="empty-state" style="padding: 2rem;">
                                <p>Belum ada teman</p>
                                <small>Cari dan tambahkan teman baru!</small>
                            </div>
                            <?php else: ?>
                                <?php foreach ($friends as $f): ?>
                                <div class="friend-item <?php echo $selected_friend_id == $f['id'] ? 'active' : ''; ?>" 
                                     onclick="window.location.href='friends.php?chat=<?php echo $f['id']; ?>'">
                                    <div class="friend-avatar">
                                        <?php echo strtoupper(substr($f['nama_lengkap'], 0, 1)); ?>
                                        <span class="status-dot <?php echo $f['is_online'] ? 'online' : 'offline'; ?>"></span>
                                    </div>
                                    <div class="friend-info">
                                        <div class="friend-name"><?php echo htmlspecialchars($f['nama_lengkap']); ?></div>
                                        <div class="friend-status <?php echo $f['is_online'] ? 'online' : ''; ?>">
                                            <?php if ($f['is_online']): ?>
                                                Online
                                            <?php else: ?>
                                                <?php echo $f['last_seen'] ? 'Terakhir online ' . date('d M H:i', strtotime($f['last_seen'])) : 'Offline'; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($f['unread'] > 0): ?>
                                    <span class="unread-badge"><?php echo $f['unread']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Chat Panel -->
                <div class="chat-panel">
                    <?php if ($selected_friend): ?>
                    <div class="chat-header">
                        <div class="friend-avatar">
                            <?php echo strtoupper(substr($selected_friend['nama_lengkap'], 0, 1)); ?>
                            <span class="status-dot <?php echo $selected_friend['is_online'] ? 'online' : 'offline'; ?>"></span>
                        </div>
                        <div class="chat-header-info">
                            <h3><?php echo htmlspecialchars($selected_friend['nama_lengkap']); ?></h3>
                            <span class="<?php echo $selected_friend['is_online'] ? 'online' : ''; ?>">
                                <?php echo $selected_friend['is_online'] ? 'Online' : 'Offline'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="chat-messages" id="chatMessages">
                        <?php foreach ($chat_messages as $msg): ?>
                        <div class="chat-message <?php echo $msg['sender_id'] == $_SESSION['user_id'] ? 'own' : ''; ?>">
                            <div class="message-avatar">
                                <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                            </div>
                            <div class="message-content">
                                <div class="message-text"><?php echo htmlspecialchars($msg['message']); ?></div>
                                <div class="message-time"><?php echo date('H:i', strtotime($msg['created_at'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <form class="chat-input-container" id="chatForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="receiver_id" value="<?php echo $selected_friend_id; ?>">
                        <input type="text" class="chat-input" name="message" id="messageInput" placeholder="Tulis pesan..." autocomplete="off">
                        <button type="submit" class="btn-send"><?php icon('send', 16); ?> Kirim</button>
                    </form>
                    <?php else: ?>
                    <!-- Show Suggested Users when no chat selected -->
                    <div class="suggested-users-panel">
                        <div class="section-header" style="padding: 1.25rem;">
                            <h3><?php icon('user-plus', 18); ?> Cari Teman Baru</h3>
                            <span class="badge-count"><?php echo count($suggested_users); ?> user</span>
                        </div>
                        <div class="suggested-users-grid">
                            <?php if (empty($suggested_users)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">👥</div>
                                <h3>Tidak ada user lain</h3>
                                <p>Belum ada user lain yang terdaftar</p>
                            </div>
                            <?php else: ?>
                                <?php foreach ($suggested_users as $user): ?>
                                <div class="user-card" id="user-card-<?php echo $user['id']; ?>">
                                    <div class="user-card-avatar">
                                        <?php echo strtoupper(substr($user['nama_lengkap'], 0, 1)); ?>
                                        <span class="status-dot <?php echo $user['is_online'] ? 'online' : 'offline'; ?>"></span>
                                    </div>
                                    <div class="user-card-info">
                                        <div class="user-card-name"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
                                        <div class="user-card-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                                        <div class="user-card-stats">
                                            <span><?php icon('star', 12); ?> <?php echo number_format($user['total_xp']); ?> XP</span>
                                            <span><?php icon('trending-up', 12); ?> Level <?php echo $user['level']; ?></span>
                                        </div>
                                        <div class="user-card-status">
                                            <?php if ($user['is_online']): ?>
                                                <span class="online-text"><?php icon('check-circle', 12); ?> Online</span>
                                            <?php else: ?>
                                                <span class="offline-text"><?php echo $user['last_seen'] ? 'Terakhir online ' . date('d M', strtotime($user['last_seen'])) : 'Offline'; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="user-card-actions">
                                        <?php if ($user['friendship_status'] === 'accepted'): ?>
                                            <a href="friends.php?chat=<?php echo $user['id']; ?>" class="btn-user-action btn-chat">
                                                <?php icon('message-circle', 14); ?> Chat
                                            </a>
                                        <?php elseif ($user['friendship_status'] === 'pending'): ?>
                                            <button class="btn-user-action btn-pending" disabled>
                                                <?php icon('clock', 14); ?> Menunggu
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-user-action btn-add" onclick="sendRequest(<?php echo $user['id']; ?>, this)">
                                                <?php icon('user-plus', 14); ?> Tambah
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <?php include 'includes/toast.php'; ?>

    <script src="assets/js/navbar.js"></script>
    <script>
        const csrfToken = '<?php echo generateCsrfToken(); ?>';
        const currentUserId = <?php echo $_SESSION['user_id']; ?>;
        const selectedFriendId = <?php echo $selected_friend_id ?: 'null'; ?>;
        
        // Search functionality
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.classList.remove('show');
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetch(`api/friends.php?action=search&q=${encodeURIComponent(query)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success && data.users.length > 0) {
                            searchResults.innerHTML = data.users.map(user => `
                                <div class="search-result-item">
                                    <div class="friend-avatar" style="width:36px;height:36px;font-size:0.85rem;">
                                        ${user.nama_lengkap.charAt(0).toUpperCase()}
                                        <span class="status-dot ${user.is_online ? 'online' : 'offline'}"></span>
                                    </div>
                                    <div class="friend-info">
                                        <div class="friend-name">${user.nama_lengkap}</div>
                                        <div class="friend-status">${Number(user.total_xp).toLocaleString()} XP</div>
                                    </div>
                                    ${getFriendButton(user)}
                                </div>
                            `).join('');
                            searchResults.classList.add('show');
                        } else {
                            searchResults.innerHTML = '<div class="search-result-item"><span style="color:#94a3b8;">Tidak ditemukan</span></div>';
                            searchResults.classList.add('show');
                        }
                    });
            }, 300);
        });
        
        function getFriendButton(user) {
            if (user.friendship_status === 'accepted') {
                return '<button class="btn-add-friend friends" disabled>✓ Teman</button>';
            } else if (user.friendship_status === 'pending') {
                return '<button class="btn-add-friend pending" disabled>Menunggu</button>';
            }
            return `<button class="btn-add-friend" onclick="sendRequest(${user.id}, this)">+ Tambah</button>`;
        }
        
        function sendRequest(friendId, btnElement = null) {
            fetch('api/friends.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=send_request&friend_id=${friendId}&csrf_token=${csrfToken}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Update button UI
                    if (btnElement) {
                        btnElement.className = 'btn-user-action btn-pending';
                        btnElement.disabled = true;
                        btnElement.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> Menunggu';
                    }
                    searchInput.value = '';
                    searchResults.classList.remove('show');
                    
                    // Show toast notification
                    if (typeof showToast === 'function') {
                        showToast('Permintaan pertemanan terkirim!', 'success');
                    }
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.message, 'error');
                    } else {
                        alert(data.message);
                    }
                }
            });
        }
        
        function acceptRequest(friendId) {
            fetch('api/friends.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=accept_request&friend_id=${friendId}&csrf_token=${csrfToken}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }
        
        function rejectRequest(friendId) {
            fetch('api/friends.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=reject_request&friend_id=${friendId}&csrf_token=${csrfToken}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.querySelector(`.request-item[data-id="${friendId}"]`).remove();
                }
            });
        }
        
        // Chat functionality
        const chatMessages = document.getElementById('chatMessages');
        const chatForm = document.getElementById('chatForm');
        
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        if (chatForm) {
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const messageInput = document.getElementById('messageInput');
                const message = messageInput.value.trim();
                
                if (!message) return;
                
                fetch('api/friends.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=send_message&receiver_id=${selectedFriendId}&message=${encodeURIComponent(message)}&csrf_token=${csrfToken}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // Add message to chat
                        const msgDiv = document.createElement('div');
                        msgDiv.className = 'chat-message own';
                        msgDiv.innerHTML = `
                            <div class="message-avatar">${'<?php echo strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1)); ?>'}</div>
                            <div class="message-content">
                                <div class="message-text">${message}</div>
                                <div class="message-time">${new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}</div>
                            </div>
                        `;
                        chatMessages.appendChild(msgDiv);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                        messageInput.value = '';
                    }
                });
            });
            
            // Poll for new messages
            let lastMessageId = <?php echo !empty($chat_messages) ? max(array_column($chat_messages, 'id')) : 0; ?>;
            
            setInterval(() => {
                if (!selectedFriendId) return;
                
                fetch(`api/friends.php?action=get_messages&friend_id=${selectedFriendId}&last_id=${lastMessageId}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.success && data.messages.length > 0) {
                            data.messages.forEach(msg => {
                                if (msg.sender_id != currentUserId) {
                                    const msgDiv = document.createElement('div');
                                    msgDiv.className = 'chat-message';
                                    msgDiv.innerHTML = `
                                        <div class="message-avatar">${msg.sender_name.charAt(0).toUpperCase()}</div>
                                        <div class="message-content">
                                            <div class="message-text">${msg.message}</div>
                                            <div class="message-time">${new Date(msg.created_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})}</div>
                                        </div>
                                    `;
                                    chatMessages.appendChild(msgDiv);
                                }
                                lastMessageId = Math.max(lastMessageId, msg.id);
                            });
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
                    });
            }, 3000);
        }
        
        // Update online status
        setInterval(() => {
            fetch('api/friends.php?action=update_status');
        }, 30000);
        
        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-input-wrapper')) {
                searchResults.classList.remove('show');
            }
        });
    </script>
</body>
</html>

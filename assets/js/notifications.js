document.addEventListener('DOMContentLoaded', function() {
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationBadge = document.getElementById('notificationBadge');
    const notificationList = document.getElementById('notificationList');
    const markAllReadBtn = document.getElementById('markAllRead');
    
    let isOpen = false;
    let unreadCount = 0;

    // Initialize
    fetchUnreadCount();
    
    // Poll for new notifications every 60 seconds
    setInterval(fetchUnreadCount, 60000);

    // Toggle Dropdown
    notificationBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        isOpen = !isOpen;
        
        if (isOpen) {
            notificationDropdown.classList.add('active');
            fetchNotifications();
        } else {
            notificationDropdown.classList.remove('active');
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (isOpen && !notificationDropdown.contains(e.target) && !notificationBtn.contains(e.target)) {
            isOpen = false;
            notificationDropdown.classList.remove('active');
        }
    });

    // Mark all as read
    markAllReadBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        markAllAsRead();
    });

    function fetchUnreadCount() {
        fetch('api/notifications.php?action=unread_count')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    updateBadge(data.count);
                }
            })
            .catch(error => console.error('Error fetching notification count:', error));
    }

    function updateBadge(count) {
        unreadCount = count;
        if (count > 0) {
            notificationBadge.textContent = count > 99 ? '99+' : count;
            notificationBadge.classList.add('show');
        } else {
            notificationBadge.classList.remove('show');
        }
    }

    function fetchNotifications() {
        notificationList.innerHTML = '<div class="notification-empty">Loading...</div>';
        
        fetch('api/notifications.php?action=get_all&limit=10')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    renderNotifications(data.data);
                    // If we have unread notifications, mark them as read in UI after a delay or when clicked?
                    // Usually we mark as read when clicked or when "Mark all read" is clicked.
                    // For now, let's keep them unread until interaction.
                }
            })
            .catch(error => {
                console.error('Error fetching notifications:', error);
                notificationList.innerHTML = '<div class="notification-empty">Error loading notifications</div>';
            });
    }

    function renderNotifications(notifications) {
        if (notifications.length === 0) {
            notificationList.innerHTML = '<div class="notification-empty">Tidak ada notifikasi</div>';
            return;
        }

        notificationList.innerHTML = '';
        notifications.forEach(notif => {
            const item = document.createElement('li');
            item.className = `notification-item ${notif.is_read == 0 ? 'unread' : ''}`;
            item.onclick = () => handleNotificationClick(notif);
            
            let icon = getIconForType(notif.type);
            
            item.innerHTML = `
                <div class="notification-icon">
                    ${icon}
                </div>
                <div class="notification-content">
                    <p class="notification-text">${notif.message}</p>
                    <span class="notification-time">${formatTime(notif.created_at)}</span>
                </div>
            `;
            
            notificationList.appendChild(item);
        });
    }

    function getIconForType(type) {
        // Return SVG based on type
        switch(type) {
            case 'achievement':
                return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>';
            case 'course_completed':
                return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
            case 'reply':
                return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>';
            case 'system':
            default:
                return '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
        }
    }

    function formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // seconds
        
        if (diff < 60) return 'Baru saja';
        if (diff < 3600) return `${Math.floor(diff / 60)} menit yang lalu`;
        if (diff < 86400) return `${Math.floor(diff / 3600)} jam yang lalu`;
        if (diff < 604800) return `${Math.floor(diff / 86400)} hari yang lalu`;
        return date.toLocaleDateString('id-ID');
    }

    function handleNotificationClick(notif) {
        // Mark as read
        if (notif.is_read == 0) {
            markAsRead(notif.id);
        }
        
        // Navigate if link exists (assuming link is stored in related_id or constructed)
        // For now, we'll just reload or do nothing specific unless we add links to notifications
        // Ideally, the API should return a 'link' field or we construct it here.
        
        // Simple navigation logic based on type
        if (notif.type === 'course_completed') {
            window.location.href = 'courses.php';
        } else if (notif.type === 'achievement') {
            window.location.href = 'profile.php';
        } else if (notif.type === 'reply') {
            // If we had the lesson_id, we could go there. 
            // For now, maybe just stay or go to a notifications page.
        }
    }

    function markAsRead(id) {
        const formData = new FormData();
        formData.append('id', id);
        
        fetch('api/notifications.php?action=mark_read', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                fetchUnreadCount(); // Update badge
                // Update UI item style
                // (In a real app, we'd find the DOM element and remove 'unread' class)
                fetchNotifications(); // Refresh list to show updated state
            }
        });
    }

    function markAllAsRead() {
        fetch('api/notifications.php?action=mark_all_read', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                fetchUnreadCount();
                fetchNotifications();
            }
        });
    }
});

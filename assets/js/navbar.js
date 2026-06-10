// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navbarMenu = document.getElementById('navbarMenu');

    if (mobileMenuToggle && navbarMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            navbarMenu.classList.toggle('active');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenuToggle.contains(event.target) && !navbarMenu.contains(event.target)) {
                mobileMenuToggle.classList.remove('active');
                navbarMenu.classList.remove('active');
            }
        });

        // Close menu when clicking on a menu item
        const menuItems = navbarMenu.querySelectorAll('.nav-menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                mobileMenuToggle.classList.remove('active');
                navbarMenu.classList.remove('active');
            });
        });
    }
    
    // Online status heartbeat - update every 30 seconds
    function updateOnlineStatus() {
        fetch('api/friends.php?action=update_status')
            .catch(err => console.log('Status update failed'));
    }
    
    // Update immediately on page load
    updateOnlineStatus();
    
    // Then update every 30 seconds
    setInterval(updateOnlineStatus, 30000);
    
    // Update status when user becomes active again
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            updateOnlineStatus();
        }
    });
});


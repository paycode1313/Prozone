// Global Menu Toggle Script (Mobile & Desktop)
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (!sidebar) return;
    
    // Function to apply collapsed state
    function applyCollapsedState(isCollapsed) {
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            if (mainContent) mainContent.classList.add('sidebar-collapsed');
        } else {
            sidebar.classList.remove('collapsed');
            if (mainContent) mainContent.classList.remove('sidebar-collapsed');
        }
    }
    
    // Load saved state from localStorage (only on desktop)
    if (window.innerWidth > 1024) {
        const savedState = localStorage.getItem('sidebarState');
        if (savedState === 'collapsed') {
            applyCollapsedState(true);
        }
    }
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            
            if (window.innerWidth > 1024) {
                // Desktop: collapse/expand
                const isCollapsed = sidebar.classList.contains('collapsed');
                applyCollapsedState(!isCollapsed);
                
                // Save state to localStorage
                localStorage.setItem('sidebarState', !isCollapsed ? 'collapsed' : 'expanded');
            } else {
                // Mobile: show/hide
                sidebar.classList.toggle('show');
            }
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(e.target) && 
                    !mobileMenuToggle.contains(e.target) && 
                    sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 1024) {
                sidebar.classList.remove('show');
                // Restore saved state
                const savedState = localStorage.getItem('sidebarState');
                applyCollapsedState(savedState === 'collapsed');
            } else {
                sidebar.classList.remove('collapsed');
                if (mainContent) mainContent.classList.remove('sidebar-collapsed');
            }
        }, 100);
    });
});


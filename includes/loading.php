<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p class="loading-text">Memuat...</p>
    </div>
</div>

<style>
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(10, 10, 26, 0.9);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .loading-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .loading-spinner {
        text-align: center;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 3px solid rgba(139, 92, 246, 0.2);
        border-top-color: #8b5cf6;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 1rem;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .loading-text {
        color: #a78bfa;
        font-size: 0.9rem;
        font-weight: 500;
        margin: 0;
    }

    /* Button Loading State */
    .btn-loading {
        position: relative;
        pointer-events: none;
        opacity: 0.7;
    }

    .btn-loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid transparent;
        border-top-color: currentColor;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }

    .btn-loading span {
        visibility: hidden;
    }

    /* Skeleton Loading */
    .skeleton {
        background: linear-gradient(90deg, 
            rgba(139, 92, 246, 0.1) 25%, 
            rgba(139, 92, 246, 0.2) 50%, 
            rgba(139, 92, 246, 0.1) 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s infinite;
        border-radius: 4px;
    }

    @keyframes skeleton-loading {
        0% {
            background-position: 200% 0;
        }
        100% {
            background-position: -200% 0;
        }
    }

    .skeleton-text {
        height: 1rem;
        margin-bottom: 0.5rem;
    }

    .skeleton-text.short {
        width: 60%;
    }

    .skeleton-card {
        height: 200px;
        border-radius: 12px;
    }

    .skeleton-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
    }

    /* Page Transition */
    .page-transition {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
    // Loading Overlay Functions
    const LoadingOverlay = {
        show: function(text = 'Memuat...') {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.querySelector('.loading-text').textContent = text;
                overlay.style.display = 'flex';
                setTimeout(() => overlay.classList.add('active'), 10);
            }
        },
        hide: function() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.classList.remove('active');
                setTimeout(() => overlay.style.display = 'none', 300);
            }
        }
    };

    // Button Loading State
    function setButtonLoading(button, loading = true) {
        if (loading) {
            button.classList.add('btn-loading');
            button.disabled = true;
        } else {
            button.classList.remove('btn-loading');
            button.disabled = false;
        }
    }

    // Auto-apply page transition
    document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('page-transition');
    });
</script>

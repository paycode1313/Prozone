<!-- Toast Notification System -->
<div id="toastContainer" class="toast-container"></div>

<style>
    .toast-container {
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9998;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 400px;
    }

    .toast {
        padding: 1rem 1.25rem;
        border-radius: 12px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        animation: slideIn 0.3s ease;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        position: relative;
        overflow: hidden;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .toast.hiding {
        animation: slideOut 0.3s ease forwards;
    }

    @keyframes slideOut {
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    .toast-icon {
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .toast-content {
        flex: 1;
    }

    .toast-title {
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 4px;
    }

    .toast-message {
        font-size: 0.85rem;
        opacity: 0.9;
        line-height: 1.4;
    }

    .toast-close {
        background: none;
        border: none;
        color: inherit;
        opacity: 0.6;
        cursor: pointer;
        padding: 0;
        font-size: 1.25rem;
        line-height: 1;
        transition: opacity 0.2s;
    }

    .toast-close:hover {
        opacity: 1;
    }

    .toast-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        width: 100%;
        transform-origin: left;
        animation: progress 5s linear forwards;
    }

    @keyframes progress {
        from {
            transform: scaleX(1);
        }
        to {
            transform: scaleX(0);
        }
    }

    /* Toast Types */
    .toast.success {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.95) 0%, rgba(5, 150, 105, 0.95) 100%);
        color: white;
    }

    .toast.success .toast-progress {
        background: rgba(255, 255, 255, 0.3);
    }

    .toast.error {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.95) 0%, rgba(220, 38, 38, 0.95) 100%);
        color: white;
    }

    .toast.error .toast-progress {
        background: rgba(255, 255, 255, 0.3);
    }

    .toast.warning {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.95) 0%, rgba(217, 119, 6, 0.95) 100%);
        color: white;
    }

    .toast.warning .toast-progress {
        background: rgba(255, 255, 255, 0.3);
    }

    .toast.info {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.95) 0%, rgba(37, 99, 235, 0.95) 100%);
        color: white;
    }

    .toast.info .toast-progress {
        background: rgba(255, 255, 255, 0.3);
    }

    /* Mobile Responsive */
    @media (max-width: 480px) {
        .toast-container {
            left: 10px;
            right: 10px;
            max-width: none;
        }
    }
</style>

<script>
    // Toast Notification Functions
    const Toast = {
        show: function(type, title, message, duration = 5000) {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };

            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <span class="toast-icon">${icons[type] || 'ℹ'}</span>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="Toast.close(this.parentElement)">&times;</button>
                <div class="toast-progress" style="animation-duration: ${duration}ms"></div>
            `;

            container.appendChild(toast);

            // Auto remove
            setTimeout(() => {
                this.close(toast);
            }, duration);

            return toast;
        },

        close: function(toast) {
            if (!toast || toast.classList.contains('hiding')) return;
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 300);
        },

        success: function(title, message, duration) {
            return this.show('success', title, message, duration);
        },

        error: function(title, message, duration) {
            return this.show('error', title, message, duration);
        },

        warning: function(title, message, duration) {
            return this.show('warning', title, message, duration);
        },

        info: function(title, message, duration) {
            return this.show('info', title, message, duration);
        }
    };

    // Global Error Handler
    window.onerror = function(msg, url, lineNo, columnNo, error) {
        console.error('Error:', msg, 'at', url, 'line', lineNo);
        // Optionally show toast for critical errors
        // Toast.error('Terjadi Kesalahan', 'Silakan refresh halaman dan coba lagi.');
        return false;
    };

    // Handle unhandled promise rejections
    window.addEventListener('unhandledrejection', function(event) {
        console.error('Unhandled promise rejection:', event.reason);
    });

    // Enhanced fetch with error handling
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args)
            .then(response => {
                if (!response.ok && response.status >= 500) {
                    Toast.error('Server Error', 'Terjadi kesalahan pada server. Silakan coba lagi nanti.');
                }
                return response;
            })
            .catch(error => {
                if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
                    Toast.error('Koneksi Error', 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.');
                }
                throw error;
            });
    };
</script>

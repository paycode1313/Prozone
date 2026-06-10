<!-- Footer -->
<footer class="site-footer">
    <div class="footer-glow"></div>
    <div class="footer-container">
        <div class="footer-brand">
            <div class="footer-logo">
                <img src="assets/img/Prozone Purple.png" alt="Prozone" class="footer-logo-img">
            </div>
            <p class="footer-tagline">Platform pembelajaran coding interaktif dengan gamifikasi untuk developer masa depan Indonesia.</p>
        </div>
        <div class="footer-divider"></div>
        <div class="footer-bottom">
            <p class="footer-copyright">&copy; <?php echo date('Y'); ?> <?php echo defined('APP_NAME') ? APP_NAME : 'Prozone'; ?>. All rights reserved.</p>
            <p class="footer-made">Crafted with ❤️ in Indonesia</p>
        </div>
    </div>
</footer>

<style>
    .site-footer {
        background: linear-gradient(180deg, 
            rgba(20, 20, 40, 0.98) 0%, 
            rgba(10, 10, 25, 1) 100%);
        color: #e0e7ff;
        padding: 3rem 2rem 2rem;
        text-align: center;
        position: relative;
        margin-top: 4rem;
        overflow: hidden;
    }

    .footer-glow {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80%;
        height: 2px;
        background: linear-gradient(90deg, 
            transparent 0%, 
            rgba(124, 58, 237, 0.4) 15%,
            rgba(139, 92, 246, 0.8) 35%,
            rgba(167, 139, 250, 1) 50%,
            rgba(139, 92, 246, 0.8) 65%,
            rgba(124, 58, 237, 0.4) 85%,
            transparent 100%);
        box-shadow: 
            0 0 20px rgba(139, 92, 246, 0.5), 
            0 0 40px rgba(139, 92, 246, 0.3),
            0 0 60px rgba(139, 92, 246, 0.1);
    }

    .footer-container {
        max-width: 900px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }

    .footer-brand {
        margin-bottom: 2rem;
    }

    .footer-logo {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .footer-logo-img {
        height: 50px;
        width: auto;
        object-fit: contain;
        filter: drop-shadow(0 4px 20px rgba(139, 92, 246, 0.3));
    }

    .footer-tagline {
        color: #94a3b8;
        font-size: 0.95rem;
        line-height: 1.7;
        max-width: 550px;
        margin: 0 auto;
    }

    .footer-divider {
        height: 1px;
        background: linear-gradient(90deg, 
            transparent 0%, 
            rgba(139, 92, 246, 0.25) 30%, 
            rgba(139, 92, 246, 0.25) 70%,
            transparent 100%);
        margin: 2rem 0;
    }

    .footer-bottom {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.625rem;
    }

    .footer-copyright {
        color: #64748b;
        font-size: 0.875rem;
        margin: 0;
        font-weight: 500;
    }

    .footer-made {
        color: #475569;
        font-size: 0.8rem;
        margin: 0;
    }

    @media (max-width: 768px) {
        .site-footer {
            padding: 2.5rem 1.25rem 1.75rem;
            margin-top: 3rem;
        }

        .footer-tagline {
            font-size: 0.875rem;
            padding: 0 1rem;
        }

        .footer-logo-img {
            height: 42px;
        }
        
        .footer-divider {
            margin: 1.5rem 0;
        }
    }
</style>

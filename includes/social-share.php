<!-- Social Share Component -->
<div class="social-share-container" id="socialShare">
    <button class="share-trigger" onclick="toggleShareMenu()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="18" cy="5" r="3"></circle>
            <circle cx="6" cy="12" r="3"></circle>
            <circle cx="18" cy="19" r="3"></circle>
            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
        </svg>
        <span>Bagikan</span>
    </button>
    
    <div class="share-menu" id="shareMenu">
        <div class="share-header">
            <span>Bagikan ke</span>
            <button class="close-share" onclick="toggleShareMenu()">×</button>
        </div>
        <div class="share-options">
            <button class="share-option" onclick="shareToTwitter()">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                <span>X (Twitter)</span>
            </button>
            <button class="share-option" onclick="shareToFacebook()">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                <span>Facebook</span>
            </button>
            <button class="share-option" onclick="shareToLinkedIn()">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                <span>LinkedIn</span>
            </button>
            <button class="share-option" onclick="shareToWhatsApp()">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                <span>WhatsApp</span>
            </button>
            <button class="share-option" onclick="shareToTelegram()">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                <span>Telegram</span>
            </button>
            <button class="share-option" onclick="copyLink()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                <span>Salin Link</span>
            </button>
        </div>
    </div>
</div>

<style>
.social-share-container {
    position: relative;
    display: inline-block;
}

.share-trigger {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(139, 92, 246, 0.1);
    border: 1px solid rgba(139, 92, 246, 0.2);
    color: #a78bfa;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.share-trigger:hover {
    background: rgba(139, 92, 246, 0.2);
    border-color: rgba(139, 92, 246, 0.4);
}

.share-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    background: #1e1e32;
    border: 1px solid rgba(139, 92, 246, 0.2);
    border-radius: 12px;
    padding: 0.75rem;
    min-width: 200px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    z-index: 100;
    display: none;
    animation: fadeIn 0.2s ease;
}

.share-menu.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.share-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 0.75rem;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid rgba(139, 92, 246, 0.1);
}

.share-header span {
    color: #94a3b8;
    font-size: 0.8rem;
    font-weight: 500;
}

.close-share {
    background: none;
    border: none;
    color: #64748b;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.share-options {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.share-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.625rem 0.75rem;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: #e2e8f0;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
}

.share-option:hover {
    background: rgba(139, 92, 246, 0.1);
}

.share-option svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}
</style>

<script>
// Social Share Functions
let shareData = {
    title: document.title,
    text: document.querySelector('meta[name="description"]')?.content || 'Belajar coding di Prozone!',
    url: window.location.href
};

function setShareData(title, text, url) {
    shareData = { title, text, url: url || window.location.href };
}

function toggleShareMenu() {
    document.getElementById('shareMenu').classList.toggle('active');
}

// Close menu when clicking outside
document.addEventListener('click', function(e) {
    const container = document.getElementById('socialShare');
    if (container && !container.contains(e.target)) {
        document.getElementById('shareMenu')?.classList.remove('active');
    }
});

function shareToTwitter() {
    const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareData.text)}&url=${encodeURIComponent(shareData.url)}`;
    window.open(url, '_blank', 'width=600,height=400');
    toggleShareMenu();
}

function shareToFacebook() {
    const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareData.url)}`;
    window.open(url, '_blank', 'width=600,height=400');
    toggleShareMenu();
}

function shareToLinkedIn() {
    const url = `https://www.linkedin.com/shareArticle?mini=true&url=${encodeURIComponent(shareData.url)}&title=${encodeURIComponent(shareData.title)}`;
    window.open(url, '_blank', 'width=600,height=400');
    toggleShareMenu();
}

function shareToWhatsApp() {
    const url = `https://wa.me/?text=${encodeURIComponent(shareData.text + ' ' + shareData.url)}`;
    window.open(url, '_blank');
    toggleShareMenu();
}

function shareToTelegram() {
    const url = `https://t.me/share/url?url=${encodeURIComponent(shareData.url)}&text=${encodeURIComponent(shareData.text)}`;
    window.open(url, '_blank');
    toggleShareMenu();
}

function copyLink() {
    navigator.clipboard.writeText(shareData.url).then(() => {
        if (typeof showToast === 'function') {
            showToast('Link berhasil disalin!', 'success');
        } else {
            alert('Link berhasil disalin!');
        }
        toggleShareMenu();
    });
}

// Native share if available
function nativeShare() {
    if (navigator.share) {
        navigator.share(shareData).catch(console.error);
    } else {
        toggleShareMenu();
    }
}
</script>

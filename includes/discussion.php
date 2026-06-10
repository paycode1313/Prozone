<!-- Discussion/Comment Section Component -->
<div class="discussion-section" id="discussionSection">
    <div class="discussion-header">
        <h3>Diskusi</h3>
        <span class="comment-count" id="commentCount">0 komentar</span>
    </div>
    
    <!-- Comment Form -->
    <div class="comment-form-wrapper">
        <form id="commentForm" class="comment-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="lesson_id" value="<?php echo $lesson_id ?? 0; ?>">
            <input type="hidden" name="parent_id" id="parentId" value="">
            
            <div class="comment-input-wrapper">
                <div class="user-avatar-small">
                    <?php 
                    $avatar = $_SESSION['avatar'] ?? '';
                    if ($avatar && file_exists('assets/uploads/avatars/' . $avatar)): ?>
                        <img src="assets/uploads/avatars/<?php echo htmlspecialchars($avatar); ?>" alt="Avatar">
                    <?php else: ?>
                        <span><?php echo strtoupper(substr($_SESSION['nama_lengkap'] ?? 'U', 0, 1)); ?></span>
                    <?php endif; ?>
                </div>
                <div class="comment-input-box">
                    <textarea name="content" id="commentContent" placeholder="Tulis komentar atau pertanyaan..." rows="2" required></textarea>
                    <div class="comment-actions">
                        <span class="reply-indicator" id="replyIndicator" style="display: none;">
                            Membalas <strong id="replyToName"></strong>
                            <button type="button" onclick="cancelReply()" class="cancel-reply">×</button>
                        </span>
                        <button type="submit" class="btn-send-comment">Kirim</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Comments List -->
    <div class="comments-list" id="commentsList">
        <div class="loading-comments">Memuat diskusi...</div>
    </div>
</div>

<style>
.discussion-section {
    background: var(--bg-card, #1e1e32);
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1.5rem;
    border: 1px solid rgba(139, 92, 246, 0.15);
}

.discussion-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.25rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(139, 92, 246, 0.1);
}

.discussion-header h3 {
    color: #e2e8f0;
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
}

.comment-count {
    color: #94a3b8;
    font-size: 0.85rem;
}

.comment-form-wrapper {
    margin-bottom: 1.5rem;
}

.comment-input-wrapper {
    display: flex;
    gap: 0.75rem;
}

.user-avatar-small {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}

.user-avatar-small img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-avatar-small span {
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
}

.comment-input-box {
    flex: 1;
}

.comment-input-box textarea {
    width: 100%;
    background: rgba(15, 15, 35, 0.6);
    border: 1px solid rgba(139, 92, 246, 0.2);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    color: #e2e8f0;
    font-size: 0.9rem;
    resize: none;
    transition: all 0.3s ease;
}

.comment-input-box textarea:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.comment-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 0.5rem;
}

.reply-indicator {
    color: #94a3b8;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.reply-indicator strong {
    color: #a78bfa;
}

.cancel-reply {
    background: none;
    border: none;
    color: #ef4444;
    font-size: 1rem;
    cursor: pointer;
    padding: 0 0.25rem;
}

.btn-send-comment {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-send-comment:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

.comments-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.loading-comments {
    text-align: center;
    color: #94a3b8;
    padding: 2rem;
}

.comment-item {
    display: flex;
    gap: 0.75rem;
    padding: 1rem;
    background: rgba(15, 15, 35, 0.4);
    border-radius: 8px;
    border: 1px solid rgba(139, 92, 246, 0.08);
}

.comment-item.reply {
    margin-left: 2.5rem;
    background: rgba(139, 92, 246, 0.05);
}

.comment-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}

.comment-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.comment-avatar span {
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
}

.comment-content {
    flex: 1;
}

.comment-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.35rem;
}

.comment-author {
    color: #e2e8f0;
    font-weight: 600;
    font-size: 0.9rem;
}

.comment-role {
    background: rgba(139, 92, 246, 0.2);
    color: #a78bfa;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 500;
}

.comment-role.instructor {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.comment-time {
    color: #64748b;
    font-size: 0.75rem;
    margin-left: auto;
}

.comment-text {
    color: #cbd5e1;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 0.5rem;
}

.comment-footer {
    display: flex;
    gap: 1rem;
}

.comment-action {
    background: none;
    border: none;
    color: #64748b;
    font-size: 0.8rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0;
    transition: color 0.2s;
}

.comment-action:hover {
    color: #a78bfa;
}

.no-comments {
    text-align: center;
    color: #64748b;
    padding: 2rem;
}

.no-comments p {
    margin: 0;
}
</style>

<script>
// Discussion System
const discussionLessonId = <?php echo $lesson_id ?? 0; ?>;

function loadComments() {
    fetch(`api/comments.php?lesson_id=${discussionLessonId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                renderComments(data.comments);
                document.getElementById('commentCount').textContent = `${data.total} komentar`;
            }
        })
        .catch(err => {
            document.getElementById('commentsList').innerHTML = '<p class="no-comments">Gagal memuat diskusi</p>';
        });
}

function renderComments(comments) {
    const container = document.getElementById('commentsList');
    
    if (comments.length === 0) {
        container.innerHTML = '<p class="no-comments">Belum ada diskusi. Jadilah yang pertama bertanya!</p>';
        return;
    }
    
    let html = '';
    comments.forEach(comment => {
        html += renderComment(comment);
        if (comment.replies && comment.replies.length > 0) {
            comment.replies.forEach(reply => {
                html += renderComment(reply, true);
            });
        }
    });
    
    container.innerHTML = html;
}

function renderComment(comment, isReply = false) {
    const avatarContent = comment.avatar 
        ? `<img src="assets/uploads/avatars/${comment.avatar}" alt="${comment.nama_lengkap}">`
        : `<span>${comment.nama_lengkap.charAt(0).toUpperCase()}</span>`;
    
    const roleClass = comment.role === 'instructor' ? 'instructor' : '';
    const roleBadge = comment.role !== 'student' 
        ? `<span class="comment-role ${roleClass}">${comment.role}</span>` 
        : '';
    
    const timeAgo = formatTimeAgo(comment.created_at);
    
    return `
        <div class="comment-item ${isReply ? 'reply' : ''}" data-id="${comment.id}">
            <div class="comment-avatar">${avatarContent}</div>
            <div class="comment-content">
                <div class="comment-header">
                    <span class="comment-author">${comment.nama_lengkap}</span>
                    ${roleBadge}
                    <span class="comment-time">${timeAgo}</span>
                </div>
                <div class="comment-text">${escapeHtml(comment.content)}</div>
                <div class="comment-footer">
                    ${!isReply ? `<button class="comment-action" onclick="replyTo(${comment.id}, '${comment.nama_lengkap}')">Balas</button>` : ''}
                </div>
            </div>
        </div>
    `;
}

function replyTo(commentId, authorName) {
    document.getElementById('parentId').value = commentId;
    document.getElementById('replyIndicator').style.display = 'flex';
    document.getElementById('replyToName').textContent = authorName;
    document.getElementById('commentContent').focus();
}

function cancelReply() {
    document.getElementById('parentId').value = '';
    document.getElementById('replyIndicator').style.display = 'none';
}

function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Baru saja';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' menit lalu';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' jam lalu';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' hari lalu';
    return date.toLocaleDateString('id-ID');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Submit comment
document.getElementById('commentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('api/comments.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('commentContent').value = '';
            cancelReply();
            loadComments();
            if (typeof showToast === 'function') {
                showToast('Komentar berhasil ditambahkan!', 'success');
            }
        } else {
            if (typeof showToast === 'function') {
                showToast(data.message || 'Gagal mengirim komentar', 'error');
            }
        }
    })
    .catch(err => {
        if (typeof showToast === 'function') {
            showToast('Terjadi kesalahan', 'error');
        }
    });
});

// Load comments on page load
document.addEventListener('DOMContentLoaded', loadComments);
</script>

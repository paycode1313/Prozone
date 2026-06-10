<!-- WYSIWYG Content Editor Component -->
<style>
.wysiwyg-editor {
    background: #1e1e32;
    border: 1px solid rgba(139, 92, 246, 0.2);
    border-radius: 8px;
    overflow: hidden;
}

.wysiwyg-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    padding: 0.5rem;
    background: rgba(15, 15, 35, 0.6);
    border-bottom: 1px solid rgba(139, 92, 246, 0.15);
}

.toolbar-group {
    display: flex;
    gap: 0.25rem;
    padding-right: 0.5rem;
    margin-right: 0.5rem;
    border-right: 1px solid rgba(139, 92, 246, 0.1);
}

.toolbar-group:last-child {
    border-right: none;
    margin-right: 0;
    padding-right: 0;
}

.toolbar-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    border-radius: 4px;
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.2s ease;
}

.toolbar-btn:hover {
    background: rgba(139, 92, 246, 0.2);
    color: #a78bfa;
}

.toolbar-btn.active {
    background: rgba(139, 92, 246, 0.3);
    color: #8b5cf6;
}

.toolbar-btn svg {
    width: 16px;
    height: 16px;
}

.toolbar-select {
    padding: 0.25rem 0.5rem;
    background: rgba(15, 15, 35, 0.6);
    border: 1px solid rgba(139, 92, 246, 0.2);
    border-radius: 4px;
    color: #e2e8f0;
    font-size: 0.8rem;
    cursor: pointer;
}

.wysiwyg-content {
    min-height: 300px;
    padding: 1rem;
    color: #e2e8f0;
    outline: none;
    line-height: 1.7;
}

.wysiwyg-content:focus {
    box-shadow: inset 0 0 0 2px rgba(139, 92, 246, 0.2);
}

.wysiwyg-content h1, .wysiwyg-content h2, .wysiwyg-content h3 {
    color: #e2e8f0;
    margin: 1rem 0 0.5rem;
}

.wysiwyg-content h1 { font-size: 1.75rem; }
.wysiwyg-content h2 { font-size: 1.5rem; }
.wysiwyg-content h3 { font-size: 1.25rem; }

.wysiwyg-content p {
    margin: 0.5rem 0;
}

.wysiwyg-content a {
    color: #8b5cf6;
}

.wysiwyg-content code {
    background: rgba(139, 92, 246, 0.1);
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
    font-family: 'Fira Code', monospace;
    font-size: 0.9em;
}

.wysiwyg-content pre {
    background: rgba(15, 15, 35, 0.8);
    padding: 1rem;
    border-radius: 8px;
    overflow-x: auto;
    margin: 1rem 0;
}

.wysiwyg-content pre code {
    background: transparent;
    padding: 0;
}

.wysiwyg-content blockquote {
    border-left: 3px solid #8b5cf6;
    padding-left: 1rem;
    margin: 1rem 0;
    color: #94a3b8;
    font-style: italic;
}

.wysiwyg-content ul, .wysiwyg-content ol {
    padding-left: 1.5rem;
    margin: 0.5rem 0;
}

.wysiwyg-content img {
    max-width: 100%;
    border-radius: 8px;
}

.wysiwyg-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
}

.wysiwyg-content th, .wysiwyg-content td {
    border: 1px solid rgba(139, 92, 246, 0.2);
    padding: 0.5rem;
    text-align: left;
}

.wysiwyg-content th {
    background: rgba(139, 92, 246, 0.1);
}

/* Link Dialog */
.wysiwyg-dialog {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #1e1e32;
    border: 1px solid rgba(139, 92, 246, 0.3);
    border-radius: 12px;
    padding: 1.5rem;
    z-index: 1000;
    min-width: 350px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
    display: none;
}

.wysiwyg-dialog.active {
    display: block;
}

.wysiwyg-dialog-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    display: none;
}

.wysiwyg-dialog-overlay.active {
    display: block;
}

.dialog-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.dialog-header h3 {
    color: #e2e8f0;
    margin: 0;
    font-size: 1rem;
}

.dialog-close {
    background: none;
    border: none;
    color: #64748b;
    font-size: 1.25rem;
    cursor: pointer;
}

.dialog-input {
    width: 100%;
    padding: 0.75rem;
    background: rgba(15, 15, 35, 0.6);
    border: 1px solid rgba(139, 92, 246, 0.2);
    border-radius: 6px;
    color: #e2e8f0;
    margin-bottom: 1rem;
}

.dialog-input:focus {
    outline: none;
    border-color: #8b5cf6;
}

.dialog-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.dialog-btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.dialog-btn-primary {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    color: white;
    border: none;
}

.dialog-btn-secondary {
    background: transparent;
    color: #94a3b8;
    border: 1px solid rgba(139, 92, 246, 0.2);
}
</style>

<div class="wysiwyg-dialog-overlay" id="wysiwygOverlay" onclick="closeWysiwygDialog()"></div>
<div class="wysiwyg-dialog" id="linkDialog">
    <div class="dialog-header">
        <h3>Masukkan URL</h3>
        <button class="dialog-close" onclick="closeWysiwygDialog()">×</button>
    </div>
    <input type="url" class="dialog-input" id="linkUrl" placeholder="https://example.com">
    <div class="dialog-actions">
        <button class="dialog-btn dialog-btn-secondary" onclick="closeWysiwygDialog()">Batal</button>
        <button class="dialog-btn dialog-btn-primary" onclick="insertLink()">Sisipkan</button>
    </div>
</div>

<div class="wysiwyg-dialog" id="imageDialog">
    <div class="dialog-header">
        <h3>Sisipkan Gambar</h3>
        <button class="dialog-close" onclick="closeWysiwygDialog()">×</button>
    </div>
    <input type="url" class="dialog-input" id="imageUrl" placeholder="URL gambar atau upload">
    <input type="file" class="dialog-input" id="imageUpload" accept="image/*" style="padding: 0.5rem;">
    <div class="dialog-actions">
        <button class="dialog-btn dialog-btn-secondary" onclick="closeWysiwygDialog()">Batal</button>
        <button class="dialog-btn dialog-btn-primary" onclick="insertImage()">Sisipkan</button>
    </div>
</div>

<script>
class WysiwygEditor {
    constructor(containerId, hiddenInputId) {
        this.container = document.getElementById(containerId);
        this.hiddenInput = document.getElementById(hiddenInputId);
        this.init();
    }
    
    init() {
        const toolbar = this.createToolbar();
        const content = document.createElement('div');
        content.className = 'wysiwyg-content';
        content.contentEditable = true;
        content.innerHTML = this.hiddenInput?.value || '';
        
        this.container.appendChild(toolbar);
        this.container.appendChild(content);
        
        this.content = content;
        
        // Sync to hidden input
        content.addEventListener('input', () => {
            if (this.hiddenInput) {
                this.hiddenInput.value = content.innerHTML;
            }
        });
        
        // Handle paste - clean up HTML
        content.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = e.clipboardData.getData('text/plain');
            document.execCommand('insertText', false, text);
        });
    }
    
    createToolbar() {
        const toolbar = document.createElement('div');
        toolbar.className = 'wysiwyg-toolbar';
        
        toolbar.innerHTML = `
            <div class="toolbar-group">
                <select class="toolbar-select" onchange="wysiwygFormat('formatBlock', this.value); this.value='';">
                    <option value="">Format</option>
                    <option value="h1">Heading 1</option>
                    <option value="h2">Heading 2</option>
                    <option value="h3">Heading 3</option>
                    <option value="p">Paragraph</option>
                    <option value="pre">Code Block</option>
                    <option value="blockquote">Quote</option>
                </select>
            </div>
            <div class="toolbar-group">
                <button type="button" class="toolbar-btn" onclick="wysiwygFormat('bold')" title="Bold (Ctrl+B)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path></svg>
                </button>
                <button type="button" class="toolbar-btn" onclick="wysiwygFormat('italic')" title="Italic (Ctrl+I)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="4" x2="10" y2="4"></line><line x1="14" y1="20" x2="5" y2="20"></line><line x1="15" y1="4" x2="9" y2="20"></line></svg>
                </button>
                <button type="button" class="toolbar-btn" onclick="wysiwygFormat('underline')" title="Underline (Ctrl+U)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3v7a6 6 0 0 0 6 6 6 6 0 0 0 6-6V3"></path><line x1="4" y1="21" x2="20" y2="21"></line></svg>
                </button>
                <button type="button" class="toolbar-btn" onclick="wysiwygFormat('strikeThrough')" title="Strikethrough">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.3 4.9c-2.3-.6-4.4-1-6.2-.9-2.7 0-5.3.7-5.3 3.6 0 1.5 1.8 3.3 3.6 3.9h.2m8.2 0c1.1.5 1.6 1.8 1.6 3.1 0 3.6-3.3 4.5-6.8 4.5-2.3 0-4.5-.4-6.7-1"></path><line x1="4" y1="12" x2="20" y2="12"></line></svg>
                </button>
            </div>
            <div class="toolbar-group">
                <button type="button" class="toolbar-btn" onclick="wysiwygFormat('insertUnorderedList')" title="Bullet List">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><circle cx="4" cy="6" r="1" fill="currentColor"></circle><circle cx="4" cy="12" r="1" fill="currentColor"></circle><circle cx="4" cy="18" r="1" fill="currentColor"></circle></svg>
                </button>
                <button type="button" class="toolbar-btn" onclick="wysiwygFormat('insertOrderedList')" title="Numbered List">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"></line><line x1="10" y1="12" x2="21" y2="12"></line><line x1="10" y1="18" x2="21" y2="18"></line><text x="4" y="8" font-size="8" fill="currentColor">1</text><text x="4" y="14" font-size="8" fill="currentColor">2</text><text x="4" y="20" font-size="8" fill="currentColor">3</text></svg>
                </button>
            </div>
            <div class="toolbar-group">
                <button type="button" class="toolbar-btn" onclick="openLinkDialog()" title="Insert Link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                </button>
                <button type="button" class="toolbar-btn" onclick="openImageDialog()" title="Insert Image">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                </button>
                <button type="button" class="toolbar-btn" onclick="insertCode()" title="Inline Code">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                </button>
            </div>
            <div class="toolbar-group">
                <button type="button" class="toolbar-btn" onclick="wysiwygFormat('justifyLeft')" title="Align Left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="15" y2="12"></line><line x1="3" y1="18" x2="18" y2="18"></line></svg>
                </button>
                <button type="button" class="toolbar-btn" onclick="wysiwygFormat('justifyCenter')" title="Align Center">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="6" y1="12" x2="18" y2="12"></line><line x1="4" y1="18" x2="20" y2="18"></line></svg>
                </button>
                <button type="button" class="toolbar-btn" onclick="wysiwygFormat('justifyRight')" title="Align Right">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="9" y1="12" x2="21" y2="12"></line><line x1="6" y1="18" x2="21" y2="18"></line></svg>
                </button>
            </div>
            <div class="toolbar-group">
                <button type="button" class="toolbar-btn" onclick="wysiwygFormat('undo')" title="Undo (Ctrl+Z)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"></path><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"></path></svg>
                </button>
                <button type="button" class="toolbar-btn" onclick="wysiwygFormat('redo')" title="Redo (Ctrl+Y)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"></path><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"></path></svg>
                </button>
            </div>
        `;
        
        return toolbar;
    }
    
    getContent() {
        return this.content.innerHTML;
    }
    
    setContent(html) {
        this.content.innerHTML = html;
        if (this.hiddenInput) {
            this.hiddenInput.value = html;
        }
    }
}

let activeEditor = null;

function wysiwygFormat(command, value = null) {
    document.execCommand(command, false, value);
}

function openLinkDialog() {
    document.getElementById('wysiwygOverlay').classList.add('active');
    document.getElementById('linkDialog').classList.add('active');
    document.getElementById('linkUrl').value = '';
    document.getElementById('linkUrl').focus();
}

function openImageDialog() {
    document.getElementById('wysiwygOverlay').classList.add('active');
    document.getElementById('imageDialog').classList.add('active');
    document.getElementById('imageUrl').value = '';
}

function closeWysiwygDialog() {
    document.getElementById('wysiwygOverlay').classList.remove('active');
    document.querySelectorAll('.wysiwyg-dialog').forEach(d => d.classList.remove('active'));
}

function insertLink() {
    const url = document.getElementById('linkUrl').value;
    if (url) {
        document.execCommand('createLink', false, url);
    }
    closeWysiwygDialog();
}

function insertImage() {
    const url = document.getElementById('imageUrl').value;
    const file = document.getElementById('imageUpload').files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.execCommand('insertImage', false, e.target.result);
        };
        reader.readAsDataURL(file);
    } else if (url) {
        document.execCommand('insertImage', false, url);
    }
    closeWysiwygDialog();
}

function insertCode() {
    const selection = window.getSelection();
    if (selection.rangeCount > 0) {
        const range = selection.getRangeAt(0);
        const code = document.createElement('code');
        range.surroundContents(code);
    }
}

// Initialize editor function
function initWysiwyg(containerId, hiddenInputId) {
    return new WysiwygEditor(containerId, hiddenInputId);
}
</script>

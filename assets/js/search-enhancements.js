/**
 * Enhanced Search Functionality for Prozone
 * Provides real-time search, debouncing, and better UX
 */

(function() {
    'use strict';

    // Search Enhancement Class
    class SearchEnhancement {
        constructor(options = {}) {
            this.searchInput = options.searchInput || document.querySelector('.search-input, input[type="search"], #search');
            this.searchForm = options.searchForm || this.searchInput?.closest('form');
            this.resultsContainer = options.resultsContainer || document.querySelector('.courses-grid, .results-container');
            this.debounceDelay = options.debounceDelay || 300;
            this.minChars = options.minChars || 2;
            
            this.init();
        }

        init() {
            if (!this.searchInput) return;

            // Add search icon if not present
            this.addSearchIcon();
            
            // Add real-time search with debouncing
            this.searchInput.addEventListener('input', this.debounce(() => {
                this.handleSearch();
            }, this.debounceDelay));

            // Add keyboard shortcuts
            this.searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.clearSearch();
                }
                if (e.key === 'Enter' && !this.searchForm) {
                    e.preventDefault();
                    this.submitSearch();
                }
            });

            // Add clear button
            this.addClearButton();
        }

        addSearchIcon() {
            if (this.searchInput.parentElement.querySelector('.search-icon')) return;
            
            const wrapper = document.createElement('div');
            wrapper.className = 'search-wrapper';
            wrapper.style.position = 'relative';
            
            const icon = document.createElement('span');
            icon.className = 'search-icon';
            icon.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            `;
            icon.style.cssText = `
                position: absolute;
                left: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: rgba(139, 92, 246, 0.6);
                pointer-events: none;
                z-index: 1;
            `;
            
            this.searchInput.parentElement.insertBefore(wrapper, this.searchInput);
            wrapper.appendChild(this.searchInput);
            wrapper.appendChild(icon);
            
            if (this.searchInput.style.paddingLeft === '') {
                this.searchInput.style.paddingLeft = '40px';
            }
        }

        addClearButton() {
            if (this.searchInput.parentElement.querySelector('.search-clear')) return;
            
            const clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'search-clear';
            clearBtn.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            `;
            clearBtn.style.cssText = `
                position: absolute;
                right: 8px;
                top: 50%;
                transform: translateY(-50%);
                background: transparent;
                border: none;
                color: rgba(148, 163, 184, 0.6);
                cursor: pointer;
                padding: 4px;
                display: none;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                transition: all 0.2s ease;
            `;
            clearBtn.addEventListener('click', () => this.clearSearch());
            clearBtn.addEventListener('mouseenter', () => {
                clearBtn.style.color = 'rgba(139, 92, 246, 0.8)';
                clearBtn.style.background = 'rgba(139, 92, 246, 0.1)';
            });
            clearBtn.addEventListener('mouseleave', () => {
                clearBtn.style.color = 'rgba(148, 163, 184, 0.6)';
                clearBtn.style.background = 'transparent';
            });
            
            const wrapper = this.searchInput.parentElement;
            if (wrapper.classList.contains('search-wrapper')) {
                wrapper.appendChild(clearBtn);
            } else {
                const newWrapper = document.createElement('div');
                newWrapper.className = 'search-wrapper';
                newWrapper.style.position = 'relative';
                this.searchInput.parentElement.insertBefore(newWrapper, this.searchInput);
                newWrapper.appendChild(this.searchInput);
                newWrapper.appendChild(clearBtn);
            }

            // Show/hide clear button based on input value
            this.searchInput.addEventListener('input', () => {
                clearBtn.style.display = this.searchInput.value.trim() ? 'flex' : 'none';
            });
        }

        handleSearch() {
            const query = this.searchInput.value.trim();
            
            if (query.length < this.minChars && query.length > 0) {
                this.showMessage(`Ketik minimal ${this.minChars} karakter untuk mencari`);
                return;
            }

            if (query.length === 0) {
                this.clearSearch();
                return;
            }

            // Show loading state
            this.showLoading();

            // If form exists, submit it
            if (this.searchForm) {
                // Use URLSearchParams to update URL without reload
                const formData = new FormData(this.searchForm);
                const params = new URLSearchParams(formData);
                const url = new URL(window.location);
                url.search = params.toString();
                window.history.pushState({}, '', url);
                
                // Reload or use AJAX
                if (this.resultsContainer) {
                    this.fetchResults(query);
                } else {
                    window.location.reload();
                }
            } else {
                this.fetchResults(query);
            }
        }

        fetchResults(query) {
            // This would be replaced with actual AJAX call
            // For now, just trigger a reload or filter client-side
            const url = new URL(window.location);
            url.searchParams.set('search', query);
            window.history.pushState({}, '', url);
            
            // If results container exists, filter client-side
            if (this.resultsContainer) {
                this.filterClientSide(query);
            } else {
                window.location.reload();
            }
        }

        filterClientSide(query) {
            const items = this.resultsContainer.querySelectorAll('.course-card, .card, [data-searchable]');
            let visibleCount = 0;
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                const matches = text.includes(query.toLowerCase());
                
                if (matches) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            this.hideLoading();
            
            if (visibleCount === 0) {
                this.showEmptyState(query);
            } else {
                this.hideEmptyState();
            }
        }

        showLoading() {
            if (!this.resultsContainer) return;
            
            let loader = this.resultsContainer.querySelector('.search-loading');
            if (!loader) {
                loader = document.createElement('div');
                loader.className = 'search-loading';
                loader.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: rgba(139, 92, 246, 0.8);">
                        <div class="spinner" style="width: 40px; height: 40px; border: 3px solid rgba(139, 92, 246, 0.2); border-top-color: #8b5cf6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                        <p>Mencari...</p>
                    </div>
                `;
                this.resultsContainer.appendChild(loader);
            }
            loader.style.display = 'block';
        }

        hideLoading() {
            const loader = this.resultsContainer?.querySelector('.search-loading');
            if (loader) loader.style.display = 'none';
        }

        showEmptyState(query) {
            if (!this.resultsContainer) return;
            
            let emptyState = this.resultsContainer.querySelector('.search-empty');
            if (!emptyState) {
                emptyState = document.createElement('div');
                emptyState.className = 'search-empty';
                emptyState.style.cssText = `
                    text-align: center;
                    padding: 3rem 1rem;
                    color: rgba(148, 163, 184, 0.8);
                `;
                this.resultsContainer.appendChild(emptyState);
            }
            
            emptyState.innerHTML = `
                <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">🔍</div>
                <h3 style="color: rgba(167, 139, 250, 0.9); margin-bottom: 0.5rem;">Tidak ada hasil</h3>
                <p style="color: rgba(139, 92, 246, 0.6);">Tidak ada kursus yang cocok dengan "<strong>${this.escapeHtml(query)}</strong>"</p>
            `;
            emptyState.style.display = 'block';
        }

        hideEmptyState() {
            const emptyState = this.resultsContainer?.querySelector('.search-empty');
            if (emptyState) emptyState.style.display = 'none';
        }

        clearSearch() {
            this.searchInput.value = '';
            this.searchInput.dispatchEvent(new Event('input'));
            
            if (this.searchForm) {
                const url = new URL(window.location);
                url.searchParams.delete('search');
                window.history.pushState({}, '', url);
                this.searchForm.submit();
            } else {
                window.location.search = '';
            }
        }

        submitSearch() {
            if (this.searchForm) {
                this.searchForm.submit();
            } else {
                const query = this.searchInput.value.trim();
                if (query) {
                    const url = new URL(window.location);
                    url.searchParams.set('search', query);
                    window.location.href = url.toString();
                }
            }
        }

        showMessage(text) {
            // Use toast notification if available
            if (window.showToast) {
                window.showToast(text, 'info');
            } else {
                console.log(text);
            }
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            new SearchEnhancement();
        });
    } else {
        new SearchEnhancement();
    }

    // Export for global use
    window.SearchEnhancement = SearchEnhancement;
})();


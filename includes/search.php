<!-- Search Component -->
<style>
    .search-container {
        position: relative;
        width: 100%;
        max-width: 400px;
    }

    .search-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .search-input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.75rem;
        background: rgba(15, 15, 35, 0.6);
        border: 1px solid rgba(124, 58, 237, 0.2);
        border-radius: 12px;
        color: #e0e7ff;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        outline: none;
    }

    .search-input::placeholder {
        color: rgba(139, 92, 246, 0.5);
    }

    .search-input:focus {
        border-color: rgba(124, 58, 237, 0.5);
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        background: rgba(15, 15, 35, 0.8);
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        color: rgba(139, 92, 246, 0.6);
        pointer-events: none;
    }

    .search-clear {
        position: absolute;
        right: 0.75rem;
        background: none;
        border: none;
        color: rgba(139, 92, 246, 0.6);
        cursor: pointer;
        padding: 0.25rem;
        display: none;
        transition: color 0.2s;
    }

    .search-clear:hover {
        color: #e0e7ff;
    }

    .search-input:not(:placeholder-shown) + .search-clear {
        display: block;
    }

    /* Filter Buttons */
    .filter-container {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .filter-btn {
        padding: 0.5rem 1rem;
        background: rgba(139, 92, 246, 0.1);
        border: 1px solid rgba(124, 58, 237, 0.2);
        border-radius: 20px;
        color: #a78bfa;
        font-size: 0.8rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .filter-btn:hover {
        background: rgba(139, 92, 246, 0.2);
        border-color: rgba(124, 58, 237, 0.4);
    }

    .filter-btn.active {
        background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
        border-color: transparent;
        color: white;
    }

    /* Search Results Count */
    .search-results-info {
        color: #94a3b8;
        font-size: 0.85rem;
        margin-top: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .search-results-count {
        color: #a78bfa;
        font-weight: 600;
    }

    /* No Results State */
    .no-results {
        text-align: center;
        padding: 3rem 1rem;
        color: #94a3b8;
    }

    .no-results-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .no-results h3 {
        color: #e0e7ff;
        margin-bottom: 0.5rem;
    }

    .no-results p {
        font-size: 0.9rem;
        max-width: 300px;
        margin: 0 auto;
    }

    /* Mobile Responsive */
    @media (max-width: 640px) {
        .search-container {
            max-width: 100%;
        }

        .filter-container {
            overflow-x: auto;
            flex-wrap: nowrap;
            padding-bottom: 0.5rem;
            -webkit-overflow-scrolling: touch;
        }

        .filter-btn {
            flex-shrink: 0;
        }
    }
</style>

<script>
    // Search Functionality
    class SearchHandler {
        constructor(options = {}) {
            this.inputSelector = options.inputSelector || '.search-input';
            this.itemsSelector = options.itemsSelector || '.searchable-item';
            this.searchableAttr = options.searchableAttr || 'data-search';
            this.onSearch = options.onSearch || null;
            this.debounceTime = options.debounceTime || 300;
            
            this.init();
        }

        init() {
            const input = document.querySelector(this.inputSelector);
            if (!input) return;

            let debounceTimer;
            input.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    this.performSearch(e.target.value);
                }, this.debounceTime);
            });

            // Clear button
            const clearBtn = input.parentElement.querySelector('.search-clear');
            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    input.value = '';
                    this.performSearch('');
                    input.focus();
                });
            }
        }

        performSearch(query) {
            const items = document.querySelectorAll(this.itemsSelector);
            const normalizedQuery = query.toLowerCase().trim();
            let visibleCount = 0;

            items.forEach(item => {
                const searchText = (item.getAttribute(this.searchableAttr) || item.textContent).toLowerCase();
                const isMatch = !normalizedQuery || searchText.includes(normalizedQuery);
                
                item.style.display = isMatch ? '' : 'none';
                if (isMatch) visibleCount++;
            });

            // Update results count if element exists
            const countElement = document.querySelector('.search-results-count');
            if (countElement) {
                countElement.textContent = visibleCount;
            }

            // Show/hide no results message
            const noResults = document.querySelector('.no-results');
            if (noResults) {
                noResults.style.display = visibleCount === 0 && normalizedQuery ? 'block' : 'none';
            }

            // Custom callback
            if (this.onSearch) {
                this.onSearch(query, visibleCount);
            }
        }
    }

    // Filter Functionality
    class FilterHandler {
        constructor(options = {}) {
            this.buttonSelector = options.buttonSelector || '.filter-btn';
            this.itemsSelector = options.itemsSelector || '.filterable-item';
            this.filterAttr = options.filterAttr || 'data-category';
            this.onFilter = options.onFilter || null;
            
            this.init();
        }

        init() {
            const buttons = document.querySelectorAll(this.buttonSelector);
            
            buttons.forEach(btn => {
                btn.addEventListener('click', () => {
                    // Update active state
                    buttons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    
                    // Perform filter
                    this.performFilter(btn.getAttribute('data-filter'));
                });
            });
        }

        performFilter(filter) {
            const items = document.querySelectorAll(this.itemsSelector);
            let visibleCount = 0;

            items.forEach(item => {
                const category = item.getAttribute(this.filterAttr);
                const isMatch = !filter || filter === 'all' || category === filter;
                
                item.style.display = isMatch ? '' : 'none';
                if (isMatch) visibleCount++;
            });

            // Update results count if element exists
            const countElement = document.querySelector('.search-results-count');
            if (countElement) {
                countElement.textContent = visibleCount;
            }

            // Custom callback
            if (this.onFilter) {
                this.onFilter(filter, visibleCount);
            }
        }
    }
</script>

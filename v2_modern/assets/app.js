/**
 * RSS Reader V2 - Frontend Application
 * Gmail-style interface with modern interactions
 */

// ===== Global State =====
const state = {
    currentView: 'all', // 'all', 'unread', or feed ID
    currentArticleId: null,
    feeds: [],
    articles: [],
    unreadCount: 0,
    searchQuery: ''
};

// ===== API Helper =====
const api = {
    async call(endpoint, options = {}) {
        try {
            const response = await fetch(`/v2/api${endpoint}`, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });

            const data = await response.json();

            if (!response.ok && response.status === 401) {
                window.location.href = '/v2/login';
                return null;
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, message: 'Erreur réseau' };
        }
    },

    // Auth
    async logout() {
        return this.call('/auth/logout', { method: 'POST' });
    },

    // Feeds
    async getFeeds() {
        return this.call('/feeds');
    },

    async addFeed(url, title = '') {
        return this.call('/feeds', {
            method: 'POST',
            body: JSON.stringify({ url, title })
        });
    },

    async deleteFeed(feedId) {
        return this.call(`/feeds/${feedId}`, { method: 'DELETE' });
    },

    // Articles
    async getArticles(feedId = null, unreadOnly = false, limit = 50, offset = 0) {
        let url = `/articles?limit=${limit}&offset=${offset}`;
        if (feedId) url += `&feed_id=${feedId}`;
        if (unreadOnly) url += `&unread_only=true`;
        return this.call(url);
    },

    async markArticleRead(articleId, read = true) {
        return this.call(`/articles/${articleId}/read`, {
            method: 'PUT',
            body: JSON.stringify({ read })
        });
    },

    // Stats
    async getStats() {
        return this.call('/stats');
    }
};

// ===== UI Components =====
const ui = {
    // Show loading indicator
    showLoading() {
        document.getElementById('loading-indicator').style.display = 'block';
        document.getElementById('empty-state').style.display = 'none';
    },

    hideLoading() {
        document.getElementById('loading-indicator').style.display = 'none';
    },

    showEmptyState() {
        document.getElementById('empty-state').style.display = 'block';
    },

    hideEmptyState() {
        document.getElementById('empty-state').style.display = 'none';
    },

    // Update counts
    updateCounts() {
        const totalCount = state.articles.length;
        const unreadCount = state.articles.filter(a => !a.is_read).length;

        document.getElementById('total-count').textContent = totalCount;
        document.getElementById('unread-count').textContent = unreadCount;
        state.unreadCount = unreadCount;
    },

    // Render feeds list
    renderFeeds() {
        const feedsList = document.getElementById('feeds-list');
        feedsList.innerHTML = '';

        if (state.feeds.length === 0) {
            feedsList.innerHTML = '<div style="padding: 8px 24px; font-size: 13px; color: var(--text-tertiary);">Aucun flux</div>';
            return;
        }

        state.feeds.forEach(feed => {
            const feedEl = document.createElement('a');
            feedEl.href = '#';
            feedEl.className = 'feed-item';
            if (state.currentView === feed.id.toString()) {
                feedEl.classList.add('active');
            }

            feedEl.innerHTML = `
                <span>${this.escapeHtml(feed.title)}</span>
                <span class="badge">${feed.unread_count || 0}</span>
            `;

            feedEl.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchView(feed.id.toString());
            });

            feedsList.appendChild(feedEl);
        });
    },

    // Render articles list
    renderArticles() {
        const articlesList = document.getElementById('articles-list');
        articlesList.innerHTML = '';

        let filteredArticles = state.articles;

        // Filter by search query
        if (state.searchQuery) {
            const query = state.searchQuery.toLowerCase();
            filteredArticles = filteredArticles.filter(article =>
                article.title.toLowerCase().includes(query) ||
                (article.description && article.description.toLowerCase().includes(query))
            );
        }

        if (filteredArticles.length === 0) {
            this.showEmptyState();
            return;
        }

        this.hideEmptyState();

        filteredArticles.forEach(article => {
            const articleEl = document.createElement('div');
            articleEl.className = 'article-card';
            if (!article.is_read) {
                articleEl.classList.add('unread');
            }

            const publishedDate = article.published_date
                ? new Date(article.published_date).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                })
                : '';

            articleEl.innerHTML = `
                <div class="article-header">
                    <div class="article-header-content">
                        <div class="article-feed">${this.escapeHtml(article.feed_title || 'Sans titre')}</div>
                        <div class="article-title">${this.escapeHtml(article.title)}</div>
                        <div class="article-meta">
                            ${publishedDate ? `<span>${publishedDate}</span>` : ''}
                            ${article.author ? `<span>Par ${this.escapeHtml(article.author)}</span>` : ''}
                        </div>
                    </div>
                </div>
                ${article.description ? `<div class="article-description">${this.escapeHtml(article.description)}</div>` : ''}
            `;

            articleEl.addEventListener('click', () => {
                this.openArticle(article);
            });

            articlesList.appendChild(articleEl);
        });
    },

    // Open article in reader panel
    openArticle(article) {
        const readerPanel = document.getElementById('reader-panel');
        const readerContent = document.getElementById('reader-content');

        const publishedDate = article.published_date
            ? new Date(article.published_date).toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
            : '';

        readerContent.innerHTML = `
            <h1 class="reader-title">${this.escapeHtml(article.title)}</h1>
            <div class="reader-meta">
                <div>${this.escapeHtml(article.feed_title || 'Sans titre')}</div>
                ${publishedDate ? `<div>${publishedDate}</div>` : ''}
                ${article.author ? `<div>Par ${this.escapeHtml(article.author)}</div>` : ''}
            </div>
            <div class="reader-body">
                ${article.content || article.description || '<p>Aucun contenu disponible.</p>'}
            </div>
            <div class="reader-footer">
                <a href="${article.link}" target="_blank" class="btn-primary">Lire l'article complet</a>
            </div>
        `;

        readerPanel.style.display = 'flex';
        readerPanel.classList.add('active');
        state.currentArticleId = article.id;

        // Mark as read
        if (!article.is_read) {
            api.markArticleRead(article.id, true).then(() => {
                article.is_read = true;
                this.updateCounts();
                this.renderArticles();
            });
        }
    },

    closeReader() {
        const readerPanel = document.getElementById('reader-panel');
        readerPanel.style.display = 'none';
        readerPanel.classList.remove('active');
        state.currentArticleId = null;
    },

    // Switch view (all, unread, or specific feed)
    async switchView(view) {
        state.currentView = view;

        // Update nav items
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });

        document.querySelectorAll('.feed-item').forEach(item => {
            item.classList.remove('active');
        });

        const activeNav = document.querySelector(`.nav-item[data-view="${view}"]`);
        if (activeNav) {
            activeNav.classList.add('active');
        }

        // Load articles
        await this.loadArticles();
    },

    // Load articles based on current view
    async loadArticles() {
        this.showLoading();

        let feedId = null;
        let unreadOnly = false;

        if (state.currentView === 'unread') {
            unreadOnly = true;
        } else if (state.currentView !== 'all') {
            feedId = parseInt(state.currentView);
        }

        const result = await api.getArticles(feedId, unreadOnly);

        this.hideLoading();

        if (result && result.success) {
            state.articles = result.articles || [];
            this.renderArticles();
            this.updateCounts();
        }
    },

    // Load feeds
    async loadFeeds() {
        const result = await api.getFeeds();

        if (result && result.success) {
            state.feeds = result.feeds || [];
            this.renderFeeds();
        }
    },

    // Refresh all data
    async refresh() {
        const refreshBtn = document.getElementById('refresh-btn');
        refreshBtn.disabled = true;
        refreshBtn.style.opacity = '0.5';

        await Promise.all([
            this.loadFeeds(),
            this.loadArticles()
        ]);

        refreshBtn.disabled = false;
        refreshBtn.style.opacity = '1';
    },

    // Show add feed modal
    showAddFeedModal() {
        document.getElementById('add-feed-modal').style.display = 'block';
        document.getElementById('feed-url').focus();
    },

    hideAddFeedModal() {
        document.getElementById('add-feed-modal').style.display = 'none';
        document.getElementById('add-feed-form').reset();
    },

    // Escape HTML to prevent XSS
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// ===== Event Handlers =====
function initEventHandlers() {
    // Logout
    document.getElementById('logout-btn').addEventListener('click', async () => {
        await api.logout();
        window.location.href = '/v2/login';
    });

    // Refresh
    document.getElementById('refresh-btn').addEventListener('click', () => {
        ui.refresh();
    });

    // Mark all as read
    document.getElementById('mark-all-read-btn').addEventListener('click', async () => {
        const unreadArticles = state.articles.filter(a => !a.is_read);

        if (unreadArticles.length === 0) return;

        if (!confirm(`Marquer ${unreadArticles.length} article(s) comme lu(s) ?`)) return;

        for (const article of unreadArticles) {
            await api.markArticleRead(article.id, true);
            article.is_read = true;
        }

        ui.updateCounts();
        ui.renderArticles();
    });

    // Search
    const searchInput = document.getElementById('search-input');
    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = e.target.value.trim();
            ui.renderArticles();
        }, 300);
    });

    // Navigation
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const view = item.dataset.view;
            ui.switchView(view);
        });
    });

    // Close reader
    document.getElementById('close-reader-btn').addEventListener('click', () => {
        ui.closeReader();
    });

    // Add feed modal
    document.getElementById('add-feed-btn').addEventListener('click', () => {
        ui.showAddFeedModal();
    });

    document.getElementById('modal-overlay').addEventListener('click', () => {
        ui.hideAddFeedModal();
    });

    document.getElementById('close-modal-btn').addEventListener('click', () => {
        ui.hideAddFeedModal();
    });

    document.getElementById('cancel-feed-btn').addEventListener('click', () => {
        ui.hideAddFeedModal();
    });

    // Add feed form
    document.getElementById('add-feed-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const url = document.getElementById('feed-url').value.trim();
        const title = document.getElementById('feed-title').value.trim();

        if (!url) return;

        const result = await api.addFeed(url, title);

        if (result && result.success) {
            ui.hideAddFeedModal();
            await ui.loadFeeds();
            alert('Flux ajouté avec succès !');
        } else {
            alert(result.message || 'Erreur lors de l\'ajout du flux');
        }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        // ESC to close reader
        if (e.key === 'Escape' && state.currentArticleId) {
            ui.closeReader();
        }

        // R to refresh
        if (e.key === 'r' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            ui.refresh();
        }
    });
}

// ===== Initialize Application =====
async function init() {
    console.log('RSS Reader V2 - Initializing...');

    // Initialize event handlers
    initEventHandlers();

    // Load initial data
    await Promise.all([
        ui.loadFeeds(),
        ui.loadArticles()
    ]);

    console.log('RSS Reader V2 - Ready!');
}

// Start the app when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

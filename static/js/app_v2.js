// ===== État de l'application =====
const state = {
    currentUser: null,
    feeds: [],
    articles: [],
    currentFeedId: null,
    unreadOnly: false,
    searchQuery: '',
    favorites: false
};

// ===== Éléments du DOM =====
const elements = {
    feedsList: document.getElementById('feeds-list'),
    articlesList: document.getElementById('articles-list'),
    feedModal: document.getElementById('feed-modal'),
    articleModal: document.getElementById('article-modal'),
    feedForm: document.getElementById('feed-form'),
    loading: document.getElementById('loading'),
    noArticles: document.getElementById('no-articles'),
    articlesTitle: document.getElementById('articles-title'),
    searchInput: document.getElementById('search-input'),
    searchClear: document.getElementById('search-clear'),
    searchStats: document.getElementById('search-stats')
};

// ===== Initialisation =====
document.addEventListener('DOMContentLoaded', async () => {
    await loadCurrentUser();
    initializeEventListeners();
    loadFeeds();
    loadArticles();

    // Gérer les favoris depuis l'URL
    if (window.location.hash === '#favorites') {
        showFavorites();
    }
});

// ===== Authentification =====
async function loadCurrentUser() {
    try {
        const response = await fetch('/api/auth/me');
        const data = await response.json();

        if (data.success) {
            state.currentUser = data.user;
            document.getElementById('current-username').textContent = data.user.username;
            document.getElementById('dropdown-username').textContent = data.user.username;
            document.getElementById('dropdown-role').textContent = data.user.role;

            if (data.user.role === 'admin') {
                const adminLink = document.getElementById('admin-link');
                if (adminLink) {
                    adminLink.style.display = 'flex';
                }
            }
        } else {
            window.location.href = '/login';
        }
    } catch (error) {
        window.location.href = '/login';
    }
}

async function logout() {
    try {
        await fetch('/api/auth/logout', { method: 'POST' });
    } catch (error) {
        console.error('Logout error:', error);
    } finally {
        window.location.href = '/login';
    }
}

// ===== Gestionnaires d'événements =====
function initializeEventListeners() {
    // Boutons principaux
    document.getElementById('btn-add-feed').addEventListener('click', () => openFeedModal());
    document.getElementById('btn-update-all').addEventListener('click', updateAllFeeds);
    document.getElementById('btn-show-all').addEventListener('click', showAllArticles);
    document.getElementById('btn-cancel').addEventListener('click', closeFeedModal);
    document.getElementById('filter-unread').addEventListener('change', handleUnreadFilter);

    // Recherche
    elements.searchInput.addEventListener('input', handleSearch);
    elements.searchClear.addEventListener('click', clearSearch);

    // Favoris
    const favLink = document.getElementById('favorites-link');
    if (favLink) {
        favLink.addEventListener('click', (e) => {
            e.preventDefault();
            showFavorites();
        });
    }

    // Menu utilisateur
    document.getElementById('user-menu-button').addEventListener('click', toggleUserMenu);
    document.addEventListener('click', closeUserMenuOutside);

    // Déconnexion
    document.getElementById('logout-link').addEventListener('click', (e) => {
        e.preventDefault();
        logout();
    });

    // Formulaire flux
    elements.feedForm.addEventListener('submit', handleFeedSubmit);

    // Fermer les modals
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });

    // Fermer modal en cliquant en dehors
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
}

// ===== Menu utilisateur =====
function toggleUserMenu() {
    const dropdown = document.getElementById('user-dropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

function closeUserMenuOutside(e) {
    const dropdown = document.getElementById('user-dropdown');
    const button = document.getElementById('user-menu-button');
    if (dropdown && button && !button.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
    }
}

// ===== Recherche =====
let searchTimeout;
function handleSearch(e) {
    const query = e.target.value.trim();
    state.searchQuery = query;

    if (query) {
        elements.searchClear.style.display = 'block';
    } else {
        elements.searchClear.style.display = 'none';
    }

    // Debounce
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadArticles();
    }, 300);
}

function clearSearch() {
    elements.searchInput.value = '';
    state.searchQuery = '';
    elements.searchClear.style.display = 'none';
    elements.searchStats.textContent = '';
    loadArticles();
}

// ===== API: Charger les flux =====
async function loadFeeds() {
    try {
        const response = await fetch('/api/feeds');
        const data = await response.json();

        if (data.success) {
            state.feeds = data.feeds;
            renderFeeds();
            updateSidebarStats();
        }
    } catch (error) {
        console.error('Erreur lors du chargement des flux:', error);
        showNotification('Erreur lors du chargement des flux', 'error');
    }
}

// ===== API: Charger les articles =====
async function loadArticles() {
    showLoading();

    try {
        let url = `/api/articles?limit=100`;
        if (state.currentFeedId) url += `&feed_id=${state.currentFeedId}`;
        if (state.unreadOnly) url += `&unread_only=true`;
        if (state.searchQuery) url += `&search=${encodeURIComponent(state.searchQuery)}`;

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            state.articles = data.articles;

            if (state.favorites) {
                state.articles = state.articles.filter(a => a.favorite);
            }

            renderArticles();

            // Afficher stats de recherche
            if (state.searchQuery) {
                elements.searchStats.textContent = `${data.total} résultat${data.total > 1 ? 's' : ''} trouvé${data.total > 1 ? 's' : ''}`;
            } else {
                elements.searchStats.textContent = '';
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement des articles:', error);
        showNotification('Erreur lors du chargement des articles', 'error');
    } finally {
        hideLoading();
    }
}

// ===== Afficher les flux =====
function renderFeeds() {
    if (!elements.feedsList) return;

    elements.feedsList.innerHTML = '';

    if (state.feeds.length === 0) {
        elements.feedsList.innerHTML = '<div class="no-content">Aucun flux</div>';
        return;
    }

    state.feeds.forEach(feed => {
        const feedElement = createFeedElement(feed);
        elements.feedsList.appendChild(feedElement);
    });
}

// ===== Créer un élément de flux =====
function createFeedElement(feed) {
    const div = document.createElement('div');
    div.className = `feed-item ${feed.active ? '' : 'inactive'} ${state.currentFeedId === feed.id ? 'active' : ''}`;

    div.innerHTML = `
        <div class="feed-item-header">
            <span class="feed-item-title">${escapeHtml(feed.title || 'Sans titre')}</span>
            <span class="feed-item-count">${feed.article_count || 0}</span>
        </div>
        ${feed.description ? `<div class="feed-item-description">${escapeHtml(feed.description)}</div>` : ''}
        <div class="feed-item-actions">
            <button onclick="updateFeed(${feed.id})">🔄</button>
            <button onclick="editFeed(${feed.id})">✏️</button>
            <button onclick="deleteFeed(${feed.id})">🗑️</button>
        </div>
    `;

    div.addEventListener('click', (e) => {
        if (!e.target.matches('button')) {
            showFeedArticles(feed.id, feed.title);
        }
    });

    return div;
}

// ===== Afficher les articles =====
function renderArticles() {
    if (!elements.articlesList) return;

    elements.articlesList.innerHTML = '';

    if (state.articles.length === 0) {
        elements.articlesList.style.display = 'none';
        elements.noArticles.style.display = 'block';
        return;
    }

    elements.articlesList.style.display = 'block';
    elements.noArticles.style.display = 'none';

    state.articles.forEach(article => {
        const articleElement = createArticleElement(article);
        elements.articlesList.appendChild(articleElement);
    });
}

// ===== Créer un élément d'article =====
function createArticleElement(article) {
    const div = document.createElement('div');
    div.className = `article-item ${article.read ? 'read' : ''}`;

    const publishedDate = article.published_date
        ? new Date(article.published_date).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          })
        : '';

    div.innerHTML = `
        <div class="article-item-header">
            <div class="article-item-title">${escapeHtml(article.title)}</div>
            <span class="article-favorite ${article.favorite ? 'active' : ''}" onclick="toggleFavorite(event, ${article.id})">⭐</span>
        </div>
        <div class="article-item-meta">
            <span class="article-item-feed">${escapeHtml(article.feed_title)}</span>
            ${publishedDate ? `<span>${publishedDate}</span>` : ''}
            ${article.author ? `<span>Par ${escapeHtml(article.author)}</span>` : ''}
        </div>
        ${article.description ? `<div class="article-item-description">${escapeHtml(article.description)}</div>` : ''}
    `;

    div.addEventListener('click', (e) => {
        if (!e.target.classList.contains('article-favorite')) {
            showArticleDetail(article);
        }
    });

    return div;
}

// ===== Modal: Ajouter/Modifier flux =====
function openFeedModal(feed = null) {
    document.getElementById('modal-title').textContent = feed ? 'Modifier le flux' : 'Ajouter un flux RSS';
    document.getElementById('feed-id').value = feed ? feed.id : '';
    document.getElementById('feed-title').value = feed ? feed.title : '';
    document.getElementById('feed-url').value = feed ? feed.url : '';
    document.getElementById('feed-description').value = feed ? feed.description : '';
    document.getElementById('feed-interval').value = feed ? feed.update_interval : 30;
    document.getElementById('feed-active').checked = feed ? feed.active : true;

    elements.feedModal.style.display = 'flex';
}

function closeFeedModal() {
    elements.feedModal.style.display = 'none';
    elements.feedForm.reset();
}

// ===== Soumettre le formulaire de flux =====
async function handleFeedSubmit(e) {
    e.preventDefault();

    const feedId = document.getElementById('feed-id').value;
    const feedData = {
        title: document.getElementById('feed-title').value,
        url: document.getElementById('feed-url').value,
        description: document.getElementById('feed-description').value,
        update_interval: parseInt(document.getElementById('feed-interval').value),
        active: document.getElementById('feed-active').checked ? 1 : 0
    };

    try {
        const url = feedId ? `/api/feeds/${feedId}` : '/api/feeds';
        const method = feedId ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(feedData)
        });

        const data = await response.json();

        if (data.success) {
            showNotification(feedId ? 'Flux modifié avec succès' : 'Flux ajouté avec succès', 'success');
            closeFeedModal();
            await loadFeeds();

            if (!feedId && data.feed_id) {
                setTimeout(() => loadFeeds(), 2000);
            }
        } else {
            showNotification(data.error || 'Erreur lors de la sauvegarde', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la sauvegarde', 'error');
    }
}

// ===== Modifier un flux =====
window.editFeed = function(feedId) {
    const feed = state.feeds.find(f => f.id === feedId);
    if (feed) {
        openFeedModal(feed);
    }
};

// ===== Supprimer un flux =====
window.deleteFeed = async function(feedId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce flux et tous ses articles ?')) {
        return;
    }

    try {
        const response = await fetch(`/api/feeds/${feedId}`, {
            method: 'DELETE'
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Flux supprimé avec succès', 'success');

            if (state.currentFeedId === feedId) {
                showAllArticles();
            }

            await loadFeeds();
        } else {
            showNotification(data.error || 'Erreur lors de la suppression', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la suppression', 'error');
    }
};

// ===== Actualiser un flux =====
window.updateFeed = async function(feedId) {
    showNotification('Mise à jour en cours...', 'info');

    try {
        const response = await fetch(`/api/feeds/${feedId}/update`, {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            showNotification(data.message, 'success');
            await loadFeeds();

            if (state.currentFeedId === feedId || state.currentFeedId === null) {
                await loadArticles();
            }
        } else {
            showNotification(data.error || 'Erreur lors de la mise à jour', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la mise à jour', 'error');
    }
};

// ===== Actualiser tous les flux =====
async function updateAllFeeds() {
    showNotification('Mise à jour de tous les flux...', 'info');

    try {
        const response = await fetch('/api/feeds/update-all', {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            showNotification(data.message, 'success');
            await loadFeeds();
            await loadArticles();
        } else {
            showNotification(data.error || 'Erreur lors de la mise à jour', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la mise à jour', 'error');
    }
}

// ===== Afficher les articles d'un flux =====
function showFeedArticles(feedId, feedTitle) {
    state.currentFeedId = feedId;
    state.favorites = false;
    elements.articlesTitle.textContent = feedTitle;
    loadArticles();
    renderFeeds();
}

// ===== Afficher tous les articles =====
function showAllArticles() {
    state.currentFeedId = null;
    state.favorites = false;
    elements.articlesTitle.textContent = 'Tous les articles';
    loadArticles();
    renderFeeds();
}

// ===== Afficher les favoris =====
function showFavorites() {
    state.currentFeedId = null;
    state.favorites = true;
    elements.articlesTitle.textContent = '⭐ Articles favoris';
    loadArticles();
    renderFeeds();
}

// ===== Filtre non lus =====
function handleUnreadFilter(e) {
    state.unreadOnly = e.target.checked;
    loadArticles();
}

// ===== Toggle favori =====
window.toggleFavorite = async function(event, articleId) {
    event.stopPropagation();
    const star = event.target;

    try {
        const response = await fetch(`/api/articles/${articleId}/favorite`, {
            method: 'PUT'
        });

        const data = await response.json();

        if (data.success) {
            star.classList.toggle('active');

            // Mettre à jour l'état local
            const article = state.articles.find(a => a.id === articleId);
            if (article) {
                article.favorite = article.favorite ? 0 : 1;
            }

            // Si on est dans la vue favoris, recharger
            if (state.favorites) {
                loadArticles();
            }
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
};

// ===== Afficher le détail d'un article =====
async function showArticleDetail(article) {
    const publishedDate = article.published_date
        ? new Date(article.published_date).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          })
        : '';

    document.getElementById('article-detail').innerHTML = `
        <h1>${escapeHtml(article.title)}</h1>
        <div class="article-meta">
            <strong>${escapeHtml(article.feed_title)}</strong>
            ${publishedDate ? ` • ${publishedDate}` : ''}
            ${article.author ? ` • Par ${escapeHtml(article.author)}` : ''}
        </div>
        <div class="article-content">
            ${article.content || article.description || '<p>Aucun contenu disponible.</p>'}
        </div>
        <div class="article-link">
            <a href="${escapeHtml(article.link)}" target="_blank" class="btn btn-primary">📖 Lire l'article complet</a>
        </div>
    `;

    elements.articleModal.style.display = 'flex';

    // Marquer comme lu
    if (!article.read) {
        await markArticleAsRead(article.id);
    }
}

// ===== Marquer un article comme lu =====
async function markArticleAsRead(articleId) {
    try {
        await fetch(`/api/articles/${articleId}/read`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ read: true })
        });

        // Mettre à jour l'état local
        const article = state.articles.find(a => a.id === articleId);
        if (article) {
            article.read = 1;
            renderArticles();
        }

        updateSidebarStats();
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// ===== Mettre à jour les stats de la sidebar =====
async function updateSidebarStats() {
    try {
        const response = await fetch('/api/stats');
        const data = await response.json();

        if (data.success) {
            const stats = data.stats;
            const statFeeds = document.getElementById('stat-feeds');
            const statArticles = document.getElementById('stat-articles');
            const statUnread = document.getElementById('stat-unread');

            if (statFeeds) statFeeds.textContent = stats.total_feeds;
            if (statArticles) statArticles.textContent = stats.total_articles;
            if (statUnread) statUnread.textContent = stats.unread_articles;
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// ===== Loading states =====
function showLoading() {
    if (elements.loading) elements.loading.style.display = 'block';
    if (elements.articlesList) elements.articlesList.style.display = 'none';
    if (elements.noArticles) elements.noArticles.style.display = 'none';
}

function hideLoading() {
    if (elements.loading) elements.loading.style.display = 'none';
}

// ===== Notification =====
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#4ade80' : type === 'error' ? '#f87171' : '#667eea'};
        color: white;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideIn 0.3s;
        max-width: 350px;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ===== Échapper le HTML =====
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ===== Animations CSS =====
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ===== Auto-refresh des stats =====
setInterval(() => {
    updateSidebarStats();
}, 60000); // Toutes les 60 secondes

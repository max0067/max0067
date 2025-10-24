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
    feedsGridHome: document.getElementById('feeds-grid-home'),
    loading: document.getElementById('loading'),
    noFeeds: document.getElementById('no-feeds'),
    articleModal: document.getElementById('article-modal'),
    searchInput: document.getElementById('search-input'),
    searchClear: document.getElementById('search-clear'),
    searchStats: document.getElementById('search-stats')
};

// ===== Gestion des Tags Colorés (DOIT ÊTRE AVANT createArticleElement) =====

// Tags par défaut
function getDefaultTags() {
    return [
        { id: 'urgent', name: 'Urgent', color: '#EF4444' },
        { id: 'important', name: 'Important', color: '#F97316' },
        { id: 'a-lire', name: 'À lire', color: '#3B82F6' },
        { id: 'archive', name: 'Archive', color: '#6B7280' }
    ];
}

// Récupérer les tags depuis localStorage
function getUserTags() {
    const saved = localStorage.getItem('user_tags');
    if (saved) {
        try {
            return JSON.parse(saved);
        } catch (e) {
            return getDefaultTags();
        }
    }
    return getDefaultTags();
}

// Sauvegarder les tags
function saveUserTags(tags) {
    localStorage.setItem('user_tags', JSON.stringify(tags));
}

// Récupérer les tags d'un article
function getArticleTags(articleId) {
    const saved = localStorage.getItem(`article_tags_${articleId}`);
    if (saved) {
        try {
            return JSON.parse(saved);
        } catch (e) {
            return [];
        }
    }
    return [];
}

// Sauvegarder les tags d'un article
function saveArticleTags(articleId, tagIds) {
    localStorage.setItem(`article_tags_${articleId}`, JSON.stringify(tagIds));
}

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
    // Recherche
    if (elements.searchInput) {
        elements.searchInput.addEventListener('input', handleSearch);
    }
    if (elements.searchClear) {
        elements.searchClear.addEventListener('click', clearSearch);
    }

    // Menu utilisateur
    const userMenuButton = document.getElementById('user-menu-button');
    if (userMenuButton) {
        userMenuButton.addEventListener('click', toggleUserMenu);
    }
    document.addEventListener('click', closeUserMenuOutside);

    // Déconnexion
    const logoutLink = document.getElementById('logout-link');
    if (logoutLink) {
        logoutLink.addEventListener('click', (e) => {
            e.preventDefault();
            logout();
        });
    }

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

// ===== Afficher les flux dans la grille d'accueil =====
function renderFeeds() {
    if (!elements.feedsGridHome) return;

    elements.feedsGridHome.innerHTML = '';

    if (state.feeds.length === 0) {
        elements.feedsGridHome.style.display = 'none';
        elements.noFeeds.style.display = 'block';
        return;
    }

    elements.feedsGridHome.style.display = 'grid';
    elements.noFeeds.style.display = 'none';

    state.feeds.forEach(feed => {
        const feedCard = createFeedCardHome(feed);
        elements.feedsGridHome.appendChild(feedCard);
    });
}

// ===== Créer une carte de flux pour l'accueil =====
function createFeedCardHome(feed) {
    const card = document.createElement('div');
    card.className = 'feed-card-home';

    const statusBadge = feed.active
        ? '<span class="feed-card-home-badge active">Actif</span>'
        : '<span class="feed-card-home-badge inactive">Inactif</span>';

    card.innerHTML = `
        <div class="feed-card-home-icon">📡</div>
        <div class="feed-card-home-title">${escapeHtml(feed.title || 'Sans titre')}</div>
        ${feed.description ? `<div class="feed-card-home-description">${escapeHtml(feed.description)}</div>` : ''}
        <div class="feed-card-home-footer">
            <div class="feed-card-home-count">
                <span class="feed-card-home-count-value">${feed.article_count || 0}</span>
                <span>articles</span>
            </div>
            ${statusBadge}
        </div>
    `;

    // Rediriger vers la page de gestion des flux au clic
    card.addEventListener('click', () => {
        window.location.href = '/feeds';
    });

    return card;
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

    // Récupérer les tags de l'article
    const articleTagIds = getArticleTags(article.id);
    const allTags = getUserTags();
    const articleTags = articleTagIds.map(tagId => allTags.find(t => t.id === tagId)).filter(Boolean);

    // Construire le HTML des tags
    let tagsHtml = '<div class="article-tags">';
    articleTags.forEach(tag => {
        tagsHtml += `
            <span class="article-tag" style="background: ${tag.color}" onclick="event.stopPropagation()">
                ${escapeHtml(tag.name)}
                <span class="article-tag-remove" onclick="removeTagFromArticle(${article.id}, '${tag.id}')">✕</span>
            </span>
        `;
    });
    tagsHtml += `
        <button class="btn-add-tag" onclick="event.stopPropagation(); addTagToArticle(${article.id}, '${escapeHtml(article.title).replace(/'/g, "\\'")}')">
            + Tag
        </button>
    `;
    tagsHtml += '</div>';

    div.innerHTML = `
        <div class="article-item-header">
            <div class="article-item-title">${escapeHtml(article.title)}</div>
            <div class="article-item-actions">
                <span class="article-folder" onclick="classifyArticle(event, ${article.id}, '${escapeHtml(article.title)}')">📁</span>
                <span class="article-favorite ${article.favorite ? 'active' : ''}" onclick="toggleFavorite(event, ${article.id})">⭐</span>
            </div>
        </div>
        <div class="article-item-meta">
            <span class="article-item-feed">${escapeHtml(article.feed_title)}</span>
            ${publishedDate ? `<span>${publishedDate}</span>` : ''}
            ${article.author ? `<span>Par ${escapeHtml(article.author)}</span>` : ''}
        </div>
        ${article.description ? `<div class="article-item-description">${escapeHtml(article.description)}</div>` : ''}
        ${tagsHtml}
    `;

    div.addEventListener('click', (e) => {
        if (!e.target.classList.contains('article-favorite') &&
            !e.target.classList.contains('article-folder') &&
            !e.target.classList.contains('btn-add-tag') &&
            !e.target.classList.contains('article-tag-remove')) {
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
    console.log('editFeed appelée avec feedId:', feedId);
    const feed = state.feeds.find(f => f.id === feedId);
    console.log('Feed trouvé:', feed);
    if (feed) {
        openFeedModal(feed);
    } else {
        console.error('Flux non trouvé avec id:', feedId);
        showNotification('Flux non trouvé', 'error');
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

// ===== Gestion des thèmes/dossiers =====

// Récupérer les thèmes depuis localStorage
function getLocalThemes() {
    const saved = localStorage.getItem('user_themes');
    if (saved) {
        try {
            return JSON.parse(saved);
        } catch (e) {
            return [];
        }
    }
    return [];
}

// Sauvegarder les thèmes dans localStorage
function saveLocalThemes(themes) {
    localStorage.setItem('user_themes', JSON.stringify(themes));
}

// Récupérer les articles d'un thème
function getThemeArticles(themeId) {
    const saved = localStorage.getItem(`theme_articles_${themeId}`);
    if (saved) {
        try {
            return JSON.parse(saved);
        } catch (e) {
            return [];
        }
    }
    return [];
}

// Sauvegarder les articles d'un thème
function saveThemeArticles(themeId, articles) {
    localStorage.setItem(`theme_articles_${themeId}`, JSON.stringify(articles));
}

// Classer un article dans un thème
window.classifyArticle = function(event, articleId, articleTitle) {
    event.stopPropagation();

    const themes = getLocalThemes();

    if (themes.length === 0) {
        const create = confirm('Vous n\'avez pas encore de thèmes.\nVoulez-vous en créer un maintenant ?');
        if (create) {
            const name = prompt('Nom du thème :');
            if (name) {
                const colors = ['#3B82F6', '#F97316', '#EAB308', '#22C55E', '#A855F7', '#14B8A6'];
                const icons = ['📁', '⚖️', '📋', '🏛️', '📊', '🔍'];

                const newTheme = {
                    id: 'theme_' + Date.now(),
                    name: name,
                    color: colors[0],
                    icon: icons[0],
                    count: 0,
                    articles: []
                };

                themes.push(newTheme);
                saveLocalThemes(themes);

                // Ajouter l'article au nouveau thème
                addArticleToTheme(newTheme.id, articleId, articleTitle);
                showNotification(`Article ajouté au thème "${name}"`, 'success');
            }
        }
        return;
    }

    // Créer le menu de sélection
    let message = `Classer "${articleTitle.substring(0, 50)}..." dans :\n\n`;
    themes.forEach((theme, index) => {
        const articles = getThemeArticles(theme.id);
        const isInTheme = articles.some(a => a.id === articleId);
        const mark = isInTheme ? '✓ ' : '';
        message += `${index + 1}. ${mark}${theme.icon} ${theme.name} (${articles.length} articles)\n`;
    });
    message += '\nEntrez le numéro du thème (ou 0 pour annuler) :';

    const response = prompt(message);
    if (!response || response === '0') return;

    const themeIndex = parseInt(response) - 1;
    if (themeIndex >= 0 && themeIndex < themes.length) {
        const theme = themes[themeIndex];
        const articles = getThemeArticles(theme.id);

        // Vérifier si l'article est déjà dans le thème
        const existingIndex = articles.findIndex(a => a.id === articleId);

        if (existingIndex >= 0) {
            // Retirer l'article du thème
            if (confirm(`Retirer cet article du thème "${theme.name}" ?`)) {
                articles.splice(existingIndex, 1);
                saveThemeArticles(theme.id, articles);

                // Mettre à jour le compteur
                theme.count = articles.length;
                saveLocalThemes(themes);

                showNotification(`Article retiré du thème "${theme.name}"`, 'success');
            }
        } else {
            // Ajouter l'article au thème
            addArticleToTheme(theme.id, articleId, articleTitle);
            showNotification(`Article ajouté au thème "${theme.name}"`, 'success');
        }
    } else {
        showNotification('Numéro de thème invalide', 'error');
    }
}

// Ajouter un article à un thème
function addArticleToTheme(themeId, articleId, articleTitle) {
    const themes = getLocalThemes();
    const theme = themes.find(t => t.id === themeId);

    if (!theme) return;

    const articles = getThemeArticles(themeId);

    // Vérifier que l'article n'est pas déjà dans le thème
    if (!articles.some(a => a.id === articleId)) {
        articles.push({
            id: articleId,
            title: articleTitle,
            added_at: new Date().toISOString()
        });

        saveThemeArticles(themeId, articles);

        // Mettre à jour le compteur
        theme.count = articles.length;
        saveLocalThemes(themes);
    }
}

// ===== Gestion des Tags Colorés - Fonctions d'action =====

// Retirer un tag d'un article
window.removeTagFromArticle = function(articleId, tagId) {
    const articleTags = getArticleTags(articleId);
    const newTags = articleTags.filter(id => id !== tagId);
    saveArticleTags(articleId, newTags);

    const allTags = getUserTags();
    const tag = allTags.find(t => t.id === tagId);
    if (tag) {
        showNotification(`Tag "${tag.name}" retiré`, 'success');
    }

    renderArticles();
}

// Ajouter un tag à un article
window.addTagToArticle = function(articleId, articleTitle) {
    const tags = getUserTags();
    const articleTags = getArticleTags(articleId);

    if (tags.length === 0) {
        if (confirm('Vous n\'avez pas encore de tags.\nVoulez-vous en créer un maintenant ?')) {
            createNewTag(() => {
                addTagToArticle(articleId, articleTitle);
            });
        }
        return;
    }

    let message = `Ajouter un tag à "${articleTitle.substring(0, 50)}..." :\n\n`;
    tags.forEach((tag, index) => {
        const hasTag = articleTags.includes(tag.id);
        const mark = hasTag ? '✓ ' : '';
        message += `${index + 1}. ${mark}${tag.name}\n`;
    });
    message += '\n0. Créer un nouveau tag\n';
    message += '\nEntrez le numéro (ou Annuler) :';

    const response = prompt(message);
    if (!response) return;

    if (response === '0') {
        createNewTag(() => {
            addTagToArticle(articleId, articleTitle);
        });
        return;
    }

    const tagIndex = parseInt(response) - 1;
    if (tagIndex >= 0 && tagIndex < tags.length) {
        const tag = tags[tagIndex];
        const hasTag = articleTags.includes(tag.id);

        if (hasTag) {
            // Retirer le tag
            const newTags = articleTags.filter(id => id !== tag.id);
            saveArticleTags(articleId, newTags);
            showNotification(`Tag "${tag.name}" retiré`, 'success');
        } else {
            // Ajouter le tag
            articleTags.push(tag.id);
            saveArticleTags(articleId, articleTags);
            showNotification(`Tag "${tag.name}" ajouté`, 'success');
        }

        // Recharger les articles pour montrer le changement
        renderArticles();
    }
}

// Créer un nouveau tag
function createNewTag(callback) {
    const name = prompt('Nom du tag :');
    if (!name || name.trim() === '') return;

    const colors = [
        { name: 'Rouge', value: '#EF4444' },
        { name: 'Orange', value: '#F97316' },
        { name: 'Jaune', value: '#F59E0B' },
        { name: 'Vert', value: '#10B981' },
        { name: 'Bleu', value: '#3B82F6' },
        { name: 'Indigo', value: '#6366F1' },
        { name: 'Violet', value: '#8B5CF6' },
        { name: 'Rose', value: '#EC4899' },
        { name: 'Gris', value: '#6B7280' }
    ];

    let colorMessage = 'Choisissez une couleur :\n\n';
    colors.forEach((color, index) => {
        colorMessage += `${index + 1}. ${color.name}\n`;
    });

    const colorResponse = prompt(colorMessage);
    if (!colorResponse) return;

    const colorIndex = parseInt(colorResponse) - 1;
    if (colorIndex >= 0 && colorIndex < colors.length) {
        const tags = getUserTags();
        const newTag = {
            id: 'tag_' + Date.now(),
            name: name.trim(),
            color: colors[colorIndex].value
        };

        tags.push(newTag);
        saveUserTags(tags);
        showNotification(`Tag "${newTag.name}" créé avec succès !`, 'success');

        if (callback) callback();
    }
}

// Gérer les tags
window.manageTags = function() {
    const tags = getUserTags();

    if (tags.length === 0) {
        alert('Vous n\'avez pas encore de tags.\nCliquez sur "Créer un tag" pour commencer !');
        return;
    }

    let message = 'Gestion des tags :\n\n';
    tags.forEach((tag, index) => {
        message += `${index + 1}. ${tag.name} (${tag.color})\n`;
    });
    message += '\nActions :\n';
    message += '• Modifier : entrez le numéro du tag (ex: 1)\n';
    message += '• Supprimer : entrez le numéro avec "s" (ex: s1)\n';
    message += '• Annuler : entrez 0\n\n';
    message += 'Votre choix :';

    const response = prompt(message);
    if (!response || response === '0') return;

    if (response.toLowerCase().startsWith('s')) {
        const tagIndex = parseInt(response.substring(1)) - 1;
        if (tagIndex >= 0 && tagIndex < tags.length) {
            if (confirm(`Voulez-vous vraiment supprimer le tag "${tags[tagIndex].name}" ?`)) {
                tags.splice(tagIndex, 1);
                saveUserTags(tags);
                showNotification('Tag supprimé avec succès !', 'success');
            }
        }
    } else {
        const tagIndex = parseInt(response) - 1;
        if (tagIndex >= 0 && tagIndex < tags.length) {
            editTag(tagIndex);
        }
    }
}

// Modifier un tag
function editTag(tagIndex) {
    const tags = getUserTags();
    const tag = tags[tagIndex];

    const newName = prompt('Nouveau nom du tag :', tag.name);
    if (newName && newName.trim() !== '') {
        tag.name = newName.trim();
        saveUserTags(tags);
        showNotification('Tag modifié avec succès !', 'success');
    }
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

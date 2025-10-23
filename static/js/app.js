// État de l'application
const state = {
    feeds: [],
    articles: [],
    currentFeedId: null,
    unreadOnly: false
};

// Éléments du DOM
const elements = {
    feedsList: document.getElementById('feeds-list'),
    articlesList: document.getElementById('articles-list'),
    feedModal: document.getElementById('feed-modal'),
    articleModal: document.getElementById('article-modal'),
    feedForm: document.getElementById('feed-form'),
    loading: document.getElementById('loading'),
    noArticles: document.getElementById('no-articles'),
    articlesTitle: document.getElementById('articles-title'),
    statsFeeds: document.getElementById('stats-feeds'),
    statsArticles: document.getElementById('stats-articles'),
    statsUnread: document.getElementById('stats-unread')
};

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    initializeEventListeners();
    loadFeeds();
    loadArticles();
    loadStats();

    // Actualiser les stats toutes les 30 secondes
    setInterval(loadStats, 30000);
});

// Gestionnaires d'événements
function initializeEventListeners() {
    // Boutons
    document.getElementById('btn-add-feed').addEventListener('click', () => openFeedModal());
    document.getElementById('btn-update-all').addEventListener('click', updateAllFeeds);
    document.getElementById('btn-show-all').addEventListener('click', showAllArticles);
    document.getElementById('btn-cancel').addEventListener('click', closeFeedModal);
    document.getElementById('filter-unread').addEventListener('change', handleUnreadFilter);

    // Formulaire
    elements.feedForm.addEventListener('submit', handleFeedSubmit);

    // Modals
    const closeButtons = document.getElementsByClassName('close');
    Array.from(closeButtons).forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });

    // Fermer le modal en cliquant en dehors
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
}

// API: Charger les flux
async function loadFeeds() {
    try {
        const response = await fetch('/api/feeds');
        const data = await response.json();

        if (data.success) {
            state.feeds = data.feeds;
            renderFeeds();
        }
    } catch (error) {
        console.error('Erreur lors du chargement des flux:', error);
        showNotification('Erreur lors du chargement des flux', 'error');
    }
}

// API: Charger les articles
async function loadArticles(feedId = null, unreadOnly = false) {
    elements.loading.style.display = 'block';
    elements.articlesList.style.display = 'none';
    elements.noArticles.style.display = 'none';

    try {
        let url = `/api/articles?limit=100`;
        if (feedId) url += `&feed_id=${feedId}`;
        if (unreadOnly) url += `&unread_only=true`;

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            state.articles = data.articles;
            renderArticles();
        }
    } catch (error) {
        console.error('Erreur lors du chargement des articles:', error);
        showNotification('Erreur lors du chargement des articles', 'error');
    } finally {
        elements.loading.style.display = 'none';
    }
}

// API: Charger les statistiques
async function loadStats() {
    try {
        const response = await fetch('/api/stats');
        const data = await response.json();

        if (data.success) {
            const stats = data.stats;
            elements.statsFeeds.textContent = `${stats.total_feeds} flux`;
            elements.statsArticles.textContent = `${stats.total_articles} articles`;
            elements.statsUnread.textContent = `${stats.unread_articles} non lus`;
        }
    } catch (error) {
        console.error('Erreur lors du chargement des stats:', error);
    }
}

// Afficher les flux
function renderFeeds() {
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

// Créer un élément de flux
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
            <button onclick="updateFeed(${feed.id})">Actualiser</button>
            <button onclick="editFeed(${feed.id})">Modifier</button>
            <button onclick="deleteFeed(${feed.id})">Supprimer</button>
        </div>
    `;

    // Clic sur le flux pour afficher ses articles
    div.addEventListener('click', (e) => {
        if (!e.target.matches('button')) {
            showFeedArticles(feed.id, feed.title);
        }
    });

    return div;
}

// Afficher les articles
function renderArticles() {
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

// Créer un élément d'article
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
        </div>
        <div class="article-item-meta">
            <span class="article-item-feed">${escapeHtml(article.feed_title)}</span>
            ${publishedDate ? `<span>${publishedDate}</span>` : ''}
            ${article.author ? `<span>Par ${escapeHtml(article.author)}</span>` : ''}
        </div>
        ${article.description ? `<div class="article-item-description">${escapeHtml(article.description)}</div>` : ''}
    `;

    div.addEventListener('click', () => showArticleDetail(article));

    return div;
}

// Modal: Ajouter un flux
function openFeedModal(feed = null) {
    document.getElementById('modal-title').textContent = feed ? 'Modifier le flux' : 'Ajouter un flux RSS';
    document.getElementById('feed-id').value = feed ? feed.id : '';
    document.getElementById('feed-title').value = feed ? feed.title : '';
    document.getElementById('feed-url').value = feed ? feed.url : '';
    document.getElementById('feed-description').value = feed ? feed.description : '';
    document.getElementById('feed-interval').value = feed ? feed.update_interval : 30;
    document.getElementById('feed-active').checked = feed ? feed.active : true;

    elements.feedModal.style.display = 'block';
}

function closeFeedModal() {
    elements.feedModal.style.display = 'none';
    elements.feedForm.reset();
}

// Soumettre le formulaire de flux
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
            await loadStats();

            // Si c'est un nouveau flux, charger ses articles
            if (!feedId && data.feed_id) {
                setTimeout(() => loadFeeds(), 2000); // Recharger après que les articles soient récupérés
            }
        } else {
            showNotification(data.error || 'Erreur lors de la sauvegarde', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la sauvegarde', 'error');
    }
}

// Modifier un flux
function editFeed(feedId) {
    const feed = state.feeds.find(f => f.id === feedId);
    if (feed) {
        openFeedModal(feed);
    }
}

// Supprimer un flux
async function deleteFeed(feedId) {
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
            await loadStats();
        } else {
            showNotification(data.error || 'Erreur lors de la suppression', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la suppression', 'error');
    }
}

// Actualiser un flux
async function updateFeed(feedId) {
    showNotification('Mise à jour en cours...', 'info');

    try {
        const response = await fetch(`/api/feeds/${feedId}/update`, {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            showNotification(data.message, 'success');
            await loadFeeds();
            await loadStats();

            if (state.currentFeedId === feedId || state.currentFeedId === null) {
                await loadArticles(state.currentFeedId, state.unreadOnly);
            }
        } else {
            showNotification(data.error || 'Erreur lors de la mise à jour', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la mise à jour', 'error');
    }
}

// Actualiser tous les flux
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
            await loadStats();
            await loadArticles(state.currentFeedId, state.unreadOnly);
        } else {
            showNotification(data.error || 'Erreur lors de la mise à jour', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la mise à jour', 'error');
    }
}

// Afficher les articles d'un flux
function showFeedArticles(feedId, feedTitle) {
    state.currentFeedId = feedId;
    elements.articlesTitle.textContent = feedTitle;
    loadArticles(feedId, state.unreadOnly);
    renderFeeds(); // Re-render pour mettre à jour la sélection
}

// Afficher tous les articles
function showAllArticles() {
    state.currentFeedId = null;
    elements.articlesTitle.textContent = 'Tous les articles';
    loadArticles(null, state.unreadOnly);
    renderFeeds(); // Re-render pour mettre à jour la sélection
}

// Filtre non lus
function handleUnreadFilter(e) {
    state.unreadOnly = e.target.checked;
    loadArticles(state.currentFeedId, state.unreadOnly);
}

// Afficher le détail d'un article
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
            <a href="${escapeHtml(article.link)}" target="_blank" class="btn btn-primary">Lire l'article complet</a>
        </div>
    `;

    elements.articleModal.style.display = 'block';

    // Marquer comme lu
    if (!article.read) {
        await markArticleAsRead(article.id);
    }
}

// Marquer un article comme lu
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

        await loadStats();
    } catch (error) {
        console.error('Erreur lors du marquage de l\'article:', error);
    }
}

// Notification
function showNotification(message, type = 'info') {
    // Simple console log pour l'instant, peut être amélioré avec une vraie notification
    console.log(`[${type.toUpperCase()}] ${message}`);

    // Création d'une notification temporaire
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
        color: white;
        border-radius: 6px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideIn 0.3s;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Échapper le HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Animations CSS (ajoutées dynamiquement)
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

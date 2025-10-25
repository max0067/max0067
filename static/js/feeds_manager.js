// ===== STATE =====
const state = {
    feeds: [],
    currentUser: null
};

// ===== INITIALISATION =====
document.addEventListener('DOMContentLoaded', () => {
    initializeEventListeners();
    loadCurrentUser();
    loadFeeds();
});

// ===== EVENT LISTENERS =====
function initializeEventListeners() {
    document.getElementById('btn-add-feed').addEventListener('click', () => openFeedModal());
    document.getElementById('btn-update-all').addEventListener('click', updateAllFeeds);
    document.getElementById('modal-close').addEventListener('click', closeFeedModal);
    document.getElementById('btn-cancel').addEventListener('click', closeFeedModal);
    document.getElementById('feed-form').addEventListener('submit', handleFeedSubmit);
    document.getElementById('user-menu-button').addEventListener('click', toggleUserMenu);
    document.getElementById('logout-link').addEventListener('click', (e) => {
        e.preventDefault();
        logout();
    });
    document.addEventListener('click', closeUserMenuOutside);

    // Menu hamburger
    const menuToggle = document.getElementById('menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleMobileMenu();
        });
    }
    document.addEventListener('click', closeMobileMenuOutside);

    // Fermer le modal en cliquant en dehors
    document.getElementById('feed-modal').addEventListener('click', (e) => {
        if (e.target.id === 'feed-modal') {
            closeFeedModal();
        }
    });
}

// ===== USER =====
async function loadCurrentUser() {
    try {
        const response = await fetch('/api/current-user');
        const data = await response.json();

        if (data.success) {
            state.currentUser = data.user;
            document.getElementById('current-username').textContent = data.user.username;
            document.getElementById('dropdown-username').textContent = data.user.username;
            document.getElementById('dropdown-role').textContent = data.user.role;
        }
    } catch (error) {
        console.error('Erreur lors du chargement de l\'utilisateur:', error);
    }
}

function toggleUserMenu() {
    const dropdown = document.getElementById('user-dropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

function closeUserMenuOutside(e) {
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.getElementById('user-dropdown');

    if (userMenu && !userMenu.contains(e.target)) {
        dropdown.style.display = 'none';
    }
}

// ===== MENU MOBILE =====
function toggleMobileMenu() {
    const menuToggle = document.getElementById('menu-toggle');
    const navbarMenu = document.getElementById('navbar-menu');

    if (menuToggle && navbarMenu) {
        menuToggle.classList.toggle('active');
        navbarMenu.classList.toggle('active');
    }
}

function closeMobileMenuOutside(e) {
    const menuToggle = document.getElementById('menu-toggle');
    const navbarMenu = document.getElementById('navbar-menu');

    if (menuToggle && navbarMenu &&
        !menuToggle.contains(e.target) &&
        !navbarMenu.contains(e.target) &&
        navbarMenu.classList.contains('active')) {
        menuToggle.classList.remove('active');
        navbarMenu.classList.remove('active');
    }
}

async function logout() {
    try {
        const response = await fetch('/logout', { method: 'POST' });
        if (response.ok) {
            window.location.href = '/login';
        }
    } catch (error) {
        console.error('Erreur lors de la déconnexion:', error);
    }
}

// ===== LOAD FEEDS =====
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

// ===== RENDER FEEDS =====
function renderFeeds() {
    const feedsList = document.getElementById('feeds-list');
    const emptyState = document.getElementById('empty-state');

    if (state.feeds.length === 0) {
        feedsList.style.display = 'none';
        emptyState.style.display = 'block';
        return;
    }

    feedsList.style.display = 'grid';
    emptyState.style.display = 'none';
    feedsList.innerHTML = '';

    state.feeds.forEach(feed => {
        const feedCard = createFeedCard(feed);
        feedsList.appendChild(feedCard);
    });
}

// ===== CREATE FEED CARD =====
function createFeedCard(feed) {
    const card = document.createElement('div');
    card.className = 'feed-card';

    const statusBadge = feed.active
        ? '<span class="feed-badge active">Actif</span>'
        : '<span class="feed-badge inactive">Inactif</span>';

    card.innerHTML = `
        <div class="feed-card-header">
            <div class="feed-info">
                <div class="feed-title">${escapeHtml(feed.title || 'Sans titre')}</div>
                <div class="feed-url">${escapeHtml(feed.url)}</div>
            </div>
            <div class="feed-actions">
                <button class="btn btn-sm btn-outline" data-action="update" data-feed-id="${feed.id}" title="Actualiser">
                    <svg class="icon-linear icon-linear-sm" viewBox="0 0 24 24">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <polyline points="1 20 1 14 7 14"></polyline>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                    </svg>
                </button>
                <button class="btn btn-sm btn-edit" data-action="edit" data-feed-id="${feed.id}" title="Modifier">
                    <svg class="icon-linear icon-linear-sm" viewBox="0 0 24 24">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
                <button class="btn btn-sm btn-delete" data-action="delete" data-feed-id="${feed.id}" title="Supprimer">
                    <svg class="icon-linear icon-linear-sm" viewBox="0 0 24 24">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
        </div>
        ${feed.description ? `<div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1rem;">${escapeHtml(feed.description)}</div>` : ''}
        <div class="feed-meta">
            <div class="feed-stat">
                <svg class="icon-linear icon-linear-sm" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                    <polyline points="13 2 13 9 20 9"></polyline>
                </svg>
                <span>${feed.article_count || 0} articles</span>
            </div>
            <div class="feed-stat">
                <svg class="icon-linear icon-linear-sm" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Toutes les ${feed.update_interval || 30} min</span>
            </div>
            <div class="feed-stat">
                ${statusBadge}
            </div>
        </div>
    `;

    // Event listeners pour les boutons
    card.querySelectorAll('button[data-action]').forEach(button => {
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            const action = button.dataset.action;
            const feedId = parseInt(button.dataset.feedId);

            if (action === 'update') {
                updateFeed(feedId);
            } else if (action === 'edit') {
                editFeed(feedId);
            } else if (action === 'delete') {
                deleteFeed(feedId);
            }
        });
    });

    return card;
}

// ===== MODAL =====
function openFeedModal(feed = null) {
    document.getElementById('modal-title').textContent = feed ? 'Modifier le flux' : 'Ajouter un flux RSS';
    document.getElementById('feed-id').value = feed ? feed.id : '';
    document.getElementById('feed-title').value = feed ? feed.title : '';
    document.getElementById('feed-url').value = feed ? feed.url : '';
    document.getElementById('feed-description').value = feed ? feed.description : '';
    document.getElementById('feed-interval').value = feed ? feed.update_interval : 30;
    document.getElementById('feed-active').checked = feed ? feed.active : true;

    document.getElementById('feed-modal').style.display = 'flex';
}

function closeFeedModal() {
    document.getElementById('feed-modal').style.display = 'none';
    document.getElementById('feed-form').reset();
}

// ===== FORM SUBMIT =====
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

// ===== ACTIONS =====
function editFeed(feedId) {
    const feed = state.feeds.find(f => f.id === feedId);
    if (feed) {
        openFeedModal(feed);
    }
}

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
            await loadFeeds();
        } else {
            showNotification(data.error || 'Erreur lors de la suppression', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la suppression', 'error');
    }
}

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
        } else {
            showNotification(data.error || 'Erreur lors de la mise à jour', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la mise à jour', 'error');
    }
}

async function updateAllFeeds() {
    showNotification('Mise à jour de tous les flux en cours...', 'info');

    try {
        const response = await fetch('/api/feeds/update-all', {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Tous les flux ont été mis à jour', 'success');
            await loadFeeds();
        } else {
            showNotification(data.error || 'Erreur lors de la mise à jour', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la mise à jour', 'error');
    }
}

// ===== NOTIFICATIONS =====
function showNotification(message, type = 'info') {
    // Supprimer les notifications existantes
    document.querySelectorAll('.notification').forEach(n => n.remove());

    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ===== UTILS =====
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

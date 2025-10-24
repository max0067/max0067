/**
 * Dashboard Moderne - JavaScript
 */

let currentUser = null;

// Charger l'utilisateur actuel
async function loadCurrentUser() {
    try {
        const response = await fetch('/api/auth/me');
        const data = await response.json();
        if (data.success) {
            currentUser = data.user;
            document.getElementById('current-username').textContent = currentUser.username;
            document.getElementById('dropdown-username').textContent = currentUser.username;
            document.getElementById('dropdown-role').textContent = currentUser.role;

            if (currentUser.role === 'admin') {
                const adminLink = document.getElementById('admin-link');
                if (adminLink) {
                    adminLink.style.display = 'flex';
                }
            }
        } else {
            window.location.href = '/login';
        }
    } catch (error) {
        console.error('Error loading user:', error);
        window.location.href = '/login';
    }
}

// Charger les statistiques
async function loadStats() {
    try {
        const response = await fetch('/api/stats');
        const data = await response.json();

        if (data.success) {
            const stats = data.stats;

            // Mise à jour des valeurs avec animation
            animateValue('total-feeds', 0, stats.total_feeds, 800);
            animateValue('active-feeds', 0, stats.active_feeds, 800);
            animateValue('total-articles', 0, stats.total_articles, 1000);
            animateValue('unread-articles', 0, stats.unread_articles, 800);
            animateValue('favorite-articles', 0, stats.favorites || 0, 800);

            const avg = stats.total_feeds > 0 ? Math.round(stats.total_articles / stats.total_feeds) : 0;
            animateValue('avg-articles', 0, avg, 800);
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Animer les valeurs numériques
function animateValue(id, start, end, duration) {
    const element = document.getElementById(id);
    if (!element) return;

    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = Math.round(current);
    }, 16);
}

// Charger les flux les plus actifs
async function loadTopFeeds() {
    try {
        const response = await fetch('/api/feeds');
        const data = await response.json();

        if (data.success && data.feeds.length > 0) {
            const sorted = data.feeds
                .sort((a, b) => b.article_count - a.article_count)
                .slice(0, 5);

            updateTopFeedsList(sorted);
        }
    } catch (error) {
        console.error('Error loading top feeds:', error);
    }
}

// Mettre à jour la liste des flux les plus actifs
function updateTopFeedsList(feeds) {
    const container = document.getElementById('top-feeds-list');
    if (!container) return;

    if (feeds.length === 0) {
        container.innerHTML = `
            <div class="empty-message">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <p>Aucun flux RSS pour le moment</p>
            </div>
        `;
        return;
    }

    container.innerHTML = feeds.map((feed, index) => {
        const rankClass = index === 0 ? 'rank-1' : index === 1 ? 'rank-2' : index === 2 ? 'rank-3' : 'rank-other';

        return `
            <div class="feed-item">
                <div class="feed-rank ${rankClass}">${index + 1}</div>
                <div class="feed-info">
                    <div class="feed-name">${escapeHtml(feed.title)}</div>
                    <div class="feed-count">${feed.article_count} articles</div>
                </div>
            </div>
        `;
    }).join('');
}

// Échapper le HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Charger les alertes importantes
async function loadAlerts() {
    try {
        const response = await fetch('/api/articles?limit=100');
        const data = await response.json();

        if (data.success && data.articles) {
            // Détecter les articles importants (lois, décrets, décisions)
            const keywords = {
                loi: ['loi', 'législation', 'projet de loi', 'proposition de loi'],
                decret: ['décret', 'arrêté', 'ordonnance'],
                decision: ['décision', 'arrêt', 'jugement', 'cour', 'tribunal', 'conseil d\'état']
            };

            const alerts = [];
            data.articles.forEach(article => {
                const titleLower = article.title.toLowerCase();
                const descLower = (article.description || '').toLowerCase();
                const combined = titleLower + ' ' + descLower;

                for (const [type, words] of Object.entries(keywords)) {
                    if (words.some(word => combined.includes(word))) {
                        alerts.push({
                            ...article,
                            alert_type: type
                        });
                        break;
                    }
                }
            });

            // Limiter aux 10 dernières alertes
            const recentAlerts = alerts.slice(0, 10);
            updateAlertsList(recentAlerts);
            updateAlertsCount(recentAlerts.length);
        }
    } catch (error) {
        console.error('Error loading alerts:', error);
    }
}

// Mettre à jour la liste des alertes
function updateAlertsList(alerts) {
    const container = document.getElementById('alerts-list');
    if (!container) return;

    if (alerts.length === 0) {
        container.innerHTML = `
            <div class="empty-message">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <p>Aucune alerte pour le moment</p>
            </div>
        `;
        return;
    }

    const typeLabels = {
        loi: 'Loi',
        decret: 'Décret',
        decision: 'Décision'
    };

    const typeIcons = {
        loi: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
              </svg>`,
        decret: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
              </svg>`,
        decision: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 3h18v18H3z"></path>
                <path d="M12 8v8"></path>
                <path d="M8 12h8"></path>
              </svg>`
    };

    container.innerHTML = alerts.map(alert => {
        const date = new Date(alert.published || alert.created_at);
        const timeAgo = getTimeAgo(date);
        const type = alert.alert_type || 'loi';

        return `
            <div class="alert-item alert-${type}" onclick="window.open('${escapeHtml(alert.link)}', '_blank')">
                <div class="alert-icon">
                    ${typeIcons[type]}
                </div>
                <div class="alert-content">
                    <div class="alert-title">${escapeHtml(alert.title)}</div>
                    <div class="alert-meta">
                        <span class="alert-badge badge-${type}">${typeLabels[type]}</span>
                        <span>${escapeHtml(alert.feed_title || 'Source inconnue')}</span>
                        <span>•</span>
                        <span>${timeAgo}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Mettre à jour le compteur d'alertes
function updateAlertsCount(count) {
    const badge = document.getElementById('alerts-count');
    if (badge) {
        badge.textContent = count > 0 ? `${count} nouvelle${count > 1 ? 's' : ''}` : '0 nouvelle';
    }
}

// Charger les thèmes
async function loadThemes() {
    try {
        // Pour l'instant, on utilise des données locales
        // Plus tard, on ajoutera une API pour gérer les thèmes
        const themes = getLocalThemes();
        updateThemesGrid(themes);
    } catch (error) {
        console.error('Error loading themes:', error);
    }
}

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

// Mettre à jour la grille des thèmes
function updateThemesGrid(themes) {
    const container = document.getElementById('themes-grid');
    if (!container) return;

    if (themes.length === 0) {
        container.innerHTML = `
            <div class="empty-message">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
                <p>Aucun favori classé par thème</p>
                <button class="btn-add-theme" onclick="createNewTheme()">Créer un thème</button>
            </div>
        `;
        return;
    }

    container.innerHTML = themes.map(theme => {
        return `
            <div class="theme-card" style="color: ${theme.color}" onclick="openTheme('${theme.id}')">
                <div class="theme-header">
                    <div class="theme-icon">${theme.icon}</div>
                    <div class="theme-name">${escapeHtml(theme.name)}</div>
                </div>
                <div class="theme-count">${theme.count || 0}</div>
                <div class="theme-label">articles</div>
            </div>
        `;
    }).join('');
}

// Créer un nouveau thème
function createNewTheme() {
    const name = prompt('Nom du thème :');
    if (!name) return;

    const colors = ['#3B82F6', '#F97316', '#EAB308', '#22C55E', '#A855F7', '#14B8A6'];
    const icons = ['📁', '⚖️', '📋', '🏛️', '📊', '🔍'];

    const themes = getLocalThemes();
    const newTheme = {
        id: 'theme_' + Date.now(),
        name: name,
        color: colors[themes.length % colors.length],
        icon: icons[themes.length % icons.length],
        count: 0,
        articles: []
    };

    themes.push(newTheme);
    saveLocalThemes(themes);
    updateThemesGrid(themes);
}

// Ouvrir un thème
function openTheme(themeId) {
    // Rediriger vers la page des favoris avec le filtre du thème
    window.location.href = `/#favorites?theme=${themeId}`;
}

// Gérer les thèmes
function manageThemes() {
    alert('Fonctionnalité de gestion des thèmes à venir !');
    // TODO: Ouvrir une modal pour gérer les thèmes
}

// Calculer le temps écoulé
function getTimeAgo(date) {
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 60) return `Il y a ${minutes} min`;
    if (hours < 24) return `Il y a ${hours}h`;
    return `Il y a ${days}j`;
}

// Menu utilisateur
document.getElementById('user-menu-button').addEventListener('click', () => {
    const dropdown = document.getElementById('user-dropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
});

document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('user-dropdown');
    const button = document.getElementById('user-menu-button');
    if (!button.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

// Déconnexion
document.getElementById('logout-link').addEventListener('click', async (e) => {
    e.preventDefault();
    try {
        await fetch('/api/auth/logout', { method: 'POST' });
        window.location.href = '/login';
    } catch (error) {
        window.location.href = '/login';
    }
});

// Bouton actualiser
document.getElementById('refresh-button').addEventListener('click', async () => {
    const button = document.getElementById('refresh-button');
    button.disabled = true;

    await Promise.all([
        loadStats(),
        loadTopFeeds(),
        loadAlerts(),
        loadThemes()
    ]);

    setTimeout(() => {
        button.disabled = false;
    }, 1000);
});

// Bouton gérer les thèmes
document.getElementById('manage-themes-btn').addEventListener('click', () => {
    manageThemes();
});

// Actualiser tout
document.getElementById('update-all-action').addEventListener('click', async (e) => {
    e.preventDefault();
    const item = e.currentTarget;
    const originalText = item.querySelector('span').textContent;

    item.querySelector('span').textContent = 'Actualisation...';
    item.style.pointerEvents = 'none';

    try {
        const response = await fetch('/api/feeds/update-all', { method: 'POST' });
        const data = await response.json();

        if (data.success) {
            item.querySelector('span').textContent = 'Terminé !';
            setTimeout(() => {
                item.querySelector('span').textContent = originalText;
                item.style.pointerEvents = 'auto';
                loadStats();
                loadTopFeeds();
            }, 2000);
        } else {
            item.querySelector('span').textContent = originalText;
            item.style.pointerEvents = 'auto';
        }
    } catch (error) {
        item.querySelector('span').textContent = originalText;
        item.style.pointerEvents = 'auto';
    }
});

// Initialisation
document.addEventListener('DOMContentLoaded', async () => {
    // Charger l'utilisateur
    await loadCurrentUser();

    // Charger les données
    await Promise.all([
        loadStats(),
        loadTopFeeds(),
        loadAlerts(),
        loadThemes()
    ]);

    // Actualiser toutes les 30 secondes
    setInterval(() => {
        loadStats();
        loadTopFeeds();
        loadAlerts();
    }, 30000);
});

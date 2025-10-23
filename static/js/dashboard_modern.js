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
        loadTopFeeds()
    ]);

    setTimeout(() => {
        button.disabled = false;
    }, 1000);
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
        loadTopFeeds()
    ]);

    // Actualiser toutes les 30 secondes
    setInterval(() => {
        loadStats();
        loadTopFeeds();
    }, 30000);
});

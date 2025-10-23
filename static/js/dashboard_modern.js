/**
 * Dashboard Moderne - JavaScript
 * Gestion des graphiques et des interactions
 */

let currentUser = null;
let articlesChart = null;
let feedsChart = null;

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

            // Mise à jour des valeurs
            animateValue('total-feeds', 0, stats.total_feeds, 1000);
            animateValue('active-feeds', 0, stats.active_feeds, 1000);
            animateValue('total-articles', 0, stats.total_articles, 1200);
            animateValue('unread-articles', 0, stats.unread_articles, 1000);
            animateValue('favorite-articles', 0, stats.favorites || 0, 1000);

            const avg = stats.total_feeds > 0 ? Math.round(stats.total_articles / stats.total_feeds) : 0;
            animateValue('avg-articles', 0, avg, 1000);

            // Mise à jour des barres de progression
            setTimeout(() => {
                updateProgressBar('feeds-progress', Math.min((stats.active_feeds / Math.max(stats.total_feeds, 1)) * 100, 100));
                updateProgressBar('articles-progress', Math.min((stats.total_articles / 1000) * 100, 100));
                updateProgressBar('unread-progress', Math.min((stats.unread_articles / Math.max(stats.total_articles, 1)) * 100, 100));
                updateProgressBar('favorites-progress', Math.min(((stats.favorites || 0) / Math.max(stats.total_articles, 1)) * 100, 100));
            }, 500);

            // Mettre à jour les graphiques
            updateArticlesChart(stats);
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

// Mettre à jour une barre de progression
function updateProgressBar(id, percentage) {
    const bar = document.getElementById(id);
    if (bar) {
        bar.style.width = `${percentage}%`;
    }
}

// Initialiser le graphique des articles
function initArticlesChart() {
    const ctx = document.getElementById('articlesChart');
    if (!ctx) return;

    articlesChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Non lus', 'Lus', 'Favoris'],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: [
                    'rgba(11, 163, 96, 0.8)',
                    'rgba(102, 126, 234, 0.8)',
                    'rgba(240, 152, 25, 0.8)'
                ],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 12,
                    borderRadius: 8,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            },
            cutout: '70%',
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1500,
                easing: 'easeInOutQuart'
            }
        }
    });
}

// Mettre à jour le graphique des articles
function updateArticlesChart(stats) {
    if (!articlesChart) return;

    const read = stats.total_articles - stats.unread_articles;
    articlesChart.data.datasets[0].data = [
        stats.unread_articles,
        read,
        stats.favorites || 0
    ];
    articlesChart.update();
}

// Initialiser le graphique des flux
function initFeedsChart() {
    const ctx = document.getElementById('feedsChart');
    if (!ctx) return;

    feedsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Articles',
                data: [],
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderRadius: 8,
                barThickness: 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 12,
                    borderRadius: 8,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeInOutQuart'
            }
        }
    });
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

            // Mettre à jour la liste
            updateTopFeedsList(sorted);

            // Mettre à jour le graphique
            if (feedsChart && sorted.length > 0) {
                const labels = sorted.map(feed =>
                    feed.title.length > 20 ? feed.title.substring(0, 20) + '...' : feed.title
                );
                const dataValues = sorted.map(feed => feed.article_count);

                feedsChart.data.labels = labels;
                feedsChart.data.datasets[0].data = dataValues;
                feedsChart.update();
            }
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
            <div class="empty-state">
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

    const maxArticles = Math.max(...feeds.map(f => f.article_count));

    container.innerHTML = feeds.map((feed, index) => {
        const rankClass = index < 3 ? `rank-${index + 1}` : 'rank-other';
        const percentage = (feed.article_count / maxArticles) * 100;

        return `
            <div class="top-feed-item">
                <div class="feed-rank ${rankClass}">${index + 1}</div>
                <div class="feed-info">
                    <div class="feed-name">${escapeHtml(feed.title)}</div>
                    <div class="feed-count">${feed.article_count} articles</div>
                </div>
                <div class="feed-bar">
                    <div class="feed-bar-fill" style="width: ${percentage}%"></div>
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
    button.classList.add('loading');

    await Promise.all([
        loadStats(),
        loadTopFeeds()
    ]);

    setTimeout(() => {
        button.classList.remove('loading');
    }, 1000);
});

// Actualiser tout
document.getElementById('update-all-action').addEventListener('click', async (e) => {
    e.preventDefault();
    const item = e.currentTarget;
    const icon = item.querySelector('.quick-action-icon');
    const originalContent = icon.innerHTML;

    icon.innerHTML = `
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10" stroke-dasharray="60" stroke-dashoffset="40">
                <animate attributeName="stroke-dashoffset" values="60;0" dur="1s" repeatCount="indefinite"/>
            </circle>
        </svg>
    `;

    try {
        const response = await fetch('/api/feeds/update-all', { method: 'POST' });
        const data = await response.json();

        if (data.success) {
            icon.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            `;
            setTimeout(() => {
                icon.innerHTML = originalContent;
                loadStats();
                loadTopFeeds();
            }, 2000);
        } else {
            icon.innerHTML = originalContent;
        }
    } catch (error) {
        icon.innerHTML = originalContent;
    }
});

// Initialisation
document.addEventListener('DOMContentLoaded', async () => {
    // Charger l'utilisateur
    await loadCurrentUser();

    // Initialiser les graphiques
    initArticlesChart();
    initFeedsChart();

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

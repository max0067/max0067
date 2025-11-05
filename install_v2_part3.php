<?php
/**
 * Installation RSS Reader V2 - Part 3/3
 * Frontend: Views et Assets
 */

echo "========================================\n";
echo "Installation V2 - Part 3/3: Frontend\n";
echo "========================================\n\n";

$baseDir = __DIR__;

// Vérifier que les dossiers existent
if (!is_dir($baseDir . '/views') || !is_dir($baseDir . '/assets')) {
    die("ERREUR: Exécutez d'abord install_v2_part1.php et install_v2_part2.php\n");
}

echo "[9/10] 🎨 Création des vues...\n";

// ========================================
// LOGIN.PHP
// ========================================
$loginContent = <<<'LOGIN'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSS Reader - Connexion</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <svg class="rss-icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 11a9 9 0 0 1 9 9"></path>
                    <path d="M4 4a16 16 0 0 1 16 16"></path>
                    <circle cx="5" cy="19" r="1"></circle>
                </svg>
                <h1>RSS Reader V2</h1>
                <p>Connectez-vous pour accéder à vos flux</p>
            </div>

            <form id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        autofocus
                        placeholder="admin"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="••••••••"
                    >
                </div>

                <div id="loginError" class="error-message" style="display: none;"></div>

                <button type="submit" class="btn-primary" id="loginBtn">
                    <span>Se connecter</span>
                </button>
            </form>

            <div class="login-footer">
                <p>Identifiants par défaut: <strong>admin</strong> / <strong>admin123</strong></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const btn = document.getElementById('loginBtn');
            const error = document.getElementById('loginError');
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            btn.disabled = true;
            btn.innerHTML = '<span>Connexion...</span>';
            error.style.display = 'none';

            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = '/';
                } else {
                    error.textContent = data.message || 'Identifiants incorrects';
                    error.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = '<span>Se connecter</span>';
                }
            } catch (err) {
                error.textContent = 'Erreur de connexion au serveur';
                error.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = '<span>Se connecter</span>';
            }
        });
    </script>
</body>
</html>
LOGIN;

file_put_contents($baseDir . '/views/login.php', $loginContent);
echo "✓ views/login.php créé\n";

// ========================================
// DASHBOARD.PHP
// ========================================
$dashboardContent = <<<'DASHBOARD'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSS Reader V2</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="app-header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <h1 class="app-title">
                    <svg class="rss-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 11a9 9 0 0 1 9 9"></path>
                        <path d="M4 4a16 16 0 0 1 16 16"></path>
                        <circle cx="5" cy="19" r="1"></circle>
                    </svg>
                    RSS Reader
                </h1>
            </div>
            <div class="header-center">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Rechercher des articles...">
                </div>
            </div>
            <div class="header-right">
                <button class="btn-icon" id="refreshBtn" title="Actualiser">⟳</button>
                <button class="btn-icon" id="logoutBtn" title="Déconnexion">⎋</button>
            </div>
        </header>

        <!-- Main Content -->
        <div class="app-main">
            <!-- Sidebar -->
            <aside class="sidebar" id="sidebar">
                <div class="sidebar-section">
                    <button class="sidebar-btn active" data-filter="all">
                        <span class="sidebar-icon">📰</span>
                        <span class="sidebar-label">Tous les articles</span>
                        <span class="sidebar-count" id="countAll">0</span>
                    </button>
                    <button class="sidebar-btn" data-filter="unread">
                        <span class="sidebar-icon">●</span>
                        <span class="sidebar-label">Non lus</span>
                        <span class="sidebar-count" id="countUnread">0</span>
                    </button>
                </div>

                <div class="sidebar-section">
                    <div class="sidebar-header">
                        <h3>Flux RSS</h3>
                        <button class="btn-icon-small" id="addFeedBtn" title="Ajouter un flux">+</button>
                    </div>
                    <div id="feedsList" class="feeds-list">
                        <!-- Feeds loaded dynamically -->
                    </div>
                </div>
            </aside>

            <!-- Article List -->
            <main class="article-list" id="articleList">
                <div class="loading">Chargement...</div>
            </main>

            <!-- Article Reader -->
            <aside class="article-reader" id="articleReader">
                <div class="reader-placeholder">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                    <p>Sélectionnez un article pour le lire</p>
                </div>
            </aside>
        </div>
    </div>

    <!-- Add Feed Modal -->
    <div id="addFeedModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Ajouter un flux RSS</h2>
                <button class="modal-close" id="closeModal">&times;</button>
            </div>
            <form id="addFeedForm">
                <div class="form-group">
                    <label for="feedUrl">URL du flux RSS</label>
                    <input type="url" id="feedUrl" required placeholder="https://example.com/feed.xml">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="cancelAddFeed">Annuler</button>
                    <button type="submit" class="btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/assets/app.js"></script>
</body>
</html>
DASHBOARD;

file_put_contents($baseDir . '/views/dashboard.php', $dashboardContent);
echo "✓ views/dashboard.php créé\n";

echo "\n[10/10] 🎨 Création des assets...\n";

// ========================================
// STYLE.CSS
// ========================================
$cssContent = <<<'CSS'
:root {
    --primary-color: #1a73e8;
    --primary-hover: #1557b0;
    --danger-color: #d93025;
    --success-color: #1e8e3e;
    --text-primary: #202124;
    --text-secondary: #5f6368;
    --text-muted: #80868b;
    --border-color: #dadce0;
    --bg-primary: #ffffff;
    --bg-secondary: #f8f9fa;
    --bg-hover: #f1f3f4;
    --unread-bg: #e8f0fe;
    --shadow-sm: 0 1px 2px 0 rgba(60,64,67,.3), 0 1px 3px 1px rgba(60,64,67,.15);
    --shadow-md: 0 1px 3px 0 rgba(60,64,67,.3), 0 4px 8px 3px rgba(60,64,67,.15);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
    font-size: 14px;
    line-height: 1.5;
    color: var(--text-primary);
    background: var(--bg-secondary);
}

/* ========================================
   LOGIN PAGE
   ======================================== */
.login-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-container {
    width: 100%;
    max-width: 450px;
    padding: 20px;
}

.login-card {
    background: white;
    border-radius: 8px;
    box-shadow: var(--shadow-md);
    padding: 48px 40px;
}

.login-header {
    text-align: center;
    margin-bottom: 32px;
}

.login-header .rss-icon {
    color: var(--primary-color);
    margin-bottom: 16px;
}

.login-header h1 {
    font-size: 24px;
    font-weight: 400;
    margin-bottom: 8px;
}

.login-header p {
    color: var(--text-secondary);
    font-size: 14px;
}

.login-form .form-group {
    margin-bottom: 20px;
}

.login-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text-primary);
}

.login-form input {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.login-form input:focus {
    outline: none;
    border-color: var(--primary-color);
}

.error-message {
    color: var(--danger-color);
    padding: 12px;
    background: #fce8e6;
    border-radius: 4px;
    margin-bottom: 16px;
    font-size: 13px;
}

.btn-primary {
    width: 100%;
    padding: 12px 24px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-primary:hover:not(:disabled) {
    background: var(--primary-hover);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.login-footer {
    margin-top: 24px;
    text-align: center;
    color: var(--text-secondary);
    font-size: 13px;
}

/* ========================================
   APP LAYOUT
   ======================================== */
.app-container {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: var(--bg-secondary);
}

.app-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
    padding: 0 16px;
    background: var(--bg-primary);
    border-bottom: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    z-index: 100;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 0 0 auto;
}

.menu-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
}

.menu-toggle:hover {
    background: var(--bg-hover);
}

.app-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 22px;
    font-weight: 400;
    color: var(--text-primary);
}

.app-title .rss-icon {
    color: var(--primary-color);
}

.header-center {
    flex: 1;
    max-width: 720px;
    margin: 0 16px;
}

.search-box {
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 10px 16px;
    background: var(--bg-secondary);
    border: 1px solid transparent;
    border-radius: 24px;
    font-size: 14px;
    transition: all 0.2s;
}

.search-box input:focus {
    outline: none;
    background: white;
    border-color: var(--border-color);
    box-shadow: var(--shadow-sm);
}

.header-right {
    display: flex;
    gap: 8px;
}

.btn-icon {
    padding: 8px 12px;
    background: none;
    border: none;
    border-radius: 4px;
    font-size: 20px;
    cursor: pointer;
    color: var(--text-secondary);
    transition: background 0.2s;
}

.btn-icon:hover {
    background: var(--bg-hover);
}

/* ========================================
   MAIN LAYOUT
   ======================================== */
.app-main {
    display: flex;
    flex: 1;
    overflow: hidden;
}

/* Sidebar */
.sidebar {
    width: 256px;
    background: var(--bg-primary);
    border-right: 1px solid var(--border-color);
    overflow-y: auto;
    padding: 12px 0;
}

.sidebar-section {
    margin-bottom: 24px;
}

.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 24px;
    margin-bottom: 8px;
}

.sidebar-header h3 {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-secondary);
    text-transform: uppercase;
}

.btn-icon-small {
    width: 28px;
    height: 28px;
    padding: 0;
    background: none;
    border: none;
    border-radius: 50%;
    font-size: 20px;
    cursor: pointer;
    color: var(--text-secondary);
    transition: background 0.2s;
}

.btn-icon-small:hover {
    background: var(--bg-hover);
}

.sidebar-btn {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 8px 24px;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    color: var(--text-primary);
    transition: background 0.2s;
}

.sidebar-btn:hover {
    background: var(--bg-hover);
}

.sidebar-btn.active {
    background: var(--unread-bg);
    color: var(--primary-color);
    font-weight: 500;
}

.sidebar-icon {
    margin-right: 12px;
    font-size: 18px;
}

.sidebar-label {
    flex: 1;
    font-size: 14px;
}

.sidebar-count {
    font-size: 12px;
    color: var(--text-secondary);
    font-weight: 500;
}

.feeds-list {
    display: flex;
    flex-direction: column;
}

.feed-item {
    display: flex;
    align-items: center;
    padding: 8px 24px;
    cursor: pointer;
    transition: background 0.2s;
}

.feed-item:hover {
    background: var(--bg-hover);
}

.feed-item.active {
    background: var(--unread-bg);
    color: var(--primary-color);
    font-weight: 500;
}

.feed-title {
    flex: 1;
    font-size: 14px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.feed-count {
    font-size: 12px;
    color: var(--text-secondary);
    margin-left: 8px;
}

/* Article List */
.article-list {
    flex: 1;
    background: var(--bg-primary);
    border-right: 1px solid var(--border-color);
    overflow-y: auto;
}

.article-item {
    padding: 16px 24px;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    transition: background 0.2s;
}

.article-item:hover {
    background: var(--bg-hover);
}

.article-item.unread {
    background: var(--unread-bg);
}

.article-item.active {
    background: var(--bg-hover);
    border-left: 3px solid var(--primary-color);
}

.article-header {
    display: flex;
    align-items: center;
    margin-bottom: 4px;
}

.article-feed {
    font-size: 12px;
    color: var(--text-muted);
    margin-right: 8px;
}

.article-date {
    font-size: 12px;
    color: var(--text-muted);
}

.article-title {
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 4px;
    color: var(--text-primary);
}

.article-excerpt {
    font-size: 13px;
    color: var(--text-secondary);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.loading {
    text-align: center;
    padding: 48px;
    color: var(--text-secondary);
}

/* Article Reader */
.article-reader {
    width: 600px;
    background: var(--bg-primary);
    overflow-y: auto;
    padding: 32px;
}

.reader-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-muted);
}

.reader-placeholder svg {
    margin-bottom: 16px;
}

.reader-header {
    margin-bottom: 24px;
}

.reader-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    font-size: 13px;
    color: var(--text-secondary);
}

.reader-title {
    font-size: 28px;
    font-weight: 400;
    line-height: 1.3;
    margin-bottom: 16px;
    color: var(--text-primary);
}

.reader-actions {
    display: flex;
    gap: 8px;
}

.btn-secondary {
    padding: 8px 16px;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-secondary:hover {
    background: var(--bg-hover);
}

.reader-content {
    font-size: 16px;
    line-height: 1.6;
    color: var(--text-primary);
}

.reader-content img {
    max-width: 100%;
    height: auto;
    margin: 16px 0;
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 8px;
    box-shadow: var(--shadow-md);
    width: 90%;
    max-width: 500px;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h2 {
    font-size: 20px;
    font-weight: 400;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: var(--text-secondary);
    padding: 0;
    width: 32px;
    height: 32px;
    border-radius: 4px;
}

.modal-close:hover {
    background: var(--bg-hover);
}

.modal-content form {
    padding: 24px;
}

.modal-content .form-group {
    margin-bottom: 20px;
}

.modal-content label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.modal-content input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 14px;
}

.modal-content input:focus {
    outline: none;
    border-color: var(--primary-color);
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

/* Responsive */
@media (max-width: 1024px) {
    .article-reader {
        width: 480px;
    }
}

@media (max-width: 768px) {
    .menu-toggle {
        display: block;
    }

    .sidebar {
        position: fixed;
        left: -256px;
        top: 64px;
        bottom: 0;
        z-index: 99;
        transition: left 0.3s;
    }

    .sidebar.open {
        left: 0;
    }

    .article-reader {
        display: none;
    }

    .article-reader.open {
        display: block;
        position: fixed;
        top: 64px;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        z-index: 98;
    }
}
CSS;

file_put_contents($baseDir . '/assets/style.css', $cssContent);
echo "✓ assets/style.css créé\n";

// ========================================
// APP.JS
// ========================================
$jsContent = <<<'JS'
// Global State
let state = {
    feeds: [],
    articles: [],
    currentFilter: 'all',
    currentFeedId: null,
    currentArticleId: null,
    searchQuery: ''
};

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadFeeds();
    loadArticles();
    attachEventListeners();
});

// Event Listeners
function attachEventListeners() {
    // Sidebar filters
    document.querySelectorAll('.sidebar-btn[data-filter]').forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.dataset.filter;
            setActiveFilter(filter);
            filterArticles();
        });
    });

    // Search
    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchQuery = e.target.value.toLowerCase();
            filterArticles();
        }, 300);
    });

    // Refresh
    document.getElementById('refreshBtn').addEventListener('click', () => {
        loadFeeds();
        loadArticles();
    });

    // Logout
    document.getElementById('logoutBtn').addEventListener('click', async () => {
        try {
            await fetch('/api/auth/logout', { method: 'POST' });
            window.location.href = '/login';
        } catch (err) {
            console.error('Logout error:', err);
        }
    });

    // Add Feed
    document.getElementById('addFeedBtn').addEventListener('click', () => {
        document.getElementById('addFeedModal').style.display = 'flex';
        document.getElementById('feedUrl').focus();
    });

    document.getElementById('closeModal').addEventListener('click', closeAddFeedModal);
    document.getElementById('cancelAddFeed').addEventListener('click', closeAddFeedModal);

    document.getElementById('addFeedForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const url = document.getElementById('feedUrl').value;
        await addFeed(url);
    });

    // Menu toggle (mobile)
    document.getElementById('menuToggle').addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('open');
    });
}

// API Calls
async function loadFeeds() {
    try {
        const response = await fetch('/api/feeds');
        const data = await response.json();

        if (data.success) {
            state.feeds = data.data;
            renderFeeds();
        }
    } catch (err) {
        console.error('Load feeds error:', err);
    }
}

async function loadArticles() {
    try {
        const response = await fetch('/api/articles');
        const data = await response.json();

        if (data.success) {
            state.articles = data.data;
            renderArticles();
            updateCounts();
        }
    } catch (err) {
        console.error('Load articles error:', err);
    }
}

async function addFeed(url) {
    try {
        const response = await fetch('/api/feeds', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ url })
        });

        const data = await response.json();

        if (data.success) {
            closeAddFeedModal();
            loadFeeds();
            loadArticles();
        } else {
            alert(data.message || 'Erreur lors de l\'ajout du flux');
        }
    } catch (err) {
        console.error('Add feed error:', err);
        alert('Erreur de connexion au serveur');
    }
}

async function markAsRead(articleId) {
    try {
        const response = await fetch(`/api/articles/${articleId}/read`, {
            method: 'PUT'
        });

        const data = await response.json();

        if (data.success) {
            const article = state.articles.find(a => a.id === articleId);
            if (article) {
                article.is_read = 1;
            }
            updateCounts();
            renderArticles();
        }
    } catch (err) {
        console.error('Mark as read error:', err);
    }
}

// Render Functions
function renderFeeds() {
    const feedsList = document.getElementById('feedsList');

    if (state.feeds.length === 0) {
        feedsList.innerHTML = '<div style="padding: 16px; text-align: center; color: var(--text-muted);">Aucun flux</div>';
        return;
    }

    feedsList.innerHTML = state.feeds.map(feed => {
        const unreadCount = state.articles.filter(a => a.feed_id === feed.id && !a.is_read).length;
        return `
            <div class="feed-item ${state.currentFeedId === feed.id ? 'active' : ''}" data-feed-id="${feed.id}">
                <span class="feed-title" title="${escapeHtml(feed.title || feed.url)}">${escapeHtml(feed.title || feed.url)}</span>
                ${unreadCount > 0 ? `<span class="feed-count">${unreadCount}</span>` : ''}
            </div>
        `;
    }).join('');

    // Attach click handlers
    feedsList.querySelectorAll('.feed-item').forEach(item => {
        item.addEventListener('click', () => {
            state.currentFeedId = parseInt(item.dataset.feedId);
            filterArticles();
            renderFeeds();
        });
    });
}

function renderArticles() {
    const articleList = document.getElementById('articleList');

    let filtered = [...state.articles];

    // Filter by feed
    if (state.currentFeedId) {
        filtered = filtered.filter(a => a.feed_id === state.currentFeedId);
    }

    // Filter by read status
    if (state.currentFilter === 'unread') {
        filtered = filtered.filter(a => !a.is_read);
    }

    // Filter by search
    if (state.searchQuery) {
        filtered = filtered.filter(a =>
            a.title.toLowerCase().includes(state.searchQuery) ||
            (a.description && a.description.toLowerCase().includes(state.searchQuery))
        );
    }

    // Sort by date (newest first)
    filtered.sort((a, b) => new Date(b.published_date) - new Date(a.published_date));

    if (filtered.length === 0) {
        articleList.innerHTML = '<div class="loading">Aucun article trouvé</div>';
        return;
    }

    articleList.innerHTML = filtered.map(article => {
        const feed = state.feeds.find(f => f.id === article.feed_id);
        const date = new Date(article.published_date);
        const dateStr = formatDate(date);

        return `
            <div class="article-item ${!article.is_read ? 'unread' : ''} ${state.currentArticleId === article.id ? 'active' : ''}"
                 data-article-id="${article.id}">
                <div class="article-header">
                    <span class="article-feed">${escapeHtml(feed?.title || 'Flux inconnu')}</span>
                    <span class="article-date">${dateStr}</span>
                </div>
                <div class="article-title">${escapeHtml(article.title)}</div>
                <div class="article-excerpt">${escapeHtml(stripHtml(article.description || ''))}</div>
            </div>
        `;
    }).join('');

    // Attach click handlers
    articleList.querySelectorAll('.article-item').forEach(item => {
        item.addEventListener('click', () => {
            const articleId = parseInt(item.dataset.articleId);
            openArticle(articleId);
        });
    });
}

function openArticle(articleId) {
    state.currentArticleId = articleId;
    const article = state.articles.find(a => a.id === articleId);

    if (!article) return;

    const feed = state.feeds.find(f => f.id === article.feed_id);
    const date = new Date(article.published_date);
    const dateStr = formatDate(date);

    const reader = document.getElementById('articleReader');
    reader.innerHTML = `
        <div class="reader-header">
            <div class="reader-meta">
                <span>${escapeHtml(feed?.title || 'Flux inconnu')}</span>
                <span>•</span>
                <span>${dateStr}</span>
            </div>
            <h1 class="reader-title">${escapeHtml(article.title)}</h1>
            <div class="reader-actions">
                <a href="${escapeHtml(article.link)}" target="_blank" class="btn-secondary">Ouvrir l'article</a>
            </div>
        </div>
        <div class="reader-content">
            ${article.content || article.description || '<p>Aucun contenu disponible</p>'}
        </div>
    `;

    // Mark as read
    if (!article.is_read) {
        markAsRead(articleId);
    }

    // Update UI
    renderArticles();

    // Mobile: show reader
    reader.classList.add('open');
}

function updateCounts() {
    const totalCount = state.articles.length;
    const unreadCount = state.articles.filter(a => !a.is_read).length;

    document.getElementById('countAll').textContent = totalCount;
    document.getElementById('countUnread').textContent = unreadCount;
}

function filterArticles() {
    renderArticles();
}

function setActiveFilter(filter) {
    state.currentFilter = filter;
    state.currentFeedId = null;

    document.querySelectorAll('.sidebar-btn[data-filter]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === filter);
    });

    document.querySelectorAll('.feed-item').forEach(item => {
        item.classList.remove('active');
    });
}

function closeAddFeedModal() {
    document.getElementById('addFeedModal').style.display = 'none';
    document.getElementById('feedUrl').value = '';
}

// Utility Functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent || div.innerText || '';
}

function formatDate(date) {
    const now = new Date();
    const diff = now - date;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));

    if (days === 0) {
        const hours = Math.floor(diff / (1000 * 60 * 60));
        if (hours === 0) {
            const minutes = Math.floor(diff / (1000 * 60));
            return `Il y a ${minutes} min`;
        }
        return `Il y a ${hours}h`;
    } else if (days === 1) {
        return 'Hier';
    } else if (days < 7) {
        return `Il y a ${days}j`;
    } else {
        return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
    }
}
JS;

file_put_contents($baseDir . '/assets/app.js', $jsContent);
echo "✓ assets/app.js créé\n";

echo "\n========================================\n";
echo "✅ Installation V2 TERMINÉE !\n";
echo "========================================\n\n";

echo "📁 Structure complète:\n";
echo "   ✓ config.php\n";
echo "   ✓ Database.php\n";
echo "   ✓ Session.php\n";
echo "   ✓ index.php\n";
echo "   ✓ api.php\n";
echo "   ✓ views/login.php\n";
echo "   ✓ views/dashboard.php\n";
echo "   ✓ assets/style.css\n";
echo "   ✓ assets/app.js\n";
echo "   ✓ .htaccess\n";
echo "   ✓ rss_feeds.db (with admin user)\n\n";

echo "🌐 Accès:\n";
echo "   URL: http://dusselle.fr/\n";
echo "   Login: admin\n";
echo "   Password: admin123\n\n";

echo "🔍 Test:\n";
echo "   curl http://dusselle.fr/?health\n\n";

echo "✅ L'application est maintenant COMPLÈTE et FONCTIONNELLE !\n\n";
?>

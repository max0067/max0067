<?php
require_once 'config.php';

// Require authentication
$currentUser = requireLogin();

// Get query parameters
$feedId = getQuery('feed_id');
$filter = getQuery('filter', 'all'); // all, unread, favorites
$search = getQuery('search', '');

// Get articles based on filters
$unreadOnly = ($filter === 'unread');
$articles = getArticles($currentUser['id'], $feedId, 10000, 0, $unreadOnly, $search);

// If favorites filter, filter articles client-side or via query
if ($filter === 'favorites') {
    $articles = array_filter($articles, function($article) {
        return $article['favorite'] == 1;
    });
}

// Get user feeds for sidebar
$feeds = getUserFeeds($currentUser['id']);

// Get stats
$stats = getUserStats($currentUser['id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSS Feed Reader - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="/static/css/modern_pro.css">
    <style>
        /* Article Grid */
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .article-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--gray-200);
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .article-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .article-card.read {
            opacity: 0.6;
        }

        .article-source {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .article-source-icon {
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 4px;
        }

        .article-source-name {
            font-size: 0.75rem;
            color: var(--gray-500);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .article-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 12px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .article-excerpt {
            font-size: 0.875rem;
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .article-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .article-actions {
            display: flex;
            gap: 12px;
        }

        .article-action-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray-400);
            transition: color 0.2s;
            padding: 4px;
        }

        .article-action-btn:hover {
            color: var(--primary);
        }

        .article-action-btn.active {
            color: var(--accent);
        }

        /* Filters */
        .filters {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid var(--gray-300);
            background: white;
            color: var(--gray-700);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-color: transparent;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 16px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 0.875rem;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 40px;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--gray-100);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: var(--gray-200);
        }

        .modal-header {
            margin-bottom: 24px;
        }

        .modal-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 16px;
            line-height: 1.3;
        }

        .modal-meta {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .modal-body {
            font-size: 1rem;
            line-height: 1.8;
            color: var(--gray-800);
        }

        .modal-body p {
            margin-bottom: 16px;
        }

        .modal-body img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            margin: 16px 0;
        }

        .modal-footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .modal-nav {
            display: flex;
            gap: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state svg {
            width: 120px;
            height: 120px;
            margin-bottom: 24px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--gray-700);
            margin-bottom: 12px;
        }

        .empty-state p {
            color: var(--gray-500);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <svg width="40" height="40" viewBox="0 0 50 50">
                    <circle cx="25" cy="25" r="22" fill="url(#logoGradient)" />
                    <circle cx="12" cy="38" r="4" fill="white" />
                    <path d="M 12 30 Q 12 16, 26 16" stroke="white" stroke-width="4" fill="none" stroke-linecap="round"/>
                    <path d="M 12 24 Q 12 14, 22 14" stroke="white" stroke-width="4" fill="none" stroke-linecap="round"/>
                    <defs>
                        <linearGradient id="logoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#4F46E5"/>
                            <stop offset="100%" style="stop-color:#7C3AED"/>
                        </linearGradient>
                    </defs>
                </svg>
                <div class="logo-text">
                    <span class="logo-title">RSS Manager</span>
                    <span class="logo-subtitle">Professional Edition</span>
                </div>
            </div>
            <button class="sidebar-toggle" id="sidebar-toggle">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <span class="nav-section-title">NAVIGATION</span>
                <a href="/index.php" class="nav-item active">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span class="nav-text">All Articles</span>
                    <?php if ($stats['unread_articles'] > 0): ?>
                        <span class="nav-badge"><?= $stats['unread_articles'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="/feeds.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 11a9 9 0 0 1 9 9"></path>
                        <path d="M4 4a16 16 0 0 1 16 16"></path>
                        <circle cx="5" cy="19" r="1"></circle>
                    </svg>
                    <span class="nav-text">Manage Feeds</span>
                </a>
            </div>

            <?php if ($currentUser['role'] === 'admin'): ?>
            <div class="nav-section">
                <span class="nav-section-title">ADMIN</span>
                <a href="/admin.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7v10c0 5.5 4.5 10 10 10s10-4.5 10-10V7l-10-5z"></path>
                    </svg>
                    <span class="nav-text">Administration</span>
                </a>
            </div>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= e($currentUser['username']) ?></div>
                    <div class="user-role"><?= $currentUser['role'] === 'admin' ? 'Administrator' : 'Member' ?></div>
                </div>
            </div>
            <div style="padding: 12px;">
                <a href="/api/auth.php?action=logout" class="btn btn-secondary" style="width: 100%; text-align: center; display: block; text-decoration: none;">
                    Logout
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1>Your Feed</h1>
                <p class="topbar-subtitle">
                    <?= $stats['unread_articles'] ?> unread articles • <?= $stats['total_articles'] ?> total
                </p>
            </div>
            <div class="topbar-actions">
                <button class="btn btn-primary" onclick="updateFeeds()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                    Refresh Feeds
                </button>
            </div>
        </div>

        <div class="content-wrapper">
            <!-- Filters -->
            <div class="filters">
                <button class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>" onclick="setFilter('all')">All</button>
                <button class="filter-btn <?= $filter === 'unread' ? 'active' : '' ?>" onclick="setFilter('unread')">
                    Unread <?= $stats['unread_articles'] > 0 ? '(' . $stats['unread_articles'] . ')' : '' ?>
                </button>
                <button class="filter-btn <?= $filter === 'favorites' ? 'active' : '' ?>" onclick="setFilter('favorites')">
                    Favorites <?= $stats['favorites'] > 0 ? '(' . $stats['favorites'] . ')' : '' ?>
                </button>
                <div class="search-box">
                    <input type="text" placeholder="Search articles..." value="<?= e($search) ?>" onchange="searchArticles(this.value)">
                </div>
            </div>

            <!-- Articles Grid -->
            <?php if (empty($articles)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <h3>No articles found</h3>
                    <p>Try adding some RSS feeds or adjusting your filters</p>
                </div>
            <?php else: ?>
                <div class="articles-grid">
                    <?php foreach ($articles as $article): ?>
                        <div class="article-card <?= $article['read'] ? 'read' : '' ?>"
                             onclick="openArticle(<?= $article['id'] ?>)"
                             data-article-id="<?= $article['id'] ?>">
                            <div class="article-source">
                                <div class="article-source-icon"></div>
                                <div class="article-source-name"><?= e($article['feed_title']) ?></div>
                            </div>
                            <h3 class="article-title"><?= e($article['title']) ?></h3>
                            <p class="article-excerpt"><?= e(strip_tags($article['description'])) ?></p>
                            <div class="article-meta">
                                <span><?= timeAgo($article['published_date']) ?></span>
                                <div class="article-actions">
                                    <button class="article-action-btn <?= $article['favorite'] ? 'active' : '' ?>"
                                            onclick="toggleFavorite(<?= $article['id'] ?>, event)">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $article['favorite'] ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Article Modal -->
    <div class="modal" id="article-modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div id="modal-article-content"></div>
        </div>
    </div>

    <script>
        const articles = <?= json_encode($articles, JSON_UNESCAPED_UNICODE) ?>;
        let currentArticleIndex = -1;

        // Sidebar toggle
        document.getElementById('sidebar-toggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('main-content').classList.toggle('expanded');
        });

        // Filter functions
        function setFilter(filter) {
            window.location.href = '/index.php?filter=' + filter;
        }

        function searchArticles(query) {
            window.location.href = '/index.php?search=' + encodeURIComponent(query);
        }

        // Article modal
        function openArticle(articleId) {
            const article = articles.find(a => a.id == articleId);
            if (!article) return;

            currentArticleIndex = articles.findIndex(a => a.id == articleId);

            const modal = document.getElementById('article-modal');
            const content = document.getElementById('modal-article-content');

            content.innerHTML = `
                <div class="modal-header">
                    <h2 class="modal-title">${escapeHtml(article.title)}</h2>
                    <div class="modal-meta">
                        <span>${escapeHtml(article.feed_title)}</span>
                        <span>•</span>
                        <span>${article.author || 'Unknown author'}</span>
                        <span>•</span>
                        <span>${formatDate(article.published_date)}</span>
                    </div>
                </div>
                <div class="modal-body">
                    ${article.content || article.description}
                </div>
                <div class="modal-footer">
                    <div class="modal-nav">
                        <button class="btn btn-secondary" onclick="previousArticle()" ${currentArticleIndex === 0 ? 'disabled' : ''}>Previous</button>
                        <button class="btn btn-secondary" onclick="nextArticle()" ${currentArticleIndex === articles.length - 1 ? 'disabled' : ''}>Next</button>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button class="btn btn-secondary" onclick="toggleFavorite(${article.id})">
                            ${article.favorite ? 'Remove from Favorites' : 'Add to Favorites'}
                        </button>
                        <a href="${article.link}" target="_blank" class="btn btn-primary">Open Original</a>
                    </div>
                </div>
            `;

            modal.classList.add('active');

            // Mark as read
            if (!article.read) {
                markAsRead(articleId);
            }
        }

        function closeModal() {
            document.getElementById('article-modal').classList.remove('active');
        }

        function previousArticle() {
            if (currentArticleIndex > 0) {
                openArticle(articles[currentArticleIndex - 1].id);
            }
        }

        function nextArticle() {
            if (currentArticleIndex < articles.length - 1) {
                openArticle(articles[currentArticleIndex + 1].id);
            }
        }

        // Article actions
        async function markAsRead(articleId) {
            try {
                const response = await fetch('/api/articles.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'mark_read', article_id: articleId})
                });
                const data = await response.json();
                if (data.success) {
                    const card = document.querySelector(`[data-article-id="${articleId}"]`);
                    if (card) card.classList.add('read');
                    const article = articles.find(a => a.id == articleId);
                    if (article) article.read = 1;
                }
            } catch (e) {
                console.error('Error marking as read:', e);
            }
        }

        async function toggleFavorite(articleId, event) {
            if (event) {
                event.stopPropagation();
            }

            try {
                const response = await fetch('/api/articles.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'toggle_favorite', article_id: articleId})
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                }
            } catch (e) {
                console.error('Error toggling favorite:', e);
            }
        }

        async function updateFeeds() {
            const btn = event.target.closest('button');
            btn.disabled = true;
            btn.textContent = 'Updating...';

            try {
                const response = await fetch('/api/feeds.php?action=update_all', {method: 'POST'});
                const data = await response.json();
                if (data.success) {
                    alert(`Updated successfully! ${data.new_articles} new articles.`);
                    location.reload();
                } else {
                    alert('Error updating feeds');
                }
            } catch (e) {
                alert('Error updating feeds');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg> Refresh Feeds';
            }
        }

        // Helpers
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            if (!dateString) return 'Unknown date';
            const date = new Date(dateString);
            return date.toLocaleString('fr-FR');
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            const modal = document.getElementById('article-modal');
            if (modal.classList.contains('active')) {
                if (e.key === 'Escape') closeModal();
                if (e.key === 'ArrowLeft') previousArticle();
                if (e.key === 'ArrowRight') nextArticle();
            }
        });

        // Close modal on background click
        document.getElementById('article-modal').addEventListener('click', (e) => {
            if (e.target.id === 'article-modal') closeModal();
        });
    </script>
</body>
</html>

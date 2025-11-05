<?php
require_once 'config.php';

// Require authentication
$currentUser = requireLogin();

// Get user feeds
$feeds = getUserFeeds($currentUser['id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feeds - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="/static/css/modern_pro.css">
    <style>
        .feeds-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .feeds-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .feeds-table th {
            background: var(--gray-100);
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .feeds-table td {
            padding: 16px;
            border-top: 1px solid var(--gray-200);
        }

        .feeds-table tr:hover {
            background: var(--gray-50);
        }

        .feed-title {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .feed-url {
            font-size: 0.875rem;
            color: var(--gray-500);
            word-break: break-all;
        }

        .feed-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .feed-status.active {
            background: #d1fae5;
            color: #065f46;
        }

        .feed-status.inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .feed-actions {
            display: flex;
            gap: 8px;
        }

        .icon-btn {
            background: var(--gray-100);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .icon-btn:hover {
            background: var(--gray-200);
        }

        .icon-btn.danger:hover {
            background: #fee2e2;
            color: #991b1b;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-dialog {
            background: white;
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            padding: 32px;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 0.875rem;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <!-- Sidebar (same as index.php) -->
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
                <a href="/index.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span class="nav-text">All Articles</span>
                </a>
                <a href="/feeds.php" class="nav-item active">
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
                <h1>Manage Feeds</h1>
                <p class="topbar-subtitle"><?= count($feeds) ?> RSS feeds</p>
            </div>
            <div class="topbar-actions">
                <button class="btn btn-primary" onclick="openAddFeedModal()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Add Feed
                </button>
            </div>
        </div>

        <div class="content-wrapper">
            <?php if (empty($feeds)): ?>
                <div class="card">
                    <div style="text-align: center; padding: 40px;">
                        <h3 style="color: var(--gray-700); margin-bottom: 12px;">No RSS feeds yet</h3>
                        <p style="color: var(--gray-500); margin-bottom: 24px;">Start by adding your first RSS feed</p>
                        <button class="btn btn-primary" onclick="openAddFeedModal()">Add Your First Feed</button>
                    </div>
                </div>
            <?php else: ?>
                <div class="feeds-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Feed</th>
                                <th>Articles</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feeds as $feed): ?>
                                <tr>
                                    <td>
                                        <div class="feed-title"><?= e($feed['title']) ?></div>
                                        <div class="feed-url"><?= e($feed['url']) ?></div>
                                    </td>
                                    <td><?= $feed['article_count'] ?></td>
                                    <td>
                                        <span class="feed-status <?= $feed['active'] ? 'active' : 'inactive' ?>">
                                            <?= $feed['active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td><?= $feed['last_updated'] ? timeAgo($feed['last_updated']) : 'Never' ?></td>
                                    <td>
                                        <div class="feed-actions">
                                            <button class="icon-btn" onclick="updateFeed(<?= $feed['id'] ?>)" title="Update now">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="23 4 23 10 17 10"></polyline>
                                                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                                                </svg>
                                            </button>
                                            <button class="icon-btn" onclick="editFeed(<?= $feed['id'] ?>)" title="Edit">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </button>
                                            <button class="icon-btn danger" onclick="deleteFeed(<?= $feed['id'] ?>)" title="Delete">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add/Edit Feed Modal -->
    <div class="modal" id="feed-modal">
        <div class="modal-dialog">
            <h2 class="modal-title" id="modal-title">Add RSS Feed</h2>
            <form id="feed-form" onsubmit="saveFeed(event)">
                <input type="hidden" id="feed-id" name="feed_id">

                <div class="form-group">
                    <label for="feed-url">RSS Feed URL *</label>
                    <input type="url" id="feed-url" name="url" required placeholder="https://example.com/feed.xml">
                </div>

                <div class="form-group">
                    <label for="feed-title">Title *</label>
                    <input type="text" id="feed-title" name="title" required placeholder="My Blog Feed">
                </div>

                <div class="form-group">
                    <label for="feed-description">Description</label>
                    <textarea id="feed-description" name="description" placeholder="Optional description"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeFeedModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Feed</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const feeds = <?= json_encode($feeds, JSON_UNESCAPED_UNICODE) ?>;

        // Sidebar toggle
        document.getElementById('sidebar-toggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('main-content').classList.toggle('expanded');
        });

        // Modal functions
        function openAddFeedModal() {
            document.getElementById('modal-title').textContent = 'Add RSS Feed';
            document.getElementById('feed-form').reset();
            document.getElementById('feed-id').value = '';
            document.getElementById('feed-modal').classList.add('active');
        }

        function editFeed(feedId) {
            const feed = feeds.find(f => f.id == feedId);
            if (!feed) return;

            document.getElementById('modal-title').textContent = 'Edit RSS Feed';
            document.getElementById('feed-id').value = feed.id;
            document.getElementById('feed-url').value = feed.url;
            document.getElementById('feed-title').value = feed.title;
            document.getElementById('feed-description').value = feed.description || '';
            document.getElementById('feed-modal').classList.add('active');
        }

        function closeFeedModal() {
            document.getElementById('feed-modal').classList.remove('active');
        }

        async function saveFeed(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            const data = {
                action: document.getElementById('feed-id').value ? 'update' : 'add',
                feed_id: document.getElementById('feed-id').value || undefined,
                url: formData.get('url'),
                title: formData.get('title'),
                description: formData.get('description')
            };

            try {
                const response = await fetch('/api/feeds.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    alert(data.action === 'add' ? 'Feed added successfully!' : 'Feed updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (result.error || 'Unknown error'));
                }
            } catch (e) {
                alert('Error saving feed');
                console.error(e);
            }
        }

        async function updateFeed(feedId) {
            if (!confirm('Update this feed now?')) return;

            try {
                const response = await fetch('/api/feeds.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'update_single', feed_id: feedId})
                });
                const result = await response.json();

                if (result.success) {
                    alert(`Updated! ${result.new_articles} new articles.`);
                    location.reload();
                } else {
                    alert('Error updating feed');
                }
            } catch (e) {
                alert('Error updating feed');
                console.error(e);
            }
        }

        async function deleteFeed(feedId) {
            if (!confirm('Are you sure you want to delete this feed? All articles will be deleted.')) return;

            try {
                const response = await fetch('/api/feeds.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'delete', feed_id: feedId})
                });
                const result = await response.json();

                if (result.success) {
                    alert('Feed deleted successfully!');
                    location.reload();
                } else {
                    alert('Error deleting feed');
                }
            } catch (e) {
                alert('Error deleting feed');
                console.error(e);
            }
        }

        // Close modal on background click
        document.getElementById('feed-modal').addEventListener('click', (e) => {
            if (e.target.id === 'feed-modal') closeFeedModal();
        });
    </script>
</body>
</html>

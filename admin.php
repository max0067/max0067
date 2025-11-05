<?php
require_once 'config.php';

// Require admin authentication
$currentUser = requireAdmin();

// Get admin stats
$stats = getAdminStats();
$users = getAllUsers();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="/static/css/modern_pro.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            border: 1px solid var(--gray-200);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .users-table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .users-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            background: var(--gray-100);
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
        }

        .users-table td {
            padding: 16px;
            border-top: 1px solid var(--gray-200);
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-admin {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-user {
            background: #e5e7eb;
            color: #374151;
        }

        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
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
                <a href="/index.php" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span class="nav-text">All Articles</span>
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

            <div class="nav-section">
                <span class="nav-section-title">ADMIN</span>
                <a href="/admin.php" class="nav-item active">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7v10c0 5.5 4.5 10 10 10s10-4.5 10-10V7l-10-5z"></path>
                    </svg>
                    <span class="nav-text">Administration</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= e($currentUser['username']) ?></div>
                    <div class="user-role">Administrator</div>
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
                <h1>Administration</h1>
                <p class="topbar-subtitle">System overview and user management</p>
            </div>
        </div>

        <div class="content-wrapper">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total_users'] ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['active_users'] ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total_feeds'] ?></div>
                    <div class="stat-label">Total Feeds</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total_articles'] ?></div>
                    <div class="stat-label">Total Articles</div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Users</h2>
                </div>

                <div class="users-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Last Login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= e($user['username']) ?></td>
                                    <td><?= e($user['email']) ?></td>
                                    <td>
                                        <span class="badge <?= $user['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $user['active'] ? 'badge-active' : 'badge-inactive' ?>">
                                            <?= $user['active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td><?= formatDate($user['created_at']) ?></td>
                                    <td><?= $user['last_login'] ? timeAgo($user['last_login']) : 'Never' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- System Info -->
            <div class="card" style="margin-top: 24px;">
                <div class="card-header">
                    <h2 class="card-title">System Information</h2>
                </div>
                <table style="width: 100%;">
                    <tr>
                        <td style="padding: 12px; border-bottom: 1px solid var(--gray-200); font-weight: 600;">Application</td>
                        <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);"><?= e(APP_NAME) ?> <?= e(APP_VERSION) ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border-bottom: 1px solid var(--gray-200); font-weight: 600;">PHP Version</td>
                        <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);"><?= phpversion() ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; border-bottom: 1px solid var(--gray-200); font-weight: 600;">Database</td>
                        <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);">SQLite (<?= e(DB_PATH) ?>)</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; font-weight: 600;">Server Time</td>
                        <td style="padding: 12px;"><?= date('Y-m-d H:i:s') ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </main>

    <script>
        // Sidebar toggle
        document.getElementById('sidebar-toggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.getElementById('main-content').classList.toggle('expanded');
        });
    </script>
</body>
</html>

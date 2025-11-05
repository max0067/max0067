<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSS Reader - Dashboard</title>
    <link rel="stylesheet" href="/v2/assets/style.css">
</head>
<body class="dashboard-page">
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <svg class="logo-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.18 15.64a2.18 2.18 0 0 1 2.18 2.18C8.36 19 7.38 20 6.18 20C5 20 4 19 4 17.82a2.18 2.18 0 0 1 2.18-2.18M4 4.44A15.56 15.56 0 0 1 19.56 20h-2.83A12.73 12.73 0 0 0 4 7.27V4.44m0 5.66a9.9 9.9 0 0 1 9.9 9.9h-2.83A7.07 7.07 0 0 0 4 12.93V10.1z" fill="currentColor"/>
                </svg>
                <h1>RSS Reader</h1>
            </div>

            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" data-view="all">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 4h16v2H4V4zm0 5h16v2H4V9zm0 5h16v2H4v-2zm0 5h16v2H4v-2z" fill="currentColor"/>
                    </svg>
                    <span>Tous les articles</span>
                    <span class="badge" id="total-count">0</span>
                </a>

                <a href="#" class="nav-item" data-view="unread">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" fill="currentColor"/>
                    </svg>
                    <span>Non lus</span>
                    <span class="badge badge-primary" id="unread-count">0</span>
                </a>

                <div class="nav-divider"></div>

                <div class="nav-section-header">Flux RSS</div>
                <div id="feeds-list" class="feeds-list">
                    <!-- Les flux seront chargés ici -->
                </div>

                <button class="btn-add-feed" id="add-feed-btn">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" fill="currentColor"/>
                    </svg>
                    Ajouter un flux
                </button>
            </nav>

            <div class="sidebar-footer">
                <div class="user-menu">
                    <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($user['username']) ?></div>
                        <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <button class="btn-icon" id="logout-btn" title="Déconnexion">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Toolbar -->
            <div class="toolbar">
                <div class="toolbar-left">
                    <button class="btn-icon" id="refresh-btn" title="Actualiser">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.65 6.35A7.958 7.958 0 0 0 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0 1 12 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z" fill="currentColor"/>
                        </svg>
                    </button>
                    <button class="btn-icon" id="mark-all-read-btn" title="Tout marquer comme lu">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>
                <div class="toolbar-right">
                    <input type="search" id="search-input" placeholder="Rechercher des articles..." class="search-input">
                </div>
            </div>

            <!-- Articles List -->
            <div class="articles-container">
                <div id="articles-list" class="articles-list">
                    <!-- Les articles seront chargés ici -->
                </div>
                <div id="loading-indicator" class="loading-indicator" style="display: none;">
                    <span class="loader"></span>
                </div>
                <div id="empty-state" class="empty-state" style="display: none;">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z" fill="currentColor"/>
                    </svg>
                    <p>Aucun article à afficher</p>
                    <p class="empty-subtitle">Ajoutez un flux RSS pour commencer</p>
                </div>
            </div>
        </main>

        <!-- Article Reader Panel -->
        <aside class="reader-panel" id="reader-panel" style="display: none;">
            <div class="reader-header">
                <button class="btn-icon" id="close-reader-btn">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
            <div class="reader-content" id="reader-content">
                <!-- Le contenu de l'article sera chargé ici -->
            </div>
        </aside>
    </div>

    <!-- Modal: Add Feed -->
    <div class="modal" id="add-feed-modal" style="display: none;">
        <div class="modal-overlay" id="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Ajouter un flux RSS</h2>
                <button class="btn-icon" id="close-modal-btn">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
            <form id="add-feed-form" class="modal-form">
                <div class="form-group">
                    <label for="feed-url">URL du flux RSS *</label>
                    <input type="url" id="feed-url" name="url" required placeholder="https://example.com/feed.xml">
                </div>
                <div class="form-group">
                    <label for="feed-title">Titre (optionnel)</label>
                    <input type="text" id="feed-title" name="title" placeholder="Laissez vide pour auto-détection">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" id="cancel-feed-btn">Annuler</button>
                    <button type="submit" class="btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/v2/assets/app.js"></script>
</body>
</html>

<?php
/**
 * Page d'accueil - Liste des articles RSS
 */

session_start();

require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Article.php';
require_once 'includes/RSSFeed.php';
require_once 'includes/helpers.php';

$article = new Article();
$feed = new RSSFeed();

// Récupération des paramètres
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

// Pagination
$limit = ARTICLES_PER_PAGE;
$offset = ($page - 1) * $limit;

// Récupération des articles
$articles = $article->getAll($limit, $offset, $search);
$totalArticles = $article->count($search);
$totalPages = ceil($totalArticles / $limit);

// Récupération des statistiques
$stats = $article->getStats();

// Titre de la page
$pageTitle = $search ? "Recherche : " . e($search) : "Tableau de bord";

require_once 'views/layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Statistiques</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <div class="px-3 py-2">
                            <div class="stat-box">
                                <div class="stat-value"><?= number_format($stats['total_articles']) ?></div>
                                <div class="stat-label">Articles total</div>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="px-3 py-2">
                            <div class="stat-box">
                                <div class="stat-value"><?= number_format($stats['last_24h']) ?></div>
                                <div class="stat-label">Dernières 24h</div>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="px-3 py-2">
                            <div class="stat-box">
                                <div class="stat-value"><?= $stats['total_feeds'] ?></div>
                                <div class="stat-label">Flux RSS actifs</div>
                            </div>
                        </div>
                    </li>
                </ul>

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Actions rapides</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link" href="feeds.php">
                            📋 Gérer les flux
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="refresh.php" onclick="return confirm('Actualiser tous les flux RSS ?');">
                            🔄 Actualiser les flux
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= e($pageTitle) ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="refresh.php" class="btn btn-sm btn-outline-primary" onclick="return confirm('Actualiser tous les flux RSS ?');">
                        🔄 Actualiser les flux
                    </a>
                </div>
            </div>

            <?php
            $flash = getFlash();
            if ($flash):
            ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Barre de recherche -->
            <div class="row mb-4">
                <div class="col-md-6 mx-auto">
                    <form method="GET" action="index.php">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Rechercher des articles..." value="<?= e($search ?? '') ?>">
                            <button class="btn btn-primary" type="submit">
                                🔍 Rechercher
                            </button>
                            <?php if ($search): ?>
                                <a href="index.php" class="btn btn-outline-secondary">✕ Effacer</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($search && empty($articles)): ?>
                <div class="alert alert-info">
                    Aucun article trouvé pour "<strong><?= e($search) ?></strong>"
                </div>
            <?php elseif (empty($articles)): ?>
                <div class="alert alert-warning">
                    <h4>Aucun article disponible</h4>
                    <p>Ajoutez des flux RSS et actualisez-les pour voir des articles ici.</p>
                    <a href="feeds.php" class="btn btn-primary">Gérer les flux RSS</a>
                </div>
            <?php else: ?>
                <!-- Liste des articles -->
                <div class="row">
                    <?php foreach ($articles as $art): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 article-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-primary"><?= e($art['feed_name']) ?></span>
                                        <small class="text-muted"><?= timeAgo($art['pub_date']) ?></small>
                                    </div>

                                    <h5 class="card-title">
                                        <a href="<?= e($art['link']) ?>" target="_blank" class="text-decoration-none text-dark">
                                            <?= e($art['title']) ?>
                                        </a>
                                    </h5>

                                    <?php if ($art['description']): ?>
                                        <p class="card-text text-muted small">
                                            <?= truncate(strip_tags($art['description']), 120) ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <small class="text-muted">
                                            📅 <?= formatDate($art['pub_date'], 'd/m/Y') ?>
                                        </small>
                                        <a href="<?= e($art['link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            Lire →
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="mt-4">
                        <?= pagination($page, $totalPages, 'index.php' . ($search ? '?search=' . urlencode($search) . '&' : '?')) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once 'views/layout/footer.php'; ?>

<?php
/**
 * Gestion des flux RSS (CRUD)
 */

session_start();

require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/RSSFeed.php';
require_once 'includes/Article.php';
require_once 'includes/helpers.php';

$rssFeed = new RSSFeed();
$article = new Article();

// Traitement des actions
$action = input('action');
$feedId = input('id');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($action) {
            case 'create':
                $name = input('name');
                $url = input('url');
                $category = input('category');

                if (empty($name) || empty($url)) {
                    throw new Exception("Le nom et l'URL sont obligatoires");
                }

                $rssFeed->create($name, $url, $category);
                setFlash('success', "Flux RSS '{$name}' ajouté avec succès !");
                redirect('feeds.php');
                break;

            case 'update':
                $name = input('name');
                $url = input('url');
                $category = input('category');

                if (empty($name) || empty($url) || empty($feedId)) {
                    throw new Exception("Données incomplètes");
                }

                $rssFeed->update($feedId, $name, $url, $category);
                setFlash('success', "Flux RSS mis à jour !");
                redirect('feeds.php');
                break;

            case 'delete':
                if ($feedId) {
                    $feed = $rssFeed->getById($feedId);
                    $rssFeed->delete($feedId);
                    setFlash('success', "Flux '{$feed['name']}' supprimé !");
                }
                redirect('feeds.php');
                break;

            case 'refresh':
                if ($feedId) {
                    $feed = $rssFeed->getById($feedId);
                    $newArticles = $rssFeed->refresh($feedId);
                    setFlash('success', "{$newArticles} nouveaux articles récupérés pour '{$feed['name']}'");
                }
                redirect('feeds.php');
                break;
        }
    }
} catch (Exception $e) {
    setFlash('danger', "Erreur : " . $e->getMessage());
    redirect('feeds.php');
}

// Récupération des flux
$feeds = $rssFeed->getAll();

// Si édition, récupérer le flux
$editFeed = null;
if ($action === 'edit' && $feedId) {
    $editFeed = $rssFeed->getById($feedId);
}

$pageTitle = "Gestion des flux RSS";

require_once 'views/layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            🏠 Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="feeds.php">
                            📋 Gérer les flux
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="refresh.php" onclick="return confirm('Actualiser tous les flux RSS ?');">
                            🔄 Actualiser tout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">📋 Gestion des flux RSS</h1>
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

            <div class="row">
                <!-- Formulaire ajout/édition -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?= $editFeed ? '✏️ Modifier le flux' : '➕ Ajouter un flux RSS' ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="feeds.php">
                                <input type="hidden" name="action" value="<?= $editFeed ? 'update' : 'create' ?>">
                                <?php if ($editFeed): ?>
                                    <input type="hidden" name="id" value="<?= $editFeed['id'] ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom du flux *</label>
                                    <input type="text" class="form-control" id="name" name="name" required
                                           value="<?= e($editFeed['name'] ?? '') ?>"
                                           placeholder="Ex: Légifrance - Actualités">
                                </div>

                                <div class="mb-3">
                                    <label for="url" class="form-label">URL du flux RSS *</label>
                                    <input type="url" class="form-control" id="url" name="url" required
                                           value="<?= e($editFeed['url'] ?? '') ?>"
                                           placeholder="https://example.com/rss">
                                    <small class="form-text text-muted">URL complète vers le flux RSS/Atom</small>
                                </div>

                                <div class="mb-3">
                                    <label for="category" class="form-label">Catégorie</label>
                                    <input type="text" class="form-control" id="category" name="category"
                                           value="<?= e($editFeed['category'] ?? '') ?>"
                                           placeholder="Ex: Législation, Jurisprudence...">
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <?= $editFeed ? '💾 Mettre à jour' : '➕ Ajouter le flux' ?>
                                    </button>
                                    <?php if ($editFeed): ?>
                                        <a href="feeds.php" class="btn btn-secondary">Annuler</a>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <hr class="my-4">

                            <h6 class="mb-3">💡 Exemples de flux juridiques</h6>
                            <ul class="small text-muted">
                                <li>Légifrance: https://www.legifrance.gouv.fr/rss/actualite</li>
                                <li>Conseil d'État: https://www.conseil-etat.fr/ressources/decisions-contentieuses/rss</li>
                                <li>Dalloz: https://www.dalloz-actualite.fr/rss.xml</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Liste des flux -->
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Flux RSS configurés (<?= count($feeds) ?>)</h5>
                            <a href="refresh.php" class="btn btn-sm btn-outline-primary" onclick="return confirm('Actualiser tous les flux ?');">
                                🔄 Tout actualiser
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($feeds)): ?>
                                <div class="p-4 text-center text-muted">
                                    <p class="mb-0">Aucun flux RSS configuré.</p>
                                    <p>Utilisez le formulaire ci-contre pour ajouter votre premier flux.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($feeds as $feed): ?>
                                        <?php
                                        // Compter les articles de ce flux
                                        $countSql = "SELECT COUNT(*) as total FROM articles WHERE feed_id = ?";
                                        $count = Database::getInstance()->fetchOne($countSql, [$feed['id']]);
                                        $articleCount = $count['total'] ?? 0;
                                        ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?= e($feed['name']) ?></h6>
                                                    <p class="mb-1 small text-muted text-truncate" style="max-width: 400px;">
                                                        🔗 <?= e($feed['url']) ?>
                                                    </p>
                                                    <div class="mt-2">
                                                        <?php if ($feed['category']): ?>
                                                            <span class="badge bg-secondary"><?= e($feed['category']) ?></span>
                                                        <?php endif; ?>
                                                        <span class="badge bg-info"><?= $articleCount ?> articles</span>
                                                        <small class="text-muted">
                                                            Ajouté le <?= formatDate($feed['created_at'], 'd/m/Y') ?>
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="btn-group" role="group">
                                                    <form method="POST" action="feeds.php" class="d-inline">
                                                        <input type="hidden" name="action" value="refresh">
                                                        <input type="hidden" name="id" value="<?= $feed['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Actualiser ce flux">
                                                            🔄
                                                        </button>
                                                    </form>

                                                    <a href="feeds.php?action=edit&id=<?= $feed['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifier">
                                                        ✏️
                                                    </a>

                                                    <form method="POST" action="feeds.php" class="d-inline" onsubmit="return confirm('Supprimer ce flux et tous ses articles ?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $feed['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                            🗑️
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once 'views/layout/footer.php'; ?>

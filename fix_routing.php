<?php
/**
 * Correction du routage et de l'API
 */

echo "\n=== CORRECTION DU ROUTAGE ET DE L'API ===\n\n";

$baseDir = __DIR__;

// 1. Backup api.php
if (file_exists($baseDir . '/api.php')) {
    copy($baseDir . '/api.php', $baseDir . '/api.php.backup');
    echo "✓ Backup de api.php créé\n";
}

// 2. Créer api.php adaptatif
echo "\nCréation de api.php adaptatif...\n";

$apiContent = <<<'PHP'
<?php
/**
 * API REST adaptative pour RSS Reader V2
 * S'adapte automatiquement au schéma de la base de données
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Session.php';

autoInit();

$session = new Session();
$db = Database::getInstance();
$pdo = $db->getPdo();

// Get table structures
$stmt = $pdo->query("PRAGMA table_info(feeds)");
$feedColumns = array_column($stmt->fetchAll(), 'name');
$hasUserIdInFeeds = in_array('user_id', $feedColumns);

$stmt = $pdo->query("PRAGMA table_info(articles)");
$articleColumns = array_column($stmt->fetchAll(), 'name');
$hasIsRead = in_array('is_read', $articleColumns);

// Get request info
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '';

// Remove query string
$path = strtok($path, '?');

// Remove /api prefix if present
$path = preg_replace('#^/api#', '', $path);

$parts = array_filter(explode('/', $path));
$parts = array_values($parts); // Reindex

// Routes
try {
    // Health check (public)
    if (empty($parts) || (isset($parts[0]) && $parts[0] === 'health')) {
        jsonResponse(true, [
            'status' => 'ok',
            'timestamp' => time(),
            'database' => file_exists(DB_PATH) ? 'connected' : 'missing'
        ]);
    }

    // All other routes require authentication
    $session->requireAuth();
    $user = $session->getCurrentUser();
    $userId = $user['id'];

    // GET /api/feeds - List all feeds
    if ($method === 'GET' && isset($parts[0]) && $parts[0] === 'feeds' && count($parts) === 1) {
        if ($hasUserIdInFeeds) {
            $stmt = $pdo->prepare("SELECT * FROM feeds WHERE user_id = ? ORDER BY title");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->query("SELECT * FROM feeds ORDER BY title");
        }

        $feeds = $stmt->fetchAll();
        jsonResponse(true, $feeds);
    }

    // POST /api/feeds - Add new feed
    if ($method === 'POST' && isset($parts[0]) && $parts[0] === 'feeds' && count($parts) === 1) {
        $input = json_decode(file_get_contents('php://input'), true);
        $url = trim($input['url'] ?? '');

        if (empty($url)) {
            jsonResponse(false, [], 'URL requise', 400);
        }

        // Fetch feed
        $xml = @file_get_contents($url);
        if ($xml === false) {
            jsonResponse(false, [], 'Impossible de récupérer le flux', 400);
        }

        $feed = @simplexml_load_string($xml);
        if ($feed === false) {
            jsonResponse(false, [], 'XML invalide', 400);
        }

        // Get title
        $title = null;
        if (isset($feed->channel->title)) {
            $title = (string)$feed->channel->title;
        } elseif (isset($feed->title)) {
            $title = (string)$feed->title;
        } else {
            $title = $url;
        }

        // Insert feed
        if ($hasUserIdInFeeds) {
            $stmt = $pdo->prepare("INSERT INTO feeds (user_id, url, title, active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$userId, $url, $title]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO feeds (url, title, active) VALUES (?, ?, 1)");
            $stmt->execute([$url, $title]);
        }

        $feedId = $pdo->lastInsertId();

        jsonResponse(true, ['id' => $feedId, 'title' => $title], 'Flux ajouté');
    }

    // DELETE /api/feeds/:id - Delete feed
    if ($method === 'DELETE' && isset($parts[0]) && $parts[0] === 'feeds' && isset($parts[1])) {
        $feedId = (int)$parts[1];

        // Delete articles
        $pdo->prepare("DELETE FROM articles WHERE feed_id = ?")->execute([$feedId]);

        // Delete feed
        if ($hasUserIdInFeeds) {
            $stmt = $pdo->prepare("DELETE FROM feeds WHERE id = ? AND user_id = ?");
            $stmt->execute([$feedId, $userId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM feeds WHERE id = ?");
            $stmt->execute([$feedId]);
        }

        jsonResponse(true, [], 'Flux supprimé');
    }

    // GET /api/articles - List articles
    if ($method === 'GET' && isset($parts[0]) && $parts[0] === 'articles' && count($parts) === 1) {
        $filter = $_GET['filter'] ?? 'all';
        $feedId = isset($_GET['feed_id']) ? (int)$_GET['feed_id'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

        // Build query
        $where = [];
        $params = [];

        if ($hasUserIdInFeeds) {
            $where[] = "a.feed_id IN (SELECT id FROM feeds WHERE user_id = ?)";
            $params[] = $userId;
        }

        if ($feedId) {
            $where[] = "a.feed_id = ?";
            $params[] = $feedId;
        }

        if ($filter === 'unread' && $hasIsRead) {
            $where[] = "a.is_read = 0";
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $sql = "
            SELECT a.*, f.title as feed_title, f.url as feed_url
            FROM articles a
            LEFT JOIN feeds f ON a.feed_id = f.id
            {$whereClause}
            ORDER BY a.published_date DESC
            LIMIT ?
        ";

        $params[] = $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll();

        jsonResponse(true, $articles);
    }

    // PUT /api/articles/:id/read - Mark article as read
    if ($method === 'PUT' && isset($parts[0]) && $parts[0] === 'articles' && isset($parts[1]) && isset($parts[2]) && $parts[2] === 'read') {
        $articleId = (int)$parts[1];

        if ($hasIsRead) {
            $stmt = $pdo->prepare("UPDATE articles SET is_read = 1 WHERE id = ?");
            $stmt->execute([$articleId]);
        }

        jsonResponse(true, [], 'Article marqué comme lu');
    }

    // GET /api/stats - Statistics
    if ($method === 'GET' && isset($parts[0]) && $parts[0] === 'stats' && count($parts) === 1) {
        // Total articles
        if ($hasUserIdInFeeds) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM articles
                WHERE feed_id IN (SELECT id FROM feeds WHERE user_id = ?)
            ");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) FROM articles");
        }
        $totalArticles = $stmt->fetchColumn();

        // Unread articles
        if ($hasUserIdInFeeds && $hasIsRead) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM articles
                WHERE is_read = 0 AND feed_id IN (SELECT id FROM feeds WHERE user_id = ?)
            ");
            $stmt->execute([$userId]);
        } elseif ($hasIsRead) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE is_read = 0");
        } else {
            $stmt = null;
        }
        $unreadArticles = $stmt ? $stmt->fetchColumn() : 0;

        // Total feeds
        if ($hasUserIdInFeeds) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM feeds WHERE user_id = ?");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) FROM feeds");
        }
        $totalFeeds = $stmt->fetchColumn();

        jsonResponse(true, [
            'total_articles' => $totalArticles,
            'unread_articles' => $unreadArticles,
            'total_feeds' => $totalFeeds
        ]);
    }

    // 404 - Route not found
    jsonResponse(false, [], 'Route not found: ' . $path, 404);

} catch (Exception $e) {
    jsonResponse(false, [], $e->getMessage(), 500);
}
PHP;

file_put_contents($baseDir . '/api.php', $apiContent);
echo "✓ api.php adaptatif créé\n";

// 3. Vérifier .htaccess
echo "\nVérification du .htaccess...\n";

$htaccessContent = <<<'HTACCESS'
# RSS Reader V2 - Configuration Apache

RewriteEngine On
RewriteBase /

# Ne pas réécrire les fichiers et dossiers existants
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Exceptions: manage.php et autres fichiers PHP à la racine doivent être accessibles
RewriteCond %{REQUEST_URI} !^/manage\.php
RewriteCond %{REQUEST_URI} !^/test_.*\.php
RewriteCond %{REQUEST_URI} !^/.*\.html

# Rediriger tout le reste vers index.php
RewriteRule ^(.*)$ index.php [QSA,L]

# Cache pour les assets
<FilesMatch "\.(css|js|jpg|jpeg|png|gif|ico|svg)$">
    Header set Cache-Control "max-age=86400, public"
</FilesMatch>

# Désactiver l'affichage des répertoires
Options -Indexes

# PHP Settings
<IfModule mod_php.c>
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value memory_limit 256M
    php_value max_execution_time 300
</IfModule>
HTACCESS;

file_put_contents($baseDir . '/.htaccess', $htaccessContent);
echo "✓ .htaccess mis à jour\n";

echo "\n========================================\n";
echo "✅ CORRECTIONS APPLIQUÉES\n";
echo "========================================\n\n";

echo "Actions effectuées:\n";
echo "1. ✓ api.php adaptatif créé (détecte user_id automatiquement)\n";
echo "2. ✓ .htaccess corrigé (manage.php accessible)\n\n";

echo "Testez maintenant:\n";
echo "1. http://dusselle.fr/ - Devrait afficher les 19 articles\n";
echo "2. http://dusselle.fr/manage.php - Devrait afficher la gestion\n\n";

echo "Si ça ne fonctionne toujours pas, videz le cache (Ctrl+F5)\n\n";

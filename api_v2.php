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
$path = $_SERVER['PATH_INFO'] ?? '';
$parts = array_filter(explode('/', $path));

// Routes
try {
    // Health check (public)
    if ($path === '/health' || count($parts) === 0) {
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
    if ($method === 'GET' && $parts[0] === 'feeds' && count($parts) === 1) {
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
    if ($method === 'POST' && $parts[0] === 'feeds' && count($parts) === 1) {
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
    if ($method === 'DELETE' && $parts[0] === 'feeds' && isset($parts[1])) {
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
    if ($method === 'GET' && $parts[0] === 'articles' && count($parts) === 1) {
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
    if ($method === 'PUT' && $parts[0] === 'articles' && isset($parts[1]) && ($parts[2] ?? '') === 'read') {
        $articleId = (int)$parts[1];

        if ($hasIsRead) {
            $stmt = $pdo->prepare("UPDATE articles SET is_read = 1 WHERE id = ?");
            $stmt->execute([$articleId]);
        }

        jsonResponse(true, [], 'Article marqué comme lu');
    }

    // GET /api/stats - Statistics
    if ($method === 'GET' && $parts[0] === 'stats' && count($parts) === 1) {
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
    jsonResponse(false, [], 'Route not found', 404);

} catch (Exception $e) {
    jsonResponse(false, [], $e->getMessage(), 500);
}

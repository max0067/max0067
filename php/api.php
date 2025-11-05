<?php
/**
 * Gestionnaire des routes API
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/rss_updater.php';

// Initialiser les dépendances
$db = Database::getInstance();
$sessionManager = new SessionManager();
$rssUpdater = new RSSUpdater();

// Récupérer la méthode HTTP et l'URI
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$requestUri = strtok($requestUri, '?');
$requestUri = rtrim($requestUri, '/');

// Helper pour récupérer les données JSON POST
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

// Helper pour extraire l'ID de l'URI
function extractId($uri, $pattern) {
    if (preg_match($pattern, $uri, $matches)) {
        return intval($matches[1]);
    }
    return null;
}

// ===== Routes d'authentification =====

if ($requestUri === '/api/auth/login' && $requestMethod === 'POST') {
    $data = getJsonInput();
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        json_error('Username and password required', 400);
    }

    $user = $sessionManager->login($username, $password);
    if ($user) {
        json_success([
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    }

    json_error('Invalid credentials', 401);
}

if ($requestUri === '/api/auth/register' && $requestMethod === 'POST') {
    $data = getJsonInput();
    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        json_error('All fields required', 400);
    }

    $userId = $db->createUser($username, $email, $password);
    if ($userId) {
        json_success([], 'Account created successfully');
    }

    json_error('Username or email already exists', 400);
}

if ($requestUri === '/api/auth/logout' && $requestMethod === 'POST') {
    $sessionManager->requireAuth();
    $sessionManager->logout();
    json_success([], 'Logged out successfully');
}

if ($requestUri === '/api/auth/me' && $requestMethod === 'GET') {
    $sessionManager->requireAuth();
    $user = $sessionManager->getCurrentUser();
    json_success([
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
}

// ===== Routes API - Flux RSS =====

if ($requestUri === '/api/feeds' && $requestMethod === 'GET') {
    $sessionManager->requireAuth();
    $user = $sessionManager->getCurrentUser();
    $feeds = $db->getUserFeeds($user['id']);
    json_success(['feeds' => $feeds]);
}

if ($requestUri === '/api/feeds' && $requestMethod === 'POST') {
    $sessionManager->requireAuth();
    $user = $sessionManager->getCurrentUser();
    $data = getJsonInput();

    if (empty($data['url'])) {
        json_error('URL required', 400);
    }

    $title = $data['title'] ?? '';
    $url = $data['url'];
    $description = $data['description'] ?? '';
    $updateInterval = $data['update_interval'] ?? 30;

    $feedId = $db->addFeed($user['id'], $title, $url, $description, $updateInterval);

    if ($feedId) {
        log_message('INFO', "New feed added (ID: $feedId), updating...");
        try {
            $rssUpdater->updateSingleFeed($feedId);
        } catch (Exception $e) {
            log_message('ERROR', "Error updating new feed: " . $e->getMessage());
        }

        json_success(['feed_id' => $feedId], 'Feed added successfully');
    }

    json_error('Feed already exists or error adding', 400);
}

if (preg_match('#^/api/feeds/(\d+)$#', $requestUri, $matches) && $requestMethod === 'GET') {
    $sessionManager->requireAuth();
    $user = $sessionManager->getCurrentUser();
    $feedId = intval($matches[1]);

    $feed = $db->getFeedById($feedId, $user['id']);
    if ($feed) {
        json_success(['feed' => $feed]);
    }

    json_error('Feed not found', 404);
}

if (preg_match('#^/api/feeds/(\d+)$#', $requestUri, $matches) && $requestMethod === 'PUT') {
    $sessionManager->requireAuth();
    $user = $sessionManager->getCurrentUser();
    $feedId = intval($matches[1]);
    $data = getJsonInput();

    if (empty($data)) {
        json_error('Data required', 400);
    }

    $allowedFields = ['title', 'url', 'description', 'update_interval', 'active'];
    $updates = array_intersect_key($data, array_flip($allowedFields));

    $success = $db->updateFeed($feedId, $user['id'], $updates);

    if ($success) {
        json_success([], 'Feed updated successfully');
    }

    json_error('Feed not found or no changes', 404);
}

if (preg_match('#^/api/feeds/(\d+)$#', $requestUri, $matches) && $requestMethod === 'DELETE') {
    $sessionManager->requireAuth();
    $user = $sessionManager->getCurrentUser();
    $feedId = intval($matches[1]);

    $success = $db->deleteFeed($feedId, $user['id']);

    if ($success) {
        json_success([], 'Feed deleted successfully');
    }

    json_error('Feed not found', 404);
}

if (preg_match('#^/api/feeds/(\d+)/update$#', $requestUri, $matches) && $requestMethod === 'POST') {
    $sessionManager->requireAuth();
    $user = $sessionManager->getCurrentUser();
    $feedId = intval($matches[1]);

    // Vérifier que le flux appartient à l'utilisateur
    $feed = $db->getFeedById($feedId, $user['id']);
    if (!$feed) {
        json_error('Feed not found', 404);
    }

    $newArticles = $rssUpdater->updateSingleFeed($feedId);
    json_success([
        'new_articles' => $newArticles
    ], "$newArticles new articles retrieved");
}

if ($requestUri === '/api/feeds/update-all' && $requestMethod === 'POST') {
    $sessionManager->requireAuth();
    $user = $sessionManager->getCurrentUser();

    $totalNewArticles = $rssUpdater->updateAllFeeds($user['id']);
    json_success([
        'new_articles' => $totalNewArticles
    ], "$totalNewArticles new articles retrieved");
}

// ===== Routes API - Articles =====

if ($requestUri === '/api/articles' && $requestMethod === 'GET') {
    $sessionManager->requireAuth();
    $user = $sessionManager->getCurrentUser();

    $feedId = isset($_GET['feed_id']) ? intval($_GET['feed_id']) : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
    $searchQuery = isset($_GET['search']) && !empty($_GET['search']) ? $_GET['search'] : null;

    $articles = $db->getArticles(
        $user['id'],
        $feedId,
        $limit,
        $offset,
        $unreadOnly,
        $searchQuery
    );

    $total = $db->getArticleCount(
        $user['id'],
        $feedId,
        $unreadOnly,
        $searchQuery
    );

    json_success([
        'articles' => $articles,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

if (preg_match('#^/api/articles/(\d+)/read$#', $requestUri, $matches) && $requestMethod === 'PUT') {
    $sessionManager->requireAuth();
    $user = $sessionManager->getCurrentUser();
    $articleId = intval($matches[1]);
    $data = getJsonInput();

    $read = isset($data['read']) ? (bool)$data['read'] : true;

    $success = $db->markArticleRead($user['id'], $articleId, $read);

    if ($success) {
        json_success([], 'Article updated');
    }

    json_error('Article not found', 404);
}

if ($requestUri === '/api/articles/mark-all-read' && $requestMethod === 'POST') {
    $sessionManager->requireAuth();
    $user = $sessionManager->getCurrentUser();
    $data = getJsonInput();

    $articleIds = $data['article_ids'] ?? null;

    $count = $db->markAllArticlesRead($user['id'], $articleIds);

    $plural = $count > 1 ? 's' : '';
    json_success([
        'count' => $count
    ], "$count article$plural marqué$plural comme lu$plural");
}

if (preg_match('#^/api/articles/(\d+)/favorite$#', $requestUri, $matches) && $requestMethod === 'PUT') {
    $sessionManager->requireAuth();
    $user = $sessionManager->getCurrentUser();
    $articleId = intval($matches[1]);

    $success = $db->toggleArticleFavorite($user['id'], $articleId);

    if ($success) {
        json_success([], 'Favorite toggled');
    }

    json_error('Article not found', 404);
}

// ===== Routes API - Statistiques =====

if ($requestUri === '/api/stats' && $requestMethod === 'GET') {
    $sessionManager->requireAuth();
    $user = $sessionManager->getCurrentUser();

    $stats = $db->getUserStats($user['id']);
    json_success(['stats' => $stats]);
}

// ===== Routes API - Admin =====

if ($requestUri === '/api/admin/stats' && $requestMethod === 'GET') {
    $sessionManager->requireAdmin();

    $stats = $db->getAdminStats();
    json_success(['stats' => $stats]);
}

if ($requestUri === '/api/admin/users' && $requestMethod === 'GET') {
    $sessionManager->requireAdmin();

    $users = $db->getAllUsers();
    json_success(['users' => $users]);
}

if (preg_match('#^/api/admin/users/(\d+)$#', $requestUri, $matches) && $requestMethod === 'PUT') {
    $sessionManager->requireAdmin();
    $userId = intval($matches[1]);
    $data = getJsonInput();

    if (empty($data)) {
        json_error('Data required', 400);
    }

    $allowedFields = ['username', 'email', 'role', 'active', 'password'];
    $updates = array_intersect_key($data, array_flip($allowedFields));

    $success = $db->updateUser($userId, $updates);

    if ($success) {
        json_success([], 'User updated successfully');
    }

    json_error('User not found', 404);
}

if (preg_match('#^/api/admin/users/(\d+)$#', $requestUri, $matches) && $requestMethod === 'DELETE') {
    $sessionManager->requireAdmin();
    $user = $sessionManager->getCurrentUser();
    $userId = intval($matches[1]);

    // Empêcher la suppression de soi-même
    if ($userId === $user['id']) {
        json_error('Cannot delete yourself', 400);
    }

    $success = $db->deleteUser($userId);

    if ($success) {
        json_success([], 'User deleted successfully');
    }

    json_error('User not found', 404);
}

// Si on arrive ici, l'API n'existe pas
http_response_code(404);
json_error('API endpoint not found', 404);

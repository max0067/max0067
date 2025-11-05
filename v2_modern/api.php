<?php
/**
 * API REST V2 - Moderne et complète
 */

// Déjà inclus par index.php : config, Database, Session
// $session et $db sont disponibles

// Helper pour récupérer les données JSON
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

// Extraire la route API
$apiUri = str_replace('/api', '', $uri);

// ===== AUTHENTICATION =====

if ($apiUri === '/auth/login' && $method === 'POST') {
    $data = getJsonInput();
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        jsonResponse(false, [], 'Username and password required', 400);
    }

    if ($session->login($username, $password)) {
        $user = $session->getCurrentUser();
        jsonResponse(true, [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'dark_mode' => (bool)$user['dark_mode']
            ]
        ], 'Login successful');
    }

    jsonResponse(false, [], 'Invalid credentials', 401);
}

if ($apiUri === '/auth/register' && $method === 'POST') {
    $data = getJsonInput();
    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        jsonResponse(false, [], 'All fields required', 400);
    }

    $userId = $db->createUser($username, $email, $password);
    if ($userId) {
        jsonResponse(true, ['user_id' => $userId], 'Account created successfully', 201);
    }

    jsonResponse(false, [], 'Username or email already exists', 400);
}

if ($apiUri === '/auth/logout' && $method === 'POST') {
    $session->requireAuth();
    $session->logout();
    jsonResponse(true, [], 'Logged out successfully');
}

if ($apiUri === '/auth/me' && $method === 'GET') {
    $session->requireAuth();
    $user = $session->getCurrentUser();
    jsonResponse(true, [
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'dark_mode' => (bool)$user['dark_mode']
        ]
    ]);
}

// ===== FEEDS =====

$session->requireAuth();
$user = $session->getCurrentUser();

if ($apiUri === '/feeds' && $method === 'GET') {
    $feeds = $db->getUserFeeds($user['id']);
    jsonResponse(true, ['feeds' => $feeds]);
}

if ($apiUri === '/feeds' && $method === 'POST') {
    $data = getJsonInput();

    if (empty($data['url'])) {
        jsonResponse(false, [], 'URL required', 400);
    }

    $feedId = $db->addFeed(
        $user['id'],
        $data['title'] ?? '',
        $data['url'],
        $data['description'] ?? '',
        $data['update_interval'] ?? 30
    );

    if ($feedId) {
        jsonResponse(true, ['feed_id' => $feedId], 'Feed added successfully', 201);
    }

    jsonResponse(false, [], 'Feed already exists or error adding', 400);
}

if (preg_match('#^/feeds/(\d+)$#', $apiUri, $matches) && $method === 'GET') {
    $feedId = (int)$matches[1];
    $feed = $db->getFeedById($feedId, $user['id']);

    if ($feed) {
        jsonResponse(true, ['feed' => $feed]);
    }

    jsonResponse(false, [], 'Feed not found', 404);
}

if (preg_match('#^/feeds/(\d+)$#', $apiUri, $matches) && $method === 'DELETE') {
    $feedId = (int)$matches[1];

    if ($db->deleteFeed($feedId, $user['id'])) {
        jsonResponse(true, [], 'Feed deleted successfully');
    }

    jsonResponse(false, [], 'Feed not found', 404);
}

// ===== ARTICLES =====

if ($apiUri === '/articles' && $method === 'GET') {
    $feedId = isset($_GET['feed_id']) ? (int)$_GET['feed_id'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';

    $articles = $db->getArticles($user['id'], $feedId, $limit, $offset, $unreadOnly);

    jsonResponse(true, [
        'articles' => $articles,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

if (preg_match('#^/articles/(\d+)/read$#', $apiUri, $matches) && $method === 'PUT') {
    $articleId = (int)$matches[1];
    $data = getJsonInput();
    $read = isset($data['read']) ? (bool)$data['read'] : true;

    if ($db->markArticleRead($user['id'], $articleId, $read)) {
        jsonResponse(true, [], 'Article updated');
    }

    jsonResponse(false, [], 'Article not found', 404);
}

// ===== STATS =====

if ($apiUri === '/stats' && $method === 'GET') {
    $stats = $db->getUserStats($user['id']);
    jsonResponse(true, ['stats' => $stats]);
}

// ===== 404 =====

jsonResponse(false, [], 'API endpoint not found', 404);

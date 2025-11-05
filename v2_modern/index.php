<?php
/**
 * RSS Reader V2 - Point d'entrée moderne
 * Auto-initialisation et routage intelligent
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Session.php';

// Health check endpoint
if (isset($_GET['health'])) {
    $db = Database::getInstance();
    $health = $db->healthCheck();
    $health['version'] = APP_VERSION;
    $health['php'] = phpversion();
    jsonResponse(true, $health);
}

// Initialiser la session
$session = new SessionManager();
$db = Database::getInstance();

// Router simple et efficace
$uri = $_SERVER['REQUEST_URI'];
$uri = strtok($uri, '?');
$uri = str_replace('/v2', '', $uri); // Support sous-dossier
$uri = rtrim($uri, '/');
$uri = $uri ?: '/';

$method = $_SERVER['REQUEST_METHOD'];

// Routes API
if (strpos($uri, '/api/') === 0) {
    require_once __DIR__ . '/api.php';
    exit;
}

// Routes publiques
if ($uri === '/login') {
    if ($session->isAuthenticated()) {
        header('Location: /v2/');
        exit;
    }
    require_once __DIR__ . '/views/login.php';
    exit;
}

if ($uri === '/register') {
    if ($session->isAuthenticated()) {
        header('Location: /v2/');
        exit;
    }
    require_once __DIR__ . '/views/register.php';
    exit;
}

// Routes protégées
$session->requireAuth();

switch ($uri) {
    case '/':
    case '/dashboard':
        require_once __DIR__ . '/views/dashboard.php';
        break;

    case '/feeds':
        require_once __DIR__ . '/views/feeds.php';
        break;

    case '/settings':
        require_once __DIR__ . '/views/settings.php';
        break;

    case '/admin':
        $session->requireAdmin();
        require_once __DIR__ . '/views/admin.php';
        break;

    default:
        http_response_code(404);
        echo '<h1>404 - Page non trouvée</h1>';
        echo '<p><a href="/v2/">Retour à l\'accueil</a></p>';
        break;
}

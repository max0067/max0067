<?php
/**
 * Point d'entrée principal de l'application RSS Reader
 */

require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/database.php';
require_once __DIR__ . '/php/session.php';

// Initialiser le gestionnaire de sessions
$sessionManager = new SessionManager();

// Récupérer la route demandée
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Enlever les paramètres GET de l'URI
$requestUri = strtok($requestUri, '?');

// Enlever le slash de fin s'il existe
$requestUri = rtrim($requestUri, '/');
if (empty($requestUri)) {
    $requestUri = '/';
}

// Router simple
switch ($requestUri) {
    // Routes publiques
    case '/login':
        if ($sessionManager->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        include __DIR__ . '/templates/login.html';
        break;

    case '/register':
        if ($sessionManager->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        include __DIR__ . '/templates/register.html';
        break;

    // Routes protégées
    case '/':
    case '/index':
        $sessionManager->requireAuth();
        include __DIR__ . '/templates/index_v2.html';
        break;

    case '/dashboard':
        $sessionManager->requireAuth();
        include __DIR__ . '/templates/dashboard.html';
        break;

    case '/feeds':
        $sessionManager->requireAuth();
        include __DIR__ . '/templates/feeds_manager.html';
        break;

    case '/admin':
        $sessionManager->requireAdmin();
        include __DIR__ . '/templates/admin.html';
        break;

    // Routes API
    default:
        if (strpos($requestUri, '/api/') === 0) {
            // Rediriger vers le gestionnaire d'API
            include __DIR__ . '/php/api.php';
        } else {
            // Page 404
            http_response_code(404);
            echo '<h1>404 - Page non trouvée</h1>';
        }
        break;
}

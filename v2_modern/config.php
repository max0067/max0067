<?php
/**
 * Configuration V2 - RSS Reader Modern
 * Auto-initialisation et gestion intelligente
 */

// Environnement
define('APP_VERSION', '2.0.0');
define('APP_NAME', 'RSS Reader V2');
define('APP_ENV', getenv('APP_ENV') ?: 'production');

// Chemins
define('ROOT_PATH', __DIR__);
define('DATA_PATH', ROOT_PATH . '/data');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('CACHE_PATH', ROOT_PATH . '/cache');

// Base de données
define('DB_PATH', DATA_PATH . '/rss_feeds_v2.db');

// Sécurité
define('SECRET_KEY', getenv('SECRET_KEY') ?: 'change-me-in-production-' . bin2hex(random_bytes(16)));
define('SESSION_LIFETIME', 7 * 24 * 60 * 60); // 7 jours

// Application
define('ARTICLES_PER_PAGE', 50);
define('UPDATE_INTERVAL', 30); // minutes
define('ENABLE_DARK_MODE', true);
define('ENABLE_NOTIFICATIONS', true);

// Timezone
date_default_timezone_set('Europe/Paris');

// Configuration PHP
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('log_errors', 1);
ini_set('error_log', LOGS_PATH . '/php_errors.log');

/**
 * Auto-initialisation des dossiers
 */
function autoInit() {
    $dirs = [DATA_PATH, LOGS_PATH, CACHE_PATH];

    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    // Créer fichier .htaccess pour protéger les données
    $htaccess = DATA_PATH . '/.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Deny from all\n");
    }

    // Log de démarrage
    $logFile = LOGS_PATH . '/app.log';
    if (!file_exists($logFile)) {
        touch($logFile);
        chmod($logFile, 0666);
    }
}

// Exécuter l'auto-init
autoInit();

/**
 * Logger simple et efficace
 */
function logMessage($level, $message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    $logEntry = "[$timestamp] [$level] $message$contextStr\n";

    @file_put_contents(LOGS_PATH . '/app.log', $logEntry, FILE_APPEND);
}

/**
 * Réponse JSON standardisée
 */
function jsonResponse($success, $data = [], $message = null, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    $response = ['success' => $success];

    if ($message) {
        $response['message'] = $message;
    }

    if (!empty($data)) {
        $response = array_merge($response, $data);
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Helper pour obtenir l'URL de base
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . rtrim($script, '/');
}

// Log du démarrage
logMessage('INFO', 'Application V2 initialized', [
    'version' => APP_VERSION,
    'php' => phpversion(),
    'env' => APP_ENV
]);

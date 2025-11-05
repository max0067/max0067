<?php
/**
 * Configuration de l'application RSS Reader
 */

// Configuration de la base de données
define('DB_PATH', __DIR__ . '/../rss_feeds.db');

// Configuration de sécurité
define('SECRET_KEY', getenv('SECRET_KEY') ?: 'votre-cle-secrete-changez-moi-en-production');
define('SESSION_LIFETIME', 7 * 24 * 60 * 60); // 7 jours en secondes

// Configuration de l'application
define('UPDATE_INTERVAL', 30); // Intervalle de mise à jour des flux en minutes
define('ARTICLES_PER_PAGE', 100);

// Configuration des logs
define('LOG_FILE', __DIR__ . '/../logs/app.log');
define('LOG_LEVEL', 'INFO');

// Timezone
date_default_timezone_set('Europe/Paris');

// Configuration de session PHP
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

// Activer l'affichage des erreurs en développement
if (getenv('ENVIRONMENT') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Fonction helper pour les logs
function log_message($level, $message) {
    $log_dir = dirname(LOG_FILE);
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents(LOG_FILE, $log_entry, FILE_APPEND);
}

// Fonction helper pour les réponses JSON
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Fonction helper pour les erreurs JSON
function json_error($message, $status_code = 500) {
    json_response(['success' => false, 'error' => $message], $status_code);
}

// Fonction helper pour les succès JSON
function json_success($data = [], $message = null) {
    $response = ['success' => true];
    if ($message) {
        $response['message'] = $message;
    }
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    json_response($response);
}

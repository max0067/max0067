<?php
/**
 * RSS Manager - Configuration
 * PHP Version of the RSS Manager Application
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 1 for development
ini_set('log_errors', 1);

// Database configuration
define('DB_PATH', __DIR__ . '/rss_feeds.db');

// Application settings
define('APP_NAME', 'RSS Manager Pro');
define('APP_VERSION', '2.0-PHP');

// Session configuration
define('SESSION_LIFETIME', 7 * 24 * 60 * 60); // 7 days
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
session_set_cookie_params(SESSION_LIFETIME);

// Security
define('SECRET_KEY', getenv('SECRET_KEY') ?: 'change-me-in-production-' . md5(__DIR__));

// RSS Update settings
define('RSS_UPDATE_INTERVAL', 30); // minutes
define('RSS_FETCH_TIMEOUT', 30); // seconds

// Pagination
define('ARTICLES_PER_PAGE', 50);

// Timezone
date_default_timezone_set('Europe/Paris');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-load includes
function autoload_includes($file) {
    $includes_dir = __DIR__ . '/includes/';
    $files = [
        'database.php',
        'auth.php',
        'functions.php',
        'rss_parser.php'
    ];

    foreach ($files as $include_file) {
        $path = $includes_dir . $include_file;
        if (file_exists($path)) {
            require_once $path;
        }
    }
}

autoload_includes('all');

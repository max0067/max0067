<?php
/**
 * Initialization Script
 * Run this once to set up the PHP RSS Manager application
 */

echo "==============================================\n";
echo "  RSS Manager PHP - Initialization Script\n";
echo "==============================================\n\n";

// Check PHP version
echo "1. Checking PHP version...\n";
$phpVersion = phpversion();
echo "   PHP version: $phpVersion\n";
if (version_compare($phpVersion, '7.0.0', '<')) {
    echo "   ⚠️  WARNING: PHP 7.0 or higher recommended\n";
} else {
    echo "   ✓ PHP version OK\n";
}
echo "\n";

// Check required extensions
echo "2. Checking required extensions...\n";
$required = ['pdo', 'pdo_sqlite', 'curl', 'dom', 'libxml', 'json'];
$missing = [];

foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✓ $ext\n";
    } else {
        echo "   ✗ $ext (MISSING)\n";
        $missing[] = $ext;
    }
}

if (!empty($missing)) {
    echo "\n   ⚠️  MISSING EXTENSIONS: " . implode(', ', $missing) . "\n";
    echo "   Please install these extensions before continuing.\n";
    exit(1);
}
echo "   ✓ All required extensions available\n\n";

// Create necessary directories
echo "3. Creating directories...\n";
$dirs = [
    __DIR__ . '/logs',
    __DIR__ . '/includes',
    __DIR__ . '/api',
    __DIR__ . '/cron',
    __DIR__ . '/assets',
    __DIR__ . '/assets/css',
    __DIR__ . '/assets/js',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "   ✓ Created: $dir\n";
        } else {
            echo "   ✗ Failed to create: $dir\n";
        }
    } else {
        echo "   ✓ Exists: $dir\n";
    }
}
echo "\n";

// Initialize database
echo "4. Initializing database...\n";
require_once __DIR__ . '/config.php';

try {
    Database::initDB();
    echo "   ✓ Database initialized successfully\n";
    echo "   Database location: " . DB_PATH . "\n";

    if (file_exists(DB_PATH)) {
        $size = filesize(DB_PATH);
        echo "   Database size: " . number_format($size) . " bytes\n";
    }
} catch (Exception $e) {
    echo "   ✗ Database initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// Check database tables
echo "5. Verifying database tables...\n";
try {
    $db = Database::getInstance()->getConnection();
    $tables = ['users', 'sessions', 'feeds', 'articles', 'user_articles'];

    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch();
        echo "   ✓ Table '$table': {$result['count']} rows\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error checking tables: " . $e->getMessage() . "\n";
}
echo "\n";

// Check default admin user
echo "6. Checking default admin user...\n";
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $stmt->fetch();

    if ($admin) {
        echo "   ✓ Admin user exists: {$admin['username']}\n";
        echo "   Email: {$admin['email']}\n";
        echo "\n";
        echo "   Default credentials:\n";
        echo "   Username: admin\n";
        echo "   Password: admin123\n";
        echo "   ⚠️  IMPORTANT: Change the password after first login!\n";
    } else {
        echo "   ✗ No admin user found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error checking admin user: " . $e->getMessage() . "\n";
}
echo "\n";

// Check file permissions
echo "7. Checking file permissions...\n";
$checkFiles = [
    __DIR__ . '/config.php',
    __DIR__ . '/includes/database.php',
    __DIR__ . '/logs' => true, // directory
    DB_PATH
];

foreach ($checkFiles as $key => $value) {
    $file = is_int($key) ? $value : $key;
    $isDir = is_bool($value) && $value;

    if (file_exists($file)) {
        $perms = fileperms($file);
        $permsStr = substr(sprintf('%o', $perms), -4);

        if ($isDir) {
            $writable = is_writable($file);
            echo "   " . ($writable ? '✓' : '✗') . " $file ($permsStr) " . ($writable ? 'writable' : 'NOT writable') . "\n";
        } else {
            $readable = is_readable($file);
            echo "   " . ($readable ? '✓' : '✗') . " $file ($permsStr)\n";
        }
    } else {
        echo "   ✗ $file (NOT FOUND)\n";
    }
}
echo "\n";

// Configuration summary
echo "8. Configuration Summary:\n";
echo "   App Name: " . APP_NAME . "\n";
echo "   App Version: " . APP_VERSION . "\n";
echo "   Database: " . DB_PATH . "\n";
echo "   RSS Update Interval: " . RSS_UPDATE_INTERVAL . " minutes\n";
echo "   Session Lifetime: " . (SESSION_LIFETIME / 86400) . " days\n";
echo "\n";

// Next steps
echo "==============================================\n";
echo "  Initialization Complete!\n";
echo "==============================================\n\n";

echo "Next steps:\n";
echo "1. Access the application in your web browser\n";
echo "2. Login with default admin credentials\n";
echo "3. Change the admin password immediately\n";
echo "4. Add your RSS feeds\n";
echo "5. Set up cron job for automatic updates:\n";
echo "   */30 * * * * /usr/bin/php " . __DIR__ . "/cron/update_feeds.php >> " . __DIR__ . "/logs/cron.log 2>&1\n";
echo "\n";

echo "Documentation:\n";
echo "- Login page: /login.php\n";
echo "- Main page: /index.php\n";
echo "- Feed management: /feeds.php\n";
echo "- Admin panel: /admin.php\n";
echo "\n";

echo "Troubleshooting:\n";
echo "- Check logs in: " . __DIR__ . "/logs/\n";
echo "- Ensure .htaccess is properly configured\n";
echo "- Verify PHP extensions are loaded\n";
echo "\n";

echo "Happy RSS reading! 📰\n\n";

<?php
/**
 * Script de debug pour RSS Reader V2
 * Vérifie la configuration, la base de données, les flux et l'API
 *
 * Usage: php debug.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Session.php';

echo "\n";
echo "========================================\n";
echo "  RSS READER V2 - DEBUG\n";
echo "========================================\n\n";

$errors = 0;
$warnings = 0;

// ========================================
// 1. Configuration
// ========================================
echo "[1] Configuration\n";
echo "---\n";

if (defined('BASE_PATH')) {
    echo "✓ BASE_PATH: " . BASE_PATH . "\n";
} else {
    echo "✗ BASE_PATH non défini\n";
    $errors++;
}

if (defined('DB_PATH')) {
    echo "✓ DB_PATH: " . DB_PATH . "\n";
} else {
    echo "✗ DB_PATH non défini\n";
    $errors++;
}

if (defined('SECRET_KEY')) {
    echo "✓ SECRET_KEY: Défini (longueur: " . strlen(SECRET_KEY) . ")\n";
} else {
    echo "✗ SECRET_KEY non défini\n";
    $errors++;
}

echo "\n";

// ========================================
// 2. Base de données
// ========================================
echo "[2] Base de données\n";
echo "---\n";

if (!file_exists(DB_PATH)) {
    echo "✗ Base de données introuvable: " . DB_PATH . "\n";
    $errors++;
} else {
    echo "✓ Fichier base de données existe\n";

    $filesize = filesize(DB_PATH);
    echo "  Taille: " . formatBytes($filesize) . "\n";

    if (is_writable(DB_PATH)) {
        echo "✓ Droits en écriture OK\n";
    } else {
        echo "✗ Pas de droits en écriture\n";
        $errors++;
    }
}

try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    echo "✓ Connexion à la base de données OK\n";

    // Check tables
    $tables = ['users', 'sessions', 'feeds', 'articles', 'user_articles', 'meta'];
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        if (in_array($table, $existingTables)) {
            echo "✓ Table '{$table}' existe\n";
        } else {
            echo "✗ Table '{$table}' manquante\n";
            $errors++;
        }
    }
} catch (Exception $e) {
    echo "✗ Erreur de connexion: {$e->getMessage()}\n";
    $errors++;
}

echo "\n";

// ========================================
// 3. Utilisateurs
// ========================================
echo "[3] Utilisateurs\n";
echo "---\n";

try {
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll();

    echo "Nombre d'utilisateurs: " . count($users) . "\n";

    foreach ($users as $user) {
        echo "\nID: {$user['id']}\n";
        echo "Username: {$user['username']}\n";
        echo "Actif: " . ($user['active'] ? 'Oui' : 'Non') . "\n";
        echo "Dernière connexion: " . ($user['last_login'] ?: 'Jamais') . "\n";

        // Test password
        if ($user['username'] === 'admin') {
            $testPassword = 'admin123';
            if (password_verify($testPassword, $user['password'])) {
                echo "✓ Mot de passe admin123 fonctionne\n";
            } else {
                echo "✗ Mot de passe admin123 ne fonctionne PAS\n";
                echo "  Hash actuel: {$user['password']}\n";
                $errors++;
            }
        }
    }
} catch (Exception $e) {
    echo "✗ Erreur: {$e->getMessage()}\n";
    $errors++;
}

echo "\n";

// ========================================
// 4. Flux RSS
// ========================================
echo "[4] Flux RSS\n";
echo "---\n";

try {
    $stmt = $pdo->query("SELECT * FROM feeds");
    $feeds = $stmt->fetchAll();

    echo "Nombre de flux: " . count($feeds) . "\n\n";

    if (count($feeds) === 0) {
        echo "⚠ Aucun flux RSS configuré\n";
        echo "  Pour ajouter un flux: php manage_feeds.php add <url>\n";
        $warnings++;
    } else {
        foreach ($feeds as $feed) {
            echo "---\n";
            echo "ID: {$feed['id']}\n";
            echo "Titre: " . ($feed['title'] ?: 'Non défini') . "\n";
            echo "URL: {$feed['url']}\n";
            echo "Actif: " . ($feed['active'] ? 'Oui' : 'Non') . "\n";
            echo "Dernière mise à jour: " . ($feed['last_fetched'] ?: 'Jamais') . "\n";

            // Count articles
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE feed_id = ?");
            $stmt->execute([$feed['id']]);
            $count = $stmt->fetchColumn();
            echo "Articles: {$count}\n";

            if ($count === 0) {
                echo "⚠ Aucun article - Exécutez: php update_rss.php\n";
                $warnings++;
            }
        }
    }
} catch (Exception $e) {
    echo "✗ Erreur: {$e->getMessage()}\n";
    $errors++;
}

echo "\n";

// ========================================
// 5. Articles
// ========================================
echo "[5] Articles\n";
echo "---\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM articles");
    $totalArticles = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE is_read = 0");
    $unreadArticles = $stmt->fetchColumn();

    echo "Total d'articles: {$totalArticles}\n";
    echo "Articles non lus: {$unreadArticles}\n";

    if ($totalArticles === 0) {
        echo "⚠ Aucun article dans la base de données\n";
        echo "  1. Ajoutez des flux: php manage_feeds.php add <url>\n";
        echo "  2. Récupérez les articles: php update_rss.php\n";
        $warnings++;
    } else {
        // Show recent articles
        echo "\nDerniers articles:\n";
        $stmt = $pdo->query("
            SELECT a.id, a.title, a.published_date, f.title as feed_title
            FROM articles a
            LEFT JOIN feeds f ON a.feed_id = f.id
            ORDER BY a.published_date DESC
            LIMIT 5
        ");
        $articles = $stmt->fetchAll();

        foreach ($articles as $article) {
            echo "  [{$article['id']}] {$article['title']}\n";
            echo "      Flux: {$article['feed_title']}\n";
            echo "      Date: {$article['published_date']}\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Erreur: {$e->getMessage()}\n";
    $errors++;
}

echo "\n";

// ========================================
// 6. Test de l'API
// ========================================
echo "[6] Test de l'API\n";
echo "---\n";

// Test health check
$healthUrl = 'http://localhost/?health';
echo "Test: GET {$healthUrl}\n";

$ch = curl_init($healthUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✓ Health check OK (HTTP {$httpCode})\n";
    $data = json_decode($response, true);
    if ($data && isset($data['status'])) {
        echo "  Réponse: " . json_encode($data) . "\n";
    }
} else {
    echo "⚠ Health check non accessible (HTTP {$httpCode})\n";
    echo "  L'API web n'est peut-être pas accessible localement\n";
    $warnings++;
}

echo "\n";

// ========================================
// 7. Fichiers et permissions
// ========================================
echo "[7] Fichiers et permissions\n";
echo "---\n";

$files = [
    'config.php' => true,
    'Database.php' => true,
    'Session.php' => true,
    'index.php' => true,
    'api.php' => true,
    'views/login.php' => true,
    'views/dashboard.php' => true,
    'assets/style.css' => true,
    'assets/app.js' => true,
    '.htaccess' => false
];

foreach ($files as $file => $required) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $size = formatBytes(filesize($path));
        echo "✓ {$file} ({$size})\n";
    } else {
        if ($required) {
            echo "✗ {$file} MANQUANT\n";
            $errors++;
        } else {
            echo "⚠ {$file} manquant (optionnel)\n";
            $warnings++;
        }
    }
}

echo "\n";

// ========================================
// 8. Résumé
// ========================================
echo "========================================\n";
echo "  RÉSUMÉ\n";
echo "========================================\n";
echo "Erreurs: {$errors}\n";
echo "Avertissements: {$warnings}\n";

if ($errors === 0 && $warnings === 0) {
    echo "\n✓ Tout est OK ! L'application devrait fonctionner.\n";
} elseif ($errors === 0) {
    echo "\n⚠ Pas d'erreur critique, mais il y a des avertissements.\n";
} else {
    echo "\n✗ Il y a des erreurs à corriger.\n";
}

echo "\n";

// ========================================
// Helper functions
// ========================================
function formatBytes($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
    return round($bytes / 1048576, 2) . ' MB';
}

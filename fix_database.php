<?php
/**
 * Correction du schéma de la base de données
 * Ajoute les colonnes manquantes et corrige la structure
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

echo "\n";
echo "========================================\n";
echo "  CORRECTION DE LA BASE DE DONNÉES\n";
echo "========================================\n\n";

$db = Database::getInstance();
$pdo = $db->getPdo();

echo "Base de données: " . DB_PATH . "\n\n";

// Check current schema
echo "[1] Vérification du schéma actuel...\n";
echo "---\n";

$tables = ['users', 'sessions', 'feeds', 'articles', 'user_articles', 'meta'];
$fixes = [];

foreach ($tables as $table) {
    $stmt = $pdo->query("PRAGMA table_info({$table})");
    $columns = $stmt->fetchAll();

    echo "\nTable: {$table}\n";

    if (empty($columns)) {
        echo "  ✗ Table manquante\n";
        $fixes[] = "recreate_{$table}";
    } else {
        $columnNames = array_column($columns, 'name');
        echo "  Colonnes: " . implode(', ', $columnNames) . "\n";

        // Check specific missing columns
        switch ($table) {
            case 'feeds':
                if (!in_array('active', $columnNames)) {
                    echo "  ✗ Colonne 'active' manquante\n";
                    $fixes[] = "add_feeds_active";
                }
                if (!in_array('last_fetched', $columnNames)) {
                    echo "  ✗ Colonne 'last_fetched' manquante\n";
                    $fixes[] = "add_feeds_last_fetched";
                }
                break;

            case 'articles':
                if (!in_array('is_read', $columnNames)) {
                    echo "  ✗ Colonne 'is_read' manquante\n";
                    $fixes[] = "add_articles_is_read";
                }
                break;

            case 'users':
                if (!in_array('active', $columnNames)) {
                    echo "  ✗ Colonne 'active' manquante\n";
                    $fixes[] = "add_users_active";
                }
                break;
        }
    }
}

if (empty($fixes)) {
    echo "\n✓ Schéma OK, aucune correction nécessaire.\n\n";
    exit(0);
}

echo "\n";
echo "========================================\n";
echo "[2] Corrections à appliquer...\n";
echo "========================================\n";
foreach ($fixes as $fix) {
    echo "  - {$fix}\n";
}

echo "\nAppliquer les corrections? [Y/n] ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) === 'n') {
    die("\nAnnulé.\n\n");
}

echo "\n";
echo "========================================\n";
echo "[3] Application des corrections...\n";
echo "========================================\n\n";

try {
    $pdo->beginTransaction();

    foreach ($fixes as $fix) {
        echo "Correction: {$fix}...\n";

        switch ($fix) {
            case 'add_feeds_active':
                $pdo->exec("ALTER TABLE feeds ADD COLUMN active INTEGER DEFAULT 1");
                echo "  ✓ Colonne 'active' ajoutée à 'feeds'\n";
                break;

            case 'add_feeds_last_fetched':
                $pdo->exec("ALTER TABLE feeds ADD COLUMN last_fetched TIMESTAMP");
                echo "  ✓ Colonne 'last_fetched' ajoutée à 'feeds'\n";
                break;

            case 'add_articles_is_read':
                $pdo->exec("ALTER TABLE articles ADD COLUMN is_read INTEGER DEFAULT 0");
                echo "  ✓ Colonne 'is_read' ajoutée à 'articles'\n";
                break;

            case 'add_users_active':
                $pdo->exec("ALTER TABLE users ADD COLUMN active INTEGER DEFAULT 1");
                echo "  ✓ Colonne 'active' ajoutée à 'users'\n";
                break;

            case 'recreate_feeds':
                $pdo->exec("DROP TABLE IF EXISTS feeds");
                $pdo->exec("
                    CREATE TABLE feeds (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        url TEXT NOT NULL UNIQUE,
                        title TEXT,
                        active INTEGER DEFAULT 1,
                        last_fetched TIMESTAMP,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                echo "  ✓ Table 'feeds' recréée\n";
                break;

            case 'recreate_articles':
                $pdo->exec("DROP TABLE IF EXISTS articles");
                $pdo->exec("
                    CREATE TABLE articles (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        feed_id INTEGER NOT NULL,
                        title TEXT NOT NULL,
                        link TEXT NOT NULL,
                        description TEXT,
                        content TEXT,
                        published_date TIMESTAMP,
                        is_read INTEGER DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (feed_id) REFERENCES feeds(id) ON DELETE CASCADE,
                        UNIQUE(feed_id, link)
                    )
                ");
                echo "  ✓ Table 'articles' recréée\n";
                break;

            case 'recreate_users':
                $pdo->exec("DROP TABLE IF EXISTS users");
                $pdo->exec("
                    CREATE TABLE users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        username TEXT NOT NULL UNIQUE,
                        password TEXT NOT NULL,
                        active INTEGER DEFAULT 1,
                        last_login TIMESTAMP,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");

                // Re-create admin user
                $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO users (username, password, active) VALUES (?, ?, 1)")
                    ->execute(['admin', $hashedPassword]);
                echo "  ✓ Table 'users' recréée avec admin/admin123\n";
                break;

            case 'recreate_sessions':
                $pdo->exec("DROP TABLE IF EXISTS sessions");
                $pdo->exec("
                    CREATE TABLE sessions (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        token TEXT NOT NULL UNIQUE,
                        expires_at TIMESTAMP NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    )
                ");
                echo "  ✓ Table 'sessions' recréée\n";
                break;

            case 'recreate_user_articles':
                $pdo->exec("DROP TABLE IF EXISTS user_articles");
                $pdo->exec("
                    CREATE TABLE user_articles (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        article_id INTEGER NOT NULL,
                        is_read INTEGER DEFAULT 0,
                        is_starred INTEGER DEFAULT 0,
                        read_at TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
                        UNIQUE(user_id, article_id)
                    )
                ");
                echo "  ✓ Table 'user_articles' recréée\n";
                break;

            case 'recreate_meta':
                $pdo->exec("DROP TABLE IF EXISTS meta");
                $pdo->exec("
                    CREATE TABLE meta (
                        key TEXT PRIMARY KEY,
                        value TEXT,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                $pdo->exec("INSERT OR REPLACE INTO meta (key, value) VALUES ('schema_version', '2')");
                echo "  ✓ Table 'meta' recréée\n";
                break;

            default:
                echo "  ⚠ Correction inconnue: {$fix}\n";
        }
    }

    $pdo->commit();

    echo "\n";
    echo "========================================\n";
    echo "✅ CORRECTIONS APPLIQUÉES\n";
    echo "========================================\n\n";

    // Show updated schema
    echo "Schéma mis à jour:\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("PRAGMA table_info({$table})");
        $columns = $stmt->fetchAll();
        if (!empty($columns)) {
            $columnNames = array_column($columns, 'name');
            echo "  {$table}: " . implode(', ', $columnNames) . "\n";
        }
    }

    echo "\n";
    echo "Prochaines étapes:\n";
    echo "  1. php add_test_article.php    # Tester avec des articles de test\n";
    echo "  2. php add_sample_feeds.php    # Ajouter un vrai flux RSS\n";
    echo "  3. Allez sur http://dusselle.fr/ (admin/admin123)\n";
    echo "\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n✗ ERREUR: {$e->getMessage()}\n\n";
    exit(1);
}

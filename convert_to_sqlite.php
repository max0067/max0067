<?php
/**
 * Conversion de l'application de MySQL vers SQLite
 * Plus simple, pas besoin de mot de passe
 */

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "  CONVERSION VERS SQLite\n";
echo "  Rendre l'application plus simple (sans MySQL)\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$baseDir = __DIR__;

echo "SQLite vs MySQL:\n";
echo "  ✓ Pas de mot de passe à configurer\n";
echo "  ✓ Pas de création de base de données\n";
echo "  ✓ Fichier unique (rss_legal.db)\n";
echo "  ✓ Même fonctionnalités\n";
echo "  ✓ Parfait pour cette application\n\n";

echo "Continuer la conversion? [Y/n] ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) === 'n') {
    die("\n❌ Annulé.\n\n");
}

echo "\n[1/4] Création du fichier config.php pour SQLite...\n";

$configContent = <<<'CONFIG'
<?php
/**
 * Configuration de l'application RSS Legal Watch
 * Base de données: SQLite (pas besoin de MySQL)
 */

// Configuration de la base de données SQLite
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/rss_legal.db');

// Configuration de l'application
define('APP_NAME', 'RSS Legal Watch');
define('APP_URL', 'https://dusselle.fr');
define('TIMEZONE', 'Europe/Paris');

// Paramètres de pagination
define('ARTICLES_PER_PAGE', 20);

// Paramètres de rafraîchissement
define('REFRESH_INTERVAL', 21600); // 6 heures en secondes

// Fuseau horaire
date_default_timezone_set(TIMEZONE);

// Gestion des erreurs
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log');

CONFIG;

file_put_contents($baseDir . '/config.php', $configContent);
echo "✓ config.php créé\n\n";

echo "[2/4] Adaptation de Database.php pour SQLite...\n";

$databaseContent = <<<'PHP'
<?php
/**
 * Classe de gestion de la base de données SQLite
 * Pattern Singleton pour une seule connexion
 */

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $this->pdo = new PDO('sqlite:' . DB_PATH, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Optimisations SQLite
            $this->pdo->exec('PRAGMA journal_mode = WAL');
            $this->pdo->exec('PRAGMA synchronous = NORMAL');
            $this->pdo->exec('PRAGMA foreign_keys = ON');

        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    // Méthode utilitaire pour exécuter une requête
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Méthode pour récupérer tous les résultats
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    // Méthode pour récupérer un seul résultat
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    // Méthode pour obtenir le dernier ID inséré
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}

PHP;

file_put_contents($baseDir . '/includes/Database.php', $databaseContent);
echo "✓ Database.php adapté pour SQLite\n\n";

echo "[3/4] Adaptation du script d'installation...\n";

$setupContent = <<<'PHP'
<?php
/**
 * Script d'installation de la base de données SQLite
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/Database.php';

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Installation - RSS Legal Watch</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='row justify-content-center'>
            <div class='col-md-8'>
                <div class='card shadow'>
                    <div class='card-header bg-primary text-white'>
                        <h3 class='mb-0'>📦 Installation de RSS Legal Watch (SQLite)</h3>
                    </div>
                    <div class='card-body'>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "<div class='alert alert-success'>✓ Connexion à SQLite réussie</div>";
    echo "<p>Base de données: " . DB_PATH . "</p>";

    // Création de la table rss_feeds
    echo "<h5>Création de la table 'rss_feeds'...</h5>";

    $sqlFeeds = "CREATE TABLE IF NOT EXISTS rss_feeds (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        url TEXT NOT NULL UNIQUE,
        category TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sqlFeeds);
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_feeds_category ON rss_feeds(category)");
    echo "<div class='alert alert-info'>✓ Table 'rss_feeds' créée</div>";

    // Création de la table articles
    echo "<h5>Création de la table 'articles'...</h5>";

    $sqlArticles = "CREATE TABLE IF NOT EXISTS articles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        feed_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        link TEXT NOT NULL,
        pub_date DATETIME NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (feed_id) REFERENCES rss_feeds(id) ON DELETE CASCADE,
        UNIQUE(feed_id, link)
    )";

    $pdo->exec($sqlArticles);
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_articles_pub_date ON articles(pub_date)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_articles_feed_id ON articles(feed_id)");
    echo "<div class='alert alert-info'>✓ Table 'articles' créée</div>";

    // Ajout de quelques flux RSS de démo
    echo "<h5>Ajout de flux RSS de démonstration...</h5>";

    $demoFeeds = [
        [
            'name' => 'Légifrance - Actualités',
            'url' => 'https://www.legifrance.gouv.fr/rss/actualite',
            'category' => 'Législation'
        ],
        [
            'name' => 'Conseil d\'État - Décisions',
            'url' => 'https://www.conseil-etat.fr/ressources/decisions-contentieuses/rss',
            'category' => 'Jurisprudence'
        ],
        [
            'name' => 'Dalloz Actualité',
            'url' => 'https://www.dalloz-actualite.fr/rss.xml',
            'category' => 'Actualité juridique'
        ]
    ];

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO rss_feeds (name, url, category) VALUES (?, ?, ?)");

    foreach ($demoFeeds as $feed) {
        try {
            $stmt->execute([$feed['name'], $feed['url'], $feed['category']]);
            echo "<div class='text-success'>→ {$feed['name']}</div>";
        } catch (PDOException $e) {
            echo "<div class='text-warning'>⊙ {$feed['name']} (déjà existant)</div>";
        }
    }

    echo "
                    <div class='alert alert-success mt-4'>
                        <h4>✅ Installation terminée avec succès !</h4>
                        <p>La base de données SQLite a été configurée correctement.</p>
                        <hr>
                        <p class='mb-0'>
                            <a href='../index.php' class='btn btn-primary'>Accéder à l'application</a>
                            <a href='../feeds.php' class='btn btn-secondary'>Gérer les flux</a>
                        </p>
                    </div>";

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Erreur lors de l'installation</h4>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>";

PHP;

file_put_contents($baseDir . '/install/setup.php', $setupContent);
echo "✓ install/setup.php adapté\n\n";

echo "[4/4] Test de la configuration...\n";

try {
    require_once $baseDir . '/config.php';
    require_once $baseDir . '/includes/Database.php';

    $db = Database::getInstance();
    echo "✓ Connexion SQLite fonctionnelle\n";
    echo "✓ Base de données: " . DB_PATH . "\n\n";

} catch (Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n\n";
}

echo "════════════════════════════════════════════════════════════════\n";
echo "✅ CONVERSION TERMINÉE !\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "Prochaines étapes:\n\n";

echo "1. Installer la base de données:\n";
echo "   https://dusselle.fr/install/setup.php\n\n";

echo "2. Accéder à l'application:\n";
echo "   https://dusselle.fr/\n\n";

echo "3. Gérer les flux RSS:\n";
echo "   https://dusselle.fr/feeds.php\n\n";

echo "💡 Avantage: Plus besoin de mot de passe MySQL !\n\n";

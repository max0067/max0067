<?php
/**
 * Test rapide de connexion MySQL
 */

echo "\n════════════════════════════════════════════════════════════════\n";
echo "  TEST DE CONNEXION MySQL\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Tester avec les informations que vous avez fournies
$configs = [
    [
        'host' => 'localhost',
        'name' => 'wrbh3411_rss_legal',
        'user' => 'wrbh3411_rss_legal',
        'pass' => 'leajetaime',
        'label' => 'Config utilisateur = nom de la base'
    ],
    [
        'host' => 'localhost',
        'name' => 'wrbh3411_rss_legal',
        'user' => 'wrbh3411',
        'pass' => 'leajetaime',
        'label' => 'Config utilisateur = compte principal'
    ]
];

$workingConfig = null;

foreach ($configs as $index => $config) {
    echo "[Test #" . ($index + 1) . "] {$config['label']}\n";
    echo "  Host: {$config['host']}\n";
    echo "  Database: {$config['name']}\n";
    echo "  User: {$config['user']}\n";
    echo "  Pass: " . str_repeat('*', strlen($config['pass'])) . "\n\n";

    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        echo "✅ CONNEXION RÉUSSIE !\n\n";

        // Vérifier les tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tables)) {
            echo "  ℹ️  Base de données vide (pas de tables)\n";
        } else {
            echo "  ✓ Tables trouvées: " . implode(', ', $tables) . "\n";
        }

        $workingConfig = $config;
        echo "\n";
        break;

    } catch (PDOException $e) {
        echo "❌ Échec: " . $e->getMessage() . "\n\n";
    }
}

if ($workingConfig) {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "✅ Configuration fonctionnelle trouvée !\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    echo "Voulez-vous créer config.php avec cette configuration? [Y/n] ";
    $answer = trim(fgets(STDIN));

    if (strtolower($answer) !== 'n') {
        $configContent = <<<CONFIG
<?php
/**
 * Configuration de l'application RSS Legal Watch
 * Base de données MySQL
 */

// Configuration de la base de données MySQL
define('DB_HOST', '{$workingConfig['host']}');
define('DB_NAME', '{$workingConfig['name']}');
define('DB_USER', '{$workingConfig['user']}');
define('DB_PASS', '{$workingConfig['pass']}');

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

        file_put_contents(__DIR__ . '/config.php', $configContent);
        chmod(__DIR__ . '/config.php', 0640);

        echo "\n✅ Fichier config.php créé avec succès !\n\n";

        echo "════════════════════════════════════════════════════════════════\n";
        echo "PROCHAINES ÉTAPES\n";
        echo "════════════════════════════════════════════════════════════════\n\n";

        echo "1. Installer les tables de la base de données:\n";
        echo "   👉 https://dusselle.fr/install/setup.php\n\n";

        echo "2. Accéder à l'application:\n";
        echo "   👉 https://dusselle.fr/\n\n";

        echo "3. Gérer les flux RSS:\n";
        echo "   👉 https://dusselle.fr/feeds.php\n\n";

    } else {
        echo "\n⊙ config.php non créé.\n\n";
    }

} else {
    echo "════════════════════════════════════════════════════════════════\n";
    echo "❌ Aucune configuration fonctionnelle trouvée\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    echo "Suggestions:\n\n";

    echo "1. Vérifiez dans cPanel > MySQL Databases:\n";
    echo "   • Le nom exact de l'utilisateur MySQL\n";
    echo "   • Le nom exact de la base de données\n";
    echo "   • Que l'utilisateur a accès à cette base\n\n";

    echo "2. Ou créez un nouvel utilisateur MySQL dédié:\n";
    echo "   • cPanel > MySQL Databases\n";
    echo "   • Add New User: rss_user\n";
    echo "   • Add User To Database\n\n";

    echo "3. Alternative: Utilisez SQLite (plus simple):\n";
    echo "   • php convert_to_sqlite.php\n\n";
}

echo "\n";

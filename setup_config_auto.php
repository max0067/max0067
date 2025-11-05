<?php
/**
 * Script automatique pour créer config.php
 * Utilise les valeurs par défaut pour o2switch
 *
 * IMPORTANT: Vous devez avoir créé la base de données MySQL avant:
 * - Nom: wrbh3411_rss_legal
 * - Via cPanel > MySQL Databases
 */

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "  CONFIGURATION AUTOMATIQUE\n";
echo "  RSS Legal Watch - MySQL\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$baseDir = __DIR__;
$configPath = $baseDir . '/config.php';

// Valeurs par défaut pour o2switch
$dbHost = 'localhost';
$dbName = 'wrbh3411_rss_legal';
$dbUser = 'wrbh3411';

// Demander UNIQUEMENT le mot de passe MySQL
echo "Pour continuer, j'ai besoin du mot de passe MySQL.\n\n";
echo "Mot de passe MySQL pour l'utilisateur '{$dbUser}': ";

// Désactiver l'affichage du mot de passe
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $dbPass = trim(fgets(STDIN));
} else {
    system('stty -echo');
    $dbPass = trim(fgets(STDIN));
    system('stty echo');
    echo "\n";
}

if (empty($dbPass)) {
    die("\n❌ Le mot de passe ne peut pas être vide.\n\n");
}

echo "\n";
echo "Configuration qui sera utilisée:\n";
echo "  • Hôte: {$dbHost}\n";
echo "  • Base: {$dbName}\n";
echo "  • User: {$dbUser}\n";
echo "  • Pass: " . str_repeat('*', strlen($dbPass)) . "\n\n";

// Sauvegarder l'ancien config.php s'il existe
if (file_exists($configPath)) {
    $backupPath = $baseDir . '/config.php.old';
    copy($configPath, $backupPath);
    echo "✓ Ancien config.php → config.php.old\n\n";
}

// Tester la connexion AVANT de créer le fichier
echo "Test de connexion MySQL...\n";

try {
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✓ Connexion MySQL réussie !\n\n";
} catch (PDOException $e) {
    echo "✗ Erreur de connexion: " . $e->getMessage() . "\n\n";

    echo "═══════════════════════════════════════════════════════════\n";
    echo "ERREUR: Impossible de se connecter à MySQL\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "Vérifications à faire:\n\n";

    echo "1️⃣  La base de données existe-t-elle?\n";
    echo "   • Via cPanel > MySQL Databases\n";
    echo "   • Créez la base: wrbh3411_rss_legal\n\n";

    echo "2️⃣  L'utilisateur a-t-il accès à cette base?\n";
    echo "   • Via cPanel > MySQL Databases\n";
    echo "   • Ajoutez l'utilisateur wrbh3411 à la base\n\n";

    echo "3️⃣  Le mot de passe est-il correct?\n";
    echo "   • Vérifiez votre mot de passe MySQL\n\n";

    exit(1);
}

// Créer le fichier config.php
$configContent = <<<CONFIG
<?php
/**
 * Configuration de l'application RSS Legal Watch
 * Généré automatiquement le {date('Y-m-d H:i:s')}
 */

// Configuration de la base de données MySQL
define('DB_HOST', '{$dbHost}');
define('DB_NAME', '{$dbName}');
define('DB_USER', '{$dbUser}');
define('DB_PASS', '{$dbPass}');

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

file_put_contents($configPath, $configContent);
chmod($configPath, 0640);

echo "════════════════════════════════════════════════════════════════\n";
echo "✅ FICHIER config.php CRÉÉ AVEC SUCCÈS !\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Vérifier si les tables existent
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($tables)) {
    echo "⚠️  La base de données est vide (aucune table).\n\n";

    echo "════════════════════════════════════════════════════════════════\n";
    echo "PROCHAINE ÉTAPE: Installer les tables\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    echo "Dans votre navigateur, allez sur:\n";
    echo "  👉 https://dusselle.fr/install/setup.php\n\n";

    echo "Cette page va créer les tables 'rss_feeds' et 'articles'.\n\n";

} else {
    echo "✓ Tables existantes: " . implode(', ', $tables) . "\n\n";

    echo "════════════════════════════════════════════════════════════════\n";
    echo "CONFIGURATION TERMINÉE !\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    echo "Vous pouvez maintenant accéder à:\n";
    echo "  🏠 Dashboard: https://dusselle.fr/\n";
    echo "  📋 Gestion des flux: https://dusselle.fr/feeds.php\n\n";
}

<?php
/**
 * Script pour créer le fichier config.php avec les bons paramètres MySQL
 * Usage: php create_config.php
 */

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "  CRÉATION DU FICHIER config.php\n";
echo "  RSS Legal Watch - Configuration MySQL\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$baseDir = __DIR__;
$configPath = $baseDir . '/config.php';

// Sauvegarder l'ancien config.php s'il existe
if (file_exists($configPath)) {
    $backupPath = $baseDir . '/config.php.backup_' . date('Ymd_His');
    copy($configPath, $backupPath);
    echo "✓ Ancien config.php sauvegardé: " . basename($backupPath) . "\n\n";
}

// Demander les informations de connexion MySQL
echo "Veuillez entrer les informations de connexion MySQL:\n\n";

echo "Hôte de la base de données [localhost]: ";
$dbHost = trim(fgets(STDIN));
if (empty($dbHost)) {
    $dbHost = 'localhost';
}

echo "Nom de la base de données [wrbh3411_rss_legal]: ";
$dbName = trim(fgets(STDIN));
if (empty($dbName)) {
    $dbName = 'wrbh3411_rss_legal';
}

echo "Utilisateur MySQL [wrbh3411]: ";
$dbUser = trim(fgets(STDIN));
if (empty($dbUser)) {
    $dbUser = 'wrbh3411';
}

echo "Mot de passe MySQL: ";
// Désactiver l'affichage du mot de passe
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $dbPass = trim(fgets(STDIN));
} else {
    system('stty -echo');
    $dbPass = trim(fgets(STDIN));
    system('stty echo');
    echo "\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "Récapitulatif de la configuration:\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "Hôte: {$dbHost}\n";
echo "Base de données: {$dbName}\n";
echo "Utilisateur: {$dbUser}\n";
echo "Mot de passe: " . str_repeat('*', strlen($dbPass)) . "\n\n";

echo "Confirmer? [y/N] ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) !== 'y') {
    die("\n❌ Annulé.\n\n");
}

// Créer le contenu du fichier config.php
$configContent = <<<CONFIG
<?php
/**
 * Configuration de l'application RSS Legal Watch
 * Base de données MySQL
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

// Gestion des erreurs (désactiver en production)
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log');

CONFIG;

// Écrire le fichier
file_put_contents($configPath, $configContent);
chmod($configPath, 0640); // Permissions restrictives pour la sécurité

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "✅ FICHIER config.php CRÉÉ AVEC SUCCÈS !\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "Fichier: {$configPath}\n";
echo "Permissions: 0640 (lecture/écriture propriétaire uniquement)\n\n";

// Tester la connexion à la base de données
echo "════════════════════════════════════════════════════════════════\n";
echo "Test de connexion à la base de données...\n";
echo "════════════════════════════════════════════════════════════════\n\n";

try {
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ Connexion à MySQL réussie !\n\n";

    // Vérifier si les tables existent
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "⚠️  La base de données est vide.\n";
        echo "   Prochaine étape: https://dusselle.fr/install/setup.php\n\n";
    } else {
        echo "✓ Tables trouvées: " . implode(', ', $tables) . "\n\n";
    }

} catch (PDOException $e) {
    echo "❌ Erreur de connexion: " . $e->getMessage() . "\n\n";
    echo "Vérifiez:\n";
    echo "  1. Que la base de données '{$dbName}' existe\n";
    echo "  2. Que l'utilisateur '{$dbUser}' a les droits sur cette base\n";
    echo "  3. Que le mot de passe est correct\n\n";
    exit(1);
}

echo "════════════════════════════════════════════════════════════════\n";
echo "Prochaines étapes:\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "1. Installer la base de données:\n";
echo "   https://dusselle.fr/install/setup.php\n\n";

echo "2. Accéder à l'application:\n";
echo "   https://dusselle.fr/\n\n";

echo "3. Gérer les flux RSS:\n";
echo "   https://dusselle.fr/feeds.php\n\n";

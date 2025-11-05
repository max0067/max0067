<?php
/**
 * Script pour trouver les informations MySQL correctes
 * Teste différentes combinaisons
 */

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "  DIAGNOSTIC MySQL - Recherche des bonnes informations\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Informations depuis l'environnement
$homeDir = getenv('HOME');
$user = posix_getpwuid(posix_geteuid())['name'];

echo "Informations système:\n";
echo "  • Utilisateur système: {$user}\n";
echo "  • Home directory: {$homeDir}\n\n";

echo "════════════════════════════════════════════════════════════════\n";
echo "MÉTHODE 1: Chercher dans les fichiers de configuration\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Chercher des fichiers de config existants
$configFiles = [
    $homeDir . '/.my.cnf',
    $homeDir . '/public_html/.my.cnf',
    $homeDir . '/public_html/wp-config.php',
    $homeDir . '/public_html/configuration.php',
    '/home/wrbh3411/.my.cnf'
];

$foundConfigs = [];

foreach ($configFiles as $file) {
    if (file_exists($file)) {
        echo "✓ Trouvé: {$file}\n";
        $foundConfigs[] = $file;

        // Essayer de lire (si permissions le permettent)
        $content = @file_get_contents($file);
        if ($content) {
            // Chercher des indices de DB
            if (preg_match('/DB_USER["\']?\s*[=:]\s*["\']?(\w+)/i', $content, $matches)) {
                echo "  → DB_USER trouvé: {$matches[1]}\n";
            }
            if (preg_match('/DB_NAME["\']?\s*[=:]\s*["\']?([\w_]+)/i', $content, $matches)) {
                echo "  → DB_NAME trouvé: {$matches[1]}\n";
            }
        }
    }
}

if (empty($foundConfigs)) {
    echo "✗ Aucun fichier de configuration trouvé\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "MÉTHODE 2: Lister les bases de données accessibles\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "Pour connaître vos informations MySQL exactes:\n\n";

echo "Option A - Via cPanel:\n";
echo "  1. Connectez-vous à cPanel\n";
echo "  2. Allez dans 'MySQL Databases'\n";
echo "  3. Notez:\n";
echo "     • Le nom d'utilisateur MySQL (probablement: wrbh3411)\n";
echo "     • Les bases de données existantes\n";
echo "  4. Si la base 'wrbh3411_rss_legal' n'existe pas, créez-la\n";
echo "  5. Ajoutez l'utilisateur à cette base (privilèges: ALL)\n\n";

echo "Option B - Tester manuellement:\n";
echo "  mysql -u wrbh3411 -p\n";
echo "  (Entrez le mot de passe MySQL quand demandé)\n\n";

echo "Option C - Voir le mot de passe MySQL dans cPanel:\n";
echo "  1. cPanel > MySQL Databases\n";
echo "  2. Section 'Current Users'\n";
echo "  3. Cliquez sur 'Change Password' pour réinitialiser si nécessaire\n\n";

echo "════════════════════════════════════════════════════════════════\n";
echo "MÉTHODE 3: Créer un nouvel utilisateur MySQL dédié\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "Via cPanel > MySQL Databases:\n\n";

echo "1. Créer une nouvelle base:\n";
echo "   Nom: rss_legal\n";
echo "   (Le préfixe sera ajouté automatiquement: wrbh3411_rss_legal)\n\n";

echo "2. Créer un nouvel utilisateur:\n";
echo "   Username: rss_user\n";
echo "   Password: [générer un mot de passe fort]\n";
echo "   (Le préfixe sera ajouté: wrbh3411_rss_user)\n\n";

echo "3. Ajouter l'utilisateur à la base:\n";
echo "   User: wrbh3411_rss_user\n";
echo "   Database: wrbh3411_rss_legal\n";
echo "   Privileges: ALL PRIVILEGES\n\n";

echo "════════════════════════════════════════════════════════════════\n";
echo "SOLUTION ALTERNATIVE: Utiliser SQLite au lieu de MySQL\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "Si vous avez des difficultés avec MySQL, je peux adapter\n";
echo "l'application pour utiliser SQLite (comme la V2 précédente).\n\n";

echo "Avantages de SQLite:\n";
echo "  ✓ Pas besoin de mot de passe\n";
echo "  ✓ Pas de configuration MySQL\n";
echo "  ✓ Fichier unique (rss_legal.db)\n";
echo "  ✓ Fonctionne immédiatement\n\n";

echo "Voulez-vous que je crée une version SQLite? [y/N] ";
$answer = trim(fgets(STDIN));

if (strtolower($answer) === 'y') {
    echo "\n✓ Je vais créer les fichiers pour SQLite...\n\n";

    // Créer un marqueur pour indiquer qu'on veut SQLite
    file_put_contents(__DIR__ . '/.use_sqlite', 'yes');

    echo "Exécutez maintenant:\n";
    echo "  php convert_to_sqlite.php\n\n";
} else {
    echo "\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "RÉSUMÉ - Actions à faire:\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    echo "1. Trouvez votre mot de passe MySQL:\n";
    echo "   • Via cPanel > MySQL Databases > Change Password\n";
    echo "   • Ou testez avec: mysql -u wrbh3411 -p\n\n";

    echo "2. Créez la base de données:\n";
    echo "   • cPanel > MySQL Databases\n";
    echo "   • Créer: wrbh3411_rss_legal\n\n";

    echo "3. Re-exécutez:\n";
    echo "   • php setup_config_auto.php\n\n";
}

echo "\n";

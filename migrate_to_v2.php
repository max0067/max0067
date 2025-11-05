<?php
/**
 * Migration V1 -> V2 sur la racine du site
 * Sauvegarde V1 et installe V2 à la place
 *
 * Usage: php migrate_to_v2.php
 */

echo "========================================\n";
echo "Migration RSS Reader V1 -> V2\n";
echo "========================================\n\n";

// Vérifier qu'on est dans public_html
if (!file_exists('index.php')) {
    die("ERREUR: Ce script doit être exécuté depuis ~/public_html\n");
}

// Créer une sauvegarde de V1
echo "[1/12] Création d'une sauvegarde de V1...\n";
$backupName = 'backup_v1_' . date('Ymd_His') . '.tar.gz';
echo "Nom de la sauvegarde: {$backupName}\n";

$filesToBackup = [
    'app_v2.py',
    'passenger_wsgi.py',
    'config.py',
    'config_dusselle.py',
    'index.php',
    'php/',
    'templates/',
    'static/',
    '.htaccess',
    'rss_feeds.db'
];

$backupList = '';
foreach ($filesToBackup as $file) {
    if (file_exists($file)) {
        $backupList .= " {$file}";
    }
}

if (!empty($backupList)) {
    exec("tar -czf {$backupName} {$backupList} 2>/dev/null", $output, $returnCode);
    if ($returnCode === 0 && file_exists($backupName)) {
        echo "✓ Sauvegarde créée: {$backupName}\n";
    } else {
        echo "⚠ Attention: La sauvegarde a échoué, continuation...\n";
    }
} else {
    echo "⚠ Aucun fichier V1 à sauvegarder\n";
}

// Désactiver les anciens fichiers Python
echo "\n[2/12] Désactivation des fichiers Python...\n";
if (file_exists('app_v2.py')) {
    rename('app_v2.py', 'app_v2.py.disabled');
    echo "✓ app_v2.py désactivé\n";
}
if (file_exists('passenger_wsgi.py')) {
    rename('passenger_wsgi.py', 'passenger_wsgi.py.disabled');
    echo "✓ passenger_wsgi.py désactivé\n";
}

// Sauvegarder l'ancien .htaccess
echo "\n[3/12] Sauvegarde de l'ancien .htaccess...\n";
if (file_exists('.htaccess')) {
    rename('.htaccess', '.htaccess_v1_backup');
    echo "✓ .htaccess sauvegardé\n";
}

// Créer la structure V2
echo "\n[4/12] Création de la structure V2...\n";
@mkdir('views', 0755);
@mkdir('assets', 0755);
@mkdir('data', 0755);
@mkdir('logs', 0755);
@mkdir('cache', 0755);
echo "✓ Dossiers créés\n";

// URLs des fichiers sur GitHub
$baseUrl = 'https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/v2_modern/';

$files = [
    'config.php' => 'config.php',
    'Database.php' => 'Database.php',
    'Session.php' => 'Session.php',
    'index.php' => 'index.php',
    'api.php' => 'api.php',
    '.htaccess' => '.htaccess',
    'views/login.php' => 'views/login.php',
    'views/dashboard.php' => 'views/dashboard.php',
    'assets/style.css' => 'assets/style.css',
    'assets/app.js' => 'assets/app.js',
];

// Télécharger les fichiers
$step = 5;
foreach ($files as $localPath => $remotePath) {
    echo "\n[{$step}/12] Téléchargement de {$localPath}...\n";
    $content = @file_get_contents($baseUrl . $remotePath);

    if ($content === false) {
        echo "✗ Erreur lors du téléchargement de {$localPath}\n";
        continue;
    }

    // Ajuster les chemins /v2/ vers / pour la racine
    if (strpos($localPath, '.htaccess') !== false) {
        $content = str_replace('RewriteBase /v2/', 'RewriteBase /', $content);
        $content = str_replace('RewriteCond %{REQUEST_URI} !^/v2/assets/', 'RewriteCond %{REQUEST_URI} !^/assets/', $content);
    }

    if (strpos($localPath, 'views/') !== false) {
        $content = str_replace('href="/v2/assets/', 'href="/assets/', $content);
        $content = str_replace('src="/v2/assets/', 'src="/assets/', $content);
    }

    if (strpos($localPath, 'assets/app.js') !== false) {
        $content = str_replace("fetch('/v2/api", "fetch('/api", $content);
        $content = str_replace('window.location.href = \'/v2/\'', 'window.location.href = \'/\'', $content);
        $content = str_replace('window.location.href = \'/v2/login\'', 'window.location.href = \'/login\'', $content);
    }

    if (strpos($localPath, 'index.php') !== false) {
        $content = str_replace("\$path = str_replace('/v2/', '', \$path);", "", $content);
        $content = str_replace("\$path = str_replace('/v2', '', \$path);", "", $content);
    }

    file_put_contents($localPath, $content);
    echo "✓ {$localPath} installé et ajusté\n";
    $step++;
}

// Définir les permissions
echo "\n[11/12] Configuration des permissions...\n";
chmod('data', 0755);
chmod('logs', 0755);
chmod('cache', 0755);
@chmod('views', 0755);
@chmod('assets', 0755);
echo "✓ Permissions configurées\n";

// Vérifier la base de données
echo "\n[12/12] Vérification de la base de données...\n";
if (file_exists('rss_feeds.db')) {
    echo "✓ Base de données existante trouvée (rss_feeds.db)\n";
    echo "  Les données V1 seront conservées\n";
} else {
    echo "ℹ Nouvelle base de données sera créée au premier accès\n";
}

echo "\n========================================\n";
echo "✓ Migration terminée !\n";
echo "========================================\n\n";

echo "📁 Fichiers sauvegardés:\n";
echo "   - {$backupName}\n";
echo "   - .htaccess_v1_backup\n";
echo "   - app_v2.py.disabled\n";
echo "   - passenger_wsgi.py.disabled\n\n";

echo "🌐 Accès:\n";
echo "   URL: http://dusselle.fr/\n";
echo "   Login: admin / admin123\n\n";

echo "🔍 Test:\n";
echo "   curl http://dusselle.fr/?health\n\n";

echo "⚠️  IMPORTANT:\n";
echo "   - V1 est désactivé mais sauvegardé\n";
echo "   - V2 est maintenant actif sur la racine\n";
echo "   - Pour revenir à V1, restaurez {$backupName}\n\n";
?>

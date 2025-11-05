<?php
/**
 * Installation complète en 1 clic
 * Télécharge et installe tous les fichiers nécessaires
 */

set_time_limit(300);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Installation RSS Legal Watch</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#00ff00;}";
echo ".ok{color:#00ff00;} .warn{color:#ffaa00;} .error{color:#ff0000;}</style></head><body>";

echo "<h1>📦 Installation RSS Legal Watch</h1>";
echo "<pre>";

$baseUrl = "https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ";
$baseDir = __DIR__;

// Fonction pour télécharger un fichier
function downloadFile($url, $dest) {
    $content = @file_get_contents($url);
    if ($content === false) {
        return false;
    }

    $dir = dirname($dest);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($dest, $content);
    return true;
}

echo "<span class='ok'>════════════════════════════════════════════════════════════════</span>\n";
echo "<span class='ok'>[1/4] CRÉATION DE LA STRUCTURE</span>\n";
echo "<span class='ok'>════════════════════════════════════════════════════════════════</span>\n\n";

$dirs = ['includes', 'views', 'views/layout', 'assets', 'assets/css', 'assets/js', 'install'];

foreach ($dirs as $dir) {
    $path = $baseDir . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "✓ Créé: {$dir}/\n";
    } else {
        echo "⊙ Existe: {$dir}/\n";
    }
}

echo "\n<span class='ok'>════════════════════════════════════════════════════════════════</span>\n";
echo "<span class='ok'>[2/4] TÉLÉCHARGEMENT DES FICHIERS</span>\n";
echo "<span class='ok'>════════════════════════════════════════════════════════════════</span>\n\n";

$files = [
    'includes/Database.php',
    'includes/RSSFeed.php',
    'includes/Article.php',
    'includes/helpers.php',
    'views/layout/header.php',
    'views/layout/footer.php',
    'assets/css/style.css',
    'assets/js/app.js',
    'install/setup.php',
    'index_new.php',
    'feeds_new.php',
    'refresh_new.php',
    '.htaccess_new'
];

$success = 0;
$failed = 0;

foreach ($files as $file) {
    $url = $baseUrl . '/' . $file;
    $dest = $baseDir . '/' . $file;

    echo "Téléchargement: {$file}... ";
    flush();

    if (downloadFile($url, $dest)) {
        echo "<span class='ok'>✓</span>\n";
        $success++;
    } else {
        echo "<span class='error'>✗ ÉCHEC</span>\n";
        $failed++;
    }
}

echo "\n<span class='ok'>Réussis: {$success}</span> | ";
if ($failed > 0) {
    echo "<span class='error'>Échecs: {$failed}</span>\n";
} else {
    echo "Échecs: 0\n";
}

echo "\n<span class='ok'>════════════════════════════════════════════════════════════════</span>\n";
echo "<span class='ok'>[3/4] ACTIVATION DES FICHIERS</span>\n";
echo "<span class='ok'>════════════════════════════════════════════════════════════════</span>\n\n";

// Sauvegarder l'ancien index.php
if (file_exists($baseDir . '/index.php')) {
    rename($baseDir . '/index.php', $baseDir . '/index.php.v2_backup');
    echo "✓ Ancien index.php → index.php.v2_backup\n";
}

// Copier les nouveaux fichiers
$renames = [
    'index_new.php' => 'index.php',
    'feeds_new.php' => 'feeds.php',
    'refresh_new.php' => 'refresh.php',
    '.htaccess_new' => '.htaccess'
];

foreach ($renames as $source => $target) {
    if (file_exists($baseDir . '/' . $source)) {
        copy($baseDir . '/' . $source, $baseDir . '/' . $target);
        echo "✓ {$target}\n";
    } else {
        echo "<span class='warn'>⚠ {$source} manquant</span>\n";
    }
}

echo "\n<span class='ok'>════════════════════════════════════════════════════════════════</span>\n";
echo "<span class='ok'>[4/4] VÉRIFICATION</span>\n";
echo "<span class='ok'>════════════════════════════════════════════════════════════════</span>\n\n";

$required = [
    'config.php' => 'Configuration MySQL',
    'index.php' => 'Page d\'accueil',
    'feeds.php' => 'Gestion des flux',
    'refresh.php' => 'Actualisation',
    'includes/Database.php' => 'Classe Database',
    'includes/RSSFeed.php' => 'Classe RSSFeed',
    'includes/Article.php' => 'Classe Article',
    'install/setup.php' => 'Installation DB'
];

$allOk = true;

foreach ($required as $file => $desc) {
    if (file_exists($baseDir . '/' . $file)) {
        echo "✓ {$desc}: <span class='ok'>{$file}</span>\n";
    } else {
        echo "✗ {$desc}: <span class='error'>{$file} MANQUANT</span>\n";
        $allOk = false;
    }
}

echo "\n<span class='ok'>════════════════════════════════════════════════════════════════</span>\n";

if ($allOk) {
    echo "<span class='ok'>✅ INSTALLATION RÉUSSIE !</span>\n";
    echo "<span class='ok'>════════════════════════════════════════════════════════════════</span>\n\n";

    echo "Prochaines étapes:\n\n";

    if (!file_exists($baseDir . '/config.php')) {
        echo "<span class='warn'>⚠️  config.php n'existe pas encore</span>\n\n";
        echo "1. Créer config.php:\n";
        echo "   <a href='test_mysql_connection.php' style='color:#00aaff'>→ Cliquez ici pour configurer MySQL</a>\n\n";
    } else {
        echo "1. ✓ config.php existe\n\n";
    }

    echo "2. Installer la base de données:\n";
    echo "   <a href='install/setup.php' style='color:#00aaff'>→ https://dusselle.fr/install/setup.php</a>\n\n";

    echo "3. Accéder à l'application:\n";
    echo "   <a href='index.php' style='color:#00aaff'>→ https://dusselle.fr/</a>\n\n";

} else {
    echo "<span class='error'>❌ INSTALLATION INCOMPLÈTE</span>\n";
    echo "<span class='ok'>════════════════════════════════════════════════════════════════</span>\n\n";

    echo "<span class='warn'>Des fichiers sont manquants.</span>\n";
    echo "Rafraîchissez cette page pour réessayer.\n\n";
}

echo "</pre></body></html>";

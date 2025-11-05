<?php
/**
 * Script de déploiement automatique
 * RSS Legal Watch - Nouveau projet complet
 *
 * Usage: php deploy_rss_legal.php
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                                                              ║\n";
echo "║           📰 RSS LEGAL WATCH - DÉPLOIEMENT                   ║\n";
echo "║           Application de veille juridique moderne           ║\n";
echo "║                                                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$baseDir = __DIR__;
$errors = [];
$warnings = [];

// Confirmation
echo "⚠️  ATTENTION: Ce script va:\n";
echo "   1. Archiver tous les fichiers de l'ancienne version V2\n";
echo "   2. Créer la nouvelle structure du projet\n";
echo "   3. Déployer l'application RSS Legal Watch\n\n";

echo "Continuer? [y/N] ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) !== 'y') {
    die("\n❌ Déploiement annulé.\n\n");
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "[1/7] 📦 Archivage des anciens fichiers\n";
echo "════════════════════════════════════════════════════════════════\n";

// Créer un dossier d'archive
$archiveDir = $baseDir . '/archive_v2_' . date('Ymd_His');
mkdir($archiveDir, 0755, true);
echo "✓ Archive créée: " . basename($archiveDir) . "\n";

// Fichiers à archiver
$filesToArchive = [
    'install_v2_complete.php', 'install_v2_part2.php', 'install_v2_part3.php',
    'manage.php', 'api.php', 'api.php.old', 'api.php.backup', 'api_v2.php',
    'fix_database.php', 'fix_routing.php', 'add_test_article.php',
    'add_sample_feeds.php', 'add_feed_simple.php', 'manage_feeds.php',
    'update_rss.php', 'debug.php', 'test_simple.php', 'test_api.php',
    'show_schema.php', 'quick_setup.php', 'test_articles.html',
    'INSTALLATION.md', 'GUIDE_V2.md', 'index.php'
];

$archivedCount = 0;
foreach ($filesToArchive as $file) {
    $sourcePath = $baseDir . '/' . $file;
    if (file_exists($sourcePath)) {
        rename($sourcePath, $archiveDir . '/' . $file);
        $archivedCount++;
    }
}

// Archiver les dossiers V2
$dirsToArchive = ['views', 'assets'];
foreach ($dirsToArchive as $dir) {
    $sourcePath = $baseDir . '/' . $dir;
    if (is_dir($sourcePath)) {
        rename($sourcePath, $archiveDir . '/' . $dir);
    }
}

echo "✓ {$archivedCount} fichiers archivés\n\n";

echo "════════════════════════════════════════════════════════════════\n";
echo "[2/7] 📁 Création de la structure du projet\n";
echo "════════════════════════════════════════════════════════════════\n";

$newDirs = [
    'includes',
    'views',
    'views/layout',
    'assets',
    'assets/css',
    'assets/js',
    'install'
];

foreach ($newDirs as $dir) {
    $path = $baseDir . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "✓ {$dir}/\n";
    }
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "[3/7] ⚙️  Copie des nouveaux fichiers\n";
echo "════════════════════════════════════════════════════════════════\n";

// Renommer les fichiers _new en versions finales
$filesToRename = [
    'index_new.php' => 'index.php',
    'feeds_new.php' => 'feeds.php',
    'refresh_new.php' => 'refresh.php',
    '.htaccess_new' => '.htaccess',
    'README_NEW.md' => 'README.md'
];

foreach ($filesToRename as $old => $new) {
    $oldPath = $baseDir . '/' . $old;
    $newPath = $baseDir . '/' . $new;

    if (file_exists($oldPath)) {
        rename($oldPath, $newPath);
        echo "✓ {$new}\n";
    } else {
        $warnings[] = "Fichier manquant: {$old}";
    }
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "[4/7] 📝 Vérification des fichiers essentiels\n";
echo "════════════════════════════════════════════════════════════════\n";

$essentialFiles = [
    'config.example.php',
    'includes/Database.php',
    'includes/RSSFeed.php',
    'includes/Article.php',
    'includes/helpers.php',
    'install/setup.php',
    'views/layout/header.php',
    'views/layout/footer.php',
    'assets/css/style.css',
    'assets/js/app.js',
    'index.php',
    'feeds.php',
    'refresh.php',
    '.htaccess',
    'README.md'
];

$missing = [];
foreach ($essentialFiles as $file) {
    $path = $baseDir . '/' . $file;
    if (file_exists($path)) {
        echo "✓ {$file}\n";
    } else {
        echo "✗ {$file} MANQUANT\n";
        $missing[] = $file;
    }
}

if (!empty($missing)) {
    $errors[] = "Fichiers manquants: " . implode(', ', $missing);
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "[5/7] 🔐 Configuration des permissions\n";
echo "════════════════════════════════════════════════════════════════\n";

// Permissions des dossiers
$dirs = ['includes', 'views', 'assets', 'install'];
foreach ($dirs as $dir) {
    $path = $baseDir . '/' . $dir;
    if (is_dir($path)) {
        chmod($path, 0755);
        echo "✓ {$dir}/ → 755\n";
    }
}

// Permissions des fichiers PHP
$phpFiles = glob($baseDir . '/*.php');
foreach ($phpFiles as $file) {
    chmod($file, 0644);
}

echo "✓ Fichiers PHP → 644\n";

// .htaccess
if (file_exists($baseDir . '/.htaccess')) {
    chmod($baseDir . '/.htaccess', 0644);
    echo "✓ .htaccess → 644\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "[6/7] 🔧 Configuration de l'application\n";
echo "════════════════════════════════════════════════════════════════\n";

// Vérifier si config.php existe
if (!file_exists($baseDir . '/config.php')) {
    echo "⚠️  Le fichier config.php n'existe pas encore.\n";
    echo "   Vous devez le créer à partir de config.example.php\n\n";

    echo "   Commandes:\n";
    echo "   cp config.example.php config.php\n";
    echo "   nano config.php\n\n";

    echo "   Puis modifiez les paramètres de connexion MySQL.\n";
    $warnings[] = "Fichier config.php à créer et configurer";
} else {
    echo "✓ config.php existe déjà\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "[7/7] 📊 Résumé du déploiement\n";
echo "════════════════════════════════════════════════════════════════\n\n";

if (empty($errors)) {
    echo "✅ DÉPLOIEMENT RÉUSSI !\n\n";

    echo "📋 Prochaines étapes:\n\n";

    echo "1️⃣  Configurer la base de données:\n";
    if (!file_exists($baseDir . '/config.php')) {
        echo "   cp config.example.php config.php\n";
        echo "   nano config.php\n";
        echo "   (Modifiez DB_NAME, DB_USER, DB_PASS)\n\n";
    } else {
        echo "   ✓ config.php existe déjà\n\n";
    }

    echo "2️⃣  Installer la base de données:\n";
    echo "   Accédez à: https://dusselle.fr/install/setup.php\n\n";

    echo "3️⃣  Accéder à l'application:\n";
    echo "   https://dusselle.fr/\n\n";

    echo "4️⃣  Gérer les flux RSS:\n";
    echo "   https://dusselle.fr/feeds.php\n\n";

    echo "📖 Documentation complète:\n";
    echo "   Consultez README.md pour plus d'informations\n\n";

} else {
    echo "❌ ERREURS DÉTECTÉES\n\n";
    foreach ($errors as $error) {
        echo "   • {$error}\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  AVERTISSEMENTS\n\n";
    foreach ($warnings as $warning) {
        echo "   • {$warning}\n";
    }
    echo "\n";
}

echo "════════════════════════════════════════════════════════════════\n";
echo "Déploiement terminé - " . date('Y-m-d H:i:s') . "\n";
echo "════════════════════════════════════════════════════════════════\n\n";

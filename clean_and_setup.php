<?php
/**
 * Script de nettoyage et préparation pour le nouveau projet
 * À exécuter via: php clean_and_setup.php
 */

echo "\n";
echo "========================================\n";
echo "  NETTOYAGE ET PRÉPARATION\n";
echo "  RSS Legal Watch - Nouveau Projet\n";
echo "========================================\n\n";

$baseDir = __DIR__;

// 1. Créer un dossier d'archive pour les anciens fichiers
$archiveDir = $baseDir . '/archive_v2_' . date('Ymd_His');
echo "[1] Création du dossier d'archive...\n";
mkdir($archiveDir, 0755, true);
echo "✓ Archive: {$archiveDir}\n\n";

// 2. Fichiers à archiver (pas à supprimer)
echo "[2] Archivage des anciens fichiers V2...\n";

$filesToArchive = [
    'install_v2_complete.php',
    'install_v2_part2.php',
    'install_v2_part3.php',
    'manage.php',
    'api.php',
    'api.php.old',
    'api.php.backup',
    'api_v2.php',
    'fix_database.php',
    'fix_routing.php',
    'add_test_article.php',
    'add_sample_feeds.php',
    'add_feed_simple.php',
    'manage_feeds.php',
    'update_rss.php',
    'debug.php',
    'test_simple.php',
    'test_api.php',
    'show_schema.php',
    'quick_setup.php',
    'test_articles.html',
    'INSTALLATION.md',
    'GUIDE_V2.md'
];

$archivedCount = 0;
foreach ($filesToArchive as $file) {
    $sourcePath = $baseDir . '/' . $file;
    if (file_exists($sourcePath)) {
        $destPath = $archiveDir . '/' . $file;
        rename($sourcePath, $destPath);
        echo "  → {$file}\n";
        $archivedCount++;
    }
}

echo "✓ {$archivedCount} fichiers archivés\n\n";

// 3. Archiver les dossiers V2
echo "[3] Archivage des dossiers V2...\n";

$dirsToArchive = ['views', 'assets'];

foreach ($dirsToArchive as $dir) {
    $sourcePath = $baseDir . '/' . $dir;
    if (is_dir($sourcePath)) {
        $destPath = $archiveDir . '/' . $dir;
        rename($sourcePath, $destPath);
        echo "  → {$dir}/\n";
    }
}

echo "✓ Dossiers archivés\n\n";

// 4. Conserver certains fichiers essentiels
echo "[4] Fichiers conservés:\n";

$filesToKeep = [
    'config.php',
    'Database.php',
    'Session.php',
    'rss_feeds.db'
];

foreach ($filesToKeep as $file) {
    if (file_exists($baseDir . '/' . $file)) {
        echo "  ✓ {$file}\n";
    }
}

echo "\n";

// 5. Créer la nouvelle structure de dossiers
echo "[5] Création de la nouvelle structure...\n";

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
        echo "  ✓ {$dir}/\n";
    }
}

echo "\n";

echo "========================================\n";
echo "✅ PRÉPARATION TERMINÉE\n";
echo "========================================\n\n";

echo "Résumé:\n";
echo "  • Archive créée: " . basename($archiveDir) . "/\n";
echo "  • Anciens fichiers archivés: {$archivedCount}\n";
echo "  • Nouvelle structure créée\n\n";

echo "Prochaine étape:\n";
echo "  Les nouveaux fichiers du projet vont être créés.\n\n";

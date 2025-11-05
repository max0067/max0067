<?php
/**
 * Déploiement rapide des fichiers manquants
 * Sans archivage, juste copier les nouveaux fichiers
 */

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "  DÉPLOIEMENT RAPIDE - RSS Legal Watch\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$baseDir = __DIR__;

// Vérifier quels fichiers existent
echo "[1/3] Vérification des fichiers...\n";

$files = [
    'index.php' => 'index_new.php',
    'feeds.php' => 'feeds_new.php',
    'refresh.php' => 'refresh_new.php',
    '.htaccess' => '.htaccess_new'
];

foreach ($files as $target => $source) {
    if (file_exists($baseDir . '/' . $source)) {
        if (!file_exists($baseDir . '/' . $target)) {
            copy($baseDir . '/' . $source, $baseDir . '/' . $target);
            echo "✓ Copié: {$source} → {$target}\n";
        } else {
            echo "⊙ Existe déjà: {$target}\n";
        }
    } else {
        echo "✗ Source manquante: {$source}\n";
    }
}

echo "\n[2/3] Création des dossiers...\n";

$dirs = [
    'install',
    'includes',
    'views',
    'views/layout',
    'assets',
    'assets/css',
    'assets/js'
];

foreach ($dirs as $dir) {
    $path = $baseDir . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "✓ Créé: {$dir}/\n";
    } else {
        echo "⊙ Existe: {$dir}/\n";
    }
}

echo "\n[3/3] Vérification des fichiers essentiels...\n";

$essentialFiles = [
    'config.php',
    'includes/Database.php',
    'includes/RSSFeed.php',
    'includes/Article.php',
    'includes/helpers.php',
    'install/setup.php',
    'views/layout/header.php',
    'views/layout/footer.php',
    'assets/css/style.css',
    'assets/js/app.js'
];

$missing = [];
foreach ($essentialFiles as $file) {
    if (file_exists($baseDir . '/' . $file)) {
        echo "✓ {$file}\n";
    } else {
        echo "✗ MANQUANT: {$file}\n";
        $missing[] = $file;
    }
}

if (!empty($missing)) {
    echo "\n⚠️  Fichiers manquants: " . count($missing) . "\n";
    echo "Vous devez télécharger ces fichiers depuis GitHub.\n\n";

    echo "Commandes pour télécharger les fichiers manquants:\n\n";

    $baseUrl = "https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ";

    foreach ($missing as $file) {
        $dir = dirname($file);
        if ($dir !== '.') {
            echo "mkdir -p {$dir}\n";
        }
        echo "wget -O {$file} '{$baseUrl}/{$file}'\n";
    }
} else {
    echo "\n✅ Tous les fichiers essentiels sont présents !\n";
}

echo "\n════════════════════════════════════════════════════════════════\n";
echo "État du déploiement\n";
echo "════════════════════════════════════════════════════════════════\n\n";

if (empty($missing)) {
    echo "✅ Déploiement complet !\n\n";

    echo "Prochaines étapes:\n";
    echo "1. https://dusselle.fr/install/setup.php\n";
    echo "2. https://dusselle.fr/\n\n";
} else {
    echo "⚠️  Déploiement incomplet\n\n";
    echo "Exécutez les commandes ci-dessus pour télécharger les fichiers manquants.\n\n";
}

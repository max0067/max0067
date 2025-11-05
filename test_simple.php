<?php
/**
 * Test simple pour identifier le problème
 */

echo "\n=== TEST SIMPLE ===\n\n";

// Test 1: Fichiers
echo "[1] Vérification des fichiers...\n";
$files = ['config.php', 'Database.php', 'Session.php'];
foreach ($files as $file) {
    echo (file_exists($file) ? "✓" : "✗") . " {$file}\n";
}

// Test 2: Config
echo "\n[2] Chargement de la configuration...\n";
require_once __DIR__ . '/config.php';
echo "✓ Config chargée\n";
echo "  DB_PATH: " . DB_PATH . "\n";
echo "  Base existe: " . (file_exists(DB_PATH) ? "Oui" : "Non") . "\n";

// Test 3: Database
echo "\n[3] Connexion à la base de données...\n";
require_once __DIR__ . '/Database.php';
try {
    $db = Database::getInstance();
    $pdo = $db->getPdo();
    echo "✓ Connexion OK\n";

    // Count tables
    $stmt = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table'");
    $count = $stmt->fetchColumn();
    echo "  Tables: {$count}\n";

    // Count feeds
    $stmt = $pdo->query("SELECT COUNT(*) FROM feeds");
    $feedCount = $stmt->fetchColumn();
    echo "  Flux: {$feedCount}\n";

    // Count articles
    $stmt = $pdo->query("SELECT COUNT(*) FROM articles");
    $articleCount = $stmt->fetchColumn();
    echo "  Articles: {$articleCount}\n";

} catch (Exception $e) {
    echo "✗ ERREUR: {$e->getMessage()}\n";
    exit(1);
}

// Test 4: Récupération d'un flux RSS simple
echo "\n[4] Test de récupération RSS...\n";
$testUrl = "https://www.lemonde.fr/rss/une.xml";
echo "  URL: {$testUrl}\n";

// Method 1: file_get_contents
echo "  Tentative avec file_get_contents...\n";
$xml = @file_get_contents($testUrl);
if ($xml !== false) {
    echo "  ✓ Récupéré avec file_get_contents (" . strlen($xml) . " bytes)\n";

    $feed = @simplexml_load_string($xml);
    if ($feed !== false) {
        echo "  ✓ XML valide\n";
        if (isset($feed->channel->title)) {
            echo "  ✓ Titre: " . $feed->channel->title . "\n";

            $itemCount = count($feed->channel->item);
            echo "  ✓ Articles dans le flux: {$itemCount}\n";
        }
    } else {
        echo "  ✗ XML invalide\n";
    }
} else {
    echo "  ✗ Échec de file_get_contents\n";

    // Method 2: cURL
    if (function_exists('curl_init')) {
        echo "  Tentative avec cURL...\n";
        $ch = curl_init($testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $xml = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($xml !== false && $xml !== '') {
            echo "  ✓ Récupéré avec cURL (" . strlen($xml) . " bytes)\n";
        } else {
            echo "  ✗ Échec de cURL: {$error}\n";
        }
    } else {
        echo "  ✗ cURL non disponible\n";
    }
}

// Test 5: Test d'ajout manuel d'un flux
echo "\n[5] Test d'ajout de flux dans la DB...\n";
try {
    // Check if already exists
    $stmt = $pdo->prepare("SELECT id FROM feeds WHERE url = ?");
    $stmt->execute([$testUrl]);

    if ($existing = $stmt->fetch()) {
        echo "  ⊙ Flux déjà existant (ID: {$existing['id']})\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO feeds (url, title, active) VALUES (?, ?, 1)");
        $stmt->execute([$testUrl, "Le Monde - Test"]);
        echo "  ✓ Flux ajouté (ID: " . $pdo->lastInsertId() . ")\n";
    }
} catch (Exception $e) {
    echo "  ✗ ERREUR: {$e->getMessage()}\n";
}

// Test 6: API test
echo "\n[6] Test de l'API...\n";
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/?health';

ob_start();
try {
    // Simulate health check
    echo json_encode([
        'status' => 'ok',
        'timestamp' => time(),
        'database' => file_exists(DB_PATH) ? 'connected' : 'missing'
    ]);
    $output = ob_get_clean();
    echo "  ✓ API health check:\n";
    echo "    {$output}\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "  ✗ ERREUR: {$e->getMessage()}\n";
}

echo "\n=== FIN DU TEST ===\n\n";

// Recommendations
if ($articleCount == 0) {
    echo "⚠️  AUCUN ARTICLE TROUVÉ\n\n";
    echo "Prochaines étapes:\n";
    echo "  1. php manage_feeds.php add \"https://www.lemonde.fr/rss/une.xml\"\n";
    echo "  2. php update_rss.php\n";
    echo "  3. php debug.php\n";
}

echo "\n";

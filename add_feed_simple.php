<?php
/**
 * Ajoute un flux RSS simple - Version corrigée avec user_id
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

autoInit();

echo "\n=== AJOUT DE FLUX RSS (VERSION SIMPLE) ===\n\n";

$db = Database::getInstance();
$pdo = $db->getPdo();

// Get user ID (admin)
echo "[1] Récupération de l'utilisateur admin...\n";
$stmt = $pdo->query("SELECT id, username FROM users WHERE username = 'admin'");
$user = $stmt->fetch();

if (!$user) {
    die("✗ Utilisateur admin introuvable\n\n");
}

echo "✓ Utilisateur: {$user['username']} (ID: {$user['id']})\n\n";
$userId = $user['id'];

// Flux à ajouter
$feedUrl = 'https://www.lemonde.fr/rss/une.xml';
$feedTitle = 'Le Monde - Une';

echo "[2] Ajout du flux: {$feedTitle}\n";
echo "    URL: {$feedUrl}\n\n";

// Check if feed already exists
$stmt = $pdo->prepare("SELECT id FROM feeds WHERE url = ?");
$stmt->execute([$feedUrl]);

if ($existing = $stmt->fetch()) {
    echo "⊙ Flux déjà existant (ID: {$existing['id']})\n";
    $feedId = $existing['id'];
} else {
    // Get table structure
    $stmt = $pdo->query("PRAGMA table_info(feeds)");
    $columns = $stmt->fetchAll();
    $columnNames = array_column($columns, 'name');

    echo "Colonnes disponibles dans 'feeds': " . implode(', ', $columnNames) . "\n\n";

    // Prepare INSERT based on available columns
    $hasUserId = in_array('user_id', $columnNames);
    $hasActive = in_array('active', $columnNames);
    $hasLastFetched = in_array('last_fetched', $columnNames);
    $hasLastUpdated = in_array('last_updated', $columnNames);

    // Build query dynamically
    $fields = ['url', 'title'];
    $values = [$feedUrl, $feedTitle];

    if ($hasUserId) {
        $fields[] = 'user_id';
        $values[] = $userId;
        echo "✓ Ajout de user_id: {$userId}\n";
    }

    if ($hasActive) {
        $fields[] = 'active';
        $values[] = 1;
        echo "✓ Ajout de active: 1\n";
    }

    $placeholders = array_fill(0, count($fields), '?');
    $sql = sprintf(
        "INSERT INTO feeds (%s) VALUES (%s)",
        implode(', ', $fields),
        implode(', ', $placeholders)
    );

    echo "\nSQL: {$sql}\n";
    echo "Valeurs: " . implode(', ', $values) . "\n\n";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $feedId = $pdo->lastInsertId();
        echo "✓ Flux ajouté (ID: {$feedId})\n\n";
    } catch (Exception $e) {
        die("✗ ERREUR lors de l'ajout: {$e->getMessage()}\n\n");
    }
}

// Fetch articles
echo "[3] Récupération des articles...\n";

// Try with cURL
$xml = null;
if (function_exists('curl_init')) {
    echo "Tentative avec cURL...\n";
    $ch = curl_init($feedUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (RSS Reader)');
    $xml = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($xml && $xml !== '') {
        echo "✓ Récupéré avec cURL (HTTP {$httpCode}, " . strlen($xml) . " bytes)\n";
    } else {
        echo "✗ Erreur cURL: {$curlError}\n";
        $xml = null;
    }
}

// Fallback to file_get_contents
if ($xml === null) {
    echo "Tentative avec file_get_contents...\n";
    $xml = @file_get_contents($feedUrl);
    if ($xml !== false) {
        echo "✓ Récupéré avec file_get_contents (" . strlen($xml) . " bytes)\n";
    } else {
        die("✗ Impossible de récupérer le flux\n\n");
    }
}

// Parse XML
$rss = @simplexml_load_string($xml);
if ($rss === false) {
    die("✗ XML invalide\n\n");
}

echo "✓ XML valide\n";

$newCount = 0;
$existingCount = 0;

// Get article table structure
$stmt = $pdo->query("PRAGMA table_info(articles)");
$columns = $stmt->fetchAll();
$articleColumns = array_column($columns, 'name');

echo "Colonnes disponibles dans 'articles': " . implode(', ', $articleColumns) . "\n\n";

// Process items
if (isset($rss->channel->item)) {
    $totalItems = count($rss->channel->item);
    echo "Articles trouvés: {$totalItems}\n";
    echo "Importation...\n";

    foreach ($rss->channel->item as $item) {
        $title = (string)($item->title ?? '');
        $link = (string)($item->link ?? '');
        $description = (string)($item->description ?? '');
        $content = (string)($item->children('content', true)->encoded ?? '');
        $pubDate = (string)($item->pubDate ?? '');

        if (empty($title) || empty($link)) {
            continue;
        }

        // Parse date
        $publishedDate = date('Y-m-d H:i:s');
        if ($pubDate) {
            $timestamp = strtotime($pubDate);
            if ($timestamp) {
                $publishedDate = date('Y-m-d H:i:s', $timestamp);
            }
        }

        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM articles WHERE feed_id = ? AND link = ?");
        $stmt->execute([$feedId, $link]);

        if ($stmt->fetch()) {
            $existingCount++;
            continue;
        }

        // Insert article - use only available columns
        $hasIsRead = in_array('is_read', $articleColumns);

        $sql = "INSERT INTO articles (feed_id, title, link, description, content, published_date" .
               ($hasIsRead ? ", is_read" : "") .
               ") VALUES (?, ?, ?, ?, ?, ?" .
               ($hasIsRead ? ", 0" : "") .
               ")";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $feedId,
                $title,
                $link,
                $description,
                $content ?: $description,
                $publishedDate
            ]);
            $newCount++;
        } catch (Exception $e) {
            echo "  ✗ Erreur article: {$e->getMessage()}\n";
        }
    }

    echo "✓ Importation terminée\n";
    echo "  Nouveaux: {$newCount}\n";
    echo "  Existants: {$existingCount}\n\n";
}

// Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM feeds");
$feedCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM articles");
$articleCount = $stmt->fetchColumn();

echo "=== STATISTIQUES ===\n";
echo "Flux: {$feedCount}\n";
echo "Articles: {$articleCount}\n";
echo "\n";

if ($articleCount > 0) {
    echo "✓ Succès ! Allez sur http://dusselle.fr/ (admin/admin123)\n";
} else {
    echo "⚠ Aucun article récupéré\n";
}

echo "\n";

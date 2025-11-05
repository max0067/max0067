<?php
/**
 * Ajoute des flux RSS et récupère les articles
 * Version simplifiée avec meilleure gestion d'erreurs
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

autoInit();

echo "\n";
echo "========================================\n";
echo "  AJOUT DE FLUX RSS\n";
echo "========================================\n\n";

$db = Database::getInstance();
$pdo = $db->getPdo();

// Un seul flux pour commencer
$feeds = [
    [
        'url' => 'https://www.lemonde.fr/rss/une.xml',
        'title' => 'Le Monde - Une'
    ]
];

echo "Ce script va ajouter " . count($feeds) . " flux et récupérer leurs articles.\n\n";

foreach ($feeds as $feedData) {
    echo "---\n";
    echo "Flux: {$feedData['title']}\n";
    echo "URL: {$feedData['url']}\n\n";

    // Check if feed already exists
    $stmt = $pdo->prepare("SELECT id FROM feeds WHERE url = ?");
    $stmt->execute([$feedData['url']]);

    if ($existing = $stmt->fetch()) {
        echo "⊙ Flux déjà existant (ID: {$existing['id']})\n";
        $feedId = $existing['id'];
    } else {
        // Add feed
        echo "Ajout du flux...\n";
        $stmt = $pdo->prepare("INSERT INTO feeds (url, title, active) VALUES (?, ?, 1)");
        $stmt->execute([$feedData['url'], $feedData['title']]);
        $feedId = $pdo->lastInsertId();
        echo "✓ Flux ajouté (ID: {$feedId})\n";
    }

    // Fetch articles
    echo "\nRécupération des articles...\n";

    // Try with cURL first
    $xml = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($feedData['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (RSS Reader)');
        $xml = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($xml === false || $xml === '') {
            echo "✗ Erreur cURL: {$error}\n";
            $xml = null;
        } else {
            echo "✓ Récupéré avec cURL (HTTP {$httpCode}, " . strlen($xml) . " bytes)\n";
        }
    }

    // Fallback to file_get_contents
    if ($xml === null) {
        echo "Tentative avec file_get_contents...\n";
        $xml = @file_get_contents($feedData['url']);
        if ($xml !== false) {
            echo "✓ Récupéré avec file_get_contents (" . strlen($xml) . " bytes)\n";
        } else {
            echo "✗ Impossible de récupérer le flux\n";
            continue;
        }
    }

    // Parse XML
    $rss = @simplexml_load_string($xml);
    if ($rss === false) {
        echo "✗ XML invalide\n";
        continue;
    }

    echo "✓ XML valide\n";

    $newCount = 0;
    $existingCount = 0;

    // Process items
    if (isset($rss->channel->item)) {
        $totalItems = count($rss->channel->item);
        echo "Articles trouvés: {$totalItems}\n";
        echo "Importation en cours...\n";

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

            // Insert article
            $stmt = $pdo->prepare("
                INSERT INTO articles (feed_id, title, link, description, content, published_date, is_read)
                VALUES (?, ?, ?, ?, ?, ?, 0)
            ");

            $stmt->execute([
                $feedId,
                $title,
                $link,
                $description,
                $content ?: $description,
                $publishedDate
            ]);

            $newCount++;
        }

        // Update feed
        $stmt = $pdo->prepare("UPDATE feeds SET last_fetched = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$feedId]);

        echo "✓ Importation terminée\n";
        echo "  Nouveaux: {$newCount}\n";
        echo "  Existants: {$existingCount}\n";
    }

    echo "\n";
}

// Show stats
echo "========================================\n";
echo "  STATISTIQUES\n";
echo "========================================\n\n";

$stmt = $pdo->query("SELECT COUNT(*) FROM feeds WHERE active = 1");
$feedCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM articles");
$articleCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE is_read = 0");
$unreadCount = $stmt->fetchColumn();

echo "Flux actifs: {$feedCount}\n";
echo "Total articles: {$articleCount}\n";
echo "Articles non lus: {$unreadCount}\n";
echo "\n";

if ($articleCount > 0) {
    echo "✓ Articles récupérés avec succès !\n\n";
    echo "Prochaines étapes:\n";
    echo "  1. Allez sur http://dusselle.fr/\n";
    echo "  2. Login: admin / Password: admin123\n";
    echo "  3. Pour ajouter d'autres flux: php manage_feeds.php add <url>\n";
} else {
    echo "⚠️  Aucun article récupéré\n\n";
    echo "Pour débugger:\n";
    echo "  php debug.php\n";
}

echo "\n";

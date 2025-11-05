<?php
/**
 * Script de mise à jour des flux RSS
 * Récupère les nouveaux articles de tous les flux actifs
 *
 * Usage:
 *   php update_rss.php           # Met à jour tous les flux
 *   php update_rss.php 1         # Met à jour uniquement le flux #1
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

autoInit();

$db = Database::getInstance();
$pdo = $db->getPdo();

$feedId = isset($argv[1]) ? (int)$argv[1] : null;

echo "\n=== MISE À JOUR DES FLUX RSS ===\n\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Get feeds to update
if ($feedId) {
    $stmt = $pdo->prepare("SELECT * FROM feeds WHERE id = ? AND active = 1");
    $stmt->execute([$feedId]);
    $feeds = $stmt->fetchAll();

    if (empty($feeds)) {
        die("ERREUR: Flux #{$feedId} introuvable ou inactif\n");
    }
} else {
    $stmt = $pdo->query("SELECT * FROM feeds WHERE active = 1");
    $feeds = $stmt->fetchAll();
}

if (empty($feeds)) {
    die("Aucun flux actif trouvé.\nAjoutez un flux avec: php manage_feeds.php add <url>\n");
}

echo "Flux à mettre à jour: " . count($feeds) . "\n\n";

$totalNew = 0;
$totalExisting = 0;
$totalErrors = 0;

foreach ($feeds as $feed) {
    echo "---\n";
    echo "Flux: {$feed['title']}\n";
    echo "URL: {$feed['url']}\n";

    try {
        $result = updateFeed($db, $feed);

        echo "✓ Récupéré: {$result['total']} articles\n";
        echo "  Nouveaux: {$result['new']}\n";
        echo "  Existants: {$result['existing']}\n";

        $totalNew += $result['new'];
        $totalExisting += $result['existing'];

    } catch (Exception $e) {
        echo "✗ ERREUR: {$e->getMessage()}\n";
        $totalErrors++;
    }

    echo "\n";
}

echo "=== RÉSUMÉ ===\n";
echo "Nouveaux articles: {$totalNew}\n";
echo "Articles existants: {$totalExisting}\n";
echo "Erreurs: {$totalErrors}\n";
echo "\n";

/**
 * Update a single feed
 */
function updateFeed($db, $feed) {
    $pdo = $db->getPdo();

    // Fetch RSS feed
    $xml = @file_get_contents($feed['url']);
    if ($xml === false) {
        throw new Exception("Impossible de récupérer le flux");
    }

    $rss = @simplexml_load_string($xml);
    if ($rss === false) {
        throw new Exception("XML invalide");
    }

    $newCount = 0;
    $existingCount = 0;
    $totalCount = 0;

    // Handle RSS 2.0
    if (isset($rss->channel->item)) {
        foreach ($rss->channel->item as $item) {
            $totalCount++;

            $title = (string)($item->title ?? '');
            $link = (string)($item->link ?? '');
            $description = (string)($item->description ?? '');
            $content = (string)($item->children('content', true)->encoded ?? '');
            $pubDate = (string)($item->pubDate ?? '');

            if (empty($title) || empty($link)) {
                continue;
            }

            // Parse date
            $publishedDate = null;
            if ($pubDate) {
                $timestamp = strtotime($pubDate);
                if ($timestamp) {
                    $publishedDate = date('Y-m-d H:i:s', $timestamp);
                }
            }
            if (!$publishedDate) {
                $publishedDate = date('Y-m-d H:i:s');
            }

            // Check if article already exists
            $stmt = $pdo->prepare("SELECT id FROM articles WHERE feed_id = ? AND link = ?");
            $stmt->execute([$feed['id'], $link]);

            if ($stmt->fetch()) {
                $existingCount++;
                continue;
            }

            // Insert article
            $stmt = $pdo->prepare("
                INSERT INTO articles (feed_id, title, link, description, content, published_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $feed['id'],
                $title,
                $link,
                $description,
                $content ?: $description,
                $publishedDate
            ]);

            $newCount++;
        }
    }
    // Handle Atom feeds
    elseif (isset($rss->entry)) {
        foreach ($rss->entry as $entry) {
            $totalCount++;

            $title = (string)($entry->title ?? '');
            $link = '';
            if (isset($entry->link)) {
                $link = (string)($entry->link['href'] ?? $entry->link);
            }
            $summary = (string)($entry->summary ?? '');
            $content = (string)($entry->content ?? '');
            $published = (string)($entry->published ?? $entry->updated ?? '');

            if (empty($title) || empty($link)) {
                continue;
            }

            // Parse date
            $publishedDate = null;
            if ($published) {
                $timestamp = strtotime($published);
                if ($timestamp) {
                    $publishedDate = date('Y-m-d H:i:s', $timestamp);
                }
            }
            if (!$publishedDate) {
                $publishedDate = date('Y-m-d H:i:s');
            }

            // Check if article already exists
            $stmt = $pdo->prepare("SELECT id FROM articles WHERE feed_id = ? AND link = ?");
            $stmt->execute([$feed['id'], $link]);

            if ($stmt->fetch()) {
                $existingCount++;
                continue;
            }

            // Insert article
            $stmt = $pdo->prepare("
                INSERT INTO articles (feed_id, title, link, description, content, published_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $feed['id'],
                $title,
                $link,
                $summary,
                $content ?: $summary,
                $publishedDate
            ]);

            $newCount++;
        }
    }

    // Update feed's last_fetched
    $stmt = $pdo->prepare("UPDATE feeds SET last_fetched = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$feed['id']]);

    return [
        'total' => $totalCount,
        'new' => $newCount,
        'existing' => $existingCount
    ];
}

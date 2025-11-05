<?php
/**
 * Configuration rapide - Ajoute des flux RSS et récupère les articles
 * Usage: php quick_setup.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

autoInit();

echo "\n";
echo "========================================\n";
echo "  CONFIGURATION RAPIDE\n";
echo "========================================\n\n";

$db = Database::getInstance();
$pdo = $db->getPdo();

// Flux RSS français populaires
$defaultFeeds = [
    'https://www.lemonde.fr/rss/une.xml' => 'Le Monde - Une',
    'https://www.france24.com/fr/rss' => 'France 24',
    'https://www.lefigaro.fr/rss/figaro_actualites.xml' => 'Le Figaro - Actualités',
    'https://www.liberation.fr/arc/outboundfeeds/rss/' => 'Libération',
    'https://www.20minutes.fr/feeds/rss-une.xml' => '20 Minutes'
];

echo "Flux à ajouter:\n";
foreach ($defaultFeeds as $url => $title) {
    echo "  - {$title}\n";
}

echo "\nContinuer? [Y/n] ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) === 'n') {
    die("Annulé.\n");
}

echo "\n[1/2] Ajout des flux RSS...\n";
echo "---\n";

$addedCount = 0;
$existingCount = 0;

foreach ($defaultFeeds as $url => $defaultTitle) {
    // Check if feed already exists
    $stmt = $pdo->prepare("SELECT id FROM feeds WHERE url = ?");
    $stmt->execute([$url]);

    if ($stmt->fetch()) {
        echo "⊙ {$defaultTitle} - Déjà existant\n";
        $existingCount++;
        continue;
    }

    // Fetch feed to get actual title
    $xml = @file_get_contents($url);
    $title = $defaultTitle;

    if ($xml !== false) {
        $feed = @simplexml_load_string($xml);
        if ($feed !== false) {
            if (isset($feed->channel->title)) {
                $title = (string)$feed->channel->title;
            } elseif (isset($feed->title)) {
                $title = (string)$feed->title;
            }
        }
    }

    // Insert feed
    $stmt = $pdo->prepare("INSERT INTO feeds (url, title, active) VALUES (?, ?, 1)");
    $stmt->execute([$url, $title]);

    echo "✓ {$title}\n";
    $addedCount++;

    sleep(1); // Be nice to servers
}

echo "\nFlux ajoutés: {$addedCount}\n";
echo "Flux existants: {$existingCount}\n\n";

echo "[2/2] Récupération des articles...\n";
echo "---\n";

// Get all active feeds
$stmt = $pdo->query("SELECT * FROM feeds WHERE active = 1");
$feeds = $stmt->fetchAll();

$totalNew = 0;
$totalExisting = 0;

foreach ($feeds as $feed) {
    echo "\n{$feed['title']}...\n";

    try {
        // Fetch RSS feed
        $xml = @file_get_contents($feed['url']);
        if ($xml === false) {
            echo "  ✗ Impossible de récupérer le flux\n";
            continue;
        }

        $rss = @simplexml_load_string($xml);
        if ($rss === false) {
            echo "  ✗ XML invalide\n";
            continue;
        }

        $newCount = 0;
        $existingItemCount = 0;

        // Handle RSS 2.0
        if (isset($rss->channel->item)) {
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
                    $existingItemCount++;
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
                    $existingItemCount++;
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

        echo "  ✓ Nouveaux: {$newCount}, Existants: {$existingItemCount}\n";

        $totalNew += $newCount;
        $totalExisting += $existingItemCount;

    } catch (Exception $e) {
        echo "  ✗ Erreur: {$e->getMessage()}\n";
    }

    sleep(1); // Be nice to servers
}

echo "\n========================================\n";
echo "  TERMINÉ\n";
echo "========================================\n";
echo "Nouveaux articles: {$totalNew}\n";
echo "Articles existants: {$totalExisting}\n";
echo "\n";

// Show stats
$stmt = $pdo->query("SELECT COUNT(*) FROM feeds WHERE active = 1");
$feedCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM articles");
$articleCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE is_read = 0");
$unreadCount = $stmt->fetchColumn();

echo "Statistiques finales:\n";
echo "  Flux actifs: {$feedCount}\n";
echo "  Total articles: {$articleCount}\n";
echo "  Articles non lus: {$unreadCount}\n";
echo "\n";

echo "✓ Configuration terminée !\n";
echo "\nProchaines étapes:\n";
echo "  1. Connectez-vous à http://dusselle.fr/\n";
echo "  2. Login: admin / Password: admin123\n";
echo "  3. Pour mettre à jour les flux: php update_rss.php\n";
echo "\n";

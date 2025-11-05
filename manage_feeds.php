<?php
/**
 * Script de gestion des flux RSS
 * Usage:
 *   php manage_feeds.php list
 *   php manage_feeds.php add "https://example.com/feed.xml"
 *   php manage_feeds.php delete 1
 *   php manage_feeds.php update 1 "https://new-url.com/feed.xml"
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

autoInit();

$db = Database::getInstance();
$action = $argv[1] ?? 'help';

switch ($action) {
    case 'list':
        listFeeds($db);
        break;

    case 'add':
        if (!isset($argv[2])) {
            die("Usage: php manage_feeds.php add <url>\n");
        }
        addFeed($db, $argv[2]);
        break;

    case 'delete':
        if (!isset($argv[2])) {
            die("Usage: php manage_feeds.php delete <feed_id>\n");
        }
        deleteFeed($db, $argv[2]);
        break;

    case 'update':
        if (!isset($argv[2]) || !isset($argv[3])) {
            die("Usage: php manage_feeds.php update <feed_id> <new_url>\n");
        }
        updateFeed($db, $argv[2], $argv[3]);
        break;

    case 'help':
    default:
        showHelp();
        break;
}

function listFeeds($db) {
    $pdo = $db->getPdo();
    $stmt = $pdo->query("SELECT * FROM feeds ORDER BY id");
    $feeds = $stmt->fetchAll();

    if (empty($feeds)) {
        echo "Aucun flux RSS enregistré.\n\n";
        echo "Pour ajouter un flux:\n";
        echo "  php manage_feeds.php add \"https://example.com/feed.xml\"\n";
        return;
    }

    echo "\n=== FLUX RSS ===\n\n";
    foreach ($feeds as $feed) {
        echo "ID: {$feed['id']}\n";
        echo "Titre: " . ($feed['title'] ?: 'Non défini') . "\n";
        echo "URL: {$feed['url']}\n";
        echo "Actif: " . ($feed['active'] ? 'Oui' : 'Non') . "\n";
        echo "Dernière mise à jour: {$feed['last_fetched']}\n";
        echo "---\n";
    }

    // Count articles per feed
    $stmt = $pdo->query("SELECT feed_id, COUNT(*) as count FROM articles GROUP BY feed_id");
    $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo "\nNombre d'articles par flux:\n";
    foreach ($feeds as $feed) {
        $count = $counts[$feed['id']] ?? 0;
        echo "  [{$feed['id']}] {$feed['title']}: {$count} articles\n";
    }
    echo "\n";
}

function addFeed($db, $url) {
    $url = trim($url);

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        die("ERREUR: URL invalide\n");
    }

    echo "Ajout du flux: {$url}\n";

    // Fetch feed to get title
    $xml = @file_get_contents($url);
    if ($xml === false) {
        die("ERREUR: Impossible de récupérer le flux\n");
    }

    $feed = @simplexml_load_string($xml);
    if ($feed === false) {
        die("ERREUR: XML invalide\n");
    }

    // Get title
    $title = null;
    if (isset($feed->channel->title)) {
        $title = (string)$feed->channel->title;
    } elseif (isset($feed->title)) {
        $title = (string)$feed->title;
    }

    // Insert into database
    $pdo = $db->getPdo();
    $stmt = $pdo->prepare("INSERT INTO feeds (url, title, active) VALUES (?, ?, 1)");
    $stmt->execute([$url, $title]);

    $feedId = $pdo->lastInsertId();

    echo "✓ Flux ajouté avec succès !\n";
    echo "  ID: {$feedId}\n";
    echo "  Titre: {$title}\n";
    echo "  URL: {$url}\n\n";
    echo "Pour récupérer les articles:\n";
    echo "  php update_rss.php\n\n";
}

function deleteFeed($db, $feedId) {
    $pdo = $db->getPdo();

    // Check if feed exists
    $stmt = $pdo->prepare("SELECT * FROM feeds WHERE id = ?");
    $stmt->execute([$feedId]);
    $feed = $stmt->fetch();

    if (!$feed) {
        die("ERREUR: Flux #{$feedId} introuvable\n");
    }

    echo "Suppression du flux:\n";
    echo "  ID: {$feed['id']}\n";
    echo "  Titre: {$feed['title']}\n";
    echo "  URL: {$feed['url']}\n\n";

    // Count articles
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE feed_id = ?");
    $stmt->execute([$feedId]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo "Ce flux contient {$count} articles qui seront également supprimés.\n";
        echo "Continuer? [y/N] ";
        $confirm = trim(fgets(STDIN));
        if (strtolower($confirm) !== 'y') {
            die("Annulé.\n");
        }
    }

    // Delete articles first
    $stmt = $pdo->prepare("DELETE FROM user_articles WHERE article_id IN (SELECT id FROM articles WHERE feed_id = ?)");
    $stmt->execute([$feedId]);

    $stmt = $pdo->prepare("DELETE FROM articles WHERE feed_id = ?");
    $stmt->execute([$feedId]);

    // Delete feed
    $stmt = $pdo->prepare("DELETE FROM feeds WHERE id = ?");
    $stmt->execute([$feedId]);

    echo "✓ Flux supprimé avec succès !\n";
}

function updateFeed($db, $feedId, $newUrl) {
    $pdo = $db->getPdo();

    // Check if feed exists
    $stmt = $pdo->prepare("SELECT * FROM feeds WHERE id = ?");
    $stmt->execute([$feedId]);
    $feed = $stmt->fetch();

    if (!$feed) {
        die("ERREUR: Flux #{$feedId} introuvable\n");
    }

    $newUrl = trim($newUrl);

    if (!filter_var($newUrl, FILTER_VALIDATE_URL)) {
        die("ERREUR: URL invalide\n");
    }

    echo "Mise à jour du flux:\n";
    echo "  ID: {$feed['id']}\n";
    echo "  Ancien: {$feed['url']}\n";
    echo "  Nouveau: {$newUrl}\n\n";

    // Fetch new feed to get title
    $xml = @file_get_contents($newUrl);
    if ($xml !== false) {
        $feedXml = @simplexml_load_string($xml);
        if ($feedXml !== false) {
            $title = null;
            if (isset($feedXml->channel->title)) {
                $title = (string)$feedXml->channel->title;
            } elseif (isset($feedXml->title)) {
                $title = (string)$feedXml->title;
            }

            if ($title) {
                echo "  Nouveau titre: {$title}\n\n";
            }
        }
    }

    // Update database
    $stmt = $pdo->prepare("UPDATE feeds SET url = ?, title = ? WHERE id = ?");
    $stmt->execute([$newUrl, $title ?? $feed['title'], $feedId]);

    echo "✓ Flux mis à jour avec succès !\n\n";
    echo "Pour récupérer les nouveaux articles:\n";
    echo "  php update_rss.php\n\n";
}

function showHelp() {
    echo "\n=== GESTION DES FLUX RSS ===\n\n";
    echo "Usage:\n";
    echo "  php manage_feeds.php list\n";
    echo "    Liste tous les flux RSS\n\n";
    echo "  php manage_feeds.php add <url>\n";
    echo "    Ajoute un nouveau flux RSS\n";
    echo "    Exemple: php manage_feeds.php add \"https://example.com/feed.xml\"\n\n";
    echo "  php manage_feeds.php delete <feed_id>\n";
    echo "    Supprime un flux RSS et ses articles\n";
    echo "    Exemple: php manage_feeds.php delete 1\n\n";
    echo "  php manage_feeds.php update <feed_id> <new_url>\n";
    echo "    Met à jour l'URL d'un flux RSS\n";
    echo "    Exemple: php manage_feeds.php update 1 \"https://new-url.com/feed.xml\"\n\n";
    echo "Exemples de flux RSS:\n";
    echo "  - Le Monde: https://www.lemonde.fr/rss/une.xml\n";
    echo "  - France 24: https://www.france24.com/fr/rss\n";
    echo "  - Liberation: https://www.liberation.fr/arc/outboundfeeds/rss/\n\n";
}

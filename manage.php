<?php
/**
 * Interface web de gestion des flux RSS
 * Accessible via: http://dusselle.fr/manage.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Session.php';

autoInit();

$session = new Session();
$session->requireAuth();

$db = Database::getInstance();
$pdo = $db->getPdo();
$user = $session->getCurrentUser();
$userId = $user['id'];

// Get table structure
$stmt = $pdo->query("PRAGMA table_info(feeds)");
$feedColumns = array_column($stmt->fetchAll(), 'name');
$hasUserId = in_array('user_id', $feedColumns);

// Handle actions
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $url = trim($_POST['url'] ?? '');

            if (empty($url)) {
                throw new Exception("URL requise");
            }

            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception("URL invalide");
            }

            // Fetch feed to get title
            $xml = @file_get_contents($url);
            if ($xml === false) {
                throw new Exception("Impossible de récupérer le flux");
            }

            $feed = @simplexml_load_string($xml);
            if ($feed === false) {
                throw new Exception("XML invalide");
            }

            // Get title
            $title = null;
            if (isset($feed->channel->title)) {
                $title = (string)$feed->channel->title;
            } elseif (isset($feed->title)) {
                $title = (string)$feed->title;
            } else {
                $title = $url;
            }

            // Insert feed
            if ($hasUserId) {
                $stmt = $pdo->prepare("INSERT INTO feeds (user_id, url, title, active) VALUES (?, ?, ?, 1)");
                $stmt->execute([$userId, $url, $title]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO feeds (url, title, active) VALUES (?, ?, 1)");
                $stmt->execute([$url, $title]);
            }

            $message = "Flux '{$title}' ajouté avec succès !";

        } elseif ($action === 'delete') {
            $feedId = (int)($_POST['feed_id'] ?? 0);

            if ($feedId > 0) {
                // Delete articles first
                $pdo->prepare("DELETE FROM articles WHERE feed_id = ?")->execute([$feedId]);

                // Delete feed
                if ($hasUserId) {
                    $stmt = $pdo->prepare("DELETE FROM feeds WHERE id = ? AND user_id = ?");
                    $stmt->execute([$feedId, $userId]);
                } else {
                    $stmt = $pdo->prepare("DELETE FROM feeds WHERE id = ?");
                    $stmt->execute([$feedId]);
                }

                $message = "Flux supprimé avec succès !";
            }

        } elseif ($action === 'update') {
            $feedId = (int)($_POST['feed_id'] ?? 0);

            if ($feedId > 0) {
                // Get feed
                if ($hasUserId) {
                    $stmt = $pdo->prepare("SELECT * FROM feeds WHERE id = ? AND user_id = ?");
                    $stmt->execute([$feedId, $userId]);
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM feeds WHERE id = ?");
                    $stmt->execute([$feedId]);
                }

                $feed = $stmt->fetch();
                if (!$feed) {
                    throw new Exception("Flux introuvable");
                }

                // Fetch RSS
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

                // Process items
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
                }

                // Update last_fetched
                $pdo->prepare("UPDATE feeds SET last_fetched = CURRENT_TIMESTAMP WHERE id = ?")->execute([$feedId]);

                $message = "Flux mis à jour: {$newCount} nouveaux articles, {$existingCount} existants";
            }

        } elseif ($action === 'update_all') {
            // Update all feeds
            if ($hasUserId) {
                $stmt = $pdo->prepare("SELECT * FROM feeds WHERE user_id = ? AND active = 1");
                $stmt->execute([$userId]);
            } else {
                $stmt = $pdo->query("SELECT * FROM feeds WHERE active = 1");
            }

            $feeds = $stmt->fetchAll();
            $totalNew = 0;
            $totalExisting = 0;
            $errors = 0;

            foreach ($feeds as $feed) {
                try {
                    $xml = @file_get_contents($feed['url']);
                    if ($xml === false) continue;

                    $rss = @simplexml_load_string($xml);
                    if ($rss === false) continue;

                    if (isset($rss->channel->item)) {
                        foreach ($rss->channel->item as $item) {
                            $title = (string)($item->title ?? '');
                            $link = (string)($item->link ?? '');
                            $description = (string)($item->description ?? '');
                            $content = (string)($item->children('content', true)->encoded ?? '');
                            $pubDate = (string)($item->pubDate ?? '');

                            if (empty($title) || empty($link)) continue;

                            $publishedDate = date('Y-m-d H:i:s');
                            if ($pubDate) {
                                $timestamp = strtotime($pubDate);
                                if ($timestamp) $publishedDate = date('Y-m-d H:i:s', $timestamp);
                            }

                            $stmt = $pdo->prepare("SELECT id FROM articles WHERE feed_id = ? AND link = ?");
                            $stmt->execute([$feed['id'], $link]);

                            if ($stmt->fetch()) {
                                $totalExisting++;
                                continue;
                            }

                            $stmt = $pdo->prepare("
                                INSERT INTO articles (feed_id, title, link, description, content, published_date, is_read)
                                VALUES (?, ?, ?, ?, ?, ?, 0)
                            ");

                            $stmt->execute([$feed['id'], $title, $link, $description, $content ?: $description, $publishedDate]);
                            $totalNew++;
                        }
                    }

                    $pdo->prepare("UPDATE feeds SET last_fetched = CURRENT_TIMESTAMP WHERE id = ?")->execute([$feed['id']]);
                } catch (Exception $e) {
                    $errors++;
                }
            }

            $message = "Tous les flux mis à jour: {$totalNew} nouveaux, {$totalExisting} existants";
            if ($errors > 0) $message .= ", {$errors} erreurs";
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get feeds
if ($hasUserId) {
    $stmt = $pdo->prepare("SELECT * FROM feeds WHERE user_id = ? ORDER BY title");
    $stmt->execute([$userId]);
} else {
    $stmt = $pdo->query("SELECT * FROM feeds ORDER BY title");
}
$feeds = $stmt->fetchAll();

// Get article counts per feed
$articleCounts = [];
foreach ($feeds as $feed) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE feed_id = ?");
    $stmt->execute([$feed['id']]);
    $articleCounts[$feed['id']] = $stmt->fetchColumn();
}

// Total stats
$stmt = $pdo->query("SELECT COUNT(*) FROM articles");
$totalArticles = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE is_read = 0");
$unreadArticles = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Flux RSS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 32px;
        }

        h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1a73e8;
        }

        .subtitle {
            color: #5f6368;
            margin-bottom: 32px;
        }

        .stats {
            display: flex;
            gap: 24px;
            margin-bottom: 32px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat {
            flex: 1;
        }

        .stat-label {
            font-size: 13px;
            color: #5f6368;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 600;
            color: #202124;
        }

        .actions {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }

        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #1a73e8;
            color: white;
        }

        .btn-primary:hover {
            background: #1557b0;
        }

        .btn-secondary {
            background: #f1f3f4;
            color: #202124;
        }

        .btn-secondary:hover {
            background: #e8eaed;
        }

        .btn-danger {
            background: #d93025;
            color: white;
        }

        .btn-danger:hover {
            background: #b31412;
        }

        .btn-success {
            background: #1e8e3e;
            color: white;
        }

        .btn-success:hover {
            background: #137333;
        }

        .message {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 24px;
        }

        .message-success {
            background: #e6f4ea;
            color: #137333;
            border-left: 4px solid #1e8e3e;
        }

        .message-error {
            background: #fce8e6;
            color: #b31412;
            border-left: 4px solid #d93025;
        }

        .add-form {
            display: none;
            padding: 24px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .add-form.active {
            display: block;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #202124;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1a73e8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px 16px;
            background: #f8f9fa;
            color: #5f6368;
            font-weight: 500;
            font-size: 13px;
            text-transform: uppercase;
            border-bottom: 2px solid #dadce0;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #f1f3f4;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .feed-title {
            font-weight: 500;
            color: #202124;
            margin-bottom: 4px;
        }

        .feed-url {
            font-size: 13px;
            color: #5f6368;
            word-break: break-all;
        }

        .feed-actions {
            display: flex;
            gap: 8px;
        }

        .back-link {
            display: inline-block;
            color: #1a73e8;
            text-decoration: none;
            margin-bottom: 16px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #5f6368;
        }

        .empty-state svg {
            margin-bottom: 16px;
            opacity: 0.5;
        }

        small {
            font-size: 12px;
            color: #5f6368;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/" class="back-link">← Retour au lecteur</a>

        <h1>Gestion des Flux RSS</h1>
        <p class="subtitle">Connecté en tant que: <strong><?= htmlspecialchars($user['username']) ?></strong></p>

        <div class="stats">
            <div class="stat">
                <div class="stat-label">Flux RSS</div>
                <div class="stat-value"><?= count($feeds) ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Articles</div>
                <div class="stat-value"><?= $totalArticles ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Non lus</div>
                <div class="stat-value"><?= $unreadArticles ?></div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message message-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message message-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="actions">
            <button class="btn btn-primary" onclick="toggleAddForm()">+ Ajouter un flux</button>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="update_all">
                <button type="submit" class="btn btn-success">⟳ Actualiser tous les flux</button>
            </form>
        </div>

        <div class="add-form" id="addForm">
            <h3 style="margin-bottom: 16px;">Ajouter un nouveau flux RSS</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="url">URL du flux RSS</label>
                    <input type="url" id="url" name="url" placeholder="https://example.com/feed.xml" required>
                    <small>Exemples: https://www.lemonde.fr/rss/une.xml, https://www.france24.com/fr/rss</small>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleAddForm()">Annuler</button>
                </div>
            </form>
        </div>

        <?php if (empty($feeds)): ?>
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 11a9 9 0 0 1 9 9"></path>
                    <path d="M4 4a16 16 0 0 1 16 16"></path>
                    <circle cx="5" cy="19" r="1"></circle>
                </svg>
                <p>Aucun flux RSS configuré</p>
                <p><small>Cliquez sur "Ajouter un flux" pour commencer</small></p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Flux</th>
                        <th style="width: 100px; text-align: center;">Articles</th>
                        <th style="width: 200px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feeds as $feed): ?>
                        <tr>
                            <td>
                                <div class="feed-title"><?= htmlspecialchars($feed['title'] ?: 'Sans titre') ?></div>
                                <div class="feed-url"><?= htmlspecialchars($feed['url']) ?></div>
                                <?php if ($feed['last_fetched']): ?>
                                    <small>Dernière mise à jour: <?= $feed['last_fetched'] ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; font-weight: 600; color: #1a73e8;">
                                <?= $articleCounts[$feed['id']] ?? 0 ?>
                            </td>
                            <td>
                                <div class="feed-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="feed_id" value="<?= $feed['id'] ?>">
                                        <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">
                                            ⟳ Actualiser
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce flux et ses articles ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="feed_id" value="<?= $feed['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 13px;">
                                            🗑 Supprimer
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        function toggleAddForm() {
            document.getElementById('addForm').classList.toggle('active');
        }
    </script>
</body>
</html>

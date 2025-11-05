<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$user = requireApiAuth();
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'get':
        $feedId = $input['feed_id'] ?? null;
        $limit = $input['limit'] ?? ARTICLES_PER_PAGE;
        $offset = $input['offset'] ?? 0;
        $unreadOnly = $input['unread_only'] ?? false;
        $search = $input['search'] ?? null;

        $articles = getArticles($user['id'], $feedId, $limit, $offset, $unreadOnly, $search);
        $total = getArticleCount($user['id'], $feedId, $unreadOnly, $search);

        jsonResponse([
            'success' => true,
            'articles' => $articles,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
        break;

    case 'mark_read':
        $articleId = $input['article_id'] ?? null;
        $read = $input['read'] ?? true;

        if (!$articleId) {
            jsonResponse(['success' => false, 'error' => 'Article ID required'], 400);
        }

        if (markArticleRead($user['id'], $articleId, $read)) {
            jsonResponse(['success' => true, 'message' => 'Article marked']);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to mark article'], 500);
        }
        break;

    case 'mark_all_read':
        $articleIds = $input['article_ids'] ?? null;
        $count = markAllArticlesRead($user['id'], $articleIds);

        jsonResponse([
            'success' => true,
            'message' => "$count articles marked as read",
            'count' => $count
        ]);
        break;

    case 'toggle_favorite':
        $articleId = $input['article_id'] ?? null;

        if (!$articleId) {
            jsonResponse(['success' => false, 'error' => 'Article ID required'], 400);
        }

        if (toggleArticleFavorite($user['id'], $articleId)) {
            jsonResponse(['success' => true, 'message' => 'Favorite toggled']);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to toggle favorite'], 500);
        }
        break;

    case 'get_stats':
        $stats = getUserStats($user['id']);
        jsonResponse(['success' => true, 'stats' => $stats]);
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
}

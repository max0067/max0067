<?php
require_once '../config.php';

// Allow GET for some actions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $user = requireApiAuth();

    switch ($_GET['action']) {
        case 'list':
            $feeds = getUserFeeds($user['id']);
            jsonResponse(['success' => true, 'feeds' => $feeds]);
            break;

        case 'get':
            $feedId = $_GET['feed_id'] ?? null;
            if (!$feedId) {
                jsonResponse(['success' => false, 'error' => 'Feed ID required'], 400);
            }
            $feed = getFeedById($feedId, $user['id']);
            if ($feed) {
                jsonResponse(['success' => true, 'feed' => $feed]);
            } else {
                jsonResponse(['success' => false, 'error' => 'Feed not found'], 404);
            }
            break;

        default:
            jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$user = requireApiAuth();
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'add':
        $url = $input['url'] ?? '';
        $title = $input['title'] ?? '';
        $description = $input['description'] ?? '';

        if (empty($url) || empty($title)) {
            jsonResponse(['success' => false, 'error' => 'URL and title required'], 400);
        }

        // Validate URL
        if (!isValidUrl($url)) {
            jsonResponse(['success' => false, 'error' => 'Invalid URL'], 400);
        }

        // Try to fetch feed info if title not provided
        if (empty($title)) {
            $feedInfo = getFeedInfoFromUrl($url);
            if ($feedInfo) {
                $title = $feedInfo['title'];
                $description = $feedInfo['description'];
            } else {
                jsonResponse(['success' => false, 'error' => 'Could not fetch feed information'], 400);
            }
        }

        $feedId = addFeed($user['id'], $title, $url, $description);

        if ($feedId) {
            // Update feed immediately after adding
            $newArticles = updateSingleFeed($feedId);
            jsonResponse([
                'success' => true,
                'message' => 'Feed added successfully',
                'feed_id' => $feedId,
                'new_articles' => $newArticles
            ]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to add feed (maybe duplicate)'], 500);
        }
        break;

    case 'update':
        $feedId = $input['feed_id'] ?? null;
        $title = $input['title'] ?? null;
        $url = $input['url'] ?? null;
        $description = $input['description'] ?? null;
        $active = $input['active'] ?? null;

        if (!$feedId) {
            jsonResponse(['success' => false, 'error' => 'Feed ID required'], 400);
        }

        $updates = [];
        if ($title !== null) $updates['title'] = $title;
        if ($url !== null) $updates['url'] = $url;
        if ($description !== null) $updates['description'] = $description;
        if ($active !== null) $updates['active'] = $active ? 1 : 0;

        if (empty($updates)) {
            jsonResponse(['success' => false, 'error' => 'No updates provided'], 400);
        }

        if (updateFeed($feedId, $user['id'], $updates)) {
            jsonResponse(['success' => true, 'message' => 'Feed updated successfully']);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to update feed'], 500);
        }
        break;

    case 'delete':
        $feedId = $input['feed_id'] ?? null;

        if (!$feedId) {
            jsonResponse(['success' => false, 'error' => 'Feed ID required'], 400);
        }

        if (deleteFeed($feedId, $user['id'])) {
            jsonResponse(['success' => true, 'message' => 'Feed deleted successfully']);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to delete feed'], 500);
        }
        break;

    case 'update_single':
        $feedId = $input['feed_id'] ?? null;

        if (!$feedId) {
            jsonResponse(['success' => false, 'error' => 'Feed ID required'], 400);
        }

        // Verify user owns this feed
        $feed = getFeedById($feedId, $user['id']);
        if (!$feed) {
            jsonResponse(['success' => false, 'error' => 'Feed not found'], 404);
        }

        $newArticles = updateSingleFeed($feedId);

        jsonResponse([
            'success' => true,
            'message' => 'Feed updated successfully',
            'new_articles' => $newArticles
        ]);
        break;

    case 'update_all':
        // Update all feeds for current user
        $feeds = getActiveFeeds($user['id']);
        $totalNewArticles = 0;

        foreach ($feeds as $feed) {
            $newArticles = updateSingleFeed($feed['id']);
            $totalNewArticles += $newArticles;
        }

        jsonResponse([
            'success' => true,
            'message' => 'All feeds updated',
            'new_articles' => $totalNewArticles,
            'feeds_count' => count($feeds)
        ]);
        break;

    case 'validate_url':
        $url = $input['url'] ?? '';

        if (empty($url)) {
            jsonResponse(['success' => false, 'error' => 'URL required'], 400);
        }

        if (!isValidUrl($url)) {
            jsonResponse(['success' => false, 'valid' => false, 'error' => 'Invalid URL format']);
        }

        $feedInfo = getFeedInfoFromUrl($url);

        if ($feedInfo) {
            jsonResponse([
                'success' => true,
                'valid' => true,
                'title' => $feedInfo['title'],
                'description' => $feedInfo['description']
            ]);
        } else {
            jsonResponse(['success' => true, 'valid' => false, 'error' => 'Could not fetch feed']);
        }
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
}

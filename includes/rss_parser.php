<?php
/**
 * RSS Feed Parser
 * Replicates functionality from rss_updater.py
 */

/**
 * Fetch and parse RSS feed
 */
function fetchFeed($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, RSS_FETCH_TIMEOUT);
    curl_setopt($ch, CURLOPT_USERAGENT, 'RSS Manager Pro/' . APP_VERSION);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For compatibility

    $xml = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$xml) {
        return null;
    }

    return parseFeedXml($xml);
}

/**
 * Parse RSS/Atom XML
 */
function parseFeedXml($xml) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();

    if (!$dom->loadXML($xml)) {
        logMessage('Failed to parse XML: ' . implode(', ', libxml_get_errors()), 'ERROR');
        return null;
    }

    $xpath = new DOMXPath($dom);

    // Detect feed type (RSS or Atom)
    $rssChannel = $xpath->query('//channel');
    $atomFeed = $xpath->query('//feed');

    if ($rssChannel->length > 0) {
        return parseRssFeed($xpath);
    } elseif ($atomFeed->length > 0) {
        return parseAtomFeed($xpath);
    }

    return null;
}

/**
 * Parse RSS 2.0 feed
 */
function parseRssFeed($xpath) {
    $entries = [];
    $items = $xpath->query('//channel/item');

    foreach ($items as $item) {
        $entry = [];

        // Title
        $titleNode = $xpath->query('title', $item);
        $entry['title'] = $titleNode->length > 0 ? trim($titleNode->item(0)->textContent) : 'No title';

        // Link
        $linkNode = $xpath->query('link', $item);
        $entry['link'] = $linkNode->length > 0 ? trim($linkNode->item(0)->textContent) : '';

        if (empty($entry['link'])) {
            continue; // Skip entries without links
        }

        // Description
        $descNode = $xpath->query('description', $item);
        $entry['description'] = $descNode->length > 0 ? trim($descNode->item(0)->textContent) : '';

        // Author
        $authorNode = $xpath->query('author', $item);
        if ($authorNode->length === 0) {
            $authorNode = $xpath->query('dc:creator', $item);
        }
        $entry['author'] = $authorNode->length > 0 ? trim($authorNode->item(0)->textContent) : '';

        // Published date
        $pubDateNode = $xpath->query('pubDate', $item);
        $entry['published_date'] = $pubDateNode->length > 0 ? parseDate($pubDateNode->item(0)->textContent) : null;

        // Content
        $contentNode = $xpath->query('content:encoded', $item);
        if ($contentNode->length > 0) {
            $entry['content'] = trim($contentNode->item(0)->textContent);
        } else {
            $entry['content'] = $entry['description'];
        }

        $entries[] = $entry;
    }

    return $entries;
}

/**
 * Parse Atom feed
 */
function parseAtomFeed($xpath) {
    $xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');
    $entries = [];
    $items = $xpath->query('//atom:entry');

    foreach ($items as $item) {
        $entry = [];

        // Title
        $titleNode = $xpath->query('atom:title', $item);
        $entry['title'] = $titleNode->length > 0 ? trim($titleNode->item(0)->textContent) : 'No title';

        // Link
        $linkNode = $xpath->query('atom:link[@rel="alternate"]/@href', $item);
        if ($linkNode->length === 0) {
            $linkNode = $xpath->query('atom:link/@href', $item);
        }
        $entry['link'] = $linkNode->length > 0 ? trim($linkNode->item(0)->textContent) : '';

        if (empty($entry['link'])) {
            continue;
        }

        // Summary/Description
        $summaryNode = $xpath->query('atom:summary', $item);
        $entry['description'] = $summaryNode->length > 0 ? trim($summaryNode->item(0)->textContent) : '';

        // Author
        $authorNode = $xpath->query('atom:author/atom:name', $item);
        $entry['author'] = $authorNode->length > 0 ? trim($authorNode->item(0)->textContent) : '';

        // Published date
        $publishedNode = $xpath->query('atom:published', $item);
        if ($publishedNode->length === 0) {
            $publishedNode = $xpath->query('atom:updated', $item);
        }
        $entry['published_date'] = $publishedNode->length > 0 ? parseDate($publishedNode->item(0)->textContent) : null;

        // Content
        $contentNode = $xpath->query('atom:content', $item);
        if ($contentNode->length > 0) {
            $entry['content'] = trim($contentNode->item(0)->textContent);
        } else {
            $entry['content'] = $entry['description'];
        }

        $entries[] = $entry;
    }

    return $entries;
}

/**
 * Parse various date formats
 */
function parseDate($dateString) {
    if (empty($dateString)) {
        return null;
    }

    try {
        $timestamp = strtotime($dateString);
        if ($timestamp === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $timestamp);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Update a single RSS feed
 */
function updateSingleFeed($feedId) {
    $feedInfo = getFeedById($feedId);
    if (!$feedInfo) {
        logMessage("Feed $feedId not found", 'ERROR');
        return 0;
    }

    logMessage("Updating feed: {$feedInfo['title']} ({$feedInfo['url']})", 'INFO');

    $entries = fetchFeed($feedInfo['url']);
    if (!$entries) {
        logMessage("Failed to fetch feed: {$feedInfo['title']}", 'ERROR');
        return 0;
    }

    $newArticles = 0;

    foreach ($entries as $entry) {
        $articleId = addArticle(
            $feedInfo['id'],
            $entry['title'],
            $entry['link'],
            $entry['description'],
            $entry['author'],
            $entry['published_date'],
            $entry['content']
        );

        if ($articleId) {
            $newArticles++;
            logMessage("New article: {$entry['title']}", 'INFO');
        }
    }

    updateFeedTimestamp($feedId);
    logMessage("Feed {$feedInfo['title']}: $newArticles new articles", 'INFO');

    return $newArticles;
}

/**
 * Update all active feeds
 */
function updateAllFeeds() {
    logMessage("Starting update of all feeds", 'INFO');
    $feeds = getActiveFeeds();

    $totalNewArticles = 0;
    foreach ($feeds as $feed) {
        $newArticles = updateSingleFeed($feed['id']);
        $totalNewArticles += $newArticles;
    }

    logMessage("Update completed: $totalNewArticles new articles total", 'INFO');
    return $totalNewArticles;
}

/**
 * Validate RSS feed URL
 */
function validateFeedUrl($url) {
    if (!isValidUrl($url)) {
        return false;
    }

    $entries = fetchFeed($url);
    return $entries !== null && count($entries) > 0;
}

/**
 * Get feed info from URL (before adding)
 */
function getFeedInfoFromUrl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, RSS_FETCH_TIMEOUT);
    curl_setopt($ch, CURLOPT_USERAGENT, 'RSS Manager Pro/' . APP_VERSION);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $xml = curl_exec($ch);
    curl_close($ch);

    if (!$xml) {
        return null;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();

    if (!$dom->loadXML($xml)) {
        return null;
    }

    $xpath = new DOMXPath($dom);
    $info = [];

    // Try RSS format
    $titleNode = $xpath->query('//channel/title');
    $descNode = $xpath->query('//channel/description');

    if ($titleNode->length > 0) {
        $info['title'] = trim($titleNode->item(0)->textContent);
        $info['description'] = $descNode->length > 0 ? trim($descNode->item(0)->textContent) : '';
        return $info;
    }

    // Try Atom format
    $xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');
    $titleNode = $xpath->query('//atom:feed/atom:title');
    $subtitleNode = $xpath->query('//atom:feed/atom:subtitle');

    if ($titleNode->length > 0) {
        $info['title'] = trim($titleNode->item(0)->textContent);
        $info['description'] = $subtitleNode->length > 0 ? trim($subtitleNode->item(0)->textContent) : '';
        return $info;
    }

    return null;
}

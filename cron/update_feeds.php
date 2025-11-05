<?php
/**
 * Cron Job: Update all RSS feeds
 *
 * Add to crontab:
 * */30 * * * * /usr/bin/php /home/wrbh3411/dusselle.fr/cron/update_feeds.php >> /home/wrbh3411/dusselle.fr/logs/cron.log 2>&1
 *
 * Or every 15 minutes:
 * */15 * * * * /usr/bin/php /home/wrbh3411/dusselle.fr/cron/update_feeds.php >> /home/wrbh3411/dusselle.fr/logs/cron.log 2>&1
 */

// Change to parent directory
chdir(dirname(__DIR__));

require_once 'config.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting RSS feed update...\n";

try {
    // Get all active feeds
    $feeds = getActiveFeeds();
    echo "Found " . count($feeds) . " active feeds\n";

    $totalNewArticles = 0;
    $successCount = 0;
    $errorCount = 0;

    foreach ($feeds as $feed) {
        try {
            echo "Updating feed #{$feed['id']}: {$feed['title']} ({$feed['url']})\n";
            $newArticles = updateSingleFeed($feed['id']);
            $totalNewArticles += $newArticles;
            $successCount++;
            echo "  → $newArticles new articles\n";
        } catch (Exception $e) {
            $errorCount++;
            echo "  → ERROR: " . $e->getMessage() . "\n";
            logMessage("Error updating feed #{$feed['id']}: " . $e->getMessage(), 'ERROR');
        }
    }

    echo "\n=== Update Summary ===\n";
    echo "Feeds processed: " . count($feeds) . "\n";
    echo "Successful: $successCount\n";
    echo "Errors: $errorCount\n";
    echo "Total new articles: $totalNewArticles\n";
    echo "[" . date('Y-m-d H:i:s') . "] Update completed\n\n";

    logMessage("Cron update completed: $totalNewArticles new articles from " . count($feeds) . " feeds", 'INFO');

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    logMessage("Cron update failed: " . $e->getMessage(), 'ERROR');
    exit(1);
}

exit(0);

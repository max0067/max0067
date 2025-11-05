<?php
/**
 * Script pour actualiser tous les flux RSS
 * Peut être appelé manuellement ou via CRON
 */

session_start();

require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/RSSFeed.php';
require_once 'includes/helpers.php';

$rssFeed = new RSSFeed();

try {
    $stats = $rssFeed->refreshAll();

    $message = sprintf(
        "Actualisation terminée : %d flux traités, %d nouveaux articles récupérés",
        $stats['total'],
        $stats['new_articles']
    );

    if ($stats['errors'] > 0) {
        $message .= sprintf(" (%d erreurs)", $stats['errors']);
    }

    setFlash('success', $message);
} catch (Exception $e) {
    setFlash('danger', "Erreur lors de l'actualisation : " . $e->getMessage());
}

redirect('index.php');

#!/usr/bin/env php
<?php
/**
 * Script CRON pour la mise à jour automatique des flux RSS
 *
 * Configuration CRON recommandée (toutes les 30 minutes):
 *
 * */30 * * * * /usr/bin/php /chemin/vers/votre/application/cron_update_feeds.php
 *
 * Ou avec log:
 * */30 * * * * /usr/bin/php /chemin/vers/votre/application/cron_update_feeds.php >> /chemin/vers/logs/cron.log 2>&1
 */

// Vérifier que le script est exécuté en CLI
if (php_sapi_name() !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande uniquement.\n");
}

// Inclure les dépendances
require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/database.php';
require_once __DIR__ . '/php/rss_updater.php';

echo "=== Démarrage de la mise à jour des flux RSS ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $updater = new RSSUpdater();
    $totalNew = $updater->updateAllFeeds();

    echo "\n=== Mise à jour terminée avec succès ===\n";
    echo "Nouveaux articles: $totalNew\n";
    echo "Date de fin: " . date('Y-m-d H:i:s') . "\n";

    exit(0);
} catch (Exception $e) {
    echo "\n=== ERREUR lors de la mise à jour ===\n";
    echo "Erreur: " . $e->getMessage() . "\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n";

    log_message('ERROR', 'Erreur CRON: ' . $e->getMessage());

    exit(1);
}

<?php
/**
 * Gestionnaire de mise à jour des flux RSS
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

class RSSUpdater {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Parse une date de différents formats
     */
    private function parseDate($dateString) {
        if (empty($dateString)) {
            return null;
        }

        try {
            $date = new DateTime($dateString);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            log_message('WARNING', "Impossible de parser la date: $dateString");
            return null;
        }
    }

    /**
     * Récupère et parse un flux RSS
     */
    public function fetchFeed($feedUrl) {
        try {
            log_message('INFO', "Récupération du flux: $feedUrl");

            // Configuration du contexte pour les requêtes HTTP
            $context = stream_context_create([
                'http' => [
                    'user_agent' => 'PHP RSS Reader/1.0',
                    'timeout' => 30
                ]
            ]);

            // Récupérer le contenu du flux
            $xmlContent = @file_get_contents($feedUrl, false, $context);

            if ($xmlContent === false) {
                log_message('ERROR', "Impossible de récupérer le flux: $feedUrl");
                return null;
            }

            // Parser le XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xmlContent);

            if ($xml === false) {
                $errors = libxml_get_errors();
                foreach ($errors as $error) {
                    log_message('ERROR', "Erreur XML: " . trim($error->message));
                }
                libxml_clear_errors();
                return null;
            }

            return $xml;
        } catch (Exception $e) {
            log_message('ERROR', "Erreur lors de la récupération du flux $feedUrl: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse les entrées d'un flux RSS/Atom
     */
    private function parseEntries($xml) {
        $entries = [];

        // Détecter le type de flux
        if (isset($xml->channel->item)) {
            // RSS 2.0
            foreach ($xml->channel->item as $item) {
                $entries[] = [
                    'title' => (string)$item->title,
                    'link' => (string)$item->link,
                    'description' => (string)($item->description ?? ''),
                    'author' => (string)($item->author ?? $item->creator ?? ''),
                    'published_date' => (string)($item->pubDate ?? ''),
                    'content' => (string)($item->description ?? $item->content ?? '')
                ];
            }
        } elseif (isset($xml->item)) {
            // RSS 1.0
            foreach ($xml->item as $item) {
                $entries[] = [
                    'title' => (string)$item->title,
                    'link' => (string)$item->link,
                    'description' => (string)($item->description ?? ''),
                    'author' => (string)($item->creator ?? ''),
                    'published_date' => (string)($item->date ?? ''),
                    'content' => (string)($item->description ?? '')
                ];
            }
        } elseif (isset($xml->entry)) {
            // Atom
            foreach ($xml->entry as $entry) {
                // Pour Atom, le lien peut être un attribut
                $link = '';
                if (isset($entry->link)) {
                    if (isset($entry->link['href'])) {
                        $link = (string)$entry->link['href'];
                    } else {
                        $link = (string)$entry->link;
                    }
                }

                // Le contenu dans Atom peut être dans content ou summary
                $content = '';
                if (isset($entry->content)) {
                    $content = (string)$entry->content;
                } elseif (isset($entry->summary)) {
                    $content = (string)$entry->summary;
                }

                $entries[] = [
                    'title' => (string)($entry->title ?? 'Sans titre'),
                    'link' => $link,
                    'description' => (string)($entry->summary ?? ''),
                    'author' => (string)($entry->author->name ?? ''),
                    'published_date' => (string)($entry->published ?? $entry->updated ?? ''),
                    'content' => $content
                ];
            }
        }

        return $entries;
    }

    /**
     * Met à jour un seul flux RSS
     */
    public function updateSingleFeed($feedId) {
        $feedInfo = $this->db->getFeedById($feedId);
        if (!$feedInfo) {
            log_message('ERROR', "Flux $feedId non trouvé");
            return 0;
        }

        log_message('INFO', "Mise à jour du flux: {$feedInfo['title']} ({$feedInfo['url']})");

        $xml = $this->fetchFeed($feedInfo['url']);
        if (!$xml) {
            log_message('ERROR', "Impossible de récupérer le flux {$feedInfo['title']}");
            return 0;
        }

        $entries = $this->parseEntries($xml);
        $newArticles = 0;

        foreach ($entries as $entry) {
            $link = $entry['link'];
            if (empty($link)) {
                continue;
            }

            $title = $entry['title'] ?: 'Sans titre';
            $description = $entry['description'];
            $author = $entry['author'];
            $content = $entry['content'] ?: $description;
            $publishedDate = $this->parseDate($entry['published_date']);

            // Ajouter l'article à la base de données
            $articleId = $this->db->addArticle(
                $feedId,
                $title,
                $link,
                $description,
                $author,
                $publishedDate,
                $content
            );

            if ($articleId) {
                $newArticles++;
                log_message('INFO', "Nouvel article: $title");
            }
        }

        // Mettre à jour le timestamp du flux
        $this->db->updateFeedTimestamp($feedId);

        log_message('INFO', "Flux {$feedInfo['title']}: $newArticles nouveaux articles");
        return $newArticles;
    }

    /**
     * Met à jour tous les flux actifs
     */
    public function updateAllFeeds($userId = null) {
        log_message('INFO', "Début de la mise à jour de tous les flux");
        $feeds = $this->db->getActiveFeeds($userId);

        $totalNewArticles = 0;
        foreach ($feeds as $feed) {
            $newArticles = $this->updateSingleFeed($feed['id']);
            $totalNewArticles += $newArticles;
        }

        log_message('INFO', "Mise à jour terminée: $totalNewArticles nouveaux articles au total");
        return $totalNewArticles;
    }
}

// Si ce script est exécuté directement (pour un cron job par exemple)
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $updater = new RSSUpdater();
    $totalNew = $updater->updateAllFeeds();
    echo "Mise à jour terminée: $totalNew nouveaux articles\n";
}

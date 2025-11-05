<?php
/**
 * Classe de gestion des flux RSS
 */

class RSSFeed {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupérer tous les flux RSS
     */
    public function getAll() {
        $sql = "SELECT * FROM rss_feeds ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Récupérer un flux par son ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM rss_feeds WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Ajouter un nouveau flux RSS
     */
    public function create($name, $url, $category = null) {
        // Validation de l'URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception("URL invalide");
        }

        // Vérifier que le flux RSS est valide
        if (!$this->validateRSSFeed($url)) {
            throw new Exception("URL ne pointe pas vers un flux RSS valide");
        }

        $sql = "INSERT INTO rss_feeds (name, url, category, created_at)
                VALUES (?, ?, ?, NOW())";

        $this->db->query($sql, [$name, $url, $category]);
        return $this->db->lastInsertId();
    }

    /**
     * Mettre à jour un flux RSS
     */
    public function update($id, $name, $url, $category = null) {
        // Validation de l'URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception("URL invalide");
        }

        $sql = "UPDATE rss_feeds
                SET name = ?, url = ?, category = ?
                WHERE id = ?";

        $this->db->query($sql, [$name, $url, $category, $id]);
        return true;
    }

    /**
     * Supprimer un flux RSS et ses articles
     */
    public function delete($id) {
        // Supprimer d'abord les articles associés
        $sqlArticles = "DELETE FROM articles WHERE feed_id = ?";
        $this->db->query($sqlArticles, [$id]);

        // Puis supprimer le flux
        $sql = "DELETE FROM rss_feeds WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * Valider qu'une URL est bien un flux RSS
     */
    private function validateRSSFeed($url) {
        try {
            $xml = @file_get_contents($url, false, stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'RSS Legal Watch/1.0'
                ]
            ]));

            if ($xml === false) {
                return false;
            }

            $feed = @simplexml_load_string($xml);
            if ($feed === false) {
                return false;
            }

            // Vérifier que c'est bien un RSS ou Atom
            return (isset($feed->channel) || isset($feed->entry));
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Rafraîchir les articles d'un flux RSS
     */
    public function refresh($feedId) {
        $feed = $this->getById($feedId);
        if (!$feed) {
            throw new Exception("Flux introuvable");
        }

        $xml = @file_get_contents($feed['url'], false, stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'RSS Legal Watch/1.0'
            ]
        ]));

        if ($xml === false) {
            throw new Exception("Impossible de récupérer le flux");
        }

        $rss = @simplexml_load_string($xml);
        if ($rss === false) {
            throw new Exception("XML invalide");
        }

        $newArticles = 0;
        $article = new Article();

        // Format RSS 2.0
        if (isset($rss->channel->item)) {
            foreach ($rss->channel->item as $item) {
                $title = (string)($item->title ?? '');
                $link = (string)($item->link ?? '');
                $description = (string)($item->description ?? '');
                $pubDate = (string)($item->pubDate ?? '');

                if (empty($title) || empty($link)) {
                    continue;
                }

                // Convertir la date
                $publishedDate = $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : date('Y-m-d H:i:s');

                // Vérifier si l'article existe déjà
                if (!$article->exists($feedId, $link)) {
                    $article->create($feedId, $title, $link, $publishedDate, $description);
                    $newArticles++;
                }
            }
        }
        // Format Atom
        elseif (isset($rss->entry)) {
            foreach ($rss->entry as $entry) {
                $title = (string)($entry->title ?? '');
                $link = '';

                if (isset($entry->link)) {
                    $link = (string)($entry->link['href'] ?? $entry->link);
                }

                $description = (string)($entry->summary ?? $entry->content ?? '');
                $published = (string)($entry->published ?? $entry->updated ?? '');

                if (empty($title) || empty($link)) {
                    continue;
                }

                $publishedDate = $published ? date('Y-m-d H:i:s', strtotime($published)) : date('Y-m-d H:i:s');

                if (!$article->exists($feedId, $link)) {
                    $article->create($feedId, $title, $link, $publishedDate, $description);
                    $newArticles++;
                }
            }
        }

        return $newArticles;
    }

    /**
     * Rafraîchir tous les flux RSS
     */
    public function refreshAll() {
        $feeds = $this->getAll();
        $stats = [
            'total' => count($feeds),
            'new_articles' => 0,
            'errors' => 0
        ];

        foreach ($feeds as $feed) {
            try {
                $newArticles = $this->refresh($feed['id']);
                $stats['new_articles'] += $newArticles;
            } catch (Exception $e) {
                $stats['errors']++;
            }
        }

        return $stats;
    }
}

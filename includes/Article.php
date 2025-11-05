<?php
/**
 * Classe de gestion des articles
 */

class Article {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupérer tous les articles avec leurs flux
     */
    public function getAll($limit = null, $offset = 0, $search = null) {
        $sql = "SELECT a.*, f.name as feed_name, f.category
                FROM articles a
                LEFT JOIN rss_feeds f ON a.feed_id = f.id";

        $params = [];

        // Ajout de la recherche si fournie
        if ($search) {
            $sql .= " WHERE (a.title LIKE ? OR a.description LIKE ? OR f.name LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }

        $sql .= " ORDER BY a.pub_date DESC";

        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Compter le nombre total d'articles
     */
    public function count($search = null) {
        $sql = "SELECT COUNT(*) as total
                FROM articles a
                LEFT JOIN rss_feeds f ON a.feed_id = f.id";

        $params = [];

        if ($search) {
            $sql .= " WHERE (a.title LIKE ? OR a.description LIKE ? OR f.name LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }

        $result = $this->db->fetchOne($sql, $params);
        return $result['total'] ?? 0;
    }

    /**
     * Récupérer les articles récents (dernières 48h)
     */
    public function getRecent($limit = 10) {
        $sql = "SELECT a.*, f.name as feed_name, f.category
                FROM articles a
                LEFT JOIN rss_feeds f ON a.feed_id = f.id
                WHERE a.pub_date >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                ORDER BY a.pub_date DESC
                LIMIT ?";

        return $this->db->fetchAll($sql, [(int)$limit]);
    }

    /**
     * Récupérer un article par son ID
     */
    public function getById($id) {
        $sql = "SELECT a.*, f.name as feed_name, f.url as feed_url, f.category
                FROM articles a
                LEFT JOIN rss_feeds f ON a.feed_id = f.id
                WHERE a.id = ?";

        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Vérifier si un article existe déjà
     */
    public function exists($feedId, $link) {
        $sql = "SELECT COUNT(*) as count FROM articles WHERE feed_id = ? AND link = ?";
        $result = $this->db->fetchOne($sql, [$feedId, $link]);
        return $result['count'] > 0;
    }

    /**
     * Créer un nouvel article
     */
    public function create($feedId, $title, $link, $pubDate, $description = null) {
        $sql = "INSERT INTO articles (feed_id, title, link, pub_date, description, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";

        $this->db->query($sql, [$feedId, $title, $link, $pubDate, $description]);
        return $this->db->lastInsertId();
    }

    /**
     * Supprimer un article
     */
    public function delete($id) {
        $sql = "DELETE FROM articles WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }

    /**
     * Supprimer les anciens articles (plus de X jours)
     */
    public function deleteOld($days = 90) {
        $sql = "DELETE FROM articles WHERE pub_date < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $this->db->query($sql, [$days]);
        return true;
    }

    /**
     * Récupérer les statistiques
     */
    public function getStats() {
        $stats = [];

        // Total articles
        $sql = "SELECT COUNT(*) as total FROM articles";
        $result = $this->db->fetchOne($sql);
        $stats['total_articles'] = $result['total'];

        // Articles des dernières 24h
        $sql = "SELECT COUNT(*) as total FROM articles
                WHERE pub_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $result = $this->db->fetchOne($sql);
        $stats['last_24h'] = $result['total'];

        // Articles de la dernière semaine
        $sql = "SELECT COUNT(*) as total FROM articles
                WHERE pub_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $result = $this->db->fetchOne($sql);
        $stats['last_week'] = $result['total'];

        // Flux actifs
        $sql = "SELECT COUNT(*) as total FROM rss_feeds";
        $result = $this->db->fetchOne($sql);
        $stats['total_feeds'] = $result['total'];

        return $stats;
    }

    /**
     * Récupérer les articles par catégorie
     */
    public function getByCategory($category, $limit = null) {
        $sql = "SELECT a.*, f.name as feed_name, f.category
                FROM articles a
                LEFT JOIN rss_feeds f ON a.feed_id = f.id
                WHERE f.category = ?
                ORDER BY a.pub_date DESC";

        if ($limit) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$category, (int)$limit]);
        }

        return $this->db->fetchAll($sql, [$category]);
    }
}

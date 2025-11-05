<?php
/**
 * Classe de gestion de la base de données SQLite
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $this->pdo = new PDO('sqlite:' . DB_PATH);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->initDatabase();
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur de connexion à la base de données: ' . $e->getMessage());
            throw $e;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPDO() {
        return $this->pdo;
    }

    /**
     * Initialise la base de données avec les tables nécessaires
     */
    public function initDatabase() {
        try {
            // Table des utilisateurs
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT UNIQUE NOT NULL,
                    email TEXT UNIQUE NOT NULL,
                    password TEXT NOT NULL,
                    role TEXT DEFAULT 'user',
                    active INTEGER DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_login TIMESTAMP
                )
            ");

            // Table des sessions
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS sessions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    token TEXT UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
                )
            ");

            // Table des flux RSS
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS feeds (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    title TEXT NOT NULL,
                    url TEXT NOT NULL,
                    description TEXT,
                    update_interval INTEGER DEFAULT 30,
                    last_updated TIMESTAMP,
                    active INTEGER DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                    UNIQUE(user_id, url)
                )
            ");

            // Table des articles
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS articles (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    feed_id INTEGER NOT NULL,
                    title TEXT NOT NULL,
                    link TEXT NOT NULL,
                    description TEXT,
                    author TEXT,
                    published_date TIMESTAMP,
                    content TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (feed_id) REFERENCES feeds (id) ON DELETE CASCADE,
                    UNIQUE(feed_id, link)
                )
            ");

            // Table pour le statut de lecture par utilisateur
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS user_articles (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    article_id INTEGER NOT NULL,
                    read INTEGER DEFAULT 0,
                    favorite INTEGER DEFAULT 0,
                    read_at TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                    FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE,
                    UNIQUE(user_id, article_id)
                )
            ");

            // Création des index
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_articles_feed_id ON articles(feed_id)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_articles_published_date ON articles(published_date DESC)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_feeds_user_id ON feeds(user_id)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_articles_user_id ON user_articles(user_id)");
            $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(token)");

            // Créer un utilisateur admin par défaut si aucun admin n'existe
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
            $adminCount = $stmt->fetch()['count'];

            if ($adminCount == 0) {
                $adminPassword = $this->hashPassword('admin123');
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, password, role)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute(['admin', 'admin@example.com', $adminPassword, 'admin']);
                log_message('INFO', '✅ Utilisateur admin créé (username: admin, password: admin123)');
                log_message('WARNING', '⚠️  IMPORTANT: Changez le mot de passe admin après la première connexion!');
            }

            log_message('INFO', '✅ Base de données initialisée avec succès!');
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de l\'initialisation de la base de données: ' . $e->getMessage());
            throw $e;
        }
    }

    // ===== Fonctions utilitaires =====

    public function hashPassword($password) {
        return hash('sha256', $password);
    }

    public function generateToken() {
        return bin2hex(random_bytes(32));
    }

    // ===== Fonctions Utilisateurs =====

    public function createUser($username, $email, $password, $role = 'user') {
        try {
            $hashedPassword = $this->hashPassword($password);
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password, role)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$username, $email, $hashedPassword, $role]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la création de l\'utilisateur: ' . $e->getMessage());
            return null;
        }
    }

    public function authenticateUser($username, $password) {
        try {
            $hashedPassword = $this->hashPassword($password);
            $stmt = $this->pdo->prepare("
                SELECT * FROM users
                WHERE username = ? AND password = ? AND active = 1
            ");
            $stmt->execute([$username, $hashedPassword]);
            $user = $stmt->fetch();

            if ($user) {
                // Mettre à jour last_login
                $updateStmt = $this->pdo->prepare("
                    UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?
                ");
                $updateStmt->execute([$user['id']]);
                return $user;
            }
            return null;
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de l\'authentification: ' . $e->getMessage());
            return null;
        }
    }

    public function createSession($userId) {
        try {
            $token = $this->generateToken();
            $stmt = $this->pdo->prepare("
                INSERT INTO sessions (user_id, token, expires_at)
                VALUES (?, ?, datetime('now', '+7 days'))
            ");
            $stmt->execute([$userId, $token]);
            return $token;
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la création de session: ' . $e->getMessage());
            return null;
        }
    }

    public function getUserBySession($token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.* FROM users u
                JOIN sessions s ON u.id = s.user_id
                WHERE s.token = ? AND s.expires_at > datetime('now')
            ");
            $stmt->execute([$token]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la récupération de l\'utilisateur: ' . $e->getMessage());
            return null;
        }
    }

    public function deleteSession($token) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE token = ?");
            $stmt->execute([$token]);
            return true;
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la suppression de session: ' . $e->getMessage());
            return false;
        }
    }

    public function getAllUsers() {
        try {
            $stmt = $this->pdo->query("
                SELECT id, username, email, role, active, created_at, last_login
                FROM users ORDER BY created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la récupération des utilisateurs: ' . $e->getMessage());
            return [];
        }
    }

    public function updateUser($userId, $updates) {
        try {
            $fields = [];
            $params = [];

            foreach ($updates as $key => $value) {
                if (in_array($key, ['username', 'email', 'role', 'active'])) {
                    $fields[] = "$key = ?";
                    $params[] = $value;
                } elseif ($key === 'password') {
                    $fields[] = "password = ?";
                    $params[] = $this->hashPassword($value);
                }
            }

            if (empty($fields)) {
                return false;
            }

            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la mise à jour de l\'utilisateur: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteUser($userId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la suppression de l\'utilisateur: ' . $e->getMessage());
            return false;
        }
    }

    // ===== Fonctions Flux RSS =====

    public function addFeed($userId, $title, $url, $description = '', $updateInterval = 30) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO feeds (user_id, title, url, description, update_interval)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $title, $url, $description, $updateInterval]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de l\'ajout du flux: ' . $e->getMessage());
            return null;
        }
    }

    public function getUserFeeds($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT f.*, COUNT(a.id) as article_count
                FROM feeds f
                LEFT JOIN articles a ON f.id = a.feed_id
                WHERE f.user_id = ?
                GROUP BY f.id
                ORDER BY f.created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la récupération des flux: ' . $e->getMessage());
            return [];
        }
    }

    public function getFeedById($feedId, $userId = null) {
        try {
            $sql = "SELECT * FROM feeds WHERE id = ?";
            $params = [$feedId];

            if ($userId !== null) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la récupération du flux: ' . $e->getMessage());
            return null;
        }
    }

    public function updateFeed($feedId, $userId = null, $updates = []) {
        try {
            $fields = [];
            $params = [];

            foreach ($updates as $key => $value) {
                if (in_array($key, ['title', 'url', 'description', 'update_interval', 'active'])) {
                    $fields[] = "$key = ?";
                    $params[] = $value;
                }
            }

            if (empty($fields)) {
                return false;
            }

            $params[] = $feedId;
            $sql = "UPDATE feeds SET " . implode(', ', $fields) . " WHERE id = ?";

            if ($userId !== null) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la mise à jour du flux: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteFeed($feedId, $userId = null) {
        try {
            $sql = "DELETE FROM feeds WHERE id = ?";
            $params = [$feedId];

            if ($userId !== null) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la suppression du flux: ' . $e->getMessage());
            return false;
        }
    }

    public function getActiveFeeds($userId = null) {
        try {
            $sql = "SELECT * FROM feeds WHERE active = 1";
            $params = [];

            if ($userId !== null) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la récupération des flux actifs: ' . $e->getMessage());
            return [];
        }
    }

    public function updateFeedTimestamp($feedId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE feeds SET last_updated = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$feedId]);
            return true;
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la mise à jour du timestamp: ' . $e->getMessage());
            return false;
        }
    }

    // ===== Fonctions Articles =====

    public function addArticle($feedId, $title, $link, $description = '', $author = '', $publishedDate = null, $content = '') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO articles (feed_id, title, link, description, author, published_date, content)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$feedId, $title, $link, $description, $author, $publishedDate, $content]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            // L'article existe déjà (UNIQUE constraint)
            if ($e->getCode() == 23000) {
                return null;
            }
            log_message('ERROR', 'Erreur lors de l\'ajout de l\'article: ' . $e->getMessage());
            return null;
        }
    }

    public function getArticles($userId, $feedId = null, $limit = 100, $offset = 0, $unreadOnly = false, $searchQuery = null) {
        try {
            $sql = "
                SELECT a.*, f.title as feed_title,
                       COALESCE(ua.read, 0) as read,
                       COALESCE(ua.favorite, 0) as favorite
                FROM articles a
                JOIN feeds f ON a.feed_id = f.id
                LEFT JOIN user_articles ua ON a.id = ua.article_id AND ua.user_id = ?
                WHERE f.user_id = ?
            ";
            $params = [$userId, $userId];

            if ($feedId !== null) {
                $sql .= " AND a.feed_id = ?";
                $params[] = $feedId;
            }

            if ($unreadOnly) {
                $sql .= " AND COALESCE(ua.read, 0) = 0";
            }

            if ($searchQuery !== null) {
                $sql .= " AND (a.title LIKE ? OR a.description LIKE ? OR a.content LIKE ?)";
                $searchParam = "%$searchQuery%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }

            $sql .= " ORDER BY a.published_date DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la récupération des articles: ' . $e->getMessage());
            return [];
        }
    }

    public function getArticleCount($userId, $feedId = null, $unreadOnly = false, $searchQuery = null) {
        try {
            $sql = "
                SELECT COUNT(*) as count
                FROM articles a
                JOIN feeds f ON a.feed_id = f.id
                LEFT JOIN user_articles ua ON a.id = ua.article_id AND ua.user_id = ?
                WHERE f.user_id = ?
            ";
            $params = [$userId, $userId];

            if ($feedId !== null) {
                $sql .= " AND a.feed_id = ?";
                $params[] = $feedId;
            }

            if ($unreadOnly) {
                $sql .= " AND COALESCE(ua.read, 0) = 0";
            }

            if ($searchQuery !== null) {
                $sql .= " AND (a.title LIKE ? OR a.description LIKE ? OR a.content LIKE ?)";
                $searchParam = "%$searchQuery%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch()['count'];
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors du comptage des articles: ' . $e->getMessage());
            return 0;
        }
    }

    public function markArticleRead($userId, $articleId, $read = true) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_articles (user_id, article_id, read, read_at)
                VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                ON CONFLICT(user_id, article_id)
                DO UPDATE SET read = ?, read_at = CURRENT_TIMESTAMP
            ");
            $readValue = $read ? 1 : 0;
            $stmt->execute([$userId, $articleId, $readValue, $readValue]);
            return true;
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors du marquage de l\'article: ' . $e->getMessage());
            return false;
        }
    }

    public function markAllArticlesRead($userId, $articleIds = null) {
        try {
            if ($articleIds !== null && is_array($articleIds)) {
                $placeholders = str_repeat('?,', count($articleIds) - 1) . '?';
                $sql = "
                    INSERT OR REPLACE INTO user_articles (user_id, article_id, read, read_at)
                    SELECT ?, id, 1, CURRENT_TIMESTAMP
                    FROM articles
                    WHERE id IN ($placeholders)
                ";
                $params = array_merge([$userId], $articleIds);
            } else {
                $sql = "
                    INSERT OR REPLACE INTO user_articles (user_id, article_id, read, read_at)
                    SELECT ?, id, 1, CURRENT_TIMESTAMP
                    FROM articles a
                    INNER JOIN feeds f ON a.feed_id = f.id
                    WHERE f.user_id = ?
                ";
                $params = [$userId, $userId];
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors du marquage des articles: ' . $e->getMessage());
            return 0;
        }
    }

    public function toggleArticleFavorite($userId, $articleId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_articles (user_id, article_id, favorite)
                VALUES (?, ?, 1)
                ON CONFLICT(user_id, article_id)
                DO UPDATE SET favorite = 1 - favorite
            ");
            $stmt->execute([$userId, $articleId]);
            return true;
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors du basculement du favori: ' . $e->getMessage());
            return false;
        }
    }

    // ===== Fonctions Statistiques =====

    public function getUserStats($userId) {
        try {
            $stats = [];

            // Nombre de flux
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM feeds WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stats['total_feeds'] = $stmt->fetchColumn();

            // Nombre de flux actifs
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM feeds WHERE user_id = ? AND active = 1");
            $stmt->execute([$userId]);
            $stats['active_feeds'] = $stmt->fetchColumn();

            // Nombre total d'articles
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM articles a
                JOIN feeds f ON a.feed_id = f.id
                WHERE f.user_id = ?
            ");
            $stmt->execute([$userId]);
            $stats['total_articles'] = $stmt->fetchColumn();

            // Nombre d'articles non lus
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM articles a
                JOIN feeds f ON a.feed_id = f.id
                LEFT JOIN user_articles ua ON a.id = ua.article_id AND ua.user_id = ?
                WHERE f.user_id = ? AND COALESCE(ua.read, 0) = 0
            ");
            $stmt->execute([$userId, $userId]);
            $stats['unread_articles'] = $stmt->fetchColumn();

            // Nombre de favoris
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM user_articles
                WHERE user_id = ? AND favorite = 1
            ");
            $stmt->execute([$userId]);
            $stats['favorites'] = $stmt->fetchColumn();

            return $stats;
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la récupération des statistiques: ' . $e->getMessage());
            return [];
        }
    }

    public function getAdminStats() {
        try {
            $stats = [];

            // Nombre total d'utilisateurs
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
            $stats['total_users'] = $stmt->fetchColumn();

            // Utilisateurs actifs
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE active = 1");
            $stats['active_users'] = $stmt->fetchColumn();

            // Nombre total de flux
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM feeds");
            $stats['total_feeds'] = $stmt->fetchColumn();

            // Nombre total d'articles
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM articles");
            $stats['total_articles'] = $stmt->fetchColumn();

            // Articles par utilisateur (moyenne)
            $stmt = $this->pdo->query("
                SELECT AVG(article_count) FROM (
                    SELECT COUNT(a.id) as article_count
                    FROM users u
                    LEFT JOIN feeds f ON u.id = f.user_id
                    LEFT JOIN articles a ON f.id = a.feed_id
                    GROUP BY u.id
                )
            ");
            $stats['avg_articles_per_user'] = round($stmt->fetchColumn(), 1);

            return $stats;
        } catch (PDOException $e) {
            log_message('ERROR', 'Erreur lors de la récupération des statistiques admin: ' . $e->getMessage());
            return [];
        }
    }
}

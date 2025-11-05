<?php
/**
 * Database V2 - Gestion moderne avec auto-migration
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;
    private $initialized = false;

    private function __construct() {
        try {
            $this->connect();
            $this->autoMigrate();
            $this->initialized = true;
        } catch (Exception $e) {
            logMessage('ERROR', 'Database init failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            $this->pdo = new PDO('sqlite:' . DB_PATH);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Optimisations SQLite
            $this->pdo->exec('PRAGMA journal_mode = WAL');
            $this->pdo->exec('PRAGMA synchronous = NORMAL');
            $this->pdo->exec('PRAGMA foreign_keys = ON');

            logMessage('INFO', 'Database connected successfully');
        } catch (PDOException $e) {
            logMessage('ERROR', 'Database connection failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Auto-migration : crée ou met à jour le schéma
     */
    private function autoMigrate() {
        try {
            // Table de version pour suivre les migrations
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS migrations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    version INTEGER NOT NULL,
                    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            $currentVersion = $this->getCurrentVersion();
            $targetVersion = 1; // Version actuelle du schéma

            if ($currentVersion < $targetVersion) {
                $this->runMigration($targetVersion);
            }

            logMessage('INFO', 'Database schema up to date', ['version' => $currentVersion]);
        } catch (Exception $e) {
            logMessage('ERROR', 'Migration failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getCurrentVersion() {
        try {
            $stmt = $this->pdo->query("SELECT MAX(version) as version FROM migrations");
            $result = $stmt->fetch();
            return $result['version'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    private function runMigration($version) {
        logMessage('INFO', 'Running migration to version ' . $version);

        $this->pdo->beginTransaction();

        try {
            // Users table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT UNIQUE NOT NULL,
                    email TEXT UNIQUE NOT NULL,
                    password TEXT NOT NULL,
                    role TEXT DEFAULT 'user',
                    active INTEGER DEFAULT 1,
                    dark_mode INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_login TIMESTAMP
                )
            ");

            // Sessions table
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

            // Feeds table
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

            // Articles table
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

            // User articles (read/favorite status)
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

            // Indexes
            $indexes = [
                "CREATE INDEX IF NOT EXISTS idx_articles_feed_id ON articles(feed_id)",
                "CREATE INDEX IF NOT EXISTS idx_articles_published ON articles(published_date DESC)",
                "CREATE INDEX IF NOT EXISTS idx_feeds_user_id ON feeds(user_id)",
                "CREATE INDEX IF NOT EXISTS idx_user_articles_user ON user_articles(user_id)",
                "CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(token)"
            ];

            foreach ($indexes as $index) {
                $this->pdo->exec($index);
            }

            // Create admin if not exists
            $this->createDefaultAdmin();

            // Record migration
            $stmt = $this->pdo->prepare("INSERT INTO migrations (version) VALUES (?)");
            $stmt->execute([$version]);

            $this->pdo->commit();
            logMessage('INFO', 'Migration completed successfully', ['version' => $version]);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            logMessage('ERROR', 'Migration rollback: ' . $e->getMessage());
            throw $e;
        }
    }

    private function createDefaultAdmin() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            if ($stmt->fetchColumn() == 0) {
                $password = hash('sha256', 'admin123');
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (username, email, password, role)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute(['admin', 'admin@example.com', $password, 'admin']);
                logMessage('INFO', 'Default admin created (username: admin, password: admin123)');
            }
        } catch (Exception $e) {
            logMessage('WARNING', 'Could not create default admin: ' . $e->getMessage());
        }
    }

    public function getPDO() {
        return $this->pdo;
    }

    public function isInitialized() {
        return $this->initialized;
    }

    /**
     * Health check
     */
    public function healthCheck() {
        try {
            $this->pdo->query("SELECT 1");
            return ['status' => 'ok', 'database' => 'connected'];
        } catch (Exception $e) {
            return ['status' => 'error', 'database' => $e->getMessage()];
        }
    }

    // Utility methods
    public function hashPassword($password) {
        return hash('sha256', $password);
    }

    public function generateToken() {
        return bin2hex(random_bytes(32));
    }

    // === USER METHODS ===

    public function createUser($username, $email, $password, $role = 'user') {
        try {
            $hashedPassword = $this->hashPassword($password);
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password, role)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$username, $email, $hashedPassword, $role]);
            logMessage('INFO', 'User created', ['username' => $username]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            logMessage('ERROR', 'User creation failed', ['username' => $username, 'error' => $e->getMessage()]);
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
                $this->pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?")
                    ->execute([$user['id']]);
                logMessage('INFO', 'User authenticated', ['username' => $username]);
                return $user;
            }

            logMessage('WARNING', 'Authentication failed', ['username' => $username]);
            return null;
        } catch (Exception $e) {
            logMessage('ERROR', 'Authentication error', ['error' => $e->getMessage()]);
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
        } catch (Exception $e) {
            logMessage('ERROR', 'Session creation failed', ['error' => $e->getMessage()]);
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
        } catch (Exception $e) {
            logMessage('ERROR', 'Get user by session failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function deleteSession($token) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE token = ?");
            $stmt->execute([$token]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // === FEED METHODS ===

    public function addFeed($userId, $title, $url, $description = '', $updateInterval = 30) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO feeds (user_id, title, url, description, update_interval)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $title, $url, $description, $updateInterval]);
            logMessage('INFO', 'Feed added', ['url' => $url, 'user_id' => $userId]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            logMessage('ERROR', 'Feed creation failed', ['error' => $e->getMessage()]);
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
        } catch (Exception $e) {
            logMessage('ERROR', 'Get user feeds failed', ['error' => $e->getMessage()]);
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
        } catch (Exception $e) {
            return null;
        }
    }

    public function deleteFeed($feedId, $userId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM feeds WHERE id = ? AND user_id = ?");
            $stmt->execute([$feedId, $userId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    // === ARTICLE METHODS ===

    public function getArticles($userId, $feedId = null, $limit = 50, $offset = 0, $unreadOnly = false) {
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

            $sql .= " ORDER BY a.published_date DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            logMessage('ERROR', 'Get articles failed', ['error' => $e->getMessage()]);
            return [];
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
        } catch (Exception $e) {
            return false;
        }
    }

    public function getUserStats($userId) {
        try {
            $stats = [];

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM feeds WHERE user_id = ?");
            $stmt->execute([$userId]);
            $stats['total_feeds'] = $stmt->fetchColumn();

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM articles a
                JOIN feeds f ON a.feed_id = f.id
                WHERE f.user_id = ?
            ");
            $stmt->execute([$userId]);
            $stats['total_articles'] = $stmt->fetchColumn();

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM articles a
                JOIN feeds f ON a.feed_id = f.id
                LEFT JOIN user_articles ua ON a.id = ua.article_id AND ua.user_id = ?
                WHERE f.user_id = ? AND COALESCE(ua.read, 0) = 0
            ");
            $stmt->execute([$userId, $userId]);
            $stats['unread_articles'] = $stmt->fetchColumn();

            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
}

<?php
/**
 * Database Layer - SQLite PDO
 * Replicates functionality from database_v2.py
 */

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $this->conn = new PDO('sqlite:' . DB_PATH);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    /**
     * Initialize database with all tables
     */
    public static function initDB() {
        $db = self::getInstance()->getConnection();

        // Users table
        $db->exec("
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

        // Sessions table
        $db->exec("
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
        $db->exec("
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
        $db->exec("
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

        // User articles table (read/favorite status)
        $db->exec("
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

        // Create indexes
        $db->exec("CREATE INDEX IF NOT EXISTS idx_articles_feed_id ON articles(feed_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_articles_published_date ON articles(published_date DESC)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_feeds_user_id ON feeds(user_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_user_articles_user_id ON user_articles(user_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(token)");

        // Create default admin user if none exists
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $result = $stmt->fetch();

        if ($result['count'] == 0) {
            $admin_password = hash('sha256', 'admin123');
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password, role)
                VALUES (:username, :email, :password, :role)
            ");
            $stmt->execute([
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => $admin_password,
                'role' => 'admin'
            ]);
        }

        return true;
    }
}

// ===== USER FUNCTIONS =====

function hashPassword($password) {
    return hash('sha256', $password);
}

function createUser($username, $email, $password, $role = 'user') {
    $db = Database::getInstance()->getConnection();
    try {
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password, role)
            VALUES (:username, :email, :password, :role)
        ");
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => hashPassword($password),
            'role' => $role
        ]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        return null;
    }
}

function authenticateUser($username, $password) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT * FROM users
        WHERE username = :username AND password = :password AND active = 1
    ");
    $stmt->execute([
        'username' => $username,
        'password' => hashPassword($password)
    ]);
    $user = $stmt->fetch();

    if ($user) {
        // Update last login
        $updateStmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id");
        $updateStmt->execute(['id' => $user['id']]);
        return $user;
    }
    return null;
}

function createSession($userId) {
    $db = Database::getInstance()->getConnection();
    $token = bin2hex(random_bytes(32));
    $stmt = $db->prepare("
        INSERT INTO sessions (user_id, token, expires_at)
        VALUES (:user_id, :token, datetime('now', '+7 days'))
    ");
    $stmt->execute([
        'user_id' => $userId,
        'token' => $token
    ]);
    return $token;
}

function getUserBySession($token) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT u.* FROM users u
        JOIN sessions s ON u.id = s.user_id
        WHERE s.token = :token AND s.expires_at > datetime('now')
    ");
    $stmt->execute(['token' => $token]);
    return $stmt->fetch();
}

function deleteSession($token) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("DELETE FROM sessions WHERE token = :token");
    $stmt->execute(['token' => $token]);
    return $stmt->rowCount() > 0;
}

function getAllUsers() {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT id, username, email, role, active, created_at, last_login
        FROM users ORDER BY created_at DESC
    ");
    return $stmt->fetchAll();
}

function updateUser($userId, $updates) {
    $db = Database::getInstance()->getConnection();
    $allowedFields = ['username', 'email', 'role', 'active', 'password'];
    $setClause = [];
    $params = [];

    foreach ($updates as $key => $value) {
        if (in_array($key, $allowedFields)) {
            if ($key === 'password') {
                $setClause[] = "password = :password";
                $params['password'] = hashPassword($value);
            } else {
                $setClause[] = "$key = :$key";
                $params[$key] = $value;
            }
        }
    }

    if (empty($setClause)) {
        return false;
    }

    $params['id'] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $setClause) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount() > 0;
}

function deleteUser($userId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    return $stmt->rowCount() > 0;
}

// ===== FEED FUNCTIONS =====

function addFeed($userId, $title, $url, $description = '', $updateInterval = 30) {
    $db = Database::getInstance()->getConnection();
    try {
        $stmt = $db->prepare("
            INSERT INTO feeds (user_id, title, url, description, update_interval)
            VALUES (:user_id, :title, :url, :description, :update_interval)
        ");
        $stmt->execute([
            'user_id' => $userId,
            'title' => $title,
            'url' => $url,
            'description' => $description,
            'update_interval' => $updateInterval
        ]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        return null;
    }
}

function getUserFeeds($userId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT f.*, COUNT(a.id) as article_count
        FROM feeds f
        LEFT JOIN articles a ON f.id = a.feed_id
        WHERE f.user_id = :user_id
        GROUP BY f.id
        ORDER BY f.created_at DESC
    ");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function updateFeed($feedId, $userId, $updates) {
    $db = Database::getInstance()->getConnection();
    $allowedFields = ['title', 'url', 'description', 'update_interval', 'active'];
    $setClause = [];
    $params = [];

    foreach ($updates as $key => $value) {
        if (in_array($key, $allowedFields)) {
            $setClause[] = "$key = :$key";
            $params[$key] = $value;
        }
    }

    if (empty($setClause)) {
        return false;
    }

    $params['id'] = $feedId;
    $params['user_id'] = $userId;
    $sql = "UPDATE feeds SET " . implode(', ', $setClause) . " WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount() > 0;
}

function deleteFeed($feedId, $userId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("DELETE FROM feeds WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $feedId, 'user_id' => $userId]);
    return $stmt->rowCount() > 0;
}

function getFeedById($feedId, $userId = null) {
    $db = Database::getInstance()->getConnection();
    if ($userId !== null) {
        $stmt = $db->prepare("SELECT * FROM feeds WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $feedId, 'user_id' => $userId]);
    } else {
        $stmt = $db->prepare("SELECT * FROM feeds WHERE id = :id");
        $stmt->execute(['id' => $feedId]);
    }
    return $stmt->fetch();
}

function getActiveFeeds($userId = null) {
    $db = Database::getInstance()->getConnection();
    if ($userId !== null) {
        $stmt = $db->prepare("SELECT * FROM feeds WHERE active = 1 AND user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
    } else {
        $stmt = $db->query("SELECT * FROM feeds WHERE active = 1");
    }
    return $stmt->fetchAll();
}

function updateFeedTimestamp($feedId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE feeds SET last_updated = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->execute(['id' => $feedId]);
}

// ===== ARTICLE FUNCTIONS =====

function addArticle($feedId, $title, $link, $description = '', $author = '', $publishedDate = null, $content = '') {
    $db = Database::getInstance()->getConnection();
    try {
        $stmt = $db->prepare("
            INSERT INTO articles (feed_id, title, link, description, author, published_date, content)
            VALUES (:feed_id, :title, :link, :description, :author, :published_date, :content)
        ");
        $stmt->execute([
            'feed_id' => $feedId,
            'title' => $title,
            'link' => $link,
            'description' => $description,
            'author' => $author,
            'published_date' => $publishedDate,
            'content' => $content
        ]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        return null; // Duplicate article
    }
}

function getArticles($userId, $feedId = null, $limit = 10000, $offset = 0, $unreadOnly = false, $searchQuery = null) {
    $db = Database::getInstance()->getConnection();

    $sql = "
        SELECT a.*, f.title as feed_title,
               COALESCE(ua.read, 0) as read,
               COALESCE(ua.favorite, 0) as favorite
        FROM articles a
        JOIN feeds f ON a.feed_id = f.id
        LEFT JOIN user_articles ua ON a.id = ua.article_id AND ua.user_id = :user_id
        WHERE f.user_id = :user_id2
    ";

    $params = ['user_id' => $userId, 'user_id2' => $userId];

    if ($feedId) {
        $sql .= " AND a.feed_id = :feed_id";
        $params['feed_id'] = $feedId;
    }

    if ($unreadOnly) {
        $sql .= " AND COALESCE(ua.read, 0) = 0";
    }

    if ($searchQuery) {
        $sql .= " AND (a.title LIKE :search OR a.description LIKE :search2 OR a.content LIKE :search3)";
        $searchParam = "%$searchQuery%";
        $params['search'] = $searchParam;
        $params['search2'] = $searchParam;
        $params['search3'] = $searchParam;
    }

    $sql .= " ORDER BY a.published_date DESC LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function markArticleRead($userId, $articleId, $read = true) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO user_articles (user_id, article_id, read, read_at)
        VALUES (:user_id, :article_id, :read, CURRENT_TIMESTAMP)
        ON CONFLICT(user_id, article_id)
        DO UPDATE SET read = :read2, read_at = CURRENT_TIMESTAMP
    ");
    $readValue = $read ? 1 : 0;
    $stmt->execute([
        'user_id' => $userId,
        'article_id' => $articleId,
        'read' => $readValue,
        'read2' => $readValue
    ]);
    return true;
}

function markAllArticlesRead($userId, $articleIds = null) {
    $db = Database::getInstance()->getConnection();

    if ($articleIds) {
        $placeholders = implode(',', array_fill(0, count($articleIds), '?'));
        $stmt = $db->prepare("
            INSERT OR REPLACE INTO user_articles (user_id, article_id, read, read_at)
            SELECT ?, id, 1, CURRENT_TIMESTAMP
            FROM articles
            WHERE id IN ($placeholders)
        ");
        $params = array_merge([$userId], $articleIds);
        $stmt->execute($params);
    } else {
        $stmt = $db->prepare("
            INSERT OR REPLACE INTO user_articles (user_id, article_id, read, read_at)
            SELECT ?, id, 1, CURRENT_TIMESTAMP
            FROM articles a
            INNER JOIN feeds f ON a.feed_id = f.id
            WHERE f.user_id = ?
        ");
        $stmt->execute([$userId, $userId]);
    }

    return $stmt->rowCount();
}

function toggleArticleFavorite($userId, $articleId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO user_articles (user_id, article_id, favorite)
        VALUES (:user_id, :article_id, 1)
        ON CONFLICT(user_id, article_id)
        DO UPDATE SET favorite = 1 - favorite
    ");
    $stmt->execute([
        'user_id' => $userId,
        'article_id' => $articleId
    ]);
    return true;
}

function getArticleCount($userId, $feedId = null, $unreadOnly = false, $searchQuery = null) {
    $db = Database::getInstance()->getConnection();

    $sql = "
        SELECT COUNT(*) as count
        FROM articles a
        JOIN feeds f ON a.feed_id = f.id
        LEFT JOIN user_articles ua ON a.id = ua.article_id AND ua.user_id = :user_id
        WHERE f.user_id = :user_id2
    ";

    $params = ['user_id' => $userId, 'user_id2' => $userId];

    if ($feedId) {
        $sql .= " AND a.feed_id = :feed_id";
        $params['feed_id'] = $feedId;
    }

    if ($unreadOnly) {
        $sql .= " AND COALESCE(ua.read, 0) = 0";
    }

    if ($searchQuery) {
        $sql .= " AND (a.title LIKE :search OR a.description LIKE :search2 OR a.content LIKE :search3)";
        $searchParam = "%$searchQuery%";
        $params['search'] = $searchParam;
        $params['search2'] = $searchParam;
        $params['search3'] = $searchParam;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result['count'];
}

// ===== STATISTICS FUNCTIONS =====

function getUserStats($userId) {
    $db = Database::getInstance()->getConnection();

    $stats = [];

    // Total feeds
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM feeds WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $stats['total_feeds'] = $stmt->fetch()['count'];

    // Active feeds
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM feeds WHERE user_id = :user_id AND active = 1");
    $stmt->execute(['user_id' => $userId]);
    $stats['active_feeds'] = $stmt->fetch()['count'];

    // Total articles
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM articles a
        JOIN feeds f ON a.feed_id = f.id
        WHERE f.user_id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);
    $stats['total_articles'] = $stmt->fetch()['count'];

    // Unread articles
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM articles a
        JOIN feeds f ON a.feed_id = f.id
        LEFT JOIN user_articles ua ON a.id = ua.article_id AND ua.user_id = :user_id
        WHERE f.user_id = :user_id2 AND COALESCE(ua.read, 0) = 0
    ");
    $stmt->execute(['user_id' => $userId, 'user_id2' => $userId]);
    $stats['unread_articles'] = $stmt->fetch()['count'];

    // Favorites
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM user_articles
        WHERE user_id = :user_id AND favorite = 1
    ");
    $stmt->execute(['user_id' => $userId]);
    $stats['favorites'] = $stmt->fetch()['count'];

    return $stats;
}

function getAdminStats() {
    $db = Database::getInstance()->getConnection();

    $stats = [];

    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch()['count'];

    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE active = 1");
    $stats['active_users'] = $stmt->fetch()['count'];

    $stmt = $db->query("SELECT COUNT(*) as count FROM feeds");
    $stats['total_feeds'] = $stmt->fetch()['count'];

    $stmt = $db->query("SELECT COUNT(*) as count FROM articles");
    $stats['total_articles'] = $stmt->fetch()['count'];

    return $stats;
}

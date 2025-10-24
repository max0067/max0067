import sqlite3
from datetime import datetime
from contextlib import contextmanager
import hashlib
import secrets

DATABASE_NAME = 'rss_feeds.db'


@contextmanager
def get_db():
    """Context manager pour la connexion à la base de données"""
    conn = sqlite3.connect(DATABASE_NAME)
    conn.row_factory = sqlite3.Row
    try:
        yield conn
    finally:
        conn.close()


def hash_password(password):
    """Hash un mot de passe avec SHA256"""
    return hashlib.sha256(password.encode()).hexdigest()


def init_db():
    """Initialise la base de données avec les tables nécessaires"""
    with get_db() as conn:
        cursor = conn.cursor()

        # Table des utilisateurs
        cursor.execute('''
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
        ''')

        # Table des sessions
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token TEXT UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )
        ''')

        # Table des flux RSS (modifiée pour inclure l'utilisateur)
        cursor.execute('''
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
        ''')

        # Table des articles
        cursor.execute('''
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
        ''')

        # Table pour le statut de lecture par utilisateur
        cursor.execute('''
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
        ''')

        # Index pour améliorer les performances
        cursor.execute('CREATE INDEX IF NOT EXISTS idx_articles_feed_id ON articles(feed_id)')
        cursor.execute('CREATE INDEX IF NOT EXISTS idx_articles_published_date ON articles(published_date DESC)')
        cursor.execute('CREATE INDEX IF NOT EXISTS idx_feeds_user_id ON feeds(user_id)')
        cursor.execute('CREATE INDEX IF NOT EXISTS idx_user_articles_user_id ON user_articles(user_id)')
        cursor.execute('CREATE INDEX IF NOT EXISTS idx_sessions_token ON sessions(token)')

        # Créer un utilisateur admin par défaut
        cursor.execute("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")
        admin_count = cursor.fetchone()[0]

        if admin_count == 0:
            admin_password = hash_password('admin123')  # Mot de passe par défaut
            cursor.execute('''
                INSERT INTO users (username, email, password, role)
                VALUES (?, ?, ?, ?)
            ''', ('admin', 'admin@example.com', admin_password, 'admin'))
            print("✅ Utilisateur admin créé (username: admin, password: admin123)")
            print("⚠️  IMPORTANT: Changez le mot de passe admin après la première connexion!")

        conn.commit()
        print("✅ Base de données initialisée avec succès!")


# ===== Fonctions Utilisateurs =====

def create_user(username, email, password, role='user'):
    """Crée un nouvel utilisateur"""
    with get_db() as conn:
        cursor = conn.cursor()
        try:
            hashed_password = hash_password(password)
            cursor.execute('''
                INSERT INTO users (username, email, password, role)
                VALUES (?, ?, ?, ?)
            ''', (username, email, hashed_password, role))
            conn.commit()
            return cursor.lastrowid
        except sqlite3.IntegrityError:
            return None


def authenticate_user(username, password):
    """Authentifie un utilisateur"""
    with get_db() as conn:
        cursor = conn.cursor()
        hashed_password = hash_password(password)
        cursor.execute('''
            SELECT * FROM users
            WHERE username = ? AND password = ? AND active = 1
        ''', (username, hashed_password))
        user = cursor.fetchone()

        if user:
            # Mettre à jour last_login
            cursor.execute('''
                UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?
            ''', (user['id'],))
            conn.commit()
            return dict(user)
        return None


def create_session(user_id):
    """Crée une session pour un utilisateur"""
    with get_db() as conn:
        cursor = conn.cursor()
        token = secrets.token_urlsafe(32)
        cursor.execute('''
            INSERT INTO sessions (user_id, token, expires_at)
            VALUES (?, ?, datetime('now', '+7 days'))
        ''', (user_id, token))
        conn.commit()
        return token


def get_user_by_session(token):
    """Récupère un utilisateur par son token de session"""
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute('''
            SELECT u.* FROM users u
            JOIN sessions s ON u.id = s.user_id
            WHERE s.token = ? AND s.expires_at > datetime('now')
        ''', (token,))
        user = cursor.fetchone()
        return dict(user) if user else None


def delete_session(token):
    """Supprime une session"""
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute('DELETE FROM sessions WHERE token = ?', (token,))
        conn.commit()


def get_all_users():
    """Récupère tous les utilisateurs (admin)"""
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute('''
            SELECT id, username, email, role, active, created_at, last_login
            FROM users ORDER BY created_at DESC
        ''')
        return [dict(row) for row in cursor.fetchall()]


def update_user(user_id, **kwargs):
    """Met à jour un utilisateur"""
    with get_db() as conn:
        cursor = conn.cursor()
        updates = []
        params = []

        for key, value in kwargs.items():
            if key in ['username', 'email', 'role', 'active']:
                updates.append(f"{key} = ?")
                params.append(value)
            elif key == 'password':
                updates.append("password = ?")
                params.append(hash_password(value))

        if updates:
            params.append(user_id)
            query = f"UPDATE users SET {', '.join(updates)} WHERE id = ?"
            cursor.execute(query, params)
            conn.commit()
            return cursor.rowcount > 0
        return False


def delete_user(user_id):
    """Supprime un utilisateur"""
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute("DELETE FROM users WHERE id = ?", (user_id,))
        conn.commit()
        return cursor.rowcount > 0


# ===== Fonctions Flux RSS (modifiées) =====

def add_feed(user_id, title, url, description='', update_interval=30):
    """Ajoute un nouveau flux RSS pour un utilisateur"""
    with get_db() as conn:
        cursor = conn.cursor()
        try:
            cursor.execute('''
                INSERT INTO feeds (user_id, title, url, description, update_interval)
                VALUES (?, ?, ?, ?, ?)
            ''', (user_id, title, url, description, update_interval))
            conn.commit()
            return cursor.lastrowid
        except sqlite3.IntegrityError:
            return None


def get_user_feeds(user_id):
    """Récupère tous les flux d'un utilisateur"""
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute('''
            SELECT f.*, COUNT(a.id) as article_count
            FROM feeds f
            LEFT JOIN articles a ON f.id = a.feed_id
            WHERE f.user_id = ?
            GROUP BY f.id
            ORDER BY f.created_at DESC
        ''', (user_id,))
        return [dict(row) for row in cursor.fetchall()]


def update_feed(feed_id, user_id=None, **kwargs):
    """Met à jour un flux RSS"""
    with get_db() as conn:
        cursor = conn.cursor()
        updates = []
        params = []

        for key, value in kwargs.items():
            if key in ['title', 'url', 'description', 'update_interval', 'active']:
                updates.append(f"{key} = ?")
                params.append(value)

        if updates:
            params.append(feed_id)
            query = f"UPDATE feeds SET {', '.join(updates)} WHERE id = ?"
            if user_id:
                query += " AND user_id = ?"
                params.append(user_id)

            cursor.execute(query, params)
            conn.commit()
            return cursor.rowcount > 0
        return False


def delete_feed(feed_id, user_id=None):
    """Supprime un flux RSS"""
    with get_db() as conn:
        cursor = conn.cursor()
        query = "DELETE FROM feeds WHERE id = ?"
        params = [feed_id]

        if user_id:
            query += " AND user_id = ?"
            params.append(user_id)

        cursor.execute(query, params)
        conn.commit()
        return cursor.rowcount > 0


def get_feed_by_id(feed_id, user_id=None):
    """Récupère un flux RSS par son ID"""
    with get_db() as conn:
        cursor = conn.cursor()
        query = "SELECT * FROM feeds WHERE id = ?"
        params = [feed_id]

        if user_id:
            query += " AND user_id = ?"
            params.append(user_id)

        cursor.execute(query, params)
        row = cursor.fetchone()
        return dict(row) if row else None


def get_active_feeds(user_id=None):
    """Récupère tous les flux RSS actifs"""
    with get_db() as conn:
        cursor = conn.cursor()
        if user_id:
            cursor.execute("SELECT * FROM feeds WHERE active = 1 AND user_id = ?", (user_id,))
        else:
            cursor.execute("SELECT * FROM feeds WHERE active = 1")
        return [dict(row) for row in cursor.fetchall()]


def update_feed_timestamp(feed_id):
    """Met à jour le timestamp de dernière mise à jour d'un flux"""
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute('UPDATE feeds SET last_updated = CURRENT_TIMESTAMP WHERE id = ?', (feed_id,))
        conn.commit()


# ===== Fonctions Articles (modifiées) =====

def add_article(feed_id, title, link, description='', author='', published_date=None, content=''):
    """Ajoute un nouvel article"""
    with get_db() as conn:
        cursor = conn.cursor()
        try:
            cursor.execute('''
                INSERT INTO articles (feed_id, title, link, description, author, published_date, content)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ''', (feed_id, title, link, description, author, published_date, content))
            conn.commit()
            return cursor.lastrowid
        except sqlite3.IntegrityError:
            return None


def get_articles(user_id, feed_id=None, limit=10000, offset=0, unread_only=False, search_query=None):
    """Récupère les articles d'un utilisateur (tous les articles conservés à vie)"""
    with get_db() as conn:
        cursor = conn.cursor()

        query = '''
            SELECT a.*, f.title as feed_title,
                   COALESCE(ua.read, 0) as read,
                   COALESCE(ua.favorite, 0) as favorite
            FROM articles a
            JOIN feeds f ON a.feed_id = f.id
            LEFT JOIN user_articles ua ON a.id = ua.article_id AND ua.user_id = ?
            WHERE f.user_id = ?
        '''
        params = [user_id, user_id]

        if feed_id:
            query += " AND a.feed_id = ?"
            params.append(feed_id)

        if unread_only:
            query += " AND COALESCE(ua.read, 0) = 0"

        if search_query:
            query += " AND (a.title LIKE ? OR a.description LIKE ? OR a.content LIKE ?)"
            search_param = f"%{search_query}%"
            params.extend([search_param, search_param, search_param])

        query += " ORDER BY a.published_date DESC LIMIT ? OFFSET ?"
        params.extend([limit, offset])

        cursor.execute(query, params)
        return [dict(row) for row in cursor.fetchall()]


def mark_article_read(user_id, article_id, read=True):
    """Marque un article comme lu ou non lu"""
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute('''
            INSERT INTO user_articles (user_id, article_id, read, read_at)
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT(user_id, article_id)
            DO UPDATE SET read = ?, read_at = CURRENT_TIMESTAMP
        ''', (user_id, article_id, 1 if read else 0, 1 if read else 0))
        conn.commit()
        return cursor.rowcount > 0


def mark_all_articles_read(user_id, article_ids=None):
    """Marque plusieurs articles comme lus
    Args:
        user_id: ID de l'utilisateur
        article_ids: Liste d'IDs d'articles à marquer (si None, marque tous les articles)
    """
    with get_db() as conn:
        cursor = conn.cursor()

        if article_ids:
            # Marquer seulement les articles spécifiés
            placeholders = ','.join('?' * len(article_ids))
            cursor.execute(f'''
                INSERT OR REPLACE INTO user_articles (user_id, article_id, read, read_at)
                SELECT ?, id, 1, CURRENT_TIMESTAMP
                FROM articles
                WHERE id IN ({placeholders})
            ''', [user_id] + article_ids)
        else:
            # Marquer tous les articles de l'utilisateur
            cursor.execute('''
                INSERT OR REPLACE INTO user_articles (user_id, article_id, read, read_at)
                SELECT ?, id, 1, CURRENT_TIMESTAMP
                FROM articles a
                INNER JOIN feeds f ON a.feed_id = f.id
                WHERE f.user_id = ?
            ''', (user_id, user_id))

        conn.commit()
        return cursor.rowcount


def toggle_article_favorite(user_id, article_id):
    """Bascule le statut favori d'un article"""
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute('''
            INSERT INTO user_articles (user_id, article_id, favorite)
            VALUES (?, ?, 1)
            ON CONFLICT(user_id, article_id)
            DO UPDATE SET favorite = 1 - favorite
        ''', (user_id, article_id))
        conn.commit()
        return True


def get_article_count(user_id, feed_id=None, unread_only=False, search_query=None):
    """Compte le nombre d'articles"""
    with get_db() as conn:
        cursor = conn.cursor()

        query = '''
            SELECT COUNT(*) as count
            FROM articles a
            JOIN feeds f ON a.feed_id = f.id
            LEFT JOIN user_articles ua ON a.id = ua.article_id AND ua.user_id = ?
            WHERE f.user_id = ?
        '''
        params = [user_id, user_id]

        if feed_id:
            query += " AND a.feed_id = ?"
            params.append(feed_id)

        if unread_only:
            query += " AND COALESCE(ua.read, 0) = 0"

        if search_query:
            query += " AND (a.title LIKE ? OR a.description LIKE ? OR a.content LIKE ?)"
            search_param = f"%{search_query}%"
            params.extend([search_param, search_param, search_param])

        cursor.execute(query, params)
        return cursor.fetchone()[0]


# ===== Fonctions Statistiques =====

def get_user_stats(user_id):
    """Récupère les statistiques d'un utilisateur"""
    with get_db() as conn:
        cursor = conn.cursor()

        # Nombre de flux
        cursor.execute("SELECT COUNT(*) FROM feeds WHERE user_id = ?", (user_id,))
        total_feeds = cursor.fetchone()[0]

        # Nombre de flux actifs
        cursor.execute("SELECT COUNT(*) FROM feeds WHERE user_id = ? AND active = 1", (user_id,))
        active_feeds = cursor.fetchone()[0]

        # Nombre total d'articles
        cursor.execute('''
            SELECT COUNT(*) FROM articles a
            JOIN feeds f ON a.feed_id = f.id
            WHERE f.user_id = ?
        ''', (user_id,))
        total_articles = cursor.fetchone()[0]

        # Nombre d'articles non lus
        cursor.execute('''
            SELECT COUNT(*) FROM articles a
            JOIN feeds f ON a.feed_id = f.id
            LEFT JOIN user_articles ua ON a.id = ua.article_id AND ua.user_id = ?
            WHERE f.user_id = ? AND COALESCE(ua.read, 0) = 0
        ''', (user_id, user_id))
        unread_articles = cursor.fetchone()[0]

        # Nombre de favoris
        cursor.execute('''
            SELECT COUNT(*) FROM user_articles
            WHERE user_id = ? AND favorite = 1
        ''', (user_id,))
        favorites = cursor.fetchone()[0]

        return {
            'total_feeds': total_feeds,
            'active_feeds': active_feeds,
            'total_articles': total_articles,
            'unread_articles': unread_articles,
            'favorites': favorites
        }


def get_admin_stats():
    """Récupère les statistiques globales (admin)"""
    with get_db() as conn:
        cursor = conn.cursor()

        # Nombre total d'utilisateurs
        cursor.execute("SELECT COUNT(*) FROM users")
        total_users = cursor.fetchone()[0]

        # Utilisateurs actifs
        cursor.execute("SELECT COUNT(*) FROM users WHERE active = 1")
        active_users = cursor.fetchone()[0]

        # Nombre total de flux
        cursor.execute("SELECT COUNT(*) FROM feeds")
        total_feeds = cursor.fetchone()[0]

        # Nombre total d'articles
        cursor.execute("SELECT COUNT(*) FROM articles")
        total_articles = cursor.fetchone()[0]

        # Articles par utilisateur (moyenne)
        cursor.execute('''
            SELECT AVG(article_count) FROM (
                SELECT COUNT(a.id) as article_count
                FROM users u
                LEFT JOIN feeds f ON u.id = f.user_id
                LEFT JOIN articles a ON f.id = a.feed_id
                GROUP BY u.id
            )
        ''')
        avg_articles = cursor.fetchone()[0] or 0

        return {
            'total_users': total_users,
            'active_users': active_users,
            'total_feeds': total_feeds,
            'total_articles': total_articles,
            'avg_articles_per_user': round(avg_articles, 1)
        }


if __name__ == "__main__":
    init_db()

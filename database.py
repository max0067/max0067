import sqlite3
from datetime import datetime
from contextlib import contextmanager

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


def init_db():
    """Initialise la base de données avec les tables nécessaires"""
    with get_db() as conn:
        cursor = conn.cursor()

        # Table des flux RSS
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS feeds (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                url TEXT UNIQUE NOT NULL,
                description TEXT,
                update_interval INTEGER DEFAULT 30,
                last_updated TIMESTAMP,
                active INTEGER DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ''')

        # Table des articles
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS articles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                feed_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                link TEXT UNIQUE NOT NULL,
                description TEXT,
                author TEXT,
                published_date TIMESTAMP,
                content TEXT,
                read INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (feed_id) REFERENCES feeds (id) ON DELETE CASCADE
            )
        ''')

        # Index pour améliorer les performances
        cursor.execute('''
            CREATE INDEX IF NOT EXISTS idx_articles_feed_id
            ON articles(feed_id)
        ''')

        cursor.execute('''
            CREATE INDEX IF NOT EXISTS idx_articles_published_date
            ON articles(published_date DESC)
        ''')

        conn.commit()
        print("Base de données initialisée avec succès!")


def add_feed(title, url, description='', update_interval=30):
    """Ajoute un nouveau flux RSS"""
    with get_db() as conn:
        cursor = conn.cursor()
        try:
            cursor.execute('''
                INSERT INTO feeds (title, url, description, update_interval)
                VALUES (?, ?, ?, ?)
            ''', (title, url, description, update_interval))
            conn.commit()
            return cursor.lastrowid
        except sqlite3.IntegrityError:
            return None


def update_feed(feed_id, title=None, url=None, description=None, update_interval=None, active=None):
    """Met à jour un flux RSS"""
    with get_db() as conn:
        cursor = conn.cursor()
        updates = []
        params = []

        if title is not None:
            updates.append("title = ?")
            params.append(title)
        if url is not None:
            updates.append("url = ?")
            params.append(url)
        if description is not None:
            updates.append("description = ?")
            params.append(description)
        if update_interval is not None:
            updates.append("update_interval = ?")
            params.append(update_interval)
        if active is not None:
            updates.append("active = ?")
            params.append(active)

        if updates:
            params.append(feed_id)
            query = f"UPDATE feeds SET {', '.join(updates)} WHERE id = ?"
            cursor.execute(query, params)
            conn.commit()
            return cursor.rowcount > 0
        return False


def delete_feed(feed_id):
    """Supprime un flux RSS et tous ses articles"""
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute("DELETE FROM feeds WHERE id = ?", (feed_id,))
        conn.commit()
        return cursor.rowcount > 0


def get_all_feeds():
    """Récupère tous les flux RSS"""
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute('''
            SELECT f.*, COUNT(a.id) as article_count
            FROM feeds f
            LEFT JOIN articles a ON f.id = a.feed_id
            GROUP BY f.id
            ORDER BY f.created_at DESC
        ''')
        return [dict(row) for row in cursor.fetchall()]


def get_feed_by_id(feed_id):
    """Récupère un flux RSS par son ID"""
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM feeds WHERE id = ?", (feed_id,))
        row = cursor.fetchone()
        return dict(row) if row else None


def get_active_feeds():
    """Récupère tous les flux RSS actifs"""
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM feeds WHERE active = 1")
        return [dict(row) for row in cursor.fetchall()]


def update_feed_timestamp(feed_id):
    """Met à jour le timestamp de dernière mise à jour d'un flux"""
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute('''
            UPDATE feeds SET last_updated = CURRENT_TIMESTAMP WHERE id = ?
        ''', (feed_id,))
        conn.commit()


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
            # L'article existe déjà
            return None


def get_articles(feed_id=None, limit=100, offset=0, unread_only=False):
    """Récupère les articles, optionnellement filtrés par flux"""
    with get_db() as conn:
        cursor = conn.cursor()

        query = '''
            SELECT a.*, f.title as feed_title
            FROM articles a
            JOIN feeds f ON a.feed_id = f.id
        '''
        params = []
        conditions = []

        if feed_id:
            conditions.append("a.feed_id = ?")
            params.append(feed_id)

        if unread_only:
            conditions.append("a.read = 0")

        if conditions:
            query += " WHERE " + " AND ".join(conditions)

        query += " ORDER BY a.published_date DESC LIMIT ? OFFSET ?"
        params.extend([limit, offset])

        cursor.execute(query, params)
        return [dict(row) for row in cursor.fetchall()]


def mark_article_read(article_id, read=True):
    """Marque un article comme lu ou non lu"""
    with get_db() as conn:
        cursor = conn.cursor()
        cursor.execute("UPDATE articles SET read = ? WHERE id = ?", (1 if read else 0, article_id))
        conn.commit()
        return cursor.rowcount > 0


def get_article_count(feed_id=None, unread_only=False):
    """Compte le nombre d'articles"""
    with get_db() as conn:
        cursor = conn.cursor()

        query = "SELECT COUNT(*) as count FROM articles"
        params = []
        conditions = []

        if feed_id:
            conditions.append("feed_id = ?")
            params.append(feed_id)

        if unread_only:
            conditions.append("read = 0")

        if conditions:
            query += " WHERE " + " AND ".join(conditions)

        cursor.execute(query, params)
        return cursor.fetchone()[0]


if __name__ == "__main__":
    init_db()

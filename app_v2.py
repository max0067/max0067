from flask import Flask, render_template, request, jsonify, session, redirect, url_for
from functools import wraps
from apscheduler.schedulers.background import BackgroundScheduler
from apscheduler.triggers.interval import IntervalTrigger
import atexit
import logging
import os

app = Flask(__name__)
app.secret_key = os.environ.get('SECRET_KEY', 'votre-cle-secrete-changez-moi-en-production')
app.config['JSON_AS_ASCII'] = False

# Configuration du logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Import des fonctions de base de données v2
from database_v2 import (
    init_db, authenticate_user, create_user, create_session, get_user_by_session,
    delete_session, get_all_users, update_user, delete_user,
    add_feed, get_user_feeds, update_feed, delete_feed, get_feed_by_id,
    get_active_feeds, get_articles, mark_article_read, mark_all_articles_read, toggle_article_favorite,
    get_article_count, get_user_stats, get_admin_stats
)
from rss_updater import update_single_feed, update_all_feeds

# Initialisation de la base de données
init_db()

# Configuration du scheduler
scheduler = BackgroundScheduler()
scheduler.start()

# Mise à jour automatique toutes les 30 minutes
def scheduled_update():
    """Mise à jour automatique pour tous les flux actifs de tous les utilisateurs"""
    try:
        feeds = get_active_feeds()
        logger.info(f"Mise à jour automatique de {len(feeds)} flux")
        for feed in feeds:
            update_single_feed(feed['id'])
    except Exception as e:
        logger.error(f"Erreur lors de la mise à jour automatique: {str(e)}")

scheduler.add_job(
    func=scheduled_update,
    trigger=IntervalTrigger(minutes=30),
    id='update_feeds_job',
    name='Mise à jour automatique des flux RSS',
    replace_existing=True
)

atexit.register(lambda: scheduler.shutdown())


# ===== Décorateurs =====

def login_required(f):
    """Décorateur pour protéger les routes nécessitant une authentification"""
    @wraps(f)
    def decorated_function(*args, **kwargs):
        token = session.get('token')
        if not token:
            if request.is_json:
                return jsonify({'success': False, 'error': 'Authentication required'}), 401
            return redirect(url_for('login'))

        user = get_user_by_session(token)
        if not user:
            session.clear()
            if request.is_json:
                return jsonify({'success': False, 'error': 'Invalid session'}), 401
            return redirect(url_for('login'))

        request.current_user = user
        return f(*args, **kwargs)
    return decorated_function


def admin_required(f):
    """Décorateur pour protéger les routes admin"""
    @wraps(f)
    @login_required
    def decorated_function(*args, **kwargs):
        if request.current_user.get('role') != 'admin':
            if request.is_json:
                return jsonify({'success': False, 'error': 'Admin access required'}), 403
            return redirect(url_for('index'))
        return f(*args, **kwargs)
    return decorated_function


# ===== Routes d'authentification =====

@app.route('/login', methods=['GET'])
def login():
    """Page de connexion"""
    if session.get('token'):
        return redirect(url_for('index'))
    return render_template('login.html')


@app.route('/register', methods=['GET'])
def register():
    """Page d'inscription"""
    if session.get('token'):
        return redirect(url_for('index'))
    return render_template('register.html')


@app.route('/api/auth/login', methods=['POST'])
def api_login():
    """Connexion utilisateur"""
    try:
        data = request.get_json()
        username = data.get('username')
        password = data.get('password')

        if not username or not password:
            return jsonify({'success': False, 'error': 'Username and password required'}), 400

        user = authenticate_user(username, password)
        if user:
            token = create_session(user['id'])
            session['token'] = token
            return jsonify({
                'success': True,
                'user': {
                    'id': user['id'],
                    'username': user['username'],
                    'email': user['email'],
                    'role': user['role']
                }
            })

        return jsonify({'success': False, 'error': 'Invalid credentials'}), 401

    except Exception as e:
        logger.error(f"Login error: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/auth/register', methods=['POST'])
def api_register():
    """Inscription utilisateur"""
    try:
        data = request.get_json()
        username = data.get('username')
        email = data.get('email')
        password = data.get('password')

        if not username or not email or not password:
            return jsonify({'success': False, 'error': 'All fields required'}), 400

        user_id = create_user(username, email, password)
        if user_id:
            return jsonify({'success': True, 'message': 'Account created successfully'}), 201

        return jsonify({'success': False, 'error': 'Username or email already exists'}), 400

    except Exception as e:
        logger.error(f"Register error: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/auth/logout', methods=['POST'])
@login_required
def api_logout():
    """Déconnexion utilisateur"""
    try:
        token = session.get('token')
        if token:
            delete_session(token)
        session.clear()
        return jsonify({'success': True, 'message': 'Logged out successfully'})
    except Exception as e:
        logger.error(f"Logout error: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/auth/me', methods=['GET'])
@login_required
def api_current_user():
    """Récupère l'utilisateur actuel"""
    user = request.current_user
    return jsonify({
        'success': True,
        'user': {
            'id': user['id'],
            'username': user['username'],
            'email': user['email'],
            'role': user['role']
        }
    })


# ===== Routes principales =====

@app.route('/')
@login_required
def index():
    """Page principale"""
    return render_template('index_v2.html')


@app.route('/dashboard')
@login_required
def dashboard():
    """Dashboard utilisateur"""
    return render_template('dashboard.html')


@app.route('/feeds')
@login_required
def feeds_manager():
    """Page de gestion des flux RSS"""
    return render_template('feeds_manager.html')


@app.route('/admin')
@admin_required
def admin_panel():
    """Panel d'administration"""
    return render_template('admin.html')


# ===== Routes API - Flux RSS =====

@app.route('/api/feeds', methods=['GET'])
@login_required
def api_get_feeds():
    """Récupère tous les flux de l'utilisateur"""
    try:
        feeds = get_user_feeds(request.current_user['id'])
        return jsonify({'success': True, 'feeds': feeds})
    except Exception as e:
        logger.error(f"Error getting feeds: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/feeds/<int:feed_id>', methods=['GET'])
@login_required
def api_get_feed(feed_id):
    """Récupère un flux spécifique"""
    try:
        feed = get_feed_by_id(feed_id, request.current_user['id'])
        if feed:
            return jsonify({'success': True, 'feed': feed})
        return jsonify({'success': False, 'error': 'Feed not found'}), 404
    except Exception as e:
        logger.error(f"Error getting feed: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/feeds', methods=['POST'])
@login_required
def api_add_feed():
    """Ajoute un nouveau flux RSS"""
    try:
        data = request.get_json()

        if not data or 'url' not in data:
            return jsonify({'success': False, 'error': 'URL required'}), 400

        title = data.get('title', '')
        url = data.get('url')
        description = data.get('description', '')
        update_interval = data.get('update_interval', 30)

        feed_id = add_feed(
            user_id=request.current_user['id'],
            title=title,
            url=url,
            description=description,
            update_interval=update_interval
        )

        if feed_id:
            logger.info(f"New feed added (ID: {feed_id}), updating...")
            try:
                update_single_feed(feed_id)
            except Exception as e:
                logger.error(f"Error updating new feed: {str(e)}")

            return jsonify({'success': True, 'feed_id': feed_id, 'message': 'Feed added successfully'}), 201

        return jsonify({'success': False, 'error': 'Feed already exists or error adding'}), 400

    except Exception as e:
        logger.error(f"Error adding feed: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/feeds/<int:feed_id>', methods=['PUT'])
@login_required
def api_update_feed(feed_id):
    """Met à jour un flux RSS"""
    try:
        data = request.get_json()

        if not data:
            return jsonify({'success': False, 'error': 'Data required'}), 400

        success = update_feed(
            feed_id,
            user_id=request.current_user['id'],
            **{k: v for k, v in data.items() if k in ['title', 'url', 'description', 'update_interval', 'active']}
        )

        if success:
            return jsonify({'success': True, 'message': 'Feed updated successfully'})

        return jsonify({'success': False, 'error': 'Feed not found or no changes'}), 404

    except Exception as e:
        logger.error(f"Error updating feed: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/feeds/<int:feed_id>', methods=['DELETE'])
@login_required
def api_delete_feed(feed_id):
    """Supprime un flux RSS"""
    try:
        success = delete_feed(feed_id, request.current_user['id'])

        if success:
            return jsonify({'success': True, 'message': 'Feed deleted successfully'})

        return jsonify({'success': False, 'error': 'Feed not found'}), 404

    except Exception as e:
        logger.error(f"Error deleting feed: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/feeds/<int:feed_id>/update', methods=['POST'])
@login_required
def api_update_single_feed(feed_id):
    """Force la mise à jour d'un flux spécifique"""
    try:
        # Vérifier que le flux appartient à l'utilisateur
        feed = get_feed_by_id(feed_id, request.current_user['id'])
        if not feed:
            return jsonify({'success': False, 'error': 'Feed not found'}), 404

        new_articles = update_single_feed(feed_id)
        return jsonify({
            'success': True,
            'message': f'{new_articles} new articles retrieved',
            'new_articles': new_articles
        })
    except Exception as e:
        logger.error(f"Error updating feed: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/feeds/update-all', methods=['POST'])
@login_required
def api_update_all_user_feeds():
    """Force la mise à jour de tous les flux de l'utilisateur"""
    try:
        feeds = get_active_feeds(request.current_user['id'])
        total_new_articles = 0

        for feed in feeds:
            new_articles = update_single_feed(feed['id'])
            total_new_articles += new_articles

        return jsonify({
            'success': True,
            'message': f'{total_new_articles} new articles retrieved',
            'new_articles': total_new_articles
        })
    except Exception as e:
        logger.error(f"Error updating feeds: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


# ===== Routes API - Articles =====

@app.route('/api/articles', methods=['GET'])
@login_required
def api_get_articles():
    """Récupère les articles avec pagination et recherche"""
    try:
        feed_id = request.args.get('feed_id', type=int)
        limit = request.args.get('limit', 100, type=int)
        offset = request.args.get('offset', 0, type=int)
        unread_only = request.args.get('unread_only', 'false').lower() == 'true'
        search_query = request.args.get('search', '')

        articles = get_articles(
            user_id=request.current_user['id'],
            feed_id=feed_id,
            limit=limit,
            offset=offset,
            unread_only=unread_only,
            search_query=search_query if search_query else None
        )

        total = get_article_count(
            user_id=request.current_user['id'],
            feed_id=feed_id,
            unread_only=unread_only,
            search_query=search_query if search_query else None
        )

        return jsonify({
            'success': True,
            'articles': articles,
            'total': total,
            'limit': limit,
            'offset': offset
        })
    except Exception as e:
        logger.error(f"Error getting articles: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/articles/<int:article_id>/read', methods=['PUT'])
@login_required
def api_mark_article_read(article_id):
    """Marque un article comme lu/non lu"""
    try:
        data = request.get_json()
        read = data.get('read', True) if data else True

        success = mark_article_read(request.current_user['id'], article_id, read)

        if success:
            return jsonify({'success': True, 'message': 'Article updated'})

        return jsonify({'success': False, 'error': 'Article not found'}), 404

    except Exception as e:
        logger.error(f"Error marking article: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/articles/mark-all-read', methods=['POST'])
@login_required
def api_mark_all_articles_read():
    """Marque plusieurs articles comme lus"""
    try:
        data = request.get_json() or {}
        article_ids = data.get('article_ids')  # Si None, marque tous les articles

        count = mark_all_articles_read(request.current_user['id'], article_ids)

        return jsonify({
            'success': True,
            'message': f'{count} article{"s" if count > 1 else ""} marqué{"s" if count > 1 else ""} comme lu{"s" if count > 1 else ""}',
            'count': count
        })

    except Exception as e:
        logger.error(f"Error marking articles as read: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/articles/<int:article_id>/favorite', methods=['PUT'])
@login_required
def api_toggle_favorite(article_id):
    """Bascule le statut favori d'un article"""
    try:
        success = toggle_article_favorite(request.current_user['id'], article_id)

        if success:
            return jsonify({'success': True, 'message': 'Favorite toggled'})

        return jsonify({'success': False, 'error': 'Article not found'}), 404

    except Exception as e:
        logger.error(f"Error toggling favorite: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


# ===== Routes API - Statistiques =====

@app.route('/api/stats', methods=['GET'])
@login_required
def api_get_stats():
    """Récupère les statistiques de l'utilisateur"""
    try:
        stats = get_user_stats(request.current_user['id'])
        return jsonify({'success': True, 'stats': stats})
    except Exception as e:
        logger.error(f"Error getting stats: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


# ===== Routes API - Admin =====

@app.route('/api/admin/stats', methods=['GET'])
@admin_required
def api_get_admin_stats():
    """Récupère les statistiques globales"""
    try:
        stats = get_admin_stats()
        return jsonify({'success': True, 'stats': stats})
    except Exception as e:
        logger.error(f"Error getting admin stats: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/admin/users', methods=['GET'])
@admin_required
def api_get_all_users():
    """Récupère tous les utilisateurs"""
    try:
        users = get_all_users()
        return jsonify({'success': True, 'users': users})
    except Exception as e:
        logger.error(f"Error getting users: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/admin/users/<int:user_id>', methods=['PUT'])
@admin_required
def api_admin_update_user(user_id):
    """Met à jour un utilisateur (admin)"""
    try:
        data = request.get_json()

        if not data:
            return jsonify({'success': False, 'error': 'Data required'}), 400

        success = update_user(
            user_id,
            **{k: v for k, v in data.items() if k in ['username', 'email', 'role', 'active', 'password']}
        )

        if success:
            return jsonify({'success': True, 'message': 'User updated successfully'})

        return jsonify({'success': False, 'error': 'User not found'}), 404

    except Exception as e:
        logger.error(f"Error updating user: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


@app.route('/api/admin/users/<int:user_id>', methods=['DELETE'])
@admin_required
def api_admin_delete_user(user_id):
    """Supprime un utilisateur (admin)"""
    try:
        # Empêcher la suppression de soi-même
        if user_id == request.current_user['id']:
            return jsonify({'success': False, 'error': 'Cannot delete yourself'}), 400

        success = delete_user(user_id)

        if success:
            return jsonify({'success': True, 'message': 'User deleted successfully'})

        return jsonify({'success': False, 'error': 'User not found'}), 404

    except Exception as e:
        logger.error(f"Error deleting user: {str(e)}")
        return jsonify({'success': False, 'error': 'Server error'}), 500


if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)

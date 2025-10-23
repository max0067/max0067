from flask import Flask, render_template, request, jsonify
from apscheduler.schedulers.background import BackgroundScheduler
from apscheduler.triggers.interval import IntervalTrigger
import atexit
import logging
from database import (
    init_db, add_feed, update_feed, delete_feed,
    get_all_feeds, get_feed_by_id, get_articles,
    mark_article_read, get_article_count
)
from rss_updater import update_all_feeds, update_single_feed

app = Flask(__name__)
app.config['JSON_AS_ASCII'] = False

# Configuration du logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Initialisation de la base de données
init_db()

# Configuration du scheduler pour les mises à jour automatiques
scheduler = BackgroundScheduler()
scheduler.start()

# Mise à jour automatique toutes les 30 minutes
scheduler.add_job(
    func=update_all_feeds,
    trigger=IntervalTrigger(minutes=30),
    id='update_feeds_job',
    name='Mise à jour automatique des flux RSS',
    replace_existing=True
)

# Arrêt propre du scheduler
atexit.register(lambda: scheduler.shutdown())


@app.route('/')
def index():
    """Page principale"""
    return render_template('index.html')


# ===== Routes pour les flux RSS =====

@app.route('/api/feeds', methods=['GET'])
def api_get_feeds():
    """Récupère tous les flux RSS"""
    try:
        feeds = get_all_feeds()
        return jsonify({'success': True, 'feeds': feeds})
    except Exception as e:
        logger.error(f"Erreur lors de la récupération des flux: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/feeds/<int:feed_id>', methods=['GET'])
def api_get_feed(feed_id):
    """Récupère un flux RSS spécifique"""
    try:
        feed = get_feed_by_id(feed_id)
        if feed:
            return jsonify({'success': True, 'feed': feed})
        return jsonify({'success': False, 'error': 'Flux non trouvé'}), 404
    except Exception as e:
        logger.error(f"Erreur lors de la récupération du flux: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/feeds', methods=['POST'])
def api_add_feed():
    """Ajoute un nouveau flux RSS"""
    try:
        data = request.get_json()

        if not data or 'url' not in data:
            return jsonify({'success': False, 'error': 'URL requise'}), 400

        title = data.get('title', '')
        url = data.get('url')
        description = data.get('description', '')
        update_interval = data.get('update_interval', 30)

        feed_id = add_feed(title, url, description, update_interval)

        if feed_id:
            # Mise à jour immédiate du nouveau flux
            logger.info(f"Nouveau flux ajouté (ID: {feed_id}), mise à jour en cours...")
            try:
                update_single_feed(feed_id)
            except Exception as e:
                logger.error(f"Erreur lors de la mise à jour initiale: {str(e)}")

            return jsonify({'success': True, 'feed_id': feed_id, 'message': 'Flux ajouté avec succès'}), 201

        return jsonify({'success': False, 'error': 'Le flux existe déjà ou erreur lors de l\'ajout'}), 400

    except Exception as e:
        logger.error(f"Erreur lors de l'ajout du flux: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/feeds/<int:feed_id>', methods=['PUT'])
def api_update_feed(feed_id):
    """Met à jour un flux RSS"""
    try:
        data = request.get_json()

        if not data:
            return jsonify({'success': False, 'error': 'Données requises'}), 400

        success = update_feed(
            feed_id,
            title=data.get('title'),
            url=data.get('url'),
            description=data.get('description'),
            update_interval=data.get('update_interval'),
            active=data.get('active')
        )

        if success:
            return jsonify({'success': True, 'message': 'Flux mis à jour avec succès'})

        return jsonify({'success': False, 'error': 'Flux non trouvé ou aucune modification'}), 404

    except Exception as e:
        logger.error(f"Erreur lors de la mise à jour du flux: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/feeds/<int:feed_id>', methods=['DELETE'])
def api_delete_feed(feed_id):
    """Supprime un flux RSS"""
    try:
        success = delete_feed(feed_id)

        if success:
            return jsonify({'success': True, 'message': 'Flux supprimé avec succès'})

        return jsonify({'success': False, 'error': 'Flux non trouvé'}), 404

    except Exception as e:
        logger.error(f"Erreur lors de la suppression du flux: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/feeds/<int:feed_id>/update', methods=['POST'])
def api_update_single_feed(feed_id):
    """Force la mise à jour d'un flux spécifique"""
    try:
        new_articles = update_single_feed(feed_id)
        return jsonify({
            'success': True,
            'message': f'{new_articles} nouveaux articles récupérés',
            'new_articles': new_articles
        })
    except Exception as e:
        logger.error(f"Erreur lors de la mise à jour du flux: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/feeds/update-all', methods=['POST'])
def api_update_all_feeds():
    """Force la mise à jour de tous les flux"""
    try:
        total_new_articles = update_all_feeds()
        return jsonify({
            'success': True,
            'message': f'{total_new_articles} nouveaux articles récupérés au total',
            'new_articles': total_new_articles
        })
    except Exception as e:
        logger.error(f"Erreur lors de la mise à jour des flux: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500


# ===== Routes pour les articles =====

@app.route('/api/articles', methods=['GET'])
def api_get_articles():
    """Récupère les articles avec pagination"""
    try:
        feed_id = request.args.get('feed_id', type=int)
        limit = request.args.get('limit', 100, type=int)
        offset = request.args.get('offset', 0, type=int)
        unread_only = request.args.get('unread_only', 'false').lower() == 'true'

        articles = get_articles(feed_id, limit, offset, unread_only)
        total = get_article_count(feed_id, unread_only)

        return jsonify({
            'success': True,
            'articles': articles,
            'total': total,
            'limit': limit,
            'offset': offset
        })
    except Exception as e:
        logger.error(f"Erreur lors de la récupération des articles: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/articles/<int:article_id>/read', methods=['PUT'])
def api_mark_article_read(article_id):
    """Marque un article comme lu/non lu"""
    try:
        data = request.get_json()
        read = data.get('read', True) if data else True

        success = mark_article_read(article_id, read)

        if success:
            return jsonify({'success': True, 'message': 'Article mis à jour'})

        return jsonify({'success': False, 'error': 'Article non trouvé'}), 404

    except Exception as e:
        logger.error(f"Erreur lors de la mise à jour de l'article: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/api/stats', methods=['GET'])
def api_get_stats():
    """Récupère les statistiques générales"""
    try:
        feeds = get_all_feeds()
        total_articles = get_article_count()
        unread_articles = get_article_count(unread_only=True)

        return jsonify({
            'success': True,
            'stats': {
                'total_feeds': len(feeds),
                'active_feeds': len([f for f in feeds if f['active']]),
                'total_articles': total_articles,
                'unread_articles': unread_articles
            }
        })
    except Exception as e:
        logger.error(f"Erreur lors de la récupération des statistiques: {str(e)}")
        return jsonify({'success': False, 'error': str(e)}), 500


if __name__ == '__main__':
    # Lance l'application Flask
    app.run(debug=True, host='0.0.0.0', port=5000)

import feedparser
from datetime import datetime
from dateutil import parser as date_parser
import time
import logging
from database import (
    get_active_feeds, add_article, update_feed_timestamp,
    get_feed_by_id
)

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


def parse_date(date_string):
    """Parse une date de différents formats"""
    if not date_string:
        return None
    try:
        # feedparser retourne un time.struct_time
        if isinstance(date_string, time.struct_time):
            return datetime.fromtimestamp(time.mktime(date_string)).isoformat()
        # Sinon on essaie de parser la chaîne
        return date_parser.parse(date_string).isoformat()
    except:
        return None


def fetch_feed(feed_url):
    """Récupère et parse un flux RSS"""
    try:
        logger.info(f"Récupération du flux: {feed_url}")
        feed = feedparser.parse(feed_url)

        if feed.bozo:
            # Le flux a des erreurs mais peut être partiellement utilisable
            logger.warning(f"Avertissement lors du parsing de {feed_url}: {feed.bozo_exception}")

        return feed
    except Exception as e:
        logger.error(f"Erreur lors de la récupération du flux {feed_url}: {str(e)}")
        return None


def update_single_feed(feed_id):
    """Met à jour un seul flux RSS"""
    feed_info = get_feed_by_id(feed_id)
    if not feed_info:
        logger.error(f"Flux {feed_id} non trouvé")
        return 0

    logger.info(f"Mise à jour du flux: {feed_info['title']} ({feed_info['url']})")

    feed = fetch_feed(feed_info['url'])
    if not feed or not hasattr(feed, 'entries'):
        logger.error(f"Impossible de récupérer le flux {feed_info['title']}")
        return 0

    new_articles = 0

    for entry in feed.entries:
        # Extraction des données de l'article
        title = entry.get('title', 'Sans titre')
        link = entry.get('link', '')

        if not link:
            continue

        description = entry.get('summary', entry.get('description', ''))
        author = entry.get('author', '')
        content = entry.get('content', [{}])[0].get('value', description) if entry.get('content') else description

        # Parse la date de publication
        published_date = None
        if hasattr(entry, 'published_parsed'):
            published_date = parse_date(entry.published_parsed)
        elif hasattr(entry, 'updated_parsed'):
            published_date = parse_date(entry.updated_parsed)

        # Ajoute l'article à la base de données
        article_id = add_article(
            feed_id=feed_id,
            title=title,
            link=link,
            description=description,
            author=author,
            published_date=published_date,
            content=content
        )

        if article_id:
            new_articles += 1
            logger.info(f"Nouvel article: {title}")

    # Met à jour le timestamp du flux
    update_feed_timestamp(feed_id)

    logger.info(f"Flux {feed_info['title']}: {new_articles} nouveaux articles")
    return new_articles


def update_all_feeds():
    """Met à jour tous les flux actifs"""
    logger.info("Début de la mise à jour de tous les flux")
    feeds = get_active_feeds()

    total_new_articles = 0
    for feed in feeds:
        new_articles = update_single_feed(feed['id'])
        total_new_articles += new_articles

    logger.info(f"Mise à jour terminée: {total_new_articles} nouveaux articles au total")
    return total_new_articles


if __name__ == "__main__":
    # Test de mise à jour
    update_all_feeds()

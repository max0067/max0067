"""
Configuration de l'application pour différents environnements
"""

import os

class Config:
    """Configuration de base"""
    # Chemin de la base de données
    BASE_DIR = os.path.dirname(os.path.abspath(__file__))
    DATABASE_NAME = os.path.join(BASE_DIR, 'rss_feeds.db')

    # Configuration Flask
    SECRET_KEY = os.environ.get('SECRET_KEY') or 'votre-cle-secrete-a-changer-en-production'
    JSON_AS_ASCII = False

    # Configuration du scheduler
    SCHEDULER_ENABLED = True
    UPDATE_INTERVAL_MINUTES = 30


class DevelopmentConfig(Config):
    """Configuration pour le développement"""
    DEBUG = True
    TESTING = False


class ProductionConfig(Config):
    """Configuration pour la production (o2switch)"""
    DEBUG = False
    TESTING = False

    # Sur o2switch, vous pouvez mettre la DB hors du répertoire web pour plus de sécurité
    # DATABASE_NAME = os.path.expanduser('~/private/rss_feeds.db')


class TestingConfig(Config):
    """Configuration pour les tests"""
    DEBUG = True
    TESTING = True
    DATABASE_NAME = ':memory:'  # Base de données en mémoire pour les tests


# Dictionnaire de configuration
config = {
    'development': DevelopmentConfig,
    'production': ProductionConfig,
    'testing': TestingConfig,
    'default': DevelopmentConfig
}


def get_config():
    """Retourne la configuration selon l'environnement"""
    env = os.environ.get('FLASK_ENV', 'development')
    return config.get(env, config['default'])

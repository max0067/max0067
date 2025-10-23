#!/usr/bin/env python3
"""
Script de démonstration pour l'application RSS Manager
Lance l'application avec des flux de test pré-configurés

Usage: python demo.py
"""

import os
import sys
import time
import webbrowser
from threading import Timer

def check_dependencies():
    """Vérifie que toutes les dépendances sont installées"""
    print("🔍 Vérification des dépendances...")

    required_modules = {
        'flask': 'Flask',
        'feedparser': 'feedparser',
        'apscheduler': 'APScheduler',
        'dateutil': 'python-dateutil'
    }

    missing = []
    for module, package in required_modules.items():
        try:
            __import__(module)
            print(f"  ✅ {package}")
        except ImportError:
            print(f"  ❌ {package} manquant")
            missing.append(package)

    if missing:
        print(f"\n❌ Modules manquants : {', '.join(missing)}")
        print("\n📦 Installation des dépendances...")
        os.system(f"{sys.executable} -m pip install -r requirements.txt")
        print("✅ Dépendances installées !\n")
    else:
        print("✅ Toutes les dépendances sont installées\n")


def init_database():
    """Initialise la base de données"""
    print("💾 Initialisation de la base de données...")

    if os.path.exists('rss_feeds.db'):
        print("  ✅ Base de données existante trouvée")
        return

    from database import init_db
    init_db()
    print("  ✅ Base de données créée\n")


def add_demo_feeds():
    """Ajoute des flux de démonstration"""
    from database import add_feed, get_all_feeds

    feeds = get_all_feeds()

    if len(feeds) >= 3:
        print("📰 Flux RSS déjà présents")
        print(f"   {len(feeds)} flux trouvés\n")
        return

    print("📰 Ajout de flux de démonstration...")

    demo_feeds = [
        {
            'title': 'TechCrunch',
            'url': 'https://techcrunch.com/feed/',
            'description': 'Actualités tech et startups'
        },
        {
            'title': 'Le Monde - Actualités',
            'url': 'https://www.lemonde.fr/rss/une.xml',
            'description': 'Actualités françaises et internationales'
        },
        {
            'title': 'The Verge',
            'url': 'https://www.theverge.com/rss/index.xml',
            'description': 'Tech, science, art et culture'
        }
    ]

    for feed in demo_feeds:
        result = add_feed(
            title=feed['title'],
            url=feed['url'],
            description=feed['description']
        )
        if result:
            print(f"  ✅ {feed['title']}")
        else:
            print(f"  ℹ️  {feed['title']} (déjà existant)")

    print("\n📥 Récupération des articles...")
    from rss_updater import update_all_feeds

    new_articles = update_all_feeds()
    print(f"  ✅ {new_articles} articles récupérés\n")


def open_browser():
    """Ouvre le navigateur après un délai"""
    time.sleep(2)
    print("🌐 Ouverture du navigateur...")
    webbrowser.open('http://localhost:5000')


def start_app():
    """Lance l'application Flask"""
    print("🚀 Démarrage de l'application...")
    print("=" * 60)
    print()
    print("  📍 URL : http://localhost:5000")
    print("  🛑 Arrêter : Ctrl+C (Cmd+C sur Mac)")
    print()
    print("=" * 60)
    print()

    # Ouvrir le navigateur dans 2 secondes
    Timer(2, open_browser).start()

    # Lancer l'application
    from app import app
    app.run(debug=True, host='0.0.0.0', port=5000, use_reloader=False)


def main():
    """Point d'entrée principal"""
    print()
    print("=" * 60)
    print("  🌐 DÉMO - GESTIONNAIRE DE FLUX RSS")
    print("=" * 60)
    print()

    try:
        # 1. Vérifier les dépendances
        check_dependencies()

        # 2. Initialiser la base de données
        init_database()

        # 3. Ajouter des flux de démo
        add_demo_feeds()

        # 4. Lancer l'application
        start_app()

    except KeyboardInterrupt:
        print("\n\n✅ Application arrêtée. À bientôt !")
        sys.exit(0)
    except Exception as e:
        print(f"\n❌ Erreur : {e}")
        print("\n💡 Assurez-vous d'être dans le bon dossier et que Python est installé.")
        sys.exit(1)


if __name__ == "__main__":
    main()

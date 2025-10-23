#!/usr/bin/env python3
"""
Script de diagnostic pour o2switch
Lance ce script dans le Terminal cPanel pour identifier les problèmes

Usage: python diagnostic.py
"""

import os
import sys
import subprocess

print("="*60)
print("    DIAGNOSTIC DE L'APPLICATION RSS MANAGER")
print("="*60)
print()

# 1. Environnement
print("📍 1. ENVIRONNEMENT")
print("-" * 40)
print(f"Répertoire actuel : {os.getcwd()}")
print(f"Utilisateur : {os.environ.get('USER', 'inconnu')}")
print(f"Home : {os.environ.get('HOME', 'inconnu')}")
print(f"Python : {sys.version}")
print(f"Exécutable Python : {sys.executable}")
print()

# 2. Fichiers requis
print("📁 2. FICHIERS REQUIS")
print("-" * 40)
required_files = [
    'app.py',
    'database.py',
    'rss_updater.py',
    'passenger_wsgi.py',
    '.htaccess',
    'requirements.txt',
    'templates/index.html',
    'static/css/style.css',
    'static/js/app.js'
]

missing_files = []
for file in required_files:
    exists = os.path.exists(file)
    status = "✅" if exists else "❌"
    print(f"{status} {file}")
    if not exists:
        missing_files.append(file)
print()

# 3. Modules Python
print("📦 3. MODULES PYTHON")
print("-" * 40)
required_modules = ['flask', 'feedparser', 'apscheduler', 'dateutil']

missing_modules = []
for module in required_modules:
    try:
        __import__(module)
        print(f"✅ {module}")
    except ImportError:
        print(f"❌ {module} - MANQUANT")
        missing_modules.append(module)
print()

# 4. Base de données
print("💾 4. BASE DE DONNÉES")
print("-" * 40)
if os.path.exists('rss_feeds.db'):
    size = os.path.getsize('rss_feeds.db')
    print(f"✅ rss_feeds.db existe ({size} octets)")

    # Vérifier les permissions
    can_read = os.access('rss_feeds.db', os.R_OK)
    can_write = os.access('rss_feeds.db', os.W_OK)
    print(f"   Lecture : {'✅' if can_read else '❌'}")
    print(f"   Écriture : {'✅' if can_write else '❌'}")
else:
    print("❌ rss_feeds.db n'existe pas")
    print("   👉 Lancez : python database.py")
print()

# 5. Configuration Passenger
print("⚙️  5. CONFIGURATION PASSENGER")
print("-" * 40)

if os.path.exists('passenger_wsgi.py'):
    with open('passenger_wsgi.py', 'r') as f:
        content = f.read()
        if 'VOTRE_UTILISATEUR' in content or 'VOTRE_CHEMIN' in content:
            print("❌ passenger_wsgi.py contient encore des placeholders")
            print("   👉 Éditez le fichier et remplacez VOTRE_UTILISATEUR et VOTRE_CHEMIN")
        else:
            print("✅ passenger_wsgi.py semble configuré")

            # Extraire le chemin INTERP
            for line in content.split('\n'):
                if 'INTERP = ' in line and not line.strip().startswith('#'):
                    print(f"   Chemin : {line.strip()}")

                    # Vérifier si le fichier existe
                    import re
                    match = re.search(r'["\']([^"\']+)["\']', line)
                    if match:
                        interp_path = os.path.expanduser(match.group(1))
                        if os.path.exists(interp_path):
                            print(f"   ✅ L'interpréteur existe")
                        else:
                            print(f"   ❌ L'interpréteur n'existe pas à : {interp_path}")
else:
    print("❌ passenger_wsgi.py n'existe pas")
print()

if os.path.exists('.htaccess'):
    with open('.htaccess', 'r') as f:
        content = f.read()
        if 'VOTRE_UTILISATEUR' in content or 'VOTRE_CHEMIN' in content:
            print("❌ .htaccess contient encore des placeholders")
            print("   👉 Éditez le fichier et remplacez VOTRE_UTILISATEUR et VOTRE_CHEMIN")
        else:
            print("✅ .htaccess semble configuré")

            # Extraire PassengerAppRoot
            for line in content.split('\n'):
                if 'PassengerAppRoot' in line and not line.strip().startswith('#'):
                    print(f"   {line.strip()}")
else:
    print("❌ .htaccess n'existe pas")
print()

# 6. Test d'import de l'application
print("🚀 6. TEST D'IMPORT DE L'APPLICATION")
print("-" * 40)
try:
    from app import app
    print("✅ L'application Flask peut être importée")
    print(f"   Routes disponibles : {len(app.url_map._rules)}")
except Exception as e:
    print(f"❌ Erreur lors de l'import : {e}")
print()

# 7. Résumé
print("="*60)
print("    RÉSUMÉ")
print("="*60)

if missing_files:
    print(f"❌ {len(missing_files)} fichier(s) manquant(s)")
    for f in missing_files:
        print(f"   - {f}")
else:
    print("✅ Tous les fichiers requis sont présents")

if missing_modules:
    print(f"❌ {len(missing_modules)} module(s) Python manquant(s)")
    for m in missing_modules:
        print(f"   - {m}")
    print()
    print("👉 Pour installer : pip install -r requirements.txt")
else:
    print("✅ Tous les modules Python sont installés")

print()
print("="*60)
print()

# Conseils
if missing_files or missing_modules:
    print("🔧 ACTIONS À FAIRE :")
    print()
    if missing_files:
        print("1. Vérifiez que vous êtes dans le bon dossier")
        print("   cd ~/public_html/rss-manager  # Adaptez le chemin")
        print()
    if missing_modules:
        print("2. Activez l'environnement virtuel et installez les dépendances :")
        print("   source ~/virtualenv/.../bin/activate")
        print("   pip install -r requirements.txt")
        print()
    if not os.path.exists('rss_feeds.db'):
        print("3. Initialisez la base de données :")
        print("   python database.py")
        print()
else:
    print("✅ Tout semble bon !")
    print()
    print("Si vous voyez toujours 'Index of' :")
    print("1. Allez dans cPanel > Setup Python App")
    print("2. Vérifiez que l'application est 'Running'")
    print("3. Sinon, cliquez sur 'Restart'")
    print("4. Ou redémarrez avec : touch tmp/restart.txt")
    print()

print("="*60)

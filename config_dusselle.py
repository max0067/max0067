#!/usr/bin/env python3
"""
Script de configuration automatique pour dusselle.fr
Génère automatiquement passenger_wsgi.py et .htaccess avec les bons chemins
"""

import os
import sys
import subprocess

def get_username():
    """Récupère le nom d'utilisateur"""
    return os.environ.get('USER', os.environ.get('USERNAME', 'unknown'))

def get_home_dir():
    """Récupère le répertoire home"""
    return os.path.expanduser("~")

def find_python_version():
    """Trouve la version Python du virtualenv"""
    home = get_home_dir()
    possible_versions = ['3.12', '3.11', '3.10', '3.9']

    # Cherche dans dusselle.fr
    for version in possible_versions:
        path = os.path.join(home, "virtualenv", "dusselle.fr", version)
        if os.path.isdir(path):
            return version, "dusselle.fr"

    # Cherche dans public_html
    for version in possible_versions:
        path = os.path.join(home, "virtualenv", "public_html", version)
        if os.path.isdir(path):
            return version, "public_html"

    # Par défaut, utilise 3.11
    return "3.11", "dusselle.fr"

def get_app_root():
    """Détermine le root de l'application"""
    cwd = os.getcwd()
    home = get_home_dir()

    if 'public_html' in cwd:
        return 'public_html'
    elif 'dusselle.fr' in cwd:
        return 'dusselle.fr'
    else:
        # Par défaut
        return 'dusselle.fr'

def create_passenger_wsgi():
    """Crée le fichier passenger_wsgi.py"""
    username = get_username()
    python_version, venv_location = find_python_version()

    content = f"""import sys
import os

# Chemin vers l'interpréteur Python du virtualenv
INTERP = "/home/{username}/virtualenv/{venv_location}/{python_version}/bin/python"

if os.path.isfile(INTERP):
    if sys.executable != INTERP:
        os.execl(INTERP, INTERP, *sys.argv)
else:
    print(f"ERREUR: Python non trouvé à: {{INTERP}}")

# Ajouter le répertoire de l'application au path
sys.path.insert(0, os.path.dirname(__file__))

# Importer l'application V2
from app_v2 import app as application
"""

    # Backup de l'ancien fichier si existant
    if os.path.isfile('passenger_wsgi.py'):
        os.rename('passenger_wsgi.py', 'passenger_wsgi.py.backup')
        print("✅ Backup créé: passenger_wsgi.py.backup")

    with open('passenger_wsgi.py', 'w') as f:
        f.write(content)

    print("✅ passenger_wsgi.py créé avec succès!")
    print(f"   - Utilisateur: {username}")
    print(f"   - Version Python: {python_version}")
    print(f"   - Virtualenv: {venv_location}")

def create_htaccess():
    """Crée le fichier .htaccess"""
    username = get_username()
    app_root = get_app_root()
    python_version, venv_location = find_python_version()

    content = f"""# Configuration Passenger pour l'application RSS Manager V2
PassengerEnabled On
PassengerAppRoot /home/{username}/{app_root}
PassengerPython /home/{username}/virtualenv/{venv_location}/{python_version}/bin/python

# Redirection vers HTTPS (optionnel)
# RewriteEngine On
# RewriteCond %{{HTTPS}} off
# RewriteRule ^(.*)$ https://%{{HTTP_HOST}}%{{REQUEST_URI}} [L,R=301]

# Protection des fichiers sensibles
<FilesMatch "\\.(db|py|pyc|log)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Autoriser les fichiers statiques
<FilesMatch "\\.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$">
    Order allow,deny
    Allow from all
</FilesMatch>
"""

    # Backup de l'ancien fichier si existant
    if os.path.isfile('.htaccess'):
        os.rename('.htaccess', '.htaccess.backup')
        print("✅ Backup créé: .htaccess.backup")

    with open('.htaccess', 'w') as f:
        f.write(content)

    print("✅ .htaccess créé avec succès!")
    print(f"   - Utilisateur: {username}")
    print(f"   - App root: {app_root}")
    print(f"   - Version Python: {python_version}")

def create_tmp_dir():
    """Crée le dossier tmp et le fichier restart.txt"""
    if not os.path.isdir('tmp'):
        os.makedirs('tmp')
        print("✅ Dossier tmp créé")

    with open('tmp/restart.txt', 'w') as f:
        f.write('')
    print("✅ tmp/restart.txt créé (redémarrage de l'application)")

def check_database():
    """Vérifie si la base de données existe"""
    if not os.path.isfile('rss_feeds.db'):
        print("\n⚠️ Base de données non trouvée!")
        print("   Exécutez: python database_v2.py")
        return False

    print("✅ Base de données trouvée")

    # Vérifie les permissions
    if not os.access('rss_feeds.db', os.W_OK):
        print("⚠️ Base de données non modifiable")
        print("   Exécutez: chmod 666 rss_feeds.db")
        return False

    return True

def install_dependencies():
    """Propose d'installer les dépendances"""
    if not os.path.isfile('requirements.txt'):
        print("⚠️ requirements.txt non trouvé")
        return False

    print("\n📦 Installation des dépendances...")
    print("   (Cela peut prendre quelques minutes)")

    try:
        # Essaye d'installer avec pip
        result = subprocess.run(
            [sys.executable, '-m', 'pip', 'install', '-r', 'requirements.txt'],
            capture_output=True,
            text=True,
            timeout=300  # 5 minutes max
        )

        if result.returncode == 0:
            print("✅ Dépendances installées avec succès!")
            return True
        else:
            print(f"❌ Erreur lors de l'installation:")
            print(result.stderr)
            return False
    except subprocess.TimeoutExpired:
        print("⚠️ Installation trop longue (timeout)")
        return False
    except Exception as e:
        print(f"⚠️ Impossible d'installer automatiquement: {e}")
        return False

def main():
    print("="*60)
    print("  CONFIGURATION AUTOMATIQUE - DUSSELLE.FR")
    print("="*60)

    # Vérifie qu'on est dans le bon répertoire
    if not os.path.isfile('app_v2.py'):
        print("\n❌ ERREUR: app_v2.py non trouvé!")
        print("   Assurez-vous d'être dans le bon répertoire")
        print(f"   Répertoire actuel: {os.getcwd()}")
        return 1

    print(f"\n📁 Répertoire: {os.getcwd()}")
    print(f"👤 Utilisateur: {get_username()}")

    # Crée les fichiers de configuration
    print("\n" + "-"*60)
    print("CRÉATION DES FICHIERS DE CONFIGURATION")
    print("-"*60)

    try:
        create_passenger_wsgi()
        create_htaccess()
        create_tmp_dir()
    except Exception as e:
        print(f"\n❌ Erreur lors de la création des fichiers: {e}")
        return 1

    # Vérifie la base de données
    print("\n" + "-"*60)
    print("VÉRIFICATION DE LA BASE DE DONNÉES")
    print("-"*60)
    db_ok = check_database()

    # Instructions finales
    print("\n" + "="*60)
    print("CONFIGURATION TERMINÉE!")
    print("="*60)

    print("\n📋 PROCHAINES ÉTAPES:\n")

    if not db_ok:
        print("1️⃣ Initialiser la base de données:")
        print("   python database_v2.py")
        print()

    print("2️⃣ Installer les dépendances (si pas déjà fait):")
    python_version, venv_location = find_python_version()
    print(f"   source ~/virtualenv/{venv_location}/{python_version}/bin/activate")
    print("   pip install -r requirements.txt")
    print()

    print("3️⃣ Redémarrer l'application:")
    print("   touch tmp/restart.txt")
    print()

    print("4️⃣ Tester l'application:")
    print("   https://dusselle.fr/login")
    print()

    print("5️⃣ Connexion par défaut:")
    print("   Username: admin")
    print("   Password: admin123")
    print()

    print("="*60)
    print("✅ Configuration terminée avec succès!")
    print("="*60)

    return 0

if __name__ == '__main__':
    sys.exit(main())

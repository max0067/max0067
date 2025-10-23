#!/usr/bin/env python3
"""
Script de vérification du déploiement sur o2switch/dusselle.fr
À exécuter sur le serveur pour diagnostiquer les problèmes
"""

import os
import sys
import subprocess

def print_section(title):
    print("\n" + "="*60)
    print(f"  {title}")
    print("="*60)

def check_file(filepath, required=True):
    """Vérifie si un fichier existe"""
    exists = os.path.isfile(filepath)
    status = "✅" if exists else ("❌" if required else "⚠️")
    print(f"{status} {filepath}: {'EXISTS' if exists else 'NOT FOUND'}")
    return exists

def check_directory(dirpath):
    """Vérifie si un dossier existe"""
    exists = os.path.isdir(dirpath)
    status = "✅" if exists else "❌"
    print(f"{status} {dirpath}: {'EXISTS' if exists else 'NOT FOUND'}")
    return exists

def check_python_version():
    """Vérifie la version de Python"""
    version = sys.version_info
    print(f"Python version: {version.major}.{version.minor}.{version.micro}")
    if version.major >= 3 and version.minor >= 8:
        print("✅ Version Python OK (>= 3.8)")
        return True
    else:
        print("⚠️ Version Python < 3.8 (peut causer des problèmes)")
        return False

def check_module(module_name):
    """Vérifie si un module Python est installé"""
    try:
        __import__(module_name)
        print(f"✅ {module_name}: INSTALLED")
        return True
    except ImportError:
        print(f"❌ {module_name}: NOT INSTALLED")
        return False

def check_database():
    """Vérifie la base de données"""
    if not os.path.isfile('rss_feeds.db'):
        print("❌ Base de données rss_feeds.db introuvable")
        return False

    # Vérifie les permissions
    readable = os.access('rss_feeds.db', os.R_OK)
    writable = os.access('rss_feeds.db', os.W_OK)

    print(f"{'✅' if readable else '❌'} Base de données lisible: {readable}")
    print(f"{'✅' if writable else '❌'} Base de données modifiable: {writable}")

    # Vérifie la taille
    size = os.path.getsize('rss_feeds.db')
    print(f"📊 Taille: {size} bytes")

    if size < 1000:
        print("⚠️ Base de données très petite - peut-être non initialisée")
        return False

    return readable and writable

def check_passenger_wsgi():
    """Vérifie passenger_wsgi.py"""
    if not os.path.isfile('passenger_wsgi.py'):
        print("❌ passenger_wsgi.py introuvable")
        return False

    with open('passenger_wsgi.py', 'r') as f:
        content = f.read()

    checks = {
        'INTERP': 'INTERP' in content,
        'app_v2': 'app_v2' in content,
        'virtualenv': 'virtualenv' in content,
        'VOTRE_USER': 'VOTRE_USER' not in content,  # Ne doit PAS contenir VOTRE_USER
    }

    for check, passed in checks.items():
        status = "✅" if passed else "❌"
        if check == 'VOTRE_USER':
            print(f"{status} Pas de placeholder VOTRE_USER: {passed}")
        else:
            print(f"{status} Contient '{check}': {passed}")

    return all(checks.values())

def check_htaccess():
    """Vérifie .htaccess"""
    if not os.path.isfile('.htaccess'):
        print("❌ .htaccess introuvable")
        return False

    with open('.htaccess', 'r') as f:
        content = f.read()

    checks = {
        'PassengerAppRoot': 'PassengerAppRoot' in content,
        'PassengerPython': 'PassengerPython' in content,
        'VOTRE_USER': 'VOTRE_USER' not in content,  # Ne doit PAS contenir VOTRE_USER
    }

    for check, passed in checks.items():
        status = "✅" if passed else "❌"
        if check == 'VOTRE_USER':
            print(f"{status} Pas de placeholder VOTRE_USER: {passed}")
        else:
            print(f"{status} Contient '{check}': {passed}")

    return all(checks.values())

def find_virtualenv():
    """Trouve le chemin du virtualenv"""
    home = os.path.expanduser("~")
    possible_paths = [
        os.path.join(home, "virtualenv", "dusselle.fr"),
        os.path.join(home, "virtualenv", "public_html"),
    ]

    for base_path in possible_paths:
        if os.path.isdir(base_path):
            # Cherche les versions Python
            for version in ['3.12', '3.11', '3.10', '3.9']:
                full_path = os.path.join(base_path, version)
                if os.path.isdir(full_path):
                    print(f"✅ Virtualenv trouvé: {full_path}")
                    return full_path

    print("❌ Aucun virtualenv trouvé")
    return None

def main():
    print_section("DIAGNOSTIC DE DÉPLOIEMENT - DUSSELLE.FR")

    # Informations système
    print_section("1. INFORMATIONS SYSTÈME")
    print(f"Répertoire actuel: {os.getcwd()}")
    print(f"Utilisateur: {os.environ.get('USER', 'unknown')}")
    check_python_version()

    # Fichiers essentiels
    print_section("2. FICHIERS ESSENTIELS")
    files_ok = True
    files_ok &= check_file('app_v2.py', required=True)
    files_ok &= check_file('database_v2.py', required=True)
    files_ok &= check_file('requirements.txt', required=True)
    files_ok &= check_file('passenger_wsgi.py', required=True)
    files_ok &= check_file('.htaccess', required=True)

    # Dossiers
    print_section("3. DOSSIERS")
    dirs_ok = True
    dirs_ok &= check_directory('templates')
    dirs_ok &= check_directory('static')
    dirs_ok &= check_directory('static/css')
    dirs_ok &= check_directory('static/js')

    # Templates V2
    print_section("4. TEMPLATES V2")
    templates_ok = True
    templates_ok &= check_file('templates/login.html')
    templates_ok &= check_file('templates/register.html')
    templates_ok &= check_file('templates/index_v2.html')
    templates_ok &= check_file('templates/dashboard.html')
    templates_ok &= check_file('templates/admin.html')

    # Modules Python
    print_section("5. MODULES PYTHON")
    modules_ok = True
    modules_ok &= check_module('flask')
    modules_ok &= check_module('feedparser')
    modules_ok &= check_module('apscheduler')

    # Base de données
    print_section("6. BASE DE DONNÉES")
    db_ok = check_database()

    # Configuration
    print_section("7. CONFIGURATION")
    passenger_ok = check_passenger_wsgi()
    htaccess_ok = check_htaccess()

    # Virtualenv
    print_section("8. ENVIRONNEMENT VIRTUEL")
    venv_path = find_virtualenv()
    venv_ok = venv_path is not None

    # Dossier tmp
    print_section("9. DOSSIER TMP (RESTART)")
    tmp_exists = check_directory('tmp')
    if tmp_exists:
        restart_file = os.path.isfile('tmp/restart.txt')
        print(f"{'✅' if restart_file else '⚠️'} tmp/restart.txt: {'EXISTS' if restart_file else 'NOT FOUND'}")

    # Résumé
    print_section("RÉSUMÉ")

    all_checks = [
        ("Fichiers essentiels", files_ok),
        ("Dossiers", dirs_ok),
        ("Templates", templates_ok),
        ("Modules Python", modules_ok),
        ("Base de données", db_ok),
        ("passenger_wsgi.py", passenger_ok),
        (".htaccess", htaccess_ok),
        ("Virtualenv", venv_ok),
    ]

    for name, status in all_checks:
        print(f"{'✅' if status else '❌'} {name}")

    all_ok = all(status for _, status in all_checks)

    print("\n" + "="*60)
    if all_ok:
        print("✅ TOUT EST CONFIGURÉ CORRECTEMENT !")
        print("\nSi l'application ne fonctionne toujours pas:")
        print("1. Redémarrez l'application: touch tmp/restart.txt")
        print("2. Consultez les logs d'erreur dans cPanel")
        print("3. Vérifiez que l'application Python est 'Running' dans cPanel")
    else:
        print("❌ PROBLÈMES DÉTECTÉS")
        print("\nActions recommandées:")

        if not modules_ok:
            print("\n📦 INSTALLER LES DÉPENDANCES:")
            if venv_path:
                print(f"   source {venv_path}/bin/activate")
            else:
                print("   source ~/virtualenv/dusselle.fr/3.11/bin/activate")
            print("   pip install -r requirements.txt")

        if not db_ok:
            print("\n💾 INITIALISER LA BASE DE DONNÉES:")
            print("   python database_v2.py")

        if not passenger_ok or not htaccess_ok:
            print("\n⚙️ CORRIGER LA CONFIGURATION:")
            print("   Éditez passenger_wsgi.py et .htaccess")
            print("   Remplacez VOTRE_USER par votre nom d'utilisateur")
            print("   Pour trouver votre user: whoami")

        if not tmp_exists:
            print("\n📁 CRÉER LE DOSSIER TMP:")
            print("   mkdir -p tmp")
            print("   touch tmp/restart.txt")

    print("="*60)

if __name__ == '__main__':
    main()

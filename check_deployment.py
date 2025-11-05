#!/usr/bin/env python3
"""
Script de diagnostic pour vérifier le déploiement de l'application
"""
import sys
import os

print("=== DIAGNOSTIC DE DÉPLOIEMENT ===\n")

# 1. Version Python
print(f"✓ Python version: {sys.version}")
print(f"✓ Python executable: {sys.executable}\n")

# 2. Vérifier les modules requis
required_modules = [
    'flask',
    'feedparser',
    'requests',
    'apscheduler',
    'dateutil'
]

print("=== MODULES PYTHON ===")
for module_name in required_modules:
    try:
        if module_name == 'dateutil':
            __import__('dateutil')
            import dateutil
            version = getattr(dateutil, '__version__', 'unknown')
        else:
            mod = __import__(module_name)
            version = getattr(mod, '__version__', 'unknown')
        print(f"✓ {module_name}: {version}")
    except ImportError as e:
        print(f"✗ {module_name}: MANQUANT - {e}")

# 3. Vérifier les fichiers requis
print("\n=== FICHIERS REQUIS ===")
required_files = [
    'app_v2.py',
    'database_v2.py',
    'rss_updater.py',
    'passenger_wsgi.py',
    '.htaccess',
    'requirements_py36.txt',
    'templates/index_v2.html',
    'templates/login.html',
    'templates/dashboard.html'
]

for file_path in required_files:
    if os.path.exists(file_path):
        print(f"✓ {file_path}")
    else:
        print(f"✗ {file_path} - MANQUANT")

# 4. Vérifier les permissions
print("\n=== PERMISSIONS ===")
key_files = ['passenger_wsgi.py', 'app_v2.py', 'database_v2.py']
for file_path in key_files:
    if os.path.exists(file_path):
        perms = oct(os.stat(file_path).st_mode)[-3:]
        print(f"  {file_path}: {perms}")

# 5. Tester l'import de l'application
print("\n=== TEST D'IMPORT ===")
try:
    sys.path.insert(0, os.path.dirname(__file__))
    from app_v2 import app
    print("✓ Import de app_v2 réussi")
    print(f"✓ Application Flask créée: {app}")
except Exception as e:
    print(f"✗ Erreur d'import: {e}")
    import traceback
    traceback.print_exc()

# 6. Vérifier la base de données
print("\n=== BASE DE DONNÉES ===")
db_file = 'rss_manager.db'
if os.path.exists(db_file):
    size = os.path.getsize(db_file)
    print(f"✓ {db_file} existe ({size} bytes)")
else:
    print(f"✗ {db_file} n'existe pas - sera créé au premier démarrage")

print("\n=== FIN DU DIAGNOSTIC ===")

"""
VERSION SIMPLIFIÉE DE passenger_wsgi.py
Copiez ce fichier et renommez-le en passenger_wsgi.py

INSTRUCTIONS :
1. Remplacez VOTRE_UTILISATEUR par votre nom d'utilisateur o2switch
2. Remplacez VOTRE_CHEMIN par le chemin de votre application
3. Remplacez VERSION_PYTHON par votre version (3.9, 3.10, 3.11, 3.12)

Exemples de chemins :
- public_html (si fichiers directement dans public_html)
- public_html/rss-manager (si dans un sous-dossier)
- rss.votre-domaine.com (si sous-domaine)
"""

import sys
import os

# ⚠️ MODIFIEZ ICI - Version complète du chemin
# Pour trouver votre utilisateur, tapez dans le terminal : whoami
INTERP = "/home/VOTRE_UTILISATEUR/virtualenv/VOTRE_CHEMIN/VERSION_PYTHON/bin/python"

# Exemples (décommentez et modifiez celui qui correspond) :
# INTERP = "/home/max0067/virtualenv/public_html/3.11/bin/python"
# INTERP = "/home/max0067/virtualenv/public_html/rss-manager/3.11/bin/python"
# INTERP = "/home/max0067/virtualenv/rss.domaine.com/3.11/bin/python"

# Vérifier si l'interpréteur existe
if os.path.isfile(INTERP):
    if sys.executable != INTERP:
        os.execl(INTERP, INTERP, *sys.argv)
else:
    print(f"ERREUR: Python non trouvé à: {INTERP}")
    print("Vérifiez que vous avez bien créé l'application Python dans cPanel")

# Ajouter le répertoire de l'application au path
sys.path.insert(0, os.path.dirname(__file__))

# Importer l'application Flask
try:
    from app import app as application
except ImportError as e:
    print(f"ERREUR lors de l'import de l'application: {e}")
    print("Vérifiez que Flask est installé : pip install -r requirements.txt")
    raise

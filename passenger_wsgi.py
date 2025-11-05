"""
Fichier de configuration Passenger WSGI pour o2switch
Ce fichier est le point d'entrée pour l'application Flask sur les serveurs utilisant Passenger
"""

import sys
import os

# Chemin vers le répertoire de l'application
# Configuration pour dusselle.fr sur o2switch
INTERP = os.path.expanduser("~/dusselle.fr/venv/bin/python")

# Vérifier si l'interpréteur Python existe
if os.path.isfile(INTERP):
    if sys.executable != INTERP:
        os.execl(INTERP, INTERP, *sys.argv)
else:
    print(f"ERREUR: L'interpréteur Python n'a pas été trouvé à: {INTERP}")
    print("Veuillez créer l'environnement virtuel avec: python3 -m venv venv")

# Ajouter le répertoire de l'application au PYTHONPATH
sys.path.insert(0, os.path.dirname(__file__))

# Importer l'application Flask (version 2)
from app_v2 import app as application

# Pour le debugging (à désactiver en production)
# import logging
# logging.basicConfig(stream=sys.stderr)

"""
Fichier de configuration Passenger WSGI pour o2switch - SANS environnement virtuel
Utilisez ce fichier si vous installez les dépendances avec pip install --user
"""

import sys
import os

# Ajouter le répertoire de l'application au PYTHONPATH
sys.path.insert(0, os.path.dirname(__file__))

# Importer l'application Flask (version 2)
try:
    from app_v2 import app as application
except ImportError as e:
    # En cas d'erreur, créer une application Flask simple qui affiche l'erreur
    from flask import Flask
    application = Flask(__name__)

    @application.route('/')
    def error():
        return f"""
        <h1>Erreur de démarrage de l'application</h1>
        <p>Erreur: {str(e)}</p>
        <p>Python version: {sys.version}</p>
        <p>Python path: {sys.path}</p>
        <h2>Instructions de déploiement :</h2>
        <ol>
            <li>Vérifiez que Python 3.6+ est installé</li>
            <li>Installez les dépendances : <code>pip install --user -r requirements_py36.txt</code></li>
            <li>Vérifiez que tous les fichiers sont présents (app_v2.py, database_v2.py, etc.)</li>
            <li>Redémarrez Passenger : <code>touch tmp/restart.txt</code></li>
        </ol>
        """, 500

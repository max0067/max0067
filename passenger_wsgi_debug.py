"""
Fichier de debug pour Passenger - Version simplifiée pour identifier les erreurs
"""

import sys
import os
import traceback

# Créer un fichier de log pour debug
log_file = os.path.join(os.path.dirname(__file__), 'passenger_debug.log')

def log_message(msg):
    """Écrire dans le fichier de log"""
    with open(log_file, 'a') as f:
        f.write(f"{msg}\n")

log_message("=" * 50)
log_message("DÉBUT DU DÉMARRAGE PASSENGER")
log_message(f"Python version: {sys.version}")
log_message(f"Python executable: {sys.executable}")
log_message(f"Current directory: {os.getcwd()}")
log_message(f"Script directory: {os.path.dirname(__file__)}")
log_message(f"sys.path: {sys.path}")

# Ajouter le répertoire de l'application au PYTHONPATH
sys.path.insert(0, os.path.dirname(__file__))
log_message(f"Updated sys.path: {sys.path}")

# Tentative d'import de l'application
try:
    log_message("Tentative d'import de app_v2...")
    from app_v2 import app as application
    log_message("✓ Import réussi!")
    log_message(f"Application: {application}")
    log_message(f"Application name: {application.name}")
except Exception as e:
    log_message(f"✗ ERREUR D'IMPORT: {e}")
    log_message(f"Traceback complet:")
    log_message(traceback.format_exc())

    # Créer une application Flask simple qui affiche l'erreur
    from flask import Flask
    application = Flask(__name__)

    error_msg = f"""
    <html>
    <head><title>Erreur de démarrage</title></head>
    <body>
        <h1>Erreur lors de l'import de l'application</h1>
        <pre>{traceback.format_exc()}</pre>
        <h2>Informations système:</h2>
        <ul>
            <li>Python: {sys.version}</li>
            <li>Executable: {sys.executable}</li>
            <li>Working directory: {os.getcwd()}</li>
        </ul>
        <p><strong>Consultez le fichier passenger_debug.log pour plus de détails</strong></p>
    </body>
    </html>
    """

    @application.route('/')
    def error():
        return error_msg, 500

log_message("FIN DU SCRIPT passenger_wsgi_debug.py")
log_message("=" * 50)

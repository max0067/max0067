#!/usr/bin/python3
# -*- coding: utf-8 -*-
"""
Fichier de démarrage Passenger WSGI pour l'application RSS Manager V2
Version SIMPLE - Sans virtualenv
"""

import sys
import os

# Ajouter le répertoire de l'application au path Python
sys.path.insert(0, os.path.dirname(__file__))

# Importer l'application Flask depuis app_v2.py
from app_v2 import app as application

# Pour le debugging (à commenter en production)
# application.debug = False

if __name__ == '__main__':
    application.run()

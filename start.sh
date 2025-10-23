#!/bin/bash

echo "==================================="
echo "Gestionnaire de Flux RSS"
echo "==================================="
echo ""

# Vérifier si la base de données existe
if [ ! -f "rss_feeds.db" ]; then
    echo "Initialisation de la base de données..."
    python database.py
    echo ""
fi

echo "Démarrage de l'application..."
echo "L'application sera accessible à : http://localhost:5000"
echo ""
echo "Appuyez sur Ctrl+C pour arrêter l'application"
echo ""

python app.py

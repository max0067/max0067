#!/bin/bash

# Script de déploiement pour dusselle.fr
# Ce script télécharge les fichiers depuis GitHub et les met à jour sur le serveur

echo "🚀 Déploiement du système de gestion de dossiers..."

BRANCH="claude/fix-dusselle-app-issue-011CUdP9LbM4J3fMkcLWJWuc"
GITHUB_RAW="https://raw.githubusercontent.com/max0067/max0067/${BRANCH}"

# Télécharger database_v2.py
echo "📥 Téléchargement de database_v2.py..."
curl -s "${GITHUB_RAW}/database_v2.py" -o database_v2.py

# Télécharger app_v2.py
echo "📥 Téléchargement de app_v2.py..."
curl -s "${GITHUB_RAW}/app_v2.py" -o app_v2.py

# Télécharger dashboard.html
echo "📥 Téléchargement de templates/dashboard.html..."
curl -s "${GITHUB_RAW}/templates/dashboard.html" -o templates/dashboard.html

# Télécharger folders.html
echo "📥 Téléchargement de templates/folders.html..."
curl -s "${GITHUB_RAW}/templates/folders.html" -o templates/folders.html

# Télécharger folder_detail.html
echo "📥 Téléchargement de templates/folder_detail.html..."
curl -s "${GITHUB_RAW}/templates/folder_detail.html" -o templates/folder_detail.html

echo ""
echo "✅ Fichiers téléchargés avec succès !"
echo ""
echo "📋 Prochaines étapes :"
echo "1. Créer les tables : python3 database_v2.py"
echo "2. Redémarrer l'app : touch tmp/restart.txt"
echo "3. Redémarrer dans cPanel : Setup Python App → RESTART"

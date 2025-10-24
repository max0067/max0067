#!/bin/bash
#
# Script de réparation automatique du dashboard
#

echo "🔧 Réparation du dashboard en cours..."
echo ""

cd /home/wrbh3411/dusselle.fr

# Vérifier si git fonctionne
if git status &> /dev/null; then
    echo "✅ Git détecté, utilisation de git..."

    # Restaurer les fichiers depuis git
    git checkout templates/dashboard.html
    git checkout static/css/dashboard_modern.css
    git checkout static/js/dashboard_modern.js

    echo "✅ Fichiers restaurés depuis git"
else
    echo "⚠️  Git non configuré"
    echo "❌ Impossible de restaurer automatiquement"
    echo ""
    echo "Solution : Uploadez les fichiers par FTP"
    exit 1
fi

# Redémarrer l'application
mkdir -p tmp
touch tmp/restart.txt

echo ""
echo "✅ Dashboard réparé !"
echo "🎯 Testez sur : https://dusselle.fr/dashboard"

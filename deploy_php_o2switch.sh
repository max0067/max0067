#!/bin/bash
#
# Script de déploiement PHP sur o2switch
# Déploie la version PHP de l'application RSS Reader
#
# Usage: ./deploy_php_o2switch.sh
#

set -e  # Arrêter en cas d'erreur

echo "================================================"
echo "🚀 DÉPLOIEMENT VERSION PHP SUR O2SWITCH"
echo "================================================"
echo ""

# Vérifier qu'on est dans le bon répertoire
if [ ! -f "index.php" ]; then
    echo "❌ Erreur : Exécutez ce script depuis le répertoire racine de l'application"
    exit 1
fi

echo "📦 Étape 1/5 : Récupération des derniers changements..."
git pull origin claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ || echo "⚠️  Impossible de faire git pull (normal si pas de connexion git)"

echo ""
echo "🔧 Étape 2/5 : Activation de la configuration PHP..."

# Backup de l'ancien .htaccess s'il existe et n'est pas déjà sauvegardé
if [ -f ".htaccess" ] && [ ! -f ".htaccess_python_backup" ]; then
    echo "   → Sauvegarde de l'ancien .htaccess..."
    cp .htaccess .htaccess_python_backup
fi

# Copier le .htaccess PHP
if [ -f ".htaccess_php" ]; then
    echo "   → Activation du .htaccess PHP..."
    cp .htaccess_php .htaccess
else
    echo "⚠️  Attention : .htaccess_php introuvable !"
fi

echo ""
echo "🔒 Étape 3/5 : Désactivation des fichiers Python..."

# Désactiver les fichiers Python s'ils existent
if [ -f "app_v2.py" ]; then
    echo "   → Désactivation de app_v2.py..."
    mv app_v2.py app_v2.py.disabled 2>/dev/null || true
fi

if [ -f "passenger_wsgi.py" ]; then
    echo "   → Désactivation de passenger_wsgi.py..."
    mv passenger_wsgi.py passenger_wsgi.py.disabled 2>/dev/null || true
fi

echo ""
echo "📁 Étape 4/5 : Création des dossiers nécessaires..."

# Créer le dossier logs s'il n'existe pas
if [ ! -d "logs" ]; then
    mkdir -p logs
    echo "   → Dossier logs/ créé"
fi

# Définir les permissions
chmod 755 logs
chmod 755 php 2>/dev/null || true
chmod 644 php/*.php 2>/dev/null || true
chmod 755 index.php 2>/dev/null || true
chmod 755 cron_update_feeds.php 2>/dev/null || true

echo ""
echo "🎯 Étape 5/5 : Vérification de la configuration..."

# Vérifier que .htaccess contient bien la config PHP
if grep -q "Configuration Apache pour l'application PHP" .htaccess 2>/dev/null; then
    echo "   ✅ .htaccess configuré pour PHP"
else
    echo "   ⚠️  .htaccess ne semble pas être configuré pour PHP"
fi

# Vérifier que index.php existe
if [ -f "index.php" ]; then
    echo "   ✅ index.php trouvé"
else
    echo "   ❌ index.php introuvable !"
fi

# Vérifier que le dossier php/ existe
if [ -d "php" ]; then
    echo "   ✅ Dossier php/ trouvé"
else
    echo "   ❌ Dossier php/ introuvable !"
fi

echo ""
echo "🔄 Étape 6/6 : Redémarrage de Passenger..."

# Créer le dossier tmp s'il n'existe pas
if [ ! -d "tmp" ]; then
    mkdir -p tmp
    echo "   → Dossier tmp/ créé"
fi

# Redémarrer Passenger en touchant le fichier restart.txt
touch tmp/restart.txt
echo "   ✅ Passenger redémarré (désactivation en cours...)"
echo "   ⏱️  Attendez 10-15 secondes que les changements prennent effet"

echo ""
echo "================================================"
echo "✅ DÉPLOIEMENT TERMINÉ !"
echo "================================================"
echo ""
echo "📋 Prochaines étapes :"
echo ""
echo "1. 🌐 Testez votre site : http://dusselle.fr"
echo ""
echo "2. 🔐 Connectez-vous avec :"
echo "   Username: admin"
echo "   Password: admin123"
echo "   ⚠️  CHANGEZ CE MOT DE PASSE IMMÉDIATEMENT !"
echo ""
echo "3. ⏰ Configurez le CRON dans cPanel :"
echo "   Commande : /usr/bin/php $(pwd)/cron_update_feeds.php"
echo "   Intervalle : */30 * * * * (toutes les 30 minutes)"
echo ""
echo "4. 🔧 Personnalisez php/config.php :"
echo "   - Changez SECRET_KEY"
echo "   - Ajustez les paramètres si nécessaire"
echo ""
echo "================================================"
echo ""
echo "📖 Documentation :"
echo "   - DEMARRAGE_PHP.md : Guide complet"
echo "   - README_PHP.md : Documentation technique"
echo "   - SWITCH_VERSION.md : Revenir à Python si besoin"
echo ""
echo "🆘 En cas de problème :"
echo "   - Consultez logs/app.log"
echo "   - Consultez les logs Apache dans cPanel"
echo "   - Voir DEMARRAGE_PHP.md section Dépannage"
echo ""
echo "================================================"

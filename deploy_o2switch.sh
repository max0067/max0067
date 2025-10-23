#!/bin/bash

# Script de déploiement pour o2switch
# Ce script automatise le déploiement de l'application sur o2switch

echo "========================================"
echo "  Déploiement sur o2switch"
echo "========================================"
echo ""

# Configuration
read -p "Entrez votre nom d'utilisateur o2switch: " O2SWITCH_USER
read -p "Entrez votre domaine ou chemin de déploiement (ex: public_html/rss-manager): " DEPLOY_PATH

echo ""
echo "Configuration:"
echo "  Utilisateur: $O2SWITCH_USER"
echo "  Chemin: $DEPLOY_PATH"
echo ""
read -p "Continuer? (o/n) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Oo]$ ]]; then
    echo "Déploiement annulé."
    exit 1
fi

# Créer l'environnement virtuel
echo "1. Création de l'environnement virtuel..."
python3 -m venv venv

if [ $? -ne 0 ]; then
    echo "ERREUR: Impossible de créer l'environnement virtuel"
    exit 1
fi

# Activer l'environnement virtuel
echo "2. Activation de l'environnement virtuel..."
source venv/bin/activate

# Mettre à jour pip
echo "3. Mise à jour de pip..."
pip install --upgrade pip

# Installer les dépendances
echo "4. Installation des dépendances..."
pip install -r requirements.txt

if [ $? -ne 0 ]; then
    echo "ERREUR: Impossible d'installer les dépendances"
    exit 1
fi

# Initialiser la base de données
echo "5. Initialisation de la base de données..."
python database.py

if [ $? -ne 0 ]; then
    echo "ERREUR: Impossible d'initialiser la base de données"
    exit 1
fi

# Créer le dossier tmp pour Passenger
echo "6. Création du dossier tmp..."
mkdir -p tmp

# Créer le dossier logs
echo "7. Création du dossier logs..."
mkdir -p logs

# Configurer les permissions
echo "8. Configuration des permissions..."
chmod 755 .
chmod 644 *.py
chmod 755 start.sh
chmod 755 deploy_o2switch.sh
chmod 666 rss_feeds.db 2>/dev/null || echo "Base de données non encore créée"

# Mettre à jour passenger_wsgi.py avec le bon chemin
echo "9. Configuration de passenger_wsgi.py..."
FULL_PATH="/home/$O2SWITCH_USER/$DEPLOY_PATH"
sed -i "s|~/public_html/rss-manager|~/$DEPLOY_PATH|g" passenger_wsgi.py
sed -i "s|/home/VOTRE_USER/public_html/rss-manager|$FULL_PATH|g" .htaccess

echo ""
echo "========================================"
echo "  Déploiement terminé!"
echo "========================================"
echo ""
echo "Prochaines étapes:"
echo "1. Vérifiez que l'application Python est configurée dans cPanel"
echo "2. Redémarrez l'application: touch tmp/restart.txt"
echo "3. Accédez à votre application via votre navigateur"
echo ""
echo "Pour redémarrer l'application ultérieurement:"
echo "  touch tmp/restart.txt"
echo ""
echo "Pour mettre à jour les flux manuellement:"
echo "  source venv/bin/activate && python -c 'from rss_updater import update_all_feeds; update_all_feeds()'"
echo ""

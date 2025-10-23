#!/bin/bash
#
# Script de mise à jour automatique pour dusselle.fr
# Usage: ./update.sh
#

set -e  # Arrêter en cas d'erreur

echo "🚀 Mise à jour du RSS Manager sur dusselle.fr"
echo "=============================================="
echo ""

# Couleurs pour le terminal
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Aller dans le bon répertoire
cd /home/wrbh3411/dusselle.fr

echo -e "${BLUE}📁 Répertoire actuel:${NC}"
pwd
echo ""

# Vérifier si git est disponible
if command -v git &> /dev/null; then
    echo -e "${BLUE}📥 Récupération des dernières modifications...${NC}"

    # Sauvegarder les changements locaux si besoin
    if ! git diff-index --quiet HEAD --; then
        echo -e "${YELLOW}⚠️  Changements locaux détectés, sauvegarde...${NC}"
        git stash
    fi

    # Pull depuis GitHub
    git pull origin claude/rss-feed-manager-011CUPiUnRYnQYZMxMWMsZrS

    echo -e "${GREEN}✅ Code mis à jour !${NC}"
    echo ""
else
    echo -e "${YELLOW}⚠️  Git non disponible, mise à jour manuelle nécessaire${NC}"
    exit 1
fi

# Activer le virtualenv
echo -e "${BLUE}🐍 Activation de l'environnement virtuel...${NC}"
if [ -d "/home/wrbh3411/virtualenv/dusselle.fr/3.11" ]; then
    source /home/wrbh3411/virtualenv/dusselle.fr/3.11/bin/activate
    echo -e "${GREEN}✅ Virtualenv activé (Python 3.11)${NC}"
elif [ -d "/home/wrbh3411/virtualenv/dusselle.fr/3.10" ]; then
    source /home/wrbh3411/virtualenv/dusselle.fr/3.10/bin/activate
    echo -e "${GREEN}✅ Virtualenv activé (Python 3.10)${NC}"
else
    echo -e "${YELLOW}⚠️  Virtualenv non trouvé${NC}"
fi
echo ""

# Installer/Mettre à jour les dépendances
if [ -f "requirements.txt" ]; then
    echo -e "${BLUE}📦 Vérification des dépendances...${NC}"
    pip install -q -r requirements.txt --upgrade
    echo -e "${GREEN}✅ Dépendances à jour${NC}"
    echo ""
fi

# Redémarrer l'application
echo -e "${BLUE}🔄 Redémarrage de l'application...${NC}"
mkdir -p tmp
touch tmp/restart.txt
echo -e "${GREEN}✅ Application redémarrée${NC}"
echo ""

# Résumé
echo "=============================================="
echo -e "${GREEN}🎉 Mise à jour terminée avec succès !${NC}"
echo ""
echo "📊 Testez le dashboard : https://dusselle.fr/dashboard"
echo "🏠 Page d'accueil : https://dusselle.fr"
echo ""
echo "Derniers commits :"
git log --oneline -3
echo ""

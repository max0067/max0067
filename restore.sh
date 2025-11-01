#!/bin/bash

# Script de restauration de l'application RSS Manager
# Auteur: Claude
# Date: 2025-11-01

# Couleurs pour les messages
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}   🔄 RESTAURATION - RSS Manager${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Vérifier qu'un argument a été fourni
if [ $# -eq 0 ]; then
    echo -e "${RED}❌ Erreur: Vous devez spécifier le fichier de sauvegarde${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo -e "  ./restore.sh backup_YYYYMMDD_HHMMSS.tar.gz"
    echo ""
    echo -e "${YELLOW}Sauvegardes disponibles:${NC}"
    ls -lht backup_*.tar.gz 2>/dev/null | head -5
    exit 1
fi

BACKUP_FILE=$1

# Vérifier que le fichier existe
if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}❌ Erreur: Le fichier $BACKUP_FILE n'existe pas${NC}"
    echo ""
    echo -e "${YELLOW}Sauvegardes disponibles:${NC}"
    ls -lht backup_*.tar.gz 2>/dev/null | head -5
    exit 1
fi

echo -e "${GREEN}✓${NC} Fichier de sauvegarde trouvé: ${BLUE}$BACKUP_FILE${NC}"
echo ""

# Avertissement
echo -e "${YELLOW}⚠️  ATTENTION ⚠️${NC}"
echo -e "${YELLOW}Cette opération va remplacer les fichiers actuels de l'application.${NC}"
echo -e "${YELLOW}Les fichiers actuels seront sauvegardés dans ~/www_backup_before_restore${NC}"
echo ""
read -p "Voulez-vous continuer ? (oui/non) : " confirm

if [ "$confirm" != "oui" ]; then
    echo -e "${RED}❌ Restauration annulée${NC}"
    exit 0
fi

echo ""
echo -e "${BLUE}🔄 Début de la restauration...${NC}"
echo ""

# Extraire le nom du dossier depuis le nom du fichier
BACKUP_DIR=$(basename "$BACKUP_FILE" .tar.gz)

# Décompresser l'archive
echo -e "${BLUE}📦 Décompression de l'archive...${NC}"
tar -xzf "$BACKUP_FILE"

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Erreur lors de la décompression${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} Archive décompressée"
echo ""

# Sauvegarder l'état actuel (si ~/www existe)
if [ -d ~/www ]; then
    echo -e "${BLUE}💾 Sauvegarde de l'état actuel...${NC}"
    SAFETY_BACKUP="$HOME/www_backup_before_restore_$(date +%Y%m%d_%H%M%S)"
    cp -r ~/www "$SAFETY_BACKUP"
    echo -e "${GREEN}✓${NC} État actuel sauvegardé dans: ${BLUE}$SAFETY_BACKUP${NC}"
    echo ""
fi

# Afficher les informations de la sauvegarde
if [ -f "$BACKUP_DIR/BACKUP_INFO.txt" ]; then
    echo -e "${BLUE}ℹ️  Informations sur la sauvegarde:${NC}"
    echo ""
    head -20 "$BACKUP_DIR/BACKUP_INFO.txt"
    echo ""
    read -p "Appuyez sur ENTRÉE pour continuer..."
    echo ""
fi

# Restaurer les fichiers
echo -e "${BLUE}📁 Restauration des fichiers...${NC}"

# Créer le dossier www s'il n'existe pas
mkdir -p ~/www
mkdir -p ~/www/templates
mkdir -p ~/www/tmp

# Copier les fichiers
cp -v "$BACKUP_DIR"/*.py ~/www/ 2>/dev/null
cp -v "$BACKUP_DIR"/*.sh ~/www/ 2>/dev/null
cp -v "$BACKUP_DIR"/*.txt ~/www/ 2>/dev/null
cp -v "$BACKUP_DIR"/.htaccess ~/www/ 2>/dev/null
cp -v "$BACKUP_DIR"/rss_manager.db ~/www/ 2>/dev/null

# Copier les templates
if [ -d "$BACKUP_DIR/templates" ]; then
    cp -v "$BACKUP_DIR/templates"/* ~/www/templates/ 2>/dev/null
fi

# Copier les fichiers static (si existent)
if [ -d "$BACKUP_DIR/static" ]; then
    mkdir -p ~/www/static
    cp -rv "$BACKUP_DIR/static"/* ~/www/static/ 2>/dev/null
fi

echo ""
echo -e "${GREEN}✓${NC} Fichiers restaurés"
echo ""

# Définir les permissions correctes
echo -e "${BLUE}🔒 Configuration des permissions...${NC}"
chmod 755 ~/www/*.py 2>/dev/null
chmod 755 ~/www/*.sh 2>/dev/null
chmod 644 ~/www/templates/*.html 2>/dev/null
chmod 644 ~/www/rss_manager.db 2>/dev/null
echo -e "${GREEN}✓${NC} Permissions configurées"
echo ""

# Redémarrer l'application
echo -e "${BLUE}🔄 Redémarrage de l'application...${NC}"
mkdir -p ~/www/tmp
touch ~/www/tmp/restart.txt
echo -e "${GREEN}✓${NC} Application redémarrée"
echo ""

# Nettoyage (optionnel)
echo -e "${BLUE}🗑️  Nettoyage...${NC}"
read -p "Supprimer le dossier temporaire $BACKUP_DIR ? (oui/non) : " cleanup

if [ "$cleanup" = "oui" ]; then
    rm -rf "$BACKUP_DIR"
    echo -e "${GREEN}✓${NC} Dossier temporaire supprimé"
else
    echo -e "${BLUE}ℹ${NC} Dossier conservé: $BACKUP_DIR"
fi

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✓ RESTAURATION TERMINÉE AVEC SUCCÈS !${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${GREEN}Prochaines étapes:${NC}"
echo -e "1. Testez votre application: ${BLUE}https://dusselle.fr${NC}"
echo -e "2. Vérifiez que tout fonctionne correctement"
echo -e "3. Si tout est OK, vous pouvez supprimer la sauvegarde de sécurité:"
echo -e "   ${BLUE}rm -rf $SAFETY_BACKUP${NC}"
echo ""
echo -e "${YELLOW}En cas de problème:${NC}"
echo -e "La version actuelle a été sauvegardée dans:"
echo -e "${BLUE}$SAFETY_BACKUP${NC}"
echo ""

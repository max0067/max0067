#!/bin/bash

# Script de sauvegarde complète de l'application RSS Manager
# Auteur: Claude
# Date: 2025-11-01

# Couleurs pour les messages
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}   📦 SAUVEGARDE COMPLÈTE - RSS Manager${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Créer le dossier de sauvegarde avec la date et l'heure
BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo -e "${GREEN}✓${NC} Dossier de sauvegarde créé: ${BLUE}$BACKUP_DIR${NC}"
echo ""

# Sauvegarde de la base de données
echo -e "${BLUE}📊 Sauvegarde de la base de données...${NC}"
if [ -f "rss_manager.db" ]; then
    cp rss_manager.db "$BACKUP_DIR/rss_manager.db"
    echo -e "${GREEN}✓${NC} Base de données sauvegardée"
else
    echo -e "${RED}✗${NC} Base de données non trouvée"
fi
echo ""

# Sauvegarde des fichiers Python
echo -e "${BLUE}🐍 Sauvegarde des fichiers Python...${NC}"
for file in *.py; do
    if [ -f "$file" ]; then
        cp "$file" "$BACKUP_DIR/"
        echo -e "${GREEN}✓${NC} $file"
    fi
done
echo ""

# Sauvegarde des scripts shell
echo -e "${BLUE}📜 Sauvegarde des scripts shell...${NC}"
for file in *.sh; do
    if [ -f "$file" ]; then
        cp "$file" "$BACKUP_DIR/"
        echo -e "${GREEN}✓${NC} $file"
    fi
done
echo ""

# Sauvegarde du dossier templates
echo -e "${BLUE}📄 Sauvegarde des templates HTML...${NC}"
if [ -d "templates" ]; then
    mkdir -p "$BACKUP_DIR/templates"
    cp -r templates/* "$BACKUP_DIR/templates/"
    template_count=$(ls -1 templates/*.html 2>/dev/null | wc -l)
    echo -e "${GREEN}✓${NC} $template_count templates sauvegardés"
else
    echo -e "${RED}✗${NC} Dossier templates non trouvé"
fi
echo ""

# Sauvegarde du dossier static (si existe)
echo -e "${BLUE}🎨 Sauvegarde des fichiers static...${NC}"
if [ -d "static" ]; then
    mkdir -p "$BACKUP_DIR/static"
    cp -r static/* "$BACKUP_DIR/static/"
    echo -e "${GREEN}✓${NC} Fichiers static sauvegardés"
else
    echo -e "${BLUE}ℹ${NC} Pas de dossier static (normal si CSS inline)"
fi
echo ""

# Sauvegarde des fichiers de configuration
echo -e "${BLUE}⚙️  Sauvegarde des fichiers de configuration...${NC}"
config_files=("requirements.txt" "passenger_wsgi.py" ".htaccess" "README.md")
for file in "${config_files[@]}"; do
    if [ -f "$file" ]; then
        cp "$file" "$BACKUP_DIR/"
        echo -e "${GREEN}✓${NC} $file"
    fi
done
echo ""

# Création d'un fichier info sur la sauvegarde
echo -e "${BLUE}📝 Création du fichier d'information...${NC}"
cat > "$BACKUP_DIR/BACKUP_INFO.txt" << EOF
═══════════════════════════════════════════════════════════════
    SAUVEGARDE RSS MANAGER
═══════════════════════════════════════════════════════════════

Date de sauvegarde : $(date +"%Y-%m-%d %H:%M:%S")
Serveur           : $(hostname)
Utilisateur       : $(whoami)
Chemin            : $(pwd)

═══════════════════════════════════════════════════════════════
    CONTENU DE LA SAUVEGARDE
═══════════════════════════════════════════════════════════════

📊 Base de données :
$(if [ -f "$BACKUP_DIR/rss_manager.db" ]; then ls -lh "$BACKUP_DIR/rss_manager.db"; else echo "Non trouvée"; fi)

🐍 Fichiers Python :
$(ls -1 "$BACKUP_DIR"/*.py 2>/dev/null || echo "Aucun")

📄 Templates HTML :
$(ls -1 "$BACKUP_DIR/templates"/*.html 2>/dev/null | wc -l) fichiers

⚙️  Configuration :
$(ls -1 "$BACKUP_DIR"/*.txt "$BACKUP_DIR"/*.py "$BACKUP_DIR"/.htaccess 2>/dev/null | grep -E "(requirements|passenger|htaccess)" || echo "Fichiers de base")

═══════════════════════════════════════════════════════════════
    STATISTIQUES DE LA BASE DE DONNÉES
═══════════════════════════════════════════════════════════════

$(if [ -f "$BACKUP_DIR/rss_manager.db" ]; then
    sqlite3 "$BACKUP_DIR/rss_manager.db" << SQL
.mode column
SELECT 'Utilisateurs' as Table, COUNT(*) as Nombre FROM users
UNION ALL
SELECT 'Flux RSS', COUNT(*) FROM feeds
UNION ALL
SELECT 'Articles', COUNT(*) FROM articles
UNION ALL
SELECT 'Dossiers', COUNT(*) FROM folders
UNION ALL
SELECT 'Sessions', COUNT(*) FROM sessions;
SQL
else
    echo "Base de données non disponible"
fi)

═══════════════════════════════════════════════════════════════
    INSTRUCTIONS DE RESTAURATION
═══════════════════════════════════════════════════════════════

1. Arrêter l'application :
   touch ~/www/tmp/restart.txt

2. Sauvegarder l'état actuel (au cas où) :
   mv ~/www ~/www_old_$(date +%Y%m%d)

3. Restaurer les fichiers :
   cd $BACKUP_DIR
   cp -r * ~/www/

4. Vérifier les permissions :
   chmod 755 ~/www/*.py
   chmod 755 ~/www/*.sh
   chmod 644 ~/www/templates/*.html

5. Redémarrer l'application :
   touch ~/www/tmp/restart.txt

6. Tester le site :
   curl https://dusselle.fr

═══════════════════════════════════════════════════════════════

EOF

echo -e "${GREEN}✓${NC} Fichier d'information créé"
echo ""

# Création d'une archive compressée
echo -e "${BLUE}📦 Compression de la sauvegarde...${NC}"
tar -czf "${BACKUP_DIR}.tar.gz" "$BACKUP_DIR" 2>/dev/null

if [ $? -eq 0 ]; then
    BACKUP_SIZE=$(du -h "${BACKUP_DIR}.tar.gz" | cut -f1)
    echo -e "${GREEN}✓${NC} Archive créée: ${BLUE}${BACKUP_DIR}.tar.gz${NC} (${BACKUP_SIZE})"
    echo ""

    # Proposition de supprimer le dossier non compressé
    echo -e "${BLUE}🗑️  Nettoyage...${NC}"
    rm -rf "$BACKUP_DIR"
    echo -e "${GREEN}✓${NC} Dossier temporaire supprimé"
else
    echo -e "${RED}✗${NC} Erreur lors de la compression"
    BACKUP_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
fi

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✓ SAUVEGARDE TERMINÉE AVEC SUCCÈS !${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "📦 Archive : ${BLUE}${BACKUP_DIR}.tar.gz${NC}"
echo -e "📊 Taille  : ${BLUE}${BACKUP_SIZE}${NC}"
echo ""
echo -e "${GREEN}Pour restaurer cette sauvegarde :${NC}"
echo -e "1. tar -xzf ${BACKUP_DIR}.tar.gz"
echo -e "2. cd ${BACKUP_DIR}"
echo -e "3. Lire le fichier BACKUP_INFO.txt pour les instructions"
echo ""
echo -e "${BLUE}Pensez à télécharger cette archive sur votre ordinateur !${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

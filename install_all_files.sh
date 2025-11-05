#!/bin/bash
#
# Script de déploiement complet RSS Legal Watch
# Télécharge tous les fichiers depuis GitHub
#

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "  INSTALLATION COMPLÈTE - RSS Legal Watch"
echo "════════════════════════════════════════════════════════════════"
echo ""

BASE_URL="https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ"

# Créer les dossiers
echo "[1/3] Création de la structure..."
mkdir -p includes views/layout assets/css assets/js install

# Télécharger les fichiers
echo ""
echo "[2/3] Téléchargement des fichiers..."

# Fichiers includes/
wget -q -O includes/Database.php "$BASE_URL/includes/Database.php" && echo "✓ includes/Database.php"
wget -q -O includes/RSSFeed.php "$BASE_URL/includes/RSSFeed.php" && echo "✓ includes/RSSFeed.php"
wget -q -O includes/Article.php "$BASE_URL/includes/Article.php" && echo "✓ includes/Article.php"
wget -q -O includes/helpers.php "$BASE_URL/includes/helpers.php" && echo "✓ includes/helpers.php"

# Fichiers views/
wget -q -O views/layout/header.php "$BASE_URL/views/layout/header.php" && echo "✓ views/layout/header.php"
wget -q -O views/layout/footer.php "$BASE_URL/views/layout/footer.php" && echo "✓ views/layout/footer.php"

# Fichiers assets/
wget -q -O assets/css/style.css "$BASE_URL/assets/css/style.css" && echo "✓ assets/css/style.css"
wget -q -O assets/js/app.js "$BASE_URL/assets/js/app.js" && echo "✓ assets/js/app.js"

# Fichier install/
wget -q -O install/setup.php "$BASE_URL/install/setup.php" && echo "✓ install/setup.php"

# Fichiers racine
wget -q -O index_new.php "$BASE_URL/index_new.php" && echo "✓ index_new.php"
wget -q -O feeds_new.php "$BASE_URL/feeds_new.php" && echo "✓ feeds_new.php"
wget -q -O refresh_new.php "$BASE_URL/refresh_new.php" && echo "✓ refresh_new.php"
wget -q -O .htaccess_new "$BASE_URL/.htaccess_new" && echo "✓ .htaccess_new"

# Copier vers les noms finaux
echo ""
echo "[3/3] Activation des fichiers..."

[ -f index_new.php ] && cp index_new.php index.php && echo "✓ index.php"
[ -f feeds_new.php ] && cp feeds_new.php feeds.php && echo "✓ feeds.php"
[ -f refresh_new.php ] && cp refresh_new.php refresh.php && echo "✓ refresh.php"
[ -f .htaccess_new ] && cp .htaccess_new .htaccess && echo "✓ .htaccess"

# Permissions
chmod 755 includes views assets install
chmod 644 *.php includes/*.php views/layout/*.php install/*.php
chmod 644 assets/css/*.css assets/js/*.js
chmod 644 .htaccess

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "✅ INSTALLATION TERMINÉE"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "Si config.php n'existe pas encore:"
echo "  php test_mysql_connection.php"
echo ""
echo "Puis installer la base de données:"
echo "  https://dusselle.fr/install/setup.php"
echo ""
echo "Enfin accéder à l'application:"
echo "  https://dusselle.fr/"
echo ""

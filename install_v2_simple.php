<?php
/**
 * Installateur automatique RSS Reader V2
 * Copier ce fichier dans ~/public_html/ et exécuter: php install_v2_simple.php
 */

echo "========================================\n";
echo "Installation RSS Reader V2\n";
echo "========================================\n\n";

// Créer la structure
echo "[1/10] Création de la structure...\n";
@mkdir('v2', 0755);
@mkdir('v2/views', 0755);
@mkdir('v2/assets', 0755);
@mkdir('v2/data', 0755);
@mkdir('v2/logs', 0755);
@mkdir('v2/cache', 0755);
echo "✓ Structure créée\n";

// Télécharger les fichiers depuis raw.githubusercontent.com
echo "[2/10] Téléchargement de config.php...\n";
$config = file_get_contents('https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/v2_modern/config.php');
file_put_contents('v2/config.php', $config);
echo "✓ config.php téléchargé\n";

echo "[3/10] Téléchargement de Database.php...\n";
$database = file_get_contents('https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/v2_modern/Database.php');
file_put_contents('v2/Database.php', $database);
echo "✓ Database.php téléchargé\n";

echo "[4/10] Téléchargement de Session.php...\n";
$session = file_get_contents('https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/v2_modern/Session.php');
file_put_contents('v2/Session.php', $session);
echo "✓ Session.php téléchargé\n";

echo "[5/10] Téléchargement de index.php...\n";
$index = file_get_contents('https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/v2_modern/index.php');
file_put_contents('v2/index.php', $index);
echo "✓ index.php téléchargé\n";

echo "[6/10] Téléchargement de api.php...\n";
$api = file_get_contents('https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/v2_modern/api.php');
file_put_contents('v2/api.php', $api);
echo "✓ api.php téléchargé\n";

echo "[7/10] Téléchargement de .htaccess...\n";
$htaccess = file_get_contents('https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/v2_modern/.htaccess');
file_put_contents('v2/.htaccess', $htaccess);
echo "✓ .htaccess téléchargé\n";

echo "[8/10] Téléchargement des vues...\n";
$login = file_get_contents('https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/v2_modern/views/login.php');
file_put_contents('v2/views/login.php', $login);
$dashboard = file_get_contents('https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/v2_modern/views/dashboard.php');
file_put_contents('v2/views/dashboard.php', $dashboard);
echo "✓ Vues téléchargées\n";

echo "[9/10] Téléchargement des assets...\n";
$css = file_get_contents('https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/v2_modern/assets/style.css');
file_put_contents('v2/assets/style.css', $css);
$js = file_get_contents('https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/v2_modern/assets/app.js');
file_put_contents('v2/assets/app.js', $js);
echo "✓ Assets téléchargés\n";

echo "[10/10] Configuration des permissions...\n";
chmod('v2', 0755);
chmod('v2/data', 0755);
chmod('v2/logs', 0755);
chmod('v2/cache', 0755);
echo "✓ Permissions configurées\n";

echo "\n========================================\n";
echo "✓ Installation terminée !\n";
echo "========================================\n\n";
echo "Accès: http://dusselle.fr/v2/\n";
echo "Login: admin / admin123\n\n";
echo "Test health check:\n";
echo "curl http://dusselle.fr/v2/?health\n\n";

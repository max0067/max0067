<?php
/**
 * Test de l'API pour comprendre pourquoi les articles ne s'affichent pas
 */

echo "\n=== TEST DE L'API ===\n\n";

// Test 1: Vérifier que les articles existent dans la DB
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

$db = Database::getInstance();
$pdo = $db->getPdo();

echo "[1] Articles dans la base de données:\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM articles");
$count = $stmt->fetchColumn();
echo "Total: {$count} articles\n\n";

if ($count > 0) {
    echo "Derniers articles:\n";
    $stmt = $pdo->query("SELECT id, title, feed_id FROM articles ORDER BY published_date DESC LIMIT 5");
    $articles = $stmt->fetchAll();
    foreach ($articles as $article) {
        echo "  [{$article['id']}] Feed#{$article['feed_id']}: {$article['title']}\n";
    }
}

echo "\n";

// Test 2: Simuler un appel API GET /api/articles
echo "[2] Test de l'appel API GET /api/articles:\n";

// Check if index.php exists
if (!file_exists(__DIR__ . '/index.php')) {
    echo "✗ index.php n'existe pas!\n";
    echo "Les fichiers V2 ne sont pas installés.\n";
    echo "Exécutez: php install_v2_part2.php\n\n";
    exit(1);
}

// Test API via HTTP
$apiUrl = 'http://localhost/api/articles';
echo "URL: {$apiUrl}\n";

// Try with cURL
if (function_exists('curl_init')) {
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Code: {$httpCode}\n";

    if ($response) {
        echo "Réponse (premiers 500 caractères):\n";
        echo substr($response, 0, 500) . "\n";

        $data = json_decode($response, true);
        if ($data) {
            echo "\nRéponse JSON décodée:\n";
            echo "success: " . ($data['success'] ? 'true' : 'false') . "\n";
            echo "Articles: " . (isset($data['data']) ? count($data['data']) : 'N/A') . "\n";
        } else {
            echo "✗ Impossible de décoder le JSON\n";
        }
    } else {
        echo "✗ Pas de réponse\n";
    }
} else {
    echo "cURL non disponible\n";
}

echo "\n";

// Test 3: Vérifier les fichiers views et assets
echo "[3] Vérification des fichiers frontend:\n";

$files = [
    'views/login.php',
    'views/dashboard.php',
    'assets/style.css',
    'assets/app.js'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "✓ {$file} (" . number_format($size) . " bytes)\n";
    } else {
        echo "✗ {$file} MANQUANT\n";
    }
}

echo "\n";

// Test 4: Vérifier l'authentification
echo "[4] Test d'authentification:\n";

require_once __DIR__ . '/Session.php';

session_start();

// Check if user is logged in
if (isset($_SESSION['token'])) {
    echo "✓ Session token existe: {$_SESSION['token']}\n";

    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE token = ?");
    $stmt->execute([$_SESSION['token']]);
    $session = $stmt->fetch();

    if ($session) {
        echo "✓ Session valide (User ID: {$session['user_id']})\n";

        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$session['user_id']]);
        $user = $stmt->fetch();

        if ($user) {
            echo "✓ Utilisateur: {$user['username']}\n";
        }
    } else {
        echo "✗ Session token invalide ou expiré\n";
    }
} else {
    echo "⚠ Pas de session active\n";
    echo "Vous devez vous connecter sur http://dusselle.fr/\n";
}

echo "\n";

// Test 5: Créer une page de test simple
echo "[5] Création d'une page de test simple...\n";

$testPageContent = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Articles</title>
</head>
<body>
    <h1>Test d'affichage des articles</h1>
    <div id="articles"></div>

    <script>
    fetch('/api/articles')
        .then(response => response.json())
        .then(data => {
            console.log('API Response:', data);

            if (data.success && data.data) {
                document.getElementById('articles').innerHTML =
                    '<p>Articles trouvés: ' + data.data.length + '</p>' +
                    '<ul>' +
                    data.data.map(article =>
                        '<li>' + article.title + '</li>'
                    ).join('') +
                    '</ul>';
            } else {
                document.getElementById('articles').innerHTML =
                    '<p style="color: red;">Erreur: ' + (data.message || 'Pas d\'articles') + '</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('articles').innerHTML =
                '<p style="color: red;">Erreur de connexion: ' + error + '</p>';
        });
    </script>
</body>
</html>
HTML;

file_put_contents(__DIR__ . '/test_articles.html', $testPageContent);
echo "✓ Page de test créée: test_articles.html\n";
echo "  Accédez à: http://dusselle.fr/test_articles.html\n";

echo "\n";

// Summary
echo "=== RÉSUMÉ ===\n";
echo "Articles dans la DB: {$count}\n";
echo "\n";
echo "Actions recommandées:\n";
echo "1. Ouvrez votre navigateur sur http://dusselle.fr/test_articles.html\n";
echo "2. Ouvrez la Console JavaScript (F12 > Console)\n";
echo "3. Regardez ce qui s'affiche\n";
echo "4. Envoyez-moi ce que vous voyez\n";
echo "\n";

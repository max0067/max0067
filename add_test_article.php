<?php
/**
 * Ajoute un article de test directement dans la base de données
 * Utile pour tester l'affichage sans dépendre des flux RSS
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

autoInit();

echo "\n=== AJOUT D'ARTICLE DE TEST ===\n\n";

$db = Database::getInstance();
$pdo = $db->getPdo();

// Créer un flux de test s'il n'existe pas
$stmt = $pdo->prepare("SELECT id FROM feeds WHERE url = ?");
$stmt->execute(['http://test.local/feed.xml']);
$feed = $stmt->fetch();

if (!$feed) {
    echo "Création du flux de test...\n";
    $stmt = $pdo->prepare("INSERT INTO feeds (url, title, active) VALUES (?, ?, 1)");
    $stmt->execute(['http://test.local/feed.xml', 'Flux de Test']);
    $feedId = $pdo->lastInsertId();
    echo "✓ Flux créé (ID: {$feedId})\n\n";
} else {
    $feedId = $feed['id'];
    echo "✓ Flux de test existe déjà (ID: {$feedId})\n\n";
}

// Ajouter 5 articles de test
echo "Ajout d'articles de test...\n";

$articles = [
    [
        'title' => 'Article de test #1 - Vérification de l\'affichage',
        'description' => 'Ceci est un article de test pour vérifier que l\'interface affiche correctement les articles.',
        'content' => '<p>Ceci est un article de test pour vérifier que l\'interface affiche correctement les articles.</p><p>Si vous voyez cet article dans votre lecteur RSS, cela signifie que la base de données et l\'API fonctionnent correctement.</p>',
    ],
    [
        'title' => 'Article de test #2 - Deuxième test',
        'description' => 'Un deuxième article pour avoir plusieurs éléments dans la liste.',
        'content' => '<p>Un deuxième article pour avoir plusieurs éléments dans la liste.</p>',
    ],
    [
        'title' => 'Article de test #3 - Test avec contenu long',
        'description' => 'Cet article contient un texte plus long pour tester l\'affichage des articles avec beaucoup de contenu.',
        'content' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p><p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>',
    ],
    [
        'title' => 'Article de test #4 - Hier',
        'description' => 'Article daté d\'hier',
        'content' => '<p>Article daté d\'hier pour tester l\'ordre chronologique.</p>',
    ],
    [
        'title' => 'Article de test #5 - Il y a 2 jours',
        'description' => 'Article plus ancien',
        'content' => '<p>Article plus ancien pour tester le tri par date.</p>',
    ]
];

$dates = [
    date('Y-m-d H:i:s'),
    date('Y-m-d H:i:s', strtotime('-2 hours')),
    date('Y-m-d H:i:s', strtotime('-5 hours')),
    date('Y-m-d H:i:s', strtotime('-1 day')),
    date('Y-m-d H:i:s', strtotime('-2 days')),
];

$addedCount = 0;
foreach ($articles as $index => $article) {
    $link = "http://test.local/article-{$index}.html";

    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM articles WHERE feed_id = ? AND link = ?");
    $stmt->execute([$feedId, $link]);

    if ($stmt->fetch()) {
        echo "  ⊙ Article #{$index} existe déjà\n";
        continue;
    }

    // Insert
    $stmt = $pdo->prepare("
        INSERT INTO articles (feed_id, title, link, description, content, published_date, is_read)
        VALUES (?, ?, ?, ?, ?, ?, 0)
    ");

    $stmt->execute([
        $feedId,
        $article['title'],
        $link,
        $article['description'],
        $article['content'],
        $dates[$index]
    ]);

    echo "  ✓ Article #{$index} ajouté\n";
    $addedCount++;
}

echo "\n";
echo "Articles ajoutés: {$addedCount}\n";

// Show stats
$stmt = $pdo->query("SELECT COUNT(*) FROM articles");
$totalArticles = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE is_read = 0");
$unreadArticles = $stmt->fetchColumn();

echo "Total dans la base: {$totalArticles}\n";
echo "Non lus: {$unreadArticles}\n";

echo "\n";
echo "✓ Maintenant, allez sur http://dusselle.fr/\n";
echo "  Login: admin\n";
echo "  Password: admin123\n";
echo "\n";
echo "Vous devriez voir {$addedCount} articles de test.\n";
echo "\n";

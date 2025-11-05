<?php
/**
 * Script d'installation de la base de données
 * À exécuter une seule fois après la configuration
 */

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/Database.php';

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Installation - RSS Legal Watch</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='row justify-content-center'>
            <div class='col-md-8'>
                <div class='card shadow'>
                    <div class='card-header bg-primary text-white'>
                        <h3 class='mb-0'>📦 Installation de RSS Legal Watch</h3>
                    </div>
                    <div class='card-body'>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "<div class='alert alert-success'>✓ Connexion à la base de données réussie</div>";

    // Création de la table rss_feeds
    echo "<h5>Création de la table 'rss_feeds'...</h5>";

    $sqlFeeds = "CREATE TABLE IF NOT EXISTS rss_feeds (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        url VARCHAR(500) NOT NULL UNIQUE,
        category VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sqlFeeds);
    echo "<div class='alert alert-info'>✓ Table 'rss_feeds' créée</div>";

    // Création de la table articles
    echo "<h5>Création de la table 'articles'...</h5>";

    $sqlArticles = "CREATE TABLE IF NOT EXISTS articles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        feed_id INT NOT NULL,
        title VARCHAR(500) NOT NULL,
        link VARCHAR(1000) NOT NULL,
        pub_date DATETIME NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (feed_id) REFERENCES rss_feeds(id) ON DELETE CASCADE,
        INDEX idx_pub_date (pub_date),
        INDEX idx_feed_id (feed_id),
        UNIQUE KEY unique_article (feed_id, link)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sqlArticles);
    echo "<div class='alert alert-info'>✓ Table 'articles' créée</div>";

    // Ajout de quelques flux RSS de démo (juridiques français)
    echo "<h5>Ajout de flux RSS de démonstration...</h5>";

    $demoFeeds = [
        [
            'name' => 'Légifrance - Actualités',
            'url' => 'https://www.legifrance.gouv.fr/rss/actualite',
            'category' => 'Législation'
        ],
        [
            'name' => 'Conseil d\'État - Décisions',
            'url' => 'https://www.conseil-etat.fr/ressources/decisions-contentieuses/rss',
            'category' => 'Jurisprudence'
        ],
        [
            'name' => 'Dalloz Actualité',
            'url' => 'https://www.dalloz-actualite.fr/rss.xml',
            'category' => 'Actualité juridique'
        ]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO rss_feeds (name, url, category) VALUES (?, ?, ?)");

    foreach ($demoFeeds as $feed) {
        try {
            $stmt->execute([$feed['name'], $feed['url'], $feed['category']]);
            echo "<div class='text-success'>→ {$feed['name']}</div>";
        } catch (PDOException $e) {
            echo "<div class='text-warning'>⊙ {$feed['name']} (déjà existant ou erreur)</div>";
        }
    }

    echo "
                    <div class='alert alert-success mt-4'>
                        <h4>✅ Installation terminée avec succès !</h4>
                        <p>La base de données a été configurée correctement.</p>
                        <hr>
                        <p class='mb-0'>
                            <a href='../index.php' class='btn btn-primary'>Accéder à l'application</a>
                            <a href='../feeds.php' class='btn btn-secondary'>Gérer les flux</a>
                        </p>
                    </div>

                    <div class='alert alert-warning mt-3'>
                        <strong>Important :</strong> Pour des raisons de sécurité, supprimez ou renommez le dossier
                        <code>/install/</code> après l'installation.
                    </div>";

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Erreur lors de l'installation</h4>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<hr>";
    echo "<p>Vérifiez vos paramètres de connexion dans <code>config.php</code></p>";
    echo "</div>";
}

echo "
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>";

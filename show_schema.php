<?php
/**
 * Affiche le schéma complet de la base de données
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

echo "\n=== SCHÉMA DE LA BASE DE DONNÉES ===\n\n";

$db = Database::getInstance();
$pdo = $db->getPdo();

$tables = ['users', 'sessions', 'feeds', 'articles', 'user_articles', 'meta'];

foreach ($tables as $table) {
    echo "TABLE: {$table}\n";
    echo str_repeat("-", 60) . "\n";

    $stmt = $pdo->query("PRAGMA table_info({$table})");
    $columns = $stmt->fetchAll();

    if (empty($columns)) {
        echo "  Table n'existe pas\n\n";
        continue;
    }

    printf("%-4s %-20s %-12s %-8s %-8s %s\n", "CID", "NAME", "TYPE", "NOTNULL", "DEFAULT", "PK");
    echo str_repeat("-", 60) . "\n";

    foreach ($columns as $col) {
        printf(
            "%-4s %-20s %-12s %-8s %-8s %s\n",
            $col['cid'],
            $col['name'],
            $col['type'],
            $col['notnull'] ? 'NOT NULL' : '',
            $col['dflt_value'] ?? 'NULL',
            $col['pk'] ? 'PK' : ''
        );
    }

    echo "\n";
}

// Show data counts
echo "=== DONNÉES ===\n\n";

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
        $count = $stmt->fetchColumn();
        echo "{$table}: {$count} lignes\n";
    } catch (Exception $e) {
        echo "{$table}: erreur\n";
    }
}

// Show user details
echo "\n=== UTILISATEURS ===\n\n";
try {
    $stmt = $pdo->query("SELECT id, username, active FROM users");
    $users = $stmt->fetchAll();

    if (empty($users)) {
        echo "Aucun utilisateur\n";
    } else {
        foreach ($users as $user) {
            echo "ID: {$user['id']}, Username: {$user['username']}, Active: {$user['active']}\n";
        }
    }
} catch (Exception $e) {
    echo "Erreur: {$e->getMessage()}\n";
}

echo "\n";

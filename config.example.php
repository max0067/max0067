<?php
/**
 * Configuration de l'application RSS Legal Watch
 * Copiez ce fichier en config.php et modifiez les valeurs
 */

// Configuration de la base de données MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'wrbh3411_rss_legal');  // À modifier selon votre base
define('DB_USER', 'wrbh3411');             // À modifier selon votre utilisateur
define('DB_PASS', 'votre_mot_de_passe');   // À modifier

// Configuration de l'application
define('APP_NAME', 'RSS Legal Watch');
define('APP_URL', 'https://dusselle.fr');
define('TIMEZONE', 'Europe/Paris');

// Paramètres de pagination
define('ARTICLES_PER_PAGE', 20);

// Paramètres de rafraîchissement
define('REFRESH_INTERVAL', 21600); // 6 heures en secondes

// Fuseau horaire
date_default_timezone_set(TIMEZONE);

// Gestion des erreurs (à désactiver en production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

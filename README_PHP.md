# RSS Manager - PHP Version

Version professionnelle du gestionnaire RSS en PHP pur (sans dépendances Python/Passenger).

## Architecture

L'application est construite en PHP natif avec:
- **Base de données**: SQLite (compatible avec la version Python)
- **Authentification**: Sessions PHP sécurisées
- **Parser RSS**: Parser XML natif PHP (cURL + DOMDocument)
- **Interface**: Design moderne et responsive
- **API**: Endpoints REST pour les opérations AJAX

## Structure des fichiers

```
/home/wrbh3411/dusselle.fr/
├── config.php                  # Configuration principale
├── init.php                    # Script d'initialisation
├── login.php                   # Page de connexion
├── index.php                   # Lecteur d'articles
├── feeds.php                   # Gestion des flux
├── admin.php                   # Panel admin
├── includes/
│   ├── database.php            # Couche base de données
│   ├── auth.php                # Authentification
│   ├── functions.php           # Fonctions helpers
│   └── rss_parser.php          # Parser RSS
├── api/
│   ├── auth.php                # API authentification
│   ├── articles.php            # API articles
│   └── feeds.php               # API flux RSS
├── cron/
│   └── update_feeds.php        # Mise à jour automatique
├── logs/                       # Logs applicatifs
├── static/                     # Fichiers statiques (CSS existants)
├── rss_feeds.db                # Base SQLite (réutilisée)
└── .htaccess_php               # Configuration Apache

```

## Prérequis

### Extensions PHP requises:
- `pdo` et `pdo_sqlite`
- `curl`
- `dom` et `libxml`
- `json`

### Vérification sur le serveur:
```bash
php -m | grep -E '(pdo|sqlite|curl|dom|xml|json)'
```

## Installation

### 1. Sauvegarde de l'ancienne version

```bash
cd ~/dusselle.fr

# Sauvegarder l'ancienne configuration
cp .htaccess .htaccess_python_backup
cp passenger_wsgi.py passenger_wsgi.py.backup

# Sauvegarder la base de données (elle sera réutilisée)
cp rss_feeds.db rss_feeds.db.backup
```

### 2. Déploiement de la version PHP

```bash
# Remplacer .htaccess par la version PHP
cp .htaccess_php .htaccess

# Définir les permissions
chmod 644 .htaccess
chmod 644 *.php
chmod 755 includes/ api/ cron/
chmod 755 logs/ && chmod 666 logs/*  # Permettre écriture logs
chmod 666 rss_feeds.db               # Permettre écriture base de données
```

### 3. Initialisation

```bash
# Exécuter le script d'initialisation
php init.php
```

Le script va:
- Vérifier la version PHP et les extensions
- Créer les répertoires nécessaires
- Initialiser la base de données
- Créer l'utilisateur admin par défaut
- Afficher les informations de configuration

### 4. Test de l'application

Accéder à: https://dusselle.fr/login.php

**Identifiants par défaut:**
- Username: `admin`
- Password: `admin123`

⚠️ **IMPORTANT**: Changer le mot de passe admin après la première connexion!

### 5. Configuration du Cron Job

Pour les mises à jour automatiques des flux RSS:

```bash
# Éditer crontab
crontab -e

# Ajouter cette ligne (mise à jour toutes les 30 minutes)
*/30 * * * * /usr/bin/php ~/dusselle.fr/cron/update_feeds.php >> ~/dusselle.fr/logs/cron.log 2>&1
```

Ou pour une mise à jour toutes les 15 minutes:
```bash
*/15 * * * * /usr/bin/php ~/dusselle.fr/cron/update_feeds.php >> ~/dusselle.fr/logs/cron.log 2>&1
```

## Fonctionnalités

### Pour les utilisateurs:
- ✅ Lecture d'articles avec interface moderne
- ✅ Filtres (tous/non lus/favoris)
- ✅ Recherche dans les articles
- ✅ Marquage lu/non lu
- ✅ Gestion des favoris
- ✅ Navigation clavier (←/→ pour articles suivants/précédents, Esc pour fermer)
- ✅ Gestion des flux RSS (ajout/modification/suppression)
- ✅ Mise à jour manuelle des flux

### Pour les admins:
- ✅ Panel d'administration
- ✅ Statistiques globales
- ✅ Gestion des utilisateurs
- ✅ Vue système

## Configuration

### Fichier `config.php`

```php
// Base de données
define('DB_PATH', __DIR__ . '/rss_feeds.db');

// Sessions
define('SESSION_LIFETIME', 7 * 24 * 60 * 60); // 7 jours

// RSS
define('RSS_UPDATE_INTERVAL', 30); // minutes
define('RSS_FETCH_TIMEOUT', 30); // secondes
```

## API Endpoints

### Authentification
- `POST /api/auth.php` - Login/Logout
- `GET /api/auth.php?action=logout` - Déconnexion

### Articles
- `POST /api/articles.php` - Actions sur articles
  - `action=get` - Récupérer articles
  - `action=mark_read` - Marquer lu
  - `action=toggle_favorite` - Basculer favori

### Flux RSS
- `POST /api/feeds.php` - Gestion des flux
  - `action=add` - Ajouter flux
  - `action=update` - Modifier flux
  - `action=delete` - Supprimer flux
  - `action=update_single` - Mettre à jour un flux
  - `action=update_all` - Mettre à jour tous les flux

## Dépannage

### Erreur 500
1. Vérifier les logs: `tail -f logs/app.log`
2. Vérifier les erreurs PHP: `tail -f logs/php_error.log`
3. Vérifier les permissions des fichiers

### Base de données non accessible
```bash
chmod 666 rss_feeds.db
chmod 755 $(dirname rss_feeds.db)
```

### Extensions PHP manquantes
Contacter o2switch pour activer les extensions requises.

### Cron ne fonctionne pas
```bash
# Tester manuellement
php ~/dusselle.fr/cron/update_feeds.php

# Vérifier les logs
tail -f ~/dusselle.fr/logs/cron.log
```

## Migration depuis la version Python

### Avantages de la version PHP:
- ✅ Plus simple à déployer (pas de Passenger/WSGI)
- ✅ Meilleure compatibilité avec hébergement mutualisé
- ✅ Pas de problèmes de redémarrage
- ✅ Moins de dépendances
- ✅ Performance similaire
- ✅ Même base de données (SQLite)

### Compatibilité:
- ✅ Base de données 100% compatible
- ✅ Même schéma de tables
- ✅ Aucune perte de données
- ✅ Mêmes fonctionnalités

### Rollback vers Python:
Si nécessaire, simplement:
```bash
cp .htaccess_python_backup .htaccess
# Redémarrer l'application via cPanel
```

## Sécurité

### Recommandations:
1. ✅ Changer le mot de passe admin immédiatement
2. ✅ Utiliser HTTPS (déjà configuré sur dusselle.fr)
3. ✅ Sauvegarder régulièrement la base de données
4. ✅ Surveiller les logs
5. ✅ Restreindre l'accès aux fichiers sensibles (.htaccess)

### Fichiers protégés par .htaccess:
- `config.php`
- `*.db` (base de données)
- `*.log` (logs)
- Répertoire `includes/`
- Répertoire `cron/`

## Support

### Logs à consulter:
- Application: `logs/app.log`
- Cron: `logs/cron.log`
- Apache: via cPanel ou o2switch

### En cas de problème:
1. Vérifier les logs
2. Vérifier les permissions
3. Vérifier la configuration PHP
4. Tester manuellement les scripts

## Licence

Application développée pour dusselle.fr

## Changelog

### Version 2.0-PHP (2024)
- Migration complète de Python vers PHP
- Interface moderne et professionnelle
- Architecture API REST
- Meilleure compatibilité hébergement mutualisé

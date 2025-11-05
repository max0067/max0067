# 📰 RSS Legal Watch

Application web moderne de veille juridique basée sur des flux RSS. Interface type dashboard, responsive et professionnelle.

## 🚀 Fonctionnalités

- ✅ **Gestion complète des flux RSS** (CRUD: Créer, Lire, Modifier, Supprimer)
- ✅ **Actualisation automatique ou manuelle** des articles
- ✅ **Recherche par mot-clé** (titre, source, contenu)
- ✅ **Interface moderne type dashboard** (Bootstrap 5)
- ✅ **Design responsive** (mobile, tablette, desktop)
- ✅ **Statistiques en temps réel**
- ✅ **Catégorisation des flux**
- ✅ **Pagination des résultats**
- ✅ **Protection contre les injections SQL**
- ✅ **Compatible CRON** pour actualisation automatique

## 📋 Prérequis

- **PHP 8.0+** avec extensions:
  - `pdo_mysql`
  - `simplexml`
  - `mbstring`
- **MySQL 5.7+** ou **MariaDB 10.2+**
- **Apache** avec `mod_rewrite` activé
- Accès SSH au serveur (recommandé)

## 📥 Installation

### Étape 1 : Télécharger les fichiers

```bash
cd ~/public_html

# Télécharger le script de déploiement
wget https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/deploy_rss_legal.php

# Exécuter le déploiement
php deploy_rss_legal.php
```

### Étape 2 : Configuration de la base de données

1. **Créer une base de données MySQL** via cPanel ou en ligne de commande:

```sql
CREATE DATABASE wrbh3411_rss_legal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Copier et configurer le fichier de configuration**:

```bash
cp config.example.php config.php
nano config.php
```

3. **Modifier les paramètres de connexion** dans `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'wrbh3411_rss_legal');
define('DB_USER', 'wrbh3411');
define('DB_PASS', 'votre_mot_de_passe_mysql');
```

### Étape 3 : Installation de la base de données

Accédez à l'URL d'installation dans votre navigateur:

```
https://dusselle.fr/install/setup.php
```

Cette page va:
- ✅ Créer les tables `rss_feeds` et `articles`
- ✅ Ajouter quelques flux RSS juridiques de démonstration
- ✅ Vérifier la connexion à la base de données

**Important :** Supprimez ou renommez le dossier `/install/` après l'installation pour des raisons de sécurité.

### Étape 4 : Accéder à l'application

```
https://dusselle.fr/
```

🎉 **C'est terminé !** Votre application est opérationnelle.

## 📖 Utilisation

### Ajouter un flux RSS

1. Allez dans **Flux RSS** (menu ou `/feeds.php`)
2. Remplissez le formulaire:
   - **Nom du flux**: Ex: "Légifrance - Actualités"
   - **URL du flux RSS**: L'URL complète du flux
   - **Catégorie**: (Optionnel) Ex: "Législation", "Jurisprudence"
3. Cliquez sur **Ajouter le flux**

### Actualiser les flux

**Manuellement**:
- Cliquez sur le bouton **🔄 Actualiser** dans le menu
- Ou sur le bouton de chaque flux dans la liste

**Automatiquement** (via CRON):
```bash
# Éditer le crontab
crontab -e

# Ajouter cette ligne pour actualiser toutes les 6 heures
0 */6 * * * cd /home/wrbh3411/public_html && php refresh.php > /dev/null 2>&1
```

### Rechercher des articles

1. Utilisez la barre de recherche en haut de la page d'accueil
2. Entrez un mot-clé (ex: "décret", "loi", "arrêt")
3. Les résultats seront filtrés en temps réel

### Supprimer un flux

1. Allez dans **Flux RSS**
2. Cliquez sur l'icône **🗑️** à côté du flux
3. Confirmez la suppression

**Attention :** Tous les articles associés au flux seront également supprimés.

## 🏗️ Structure du projet

```
/public_html/
├── config.php                 # Configuration (DB, constantes)
├── index.php                  # Page d'accueil (liste des articles)
├── feeds.php                  # Gestion des flux RSS (CRUD)
├── refresh.php                # Script d'actualisation
├── .htaccess                  # Configuration Apache
├── README.md                  # Ce fichier
│
├── includes/
│   ├── Database.php           # Classe de connexion MySQL
│   ├── RSSFeed.php            # Classe gestion des flux
│   ├── Article.php            # Classe gestion des articles
│   └── helpers.php            # Fonctions utilitaires
│
├── views/
│   └── layout/
│       ├── header.php         # En-tête HTML
│       └── footer.php         # Pied de page HTML
│
├── assets/
│   ├── css/
│   │   └── style.css          # Styles personnalisés
│   └── js/
│       └── app.js             # JavaScript
│
└── install/
    └── setup.php              # Script d'installation DB
```

## 🎨 Design

- **Framework CSS**: Bootstrap 5
- **Police**: Inter (Google Fonts)
- **Couleurs**: Bleu professionnel (#2563eb), fond clair (#f8fafc)
- **Icônes**: Émojis Unicode (compatibilité universelle)

## 🔒 Sécurité

- ✅ Requêtes préparées (protection SQL injection)
- ✅ Validation des URL de flux RSS
- ✅ Échappement HTML (`htmlspecialchars`)
- ✅ Protection des fichiers sensibles via `.htaccess`
- ✅ Sessions PHP sécurisées

## 📊 Base de données

### Table `rss_feeds`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT | Clé primaire auto-incrémentée |
| `name` | VARCHAR(255) | Nom du flux |
| `url` | VARCHAR(500) | URL du flux RSS (unique) |
| `category` | VARCHAR(100) | Catégorie (optionnel) |
| `created_at` | TIMESTAMP | Date de création |

### Table `articles`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT | Clé primaire auto-incrémentée |
| `feed_id` | INT | Clé étrangère vers `rss_feeds` |
| `title` | VARCHAR(500) | Titre de l'article |
| `link` | VARCHAR(1000) | URL de l'article |
| `pub_date` | DATETIME | Date de publication |
| `description` | TEXT | Description/contenu |
| `created_at` | TIMESTAMP | Date d'ajout en base |

## 🌐 Exemples de flux RSS juridiques

- **Légifrance**: `https://www.legifrance.gouv.fr/rss/actualite`
- **Conseil d'État**: `https://www.conseil-etat.fr/ressources/decisions-contentieuses/rss`
- **Dalloz**: `https://www.dalloz-actualite.fr/rss.xml`
- **Cour de Cassation**: `https://www.courdecassation.fr/publications/flux-rss`
- **Journal Officiel**: `https://www.journal-officiel.gouv.fr/pages/rss.html`

## 🐛 Dépannage

### Erreur de connexion à la base de données

Vérifiez:
- Les identifiants dans `config.php`
- Que la base de données existe
- Que l'utilisateur MySQL a les droits nécessaires

### Les flux ne s'actualisent pas

Vérifiez:
- Que l'URL du flux RSS est valide et accessible
- Les logs d'erreur PHP: `/home/wrbh3411/public_html/error_log`
- Le timeout PHP (augmenter `max_execution_time` si nécessaire)

### Les articles ne s'affichent pas

1. Allez dans **Flux RSS**
2. Cliquez sur **🔄 Actualiser** pour chaque flux
3. Vérifiez que des articles ont été récupérés

### Problème de permissions

```bash
# Donner les bonnes permissions
chmod 755 /home/wrbh3411/public_html
chmod 644 /home/wrbh3411/public_html/*.php
chmod 644 /home/wrbh3411/public_html/config.php
chmod 644 /home/wrbh3411/public_html/.htaccess
```

## 📝 Maintenance

### Nettoyer les anciens articles

Pour supprimer les articles de plus de 90 jours:

```php
// Ajouter ce code dans un script maintenance.php
require_once 'config.php';
require_once 'includes/Database.php';
require_once 'includes/Article.php';

$article = new Article();
$article->deleteOld(90); // 90 jours
echo "Anciens articles supprimés";
```

### Sauvegarder la base de données

```bash
# Via ligne de commande
mysqldump -u wrbh3411 -p wrbh3411_rss_legal > backup_$(date +%Y%m%d).sql

# Ou via cPanel > phpMyAdmin > Exporter
```

## 🚀 Améliorations possibles

- [ ] Authentification utilisateur
- [ ] Favoris/Marque-pages d'articles
- [ ] Export PDF/EPUB
- [ ] Notifications par email
- [ ] API REST
- [ ] Mode sombre
- [ ] Tags/Étiquettes
- [ ] Flux privés (authentifiés)

## 📄 Licence

Ce projet est fourni "tel quel" pour usage personnel ou professionnel.

## 👤 Support

Pour toute question ou problème:
- Email: support@dusselle.fr
- Documentation: https://dusselle.fr/README.md

---

**Version**: 1.0
**Dernière mise à jour**: <?= date('d/m/Y') ?>
**Auteur**: Claude Code + Max

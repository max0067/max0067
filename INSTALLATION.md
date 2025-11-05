# RSS Reader V2 - Guide d'Installation

Installation complète du lecteur RSS moderne avec interface Gmail-style.

## 📦 Installation Complète (Recommandé)

### Option 1: Installation automatique en 3 étapes

Sur votre serveur (SSH ou cPanel File Manager):

```bash
cd ~/public_html

# Télécharger les 3 scripts d'installation
wget https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/install_v2_complete.php
wget https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/install_v2_part2.php
wget https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/install_v2_part3.php

# Exécuter les installations dans l'ordre
php install_v2_complete.php   # Part 1: Base de données et configuration
php install_v2_part2.php       # Part 2: API et routeur
php install_v2_part3.php       # Part 3: Interface (views et assets)
```

### Option 2: Configuration rapide avec flux pré-configurés

Après l'installation, ajoutez automatiquement des flux français populaires:

```bash
cd ~/public_html

# Télécharger les scripts utilitaires
wget https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/quick_setup.php
wget https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/manage_feeds.php
wget https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/update_rss.php
wget https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/debug.php

# Configuration rapide (ajoute flux + récupère articles)
php quick_setup.php
```

## 🔧 Gestion des Flux RSS

### Lister les flux

```bash
php manage_feeds.php list
```

### Ajouter un flux

```bash
php manage_feeds.php add "https://example.com/feed.xml"
```

Exemples de flux RSS français:
- Le Monde: `https://www.lemonde.fr/rss/une.xml`
- France 24: `https://www.france24.com/fr/rss`
- Le Figaro: `https://www.lefigaro.fr/rss/figaro_actualites.xml`
- Libération: `https://www.liberation.fr/arc/outboundfeeds/rss/`
- 20 Minutes: `https://www.20minutes.fr/feeds/rss-une.xml`

### Supprimer un flux

```bash
php manage_feeds.php delete 1
```

### Modifier un flux

```bash
php manage_feeds.php update 1 "https://new-url.com/feed.xml"
```

## 🔄 Mise à Jour des Articles

### Mettre à jour tous les flux

```bash
php update_rss.php
```

### Mettre à jour un flux spécifique

```bash
php update_rss.php 1
```

### Configuration CRON (mise à jour automatique)

Ajoutez cette ligne dans votre crontab pour mettre à jour les flux toutes les 30 minutes:

```bash
*/30 * * * * cd ~/public_html && php update_rss.php > /dev/null 2>&1
```

Via cPanel:
1. Allez dans **Cron Jobs**
2. Ajoutez une nouvelle tâche
3. Fréquence: `*/30 * * * *`
4. Commande: `cd ~/public_html && php update_rss.php`

## 🐛 Diagnostic et Dépannage

### Exécuter le diagnostic complet

```bash
php debug.php
```

Ce script vérifie:
- ✓ Configuration (chemins, clés secrètes)
- ✓ Base de données (connexion, tables, données)
- ✓ Utilisateurs (admin, mots de passe)
- ✓ Flux RSS (liste, état, articles)
- ✓ Articles (total, non lus, récents)
- ✓ API (health check, endpoints)
- ✓ Fichiers (présence, permissions)

### Problèmes courants

#### "Rien ne s'affiche"

```bash
# Vérifier la base de données
php debug.php

# Ajouter des flux et récupérer les articles
php quick_setup.php

# Ou manuellement
php manage_feeds.php add "https://www.lemonde.fr/rss/une.xml"
php update_rss.php
```

#### "Impossible de se connecter"

Le mot de passe par défaut est: **admin123**

Si le problème persiste:

```bash
# Vérifier que le mot de passe est correct
php debug.php
```

Si le hash est incorrect, réinstallez:

```bash
php install_v2_complete.php
```

#### "Les articles ne se mettent pas à jour"

```bash
# Mise à jour manuelle
php update_rss.php

# Vérifier les flux
php manage_feeds.php list

# Vérifier les logs/erreurs
php debug.php
```

## 📁 Structure des Fichiers

```
public_html/
├── config.php              # Configuration (chemins, clés)
├── Database.php            # Classe de base de données
├── Session.php             # Gestion des sessions
├── index.php               # Routeur principal
├── api.php                 # API REST
├── .htaccess               # Configuration Apache
├── rss_feeds.db            # Base de données SQLite
├── views/
│   ├── login.php          # Page de connexion
│   └── dashboard.php      # Dashboard principal
├── assets/
│   ├── style.css          # Styles CSS
│   └── app.js             # JavaScript
└── scripts utilitaires:
    ├── install_v2_complete.php   # Installation Part 1
    ├── install_v2_part2.php      # Installation Part 2
    ├── install_v2_part3.php      # Installation Part 3
    ├── quick_setup.php           # Configuration rapide
    ├── manage_feeds.php          # Gestion des flux
    ├── update_rss.php            # Mise à jour RSS
    └── debug.php                 # Diagnostic
```

## 🌐 Accès Web

- **URL**: http://dusselle.fr/
- **Login**: admin
- **Password**: admin123

## 🎨 Fonctionnalités

- ✓ Interface moderne Gmail-style
- ✓ Responsive (mobile, tablette, desktop)
- ✓ Gestion des flux RSS (ajout, suppression)
- ✓ Marquage lu/non lu
- ✓ Recherche d'articles
- ✓ Filtres (tous, non lus, par flux)
- ✓ Lecture en panneau latéral
- ✓ Mise à jour automatique (CRON)
- ✓ Base de données SQLite
- ✓ Session sécurisée
- ✓ API REST

## 📝 Utilisation Quotidienne

### Matin (première consultation)

```bash
# Mettre à jour les flux
ssh votre-serveur
cd ~/public_html
php update_rss.php
```

Ou configurez le CRON pour automatiser.

### Ajouter un nouveau flux

```bash
php manage_feeds.php add "https://nouveau-site.com/rss"
php update_rss.php
```

### Nettoyer les vieux articles (optionnel)

```bash
# Dans SQLite, via SSH
sqlite3 rss_feeds.db "DELETE FROM articles WHERE published_date < datetime('now', '-30 days')"
```

## 🔒 Sécurité

- Les mots de passe sont hashés avec `password_hash()` (bcrypt)
- Les sessions utilisent des tokens aléatoires
- La clé secrète est générée automatiquement
- La base de données SQLite est en mode WAL (Write-Ahead Logging)
- Protection CSRF via tokens de session

## 📞 Support

En cas de problème:

1. Exécutez `php debug.php` pour identifier le problème
2. Consultez les sections "Problèmes courants" ci-dessus
3. Vérifiez les logs du serveur (`error_log`)
4. Réinstallez si nécessaire avec `php install_v2_complete.php`

## 🚀 Prochaines Étapes

Après l'installation:

1. ✓ Connectez-vous à http://dusselle.fr/
2. ✓ Ajoutez vos flux RSS préférés (via web UI ou `manage_feeds.php`)
3. ✓ Configurez le CRON pour les mises à jour automatiques
4. ✓ Changez le mot de passe admin (recommandé)

---

**Version**: 2.0
**Dernière mise à jour**: 2025-11-05

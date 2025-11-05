# 🚀 Démarrage Rapide - Version PHP

## ✅ Changements effectués

Votre application RSS Reader fonctionne maintenant **100% en PHP** !

### Ce qui a été fait automatiquement :

1. ✅ `.htaccess` remplacé → Configuration PHP active
2. ✅ `app_v2.py` → `app_v2.py.disabled` (backup sauvegardé)
3. ✅ `passenger_wsgi.py` → `passenger_wsgi.py.disabled` (backup sauvegardé)
4. ✅ Tous les fichiers PHP créés et configurés
5. ✅ Documentation complète ajoutée

---

## 🎯 Prochaines étapes

### 1️⃣ Tester l'application

Accédez à votre site : **http://dusselle.fr** (ou votre URL)

**Connexion admin par défaut :**
- Username : `admin`
- Password : `admin123`

⚠️ **Changez ce mot de passe immédiatement !**

### 2️⃣ Configurer le CRON (mise à jour automatique des flux)

Dans votre **cPanel o2switch** :

1. Allez dans **Cron Jobs**
2. Créez une nouvelle tâche :
   - **Minute** : `*/30` (toutes les 30 minutes)
   - **Commande** : `/usr/bin/php /home/VOTRE_USER/public_html/cron_update_feeds.php`

Remplacez `/home/VOTRE_USER/public_html/` par le chemin réel de votre application.

**Exemple complet :**
```bash
*/30 * * * * /usr/bin/php /home/dusselle/public_html/cron_update_feeds.php >> /home/dusselle/public_html/logs/cron.log 2>&1
```

### 3️⃣ Personnaliser la configuration (optionnel)

Éditez `php/config.php` pour :
- Changer la clé secrète (SECRET_KEY)
- Ajuster l'intervalle de mise à jour
- Modifier les paramètres de log

```php
// php/config.php
define('SECRET_KEY', 'votre-cle-unique-super-secrete');
define('UPDATE_INTERVAL', 30); // minutes
```

---

## 📁 Structure de votre application

```
/
├── index.php                    # ✅ Point d'entrée PHP (ACTIF)
├── .htaccess                    # ✅ Config Apache PHP (ACTIF)
├── cron_update_feeds.php        # ✅ Script CRON (à configurer)
│
├── php/                         # ✅ Backend PHP complet
│   ├── config.php               # Configuration
│   ├── database.php             # Base de données SQLite
│   ├── session.php              # Authentification
│   ├── rss_updater.php          # Parser RSS
│   └── api.php                  # API REST
│
├── templates/                   # HTML (inchangés)
├── static/                      # CSS, JS (inchangés)
│
├── logs/                        # 📝 Logs de l'application
│   ├── app.log                  # Logs PHP
│   └── cron.log                 # Logs CRON (à créer)
│
├── rss_feeds.db                 # 💾 Base de données (préservée)
│
└── Fichiers Python (backup)
    ├── app_v2.py.disabled       # 🔒 Ancien backend Flask
    ├── passenger_wsgi.py.disabled # 🔒 Ancien WSGI
    └── .htaccess_python_backup  # 🔒 Ancienne config
```

---

## 🔍 Vérifications

### Vérifier que PHP est actif

```bash
# Afficher les premières lignes de .htaccess
head -5 .htaccess
```

Doit afficher :
```
# Configuration Apache pour l'application PHP RSS Reader
```

### Vérifier que Python est désactivé

```bash
# Lister les fichiers *.disabled
ls -la *.py.disabled
```

Doit afficher :
```
app_v2.py.disabled
passenger_wsgi.py.disabled
```

### Tester le script CRON manuellement

```bash
php cron_update_feeds.php
```

Doit afficher :
```
=== Démarrage de la mise à jour des flux RSS ===
Date: 2025-11-05 ...
```

---

## 🆘 Dépannage

### ❌ Erreur "500 Internal Server Error"

**Solutions :**

1. **Vérifier les permissions**
   ```bash
   chmod 755 php/
   chmod 644 php/*.php
   chmod 755 index.php
   chmod 755 cron_update_feeds.php
   ```

2. **Consulter les logs**
   ```bash
   tail -50 logs/app.log
   ```

3. **Vérifier le .htaccess**
   ```bash
   cat .htaccess | head -10
   ```
   Doit commencer par "Configuration Apache pour l'application PHP"

### ❌ Page blanche ou sans style

**Solutions :**

1. **Vérifier mod_rewrite**
   - Normalement actif sur o2switch
   - Vérifier dans cPanel > MultiPHP INI Editor

2. **Vérifier les chemins dans .htaccess**
   ```apache
   RewriteBase /
   ```
   Si l'app est dans un sous-dossier, changez en :
   ```apache
   RewriteBase /sous-dossier/
   ```

### ❌ Base de données vide ou erreur

**Solution :**

Supprimez la base et rechargez la page (elle sera recréée) :
```bash
rm rss_feeds.db
# Puis rechargez votre site dans le navigateur
```

### ❌ Je veux revenir à Python

Consultez `SWITCH_VERSION.md` pour les instructions de retour.

**Commande rapide :**
```bash
cp .htaccess_python_backup .htaccess
mv app_v2.py.disabled app_v2.py
mv passenger_wsgi.py.disabled passenger_wsgi.py
mv index.php index.php.disabled
```

---

## 📚 Documentation complète

- **README_PHP.md** → Documentation technique complète
- **MIGRATION_PYTHON_TO_PHP.md** → Détails de la migration
- **SWITCH_VERSION.md** → Basculer entre PHP et Python

---

## ✨ Avantages de la version PHP

| Avant (Python) | Maintenant (PHP) |
|----------------|------------------|
| ❌ Dépendances complexes (pip, virtualenv) | ✅ Aucune dépendance |
| ❌ Configuration Passenger complexe | ✅ Apache natif |
| ❌ APScheduler pour CRON | ✅ CRON standard |
| ⚠️ Compatibilité limitée | ✅ Compatible partout |
| ⚠️ Maintenance complexe | ✅ Maintenance simple |
| ✅ Fonctionnel | ✅ Fonctionnel (identique) |

---

## 🎉 Récapitulatif

✅ **Application migrée** de Python vers PHP
✅ **Données préservées** (même base SQLite)
✅ **Interface identique** (mêmes templates HTML)
✅ **Fichiers Python sauvegardés** (retour possible)
✅ **Documentation complète** ajoutée
✅ **Commits pushés** sur GitHub

### Prochaine action recommandée :

1. 🔐 Changez le mot de passe admin
2. ⏰ Configurez le CRON
3. 🎨 Personnalisez la clé secrète dans config.php

---

**Date de migration** : 2025-11-05
**Version PHP** : 1.0.0
**Statut** : ✅ PRÊT À L'EMPLOI

🚀 **Votre application RSS Reader est maintenant 100% PHP !**

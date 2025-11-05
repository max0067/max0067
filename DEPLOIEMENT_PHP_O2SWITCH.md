# 🚀 Déploiement PHP sur o2switch - Guide Complet

## ⚠️ IMPORTANT

Votre site affiche "something went wrong" car l'**ancienne version Python est encore active** sur le serveur o2switch.

Vous devez **déployer la nouvelle version PHP** pour que ça fonctionne.

---

## 🎯 Méthode 1 : Déploiement Automatique (RECOMMANDÉ)

### Via SSH (si vous avez accès SSH)

1. **Connectez-vous en SSH** à votre serveur o2switch :
   ```bash
   ssh votre_user@dusselle.fr
   ```

2. **Allez dans le répertoire de l'application** :
   ```bash
   cd ~/public_html  # ou ~/dusselle.fr selon votre config
   ```

3. **Récupérez les derniers changements** :
   ```bash
   git pull origin claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ
   ```

4. **Exécutez le script de déploiement** :
   ```bash
   chmod +x deploy_php_o2switch.sh
   ./deploy_php_o2switch.sh
   ```

5. **Testez** : http://dusselle.fr

---

## 🎯 Méthode 2 : Déploiement Manuel via cPanel (SI PAS DE SSH)

### Étape 1 : Télécharger les fichiers PHP

**Sur votre ordinateur local**, téléchargez les fichiers depuis GitHub :

```bash
git clone https://github.com/max0067/max0067.git
cd max0067
git checkout claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ
```

Ou téléchargez le ZIP depuis GitHub.

### Étape 2 : Se connecter à cPanel o2switch

1. Allez sur : https://www.o2switch.fr/cpanel/
2. Connectez-vous avec vos identifiants o2switch
3. Ouvrez **File Manager** (Gestionnaire de fichiers)

### Étape 3 : Sauvegarder l'ancienne configuration

Dans File Manager :

1. Naviguez vers `public_html/` (ou votre dossier d'installation)
2. **Sauvegardez** `.htaccess` :
   - Clic droit sur `.htaccess`
   - Rename → `.htaccess_python_backup`
3. **Sauvegardez** les fichiers Python :
   - Renommer `app_v2.py` → `app_v2.py.disabled`
   - Renommer `passenger_wsgi.py` → `passenger_wsgi.py.disabled`

### Étape 4 : Uploader les nouveaux fichiers PHP

Dans File Manager, **uploadez** ces fichiers :

**Fichiers à uploader à la racine :**
- `index.php`
- `.htaccess_php` (puis renommer en `.htaccess`)
- `cron_update_feeds.php`
- `DEMARRAGE_PHP.md`
- `README_PHP.md`
- `SWITCH_VERSION.md`

**Créer et uploader le dossier `php/` avec :**
- `php/config.php`
- `php/database.php`
- `php/session.php`
- `php/rss_updater.php`
- `php/api.php`

**Les dossiers existants à CONSERVER :**
- `templates/` (ne pas toucher)
- `static/` (ne pas toucher)
- `rss_feeds.db` (ne pas toucher - base de données)

### Étape 5 : Créer le dossier logs

Dans File Manager :
1. Créer un nouveau dossier : `logs`
2. Définir les permissions : 755

### Étape 6 : Définir les permissions

Via File Manager, définir les permissions :

| Fichier/Dossier | Permissions |
|-----------------|-------------|
| `index.php` | 755 |
| `cron_update_feeds.php` | 755 |
| `php/` (dossier) | 755 |
| `php/*.php` (tous les fichiers) | 644 |
| `logs/` (dossier) | 755 |
| `.htaccess` | 644 |

### Étape 7 : Vérifier que .htaccess est activé

1. Ouvrez `.htaccess` dans l'éditeur
2. La première ligne doit être :
   ```
   # Configuration Apache pour l'application PHP RSS Reader
   ```
3. Si c'est écrit "Configuration Apache pour l'application Flask", c'est l'ancien fichier !

### Étape 8 : Tester

1. Ouvrez votre navigateur
2. Allez sur : **http://dusselle.fr**
3. Vous devriez voir la page de login
4. Connectez-vous avec :
   - Username : `admin`
   - Password : `admin123`

---

## 🎯 Méthode 3 : Déploiement via FTP (ALTERNATIVE)

Si vous préférez FTP à cPanel :

### 1. Connectez-vous en FTP

Utilisez FileZilla ou un autre client FTP :
- **Hôte** : ftp.dusselle.fr
- **Utilisateur** : votre_user_o2switch
- **Mot de passe** : votre_password
- **Port** : 21

### 2. Naviguez vers le dossier web

Allez dans `public_html/` ou `dusselle.fr/`

### 3. Uploadez les fichiers

Même procédure que la méthode manuelle cPanel ci-dessus.

---

## ⚙️ Configuration post-déploiement

### 1. Configurer le CRON (IMPORTANT pour mise à jour automatique)

Dans cPanel :

1. Allez dans **Cron Jobs**
2. Ajoutez une nouvelle tâche :

**Paramètres :**
- **Minute** : `*/30`
- **Heure** : `*`
- **Jour** : `*`
- **Mois** : `*`
- **Jour de la semaine** : `*`
- **Commande** :
  ```bash
  /usr/bin/php /home/VOTRE_USER/public_html/cron_update_feeds.php >> /home/VOTRE_USER/public_html/logs/cron.log 2>&1
  ```

**Remplacez** `VOTRE_USER` et le chemin selon votre configuration.

**Exemple complet :**
```bash
*/30 * * * * /usr/bin/php /home/dusselle/public_html/cron_update_feeds.php >> /home/dusselle/public_html/logs/cron.log 2>&1
```

### 2. Changer le mot de passe admin

1. Connectez-vous avec admin/admin123
2. Allez dans le profil
3. Changez immédiatement le mot de passe

### 3. Personnaliser la configuration

Éditez `php/config.php` via File Manager :

```php
// Changez cette ligne :
define('SECRET_KEY', 'votre-cle-tres-secrete-unique-123456789');

// Optionnel : ajustez l'intervalle de mise à jour
define('UPDATE_INTERVAL', 30); // minutes
```

---

## ✅ Vérification que tout fonctionne

### Checklist complète :

- [ ] Le site affiche la page de login (pas d'erreur "something went wrong")
- [ ] Je peux me connecter avec admin/admin123
- [ ] Le dashboard s'affiche correctement
- [ ] Je peux ajouter un flux RSS
- [ ] Les articles se chargent
- [ ] Le CRON est configuré
- [ ] J'ai changé le mot de passe admin
- [ ] J'ai changé SECRET_KEY dans config.php

---

## 🔍 Structure finale sur le serveur

```
/home/votre_user/public_html/  (ou dusselle.fr/)
│
├── index.php                    ✅ NOUVEAU (PHP)
├── .htaccess                    ✅ MODIFIÉ (PHP)
├── cron_update_feeds.php        ✅ NOUVEAU
│
├── php/                         ✅ NOUVEAU DOSSIER
│   ├── config.php
│   ├── database.php
│   ├── session.php
│   ├── rss_updater.php
│   └── api.php
│
├── templates/                   ✅ EXISTANT (inchangé)
│   ├── login.html
│   ├── index_v2.html
│   └── ...
│
├── static/                      ✅ EXISTANT (inchangé)
│   ├── css/
│   └── js/
│
├── logs/                        ✅ NOUVEAU DOSSIER
│   ├── app.log
│   └── cron.log
│
├── rss_feeds.db                 ✅ EXISTANT (préservé)
│
└── Backups Python (désactivés)
    ├── .htaccess_python_backup
    ├── app_v2.py.disabled
    └── passenger_wsgi.py.disabled
```

---

## 🆘 Dépannage

### ❌ Toujours "something went wrong"

**Causes possibles :**

1. **Le .htaccess n'a pas été remplacé**
   - Vérifier que `.htaccess` commence par "Configuration Apache pour l'application PHP"
   - Si non, renommer `.htaccess_php` en `.htaccess`

2. **Les fichiers PHP ne sont pas uploadés**
   - Vérifier que `index.php` existe
   - Vérifier que le dossier `php/` existe avec tous les fichiers

3. **Passenger essaie encore de charger Python**
   - Désactiver `app_v2.py` et `passenger_wsgi.py`
   - Les renommer en `.disabled`

4. **Cache Apache**
   - Attendre 2-3 minutes
   - Vider le cache du navigateur (Ctrl+F5)
   - Essayer en navigation privée

**Solution radicale :**
```bash
# Via SSH ou File Manager, supprimez complètement :
rm .htaccess
cp .htaccess_php .htaccess

# Désactivez tous les fichiers Python
mv *.py *.py.disabled

# Attendez 2 minutes et rechargez le site
```

### ❌ Erreur 500 avec PHP

**Vérifier :**

1. **Permissions** :
   ```bash
   chmod 755 index.php
   chmod 755 php/
   chmod 644 php/*.php
   ```

2. **Logs Apache** (dans cPanel > Logs > Error Log)

3. **Logs application** : `logs/app.log`

4. **Syntaxe PHP** :
   ```bash
   php -l index.php
   php -l php/config.php
   ```

### ❌ Page blanche

1. **Vérifier mod_rewrite** (normalement actif sur o2switch)
2. **Vérifier .htaccess** :
   ```apache
   RewriteEngine On
   RewriteBase /
   ```
3. **Consulter les logs**

### ❌ Base de données vide

Si la base est corrompue ou vide :

```bash
# Sauvegarder l'ancienne
mv rss_feeds.db rss_feeds.db.old

# Recharger le site → la base sera recréée automatiquement
```

### ❌ Les flux ne se mettent pas à jour

1. **Vérifier que le CRON est configuré** (cPanel > Cron Jobs)
2. **Tester manuellement** :
   ```bash
   php cron_update_feeds.php
   ```
3. **Consulter** `logs/cron.log`

---

## 📞 Support

### Si le problème persiste

1. **Consultez les logs** :
   - cPanel > Logs > Error Log
   - File Manager > logs/app.log

2. **Vérifiez la configuration PHP** :
   - cPanel > MultiPHP INI Editor
   - Vérifier que PHP 7.4+ est actif

3. **Contactez le support o2switch** :
   - Expliquez que vous avez migré de Python vers PHP
   - Demandez s'ils voient des erreurs dans les logs

---

## 📝 Résumé : Que faire MAINTENANT

### Si vous avez accès SSH :

```bash
# 1. Connexion SSH
ssh votre_user@dusselle.fr

# 2. Aller dans le dossier
cd ~/public_html

# 3. Git pull
git pull origin claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ

# 4. Déployer
./deploy_php_o2switch.sh

# 5. Tester
# http://dusselle.fr
```

### Si vous n'avez PAS accès SSH :

1. **Télécharger** les fichiers depuis GitHub
2. **Se connecter** à cPanel File Manager
3. **Sauvegarder** l'ancien .htaccess
4. **Uploader** les nouveaux fichiers PHP
5. **Remplacer** .htaccess
6. **Désactiver** les fichiers Python (.disabled)
7. **Tester** le site

---

## ✅ Après le déploiement

1. ✅ Tester le site : http://dusselle.fr
2. ✅ Connexion admin/admin123
3. ✅ Changer le mot de passe
4. ✅ Configurer le CRON
5. ✅ Changer SECRET_KEY dans config.php
6. ✅ Ajouter quelques flux RSS pour tester

---

**Date** : 2025-11-05
**Version** : PHP 1.0.0
**Statut** : Prêt pour production 🚀

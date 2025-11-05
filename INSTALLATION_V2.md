# 🚀 Installation RSS Reader V2 - Version Moderne

## ✨ Nouveautés V2

- **Interface Gmail-style** : Design moderne, épuré et professionnel
- **Ne bug jamais au redémarrage** : Auto-initialisation complète
- **Migration automatique** : Base de données mise à jour automatiquement
- **Health check intégré** : Surveillance de l'état du système
- **Installation parallèle** : Testez sans casser la V1

---

## 📋 Prérequis

- ✅ V1 fonctionnelle (optionnel, pour partager la base de données)
- ✅ PHP 7.4 ou supérieur
- ✅ SQLite 3
- ✅ Accès SSH ou cPanel File Manager

---

## 🎯 Méthode 1 : Installation via SSH (RECOMMANDÉ)

### Étape 1 : Connexion SSH

```bash
ssh votre_user@dusselle.fr
cd ~/public_html
```

### Étape 2 : Pull des derniers changements

```bash
git pull origin claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ
```

### Étape 3 : Installer V2 en parallèle

```bash
# Créer le dossier v2
mkdir -p v2

# Copier les fichiers V2
cp -r v2_modern/* v2/

# Définir les permissions
chmod -R 755 v2
chmod 644 v2/*.php
chmod 644 v2/views/*.php
chmod 644 v2/assets/*

# Créer les dossiers nécessaires (auto-créés normalement)
mkdir -p v2/data v2/logs v2/cache
chmod 755 v2/data v2/logs v2/cache
```

### Étape 4 : Tester V2

```bash
# Test de santé
curl http://dusselle.fr/v2/?health

# Résultat attendu :
# {"status":"ok","database":"connected","version":"2.0.0"}
```

### Étape 5 : Accéder à V2

Ouvrez votre navigateur : **http://dusselle.fr/v2/**

Connectez-vous avec :
- Username : `admin`
- Password : `admin123`

---

## 🎯 Méthode 2 : Installation via cPanel (SI PAS DE SSH)

### Étape 1 : Télécharger les fichiers

**Sur votre ordinateur local :**

```bash
git clone https://github.com/max0067/max0067.git
cd max0067
git checkout claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ
```

Ou téléchargez le ZIP depuis GitHub.

### Étape 2 : Se connecter à cPanel

1. Allez sur : https://www.o2switch.fr/cpanel/
2. Connectez-vous avec vos identifiants
3. Ouvrez **File Manager**

### Étape 3 : Créer le dossier v2

Dans File Manager :

1. Naviguez vers `public_html/`
2. Cliquez sur **+ Folder** (Nouveau dossier)
3. Nommez-le : `v2`

### Étape 4 : Uploader les fichiers V2

Dans File Manager, **uploadez dans `public_html/v2/` :**

**Fichiers à la racine de v2/ :**
- `index.php`
- `api.php`
- `config.php`
- `Database.php`
- `Session.php`
- `.htaccess`
- `README.md`

**Créer et uploader le dossier `views/` avec :**
- `views/login.php`
- `views/dashboard.php`

**Créer et uploader le dossier `assets/` avec :**
- `assets/style.css`
- `assets/app.js`

### Étape 5 : Créer les dossiers nécessaires

Dans File Manager, créez ces dossiers dans `v2/` :
- `data/` (permissions 755)
- `logs/` (permissions 755)
- `cache/` (permissions 755)

### Étape 6 : Définir les permissions

Via File Manager, définir les permissions :

| Fichier/Dossier | Permissions |
|-----------------|-------------|
| `v2/` (dossier) | 755 |
| `v2/index.php` | 644 |
| `v2/*.php` | 644 |
| `v2/views/` | 755 |
| `v2/views/*.php` | 644 |
| `v2/assets/` | 755 |
| `v2/assets/*` | 644 |
| `v2/data/` | 755 |
| `v2/logs/` | 755 |
| `v2/cache/` | 755 |
| `v2/.htaccess` | 644 |

### Étape 7 : Tester

1. Ouvrez : **http://dusselle.fr/v2/**
2. Vous devriez voir la page de connexion moderne
3. Connectez-vous : `admin` / `admin123`

---

## ✅ Vérification de l'installation

### Checklist complète :

- [ ] V2 accessible via http://dusselle.fr/v2/
- [ ] Page de connexion s'affiche (design moderne)
- [ ] Connexion réussie avec admin/admin123
- [ ] Dashboard Gmail-style s'affiche
- [ ] Les flux RSS sont visibles (si V1 existait)
- [ ] Peut ajouter un nouveau flux
- [ ] Les articles se chargent
- [ ] Peut cliquer sur un article pour le lire
- [ ] Recherche fonctionne
- [ ] Health check retourne OK : http://dusselle.fr/v2/?health

---

## 🔧 Configuration post-installation

### 1. Changer le mot de passe admin

**IMPORTANT** : Changez immédiatement le mot de passe par défaut !

1. Connectez-vous à V2
2. Cliquez sur l'avatar en bas de la sidebar
3. Allez dans "Profil"
4. Changez le mot de passe

### 2. Personnaliser la configuration

Éditez `v2/config.php` via File Manager ou SSH :

```php
// Changez cette ligne avec une clé unique et secrète
define('SECRET_KEY', 'votre-cle-tres-secrete-unique-123456789');

// Optionnel : ajustez l'intervalle de mise à jour
define('UPDATE_INTERVAL', 30); // minutes
```

### 3. Configurer le CRON (optionnel pour V2)

Si vous voulez que V2 ait son propre système de mise à jour indépendant de V1 :

**Via cPanel > Cron Jobs :**

```
*/30 * * * * /usr/bin/php /home/VOTRE_USER/public_html/cron_update_feeds.php >> /home/VOTRE_USER/public_html/logs/cron.log 2>&1
```

**Note** : Si V1 et V2 partagent la même base de données, un seul CRON suffit.

---

## 🔄 Migration complète de V1 vers V2

### Quand migrer ?

Migrez vers V2 quand :
- ✅ Vous avez testé toutes les fonctionnalités de V2
- ✅ Tous vos flux RSS sont visibles dans V2
- ✅ Vous êtes satisfait de l'interface
- ✅ Pas de bugs détectés

### Étape 1 : Sauvegarder V1

**Via SSH :**

```bash
cd ~/public_html
tar -czf backup_v1_$(date +%Y%m%d).tar.gz \
  --exclude='v2' \
  --exclude='v2_modern' \
  --exclude='*.pyc' \
  --exclude='__pycache__' \
  .
```

**Via cPanel :**
1. File Manager > Sélectionner tous les fichiers (sauf v2/)
2. Compress > Create Archive
3. Nommer : `backup_v1_YYYYMMDD.tar.gz`
4. Télécharger l'archive sur votre ordinateur

### Étape 2 : Nettoyer l'ancienne version

**Via SSH :**

```bash
cd ~/public_html

# Désactiver les fichiers Python
mv app_v2.py app_v2.py.disabled
mv passenger_wsgi.py passenger_wsgi.py.disabled

# Sauvegarder l'ancien .htaccess
mv .htaccess .htaccess_v1_backup

# Supprimer les anciens fichiers PHP V1 (si présents)
rm -f php/*.php
rmdir php/
```

**Via cPanel :**
1. Renommer `app_v2.py` → `app_v2.py.disabled`
2. Renommer `passenger_wsgi.py` → `passenger_wsgi.py.disabled`
3. Renommer `.htaccess` → `.htaccess_v1_backup`

### Étape 3 : Déplacer V2 vers la racine

**Via SSH :**

```bash
cd ~/public_html

# Copier tous les fichiers de v2/ vers la racine
cp -r v2/* .

# Vérifier que tout est copié
ls -la
```

**Via cPanel File Manager :**
1. Ouvrir le dossier `v2/`
2. Sélectionner tous les fichiers
3. Clic droit > Copy
4. Remonter à `public_html/`
5. Clic droit > Paste

### Étape 4 : Ajuster le .htaccess

Éditez le nouveau `.htaccess` et changez :

```apache
# DE :
RewriteBase /v2/

# VERS :
RewriteBase /
```

Et dans les règles de réécriture, changez :

```apache
# DE :
RewriteCond %{REQUEST_URI} !^/v2/assets/

# VERS :
RewriteCond %{REQUEST_URI} !^/assets/
```

### Étape 5 : Ajuster les chemins dans les templates

Éditez `views/login.php` et `views/dashboard.php` :

```html
<!-- DE : -->
<link rel="stylesheet" href="/v2/assets/style.css">
<script src="/v2/assets/app.js"></script>

<!-- VERS : -->
<link rel="stylesheet" href="/assets/style.css">
<script src="/assets/app.js"></script>
```

Et dans `assets/app.js` :

```javascript
// DE :
const response = await fetch('/v2/api${endpoint}', {

// VERS :
const response = await fetch('/api${endpoint}', {
```

### Étape 6 : Tester la nouvelle installation

1. Ouvrez : **http://dusselle.fr/**
2. Vous devriez voir la page de connexion V2
3. Connectez-vous et testez toutes les fonctionnalités

### Étape 7 : Nettoyer (optionnel)

Si tout fonctionne, supprimez les anciens fichiers :

```bash
cd ~/public_html
rm -rf v2/ v2_modern/
rm -f *.py.disabled
rm -f .htaccess_v1_backup
```

---

## 🆘 Dépannage

### ❌ Erreur 404 sur V2

**Cause** : `.htaccess` pas correctement configuré

**Solution** :
```bash
# Vérifier que .htaccess existe dans v2/
ls -la v2/.htaccess

# Vérifier le contenu
head -10 v2/.htaccess

# Si absent, copier depuis v2_modern/
cp v2_modern/.htaccess v2/.htaccess
```

### ❌ Erreur 500 sur V2

**Cause** : Permissions incorrectes ou erreur PHP

**Solution** :
```bash
# Corriger les permissions
chmod -R 755 v2/
chmod 644 v2/*.php

# Consulter les logs
tail -50 v2/logs/app.log
tail -50 ~/logs/error_log
```

### ❌ Styles ne se chargent pas

**Cause** : Chemin incorrect ou fichier manquant

**Solution** :
```bash
# Vérifier que les assets existent
ls -la v2/assets/

# Vérifier les permissions
chmod 755 v2/assets/
chmod 644 v2/assets/*.css
chmod 644 v2/assets/*.js
```

### ❌ Base de données introuvable

**Cause** : Le V2 ne trouve pas la base de V1

**Solution** :

Le V2 cherche la base de données dans cet ordre :
1. `../rss_feeds.db` (racine du site, partagée avec V1)
2. `data/rss_feeds.db` (locale à V2)
3. Crée automatiquement `data/rss_feeds.db` si rien trouvé

Pour forcer l'utilisation de la base V1 :
```bash
# Vérifier que la base existe
ls -la ~/public_html/rss_feeds.db

# Si elle existe mais V2 ne la trouve pas, créer un lien symbolique
ln -s ../rss_feeds.db v2/data/rss_feeds.db
```

### ❌ Health check retourne une erreur

```bash
# Tester le health check
curl http://dusselle.fr/v2/?health

# Si erreur, consulter les logs
tail -50 v2/logs/app.log
```

---

## 📊 Comparaison V1 vs V2

| Fonctionnalité | V1 | V2 |
|----------------|----|----|
| **Interface** | Basique | Gmail-style moderne |
| **Auto-initialisation** | ⚠️ Manuel | ✅ Automatique |
| **Migration DB** | ⚠️ Manuel | ✅ Automatique |
| **Health check** | ❌ Non | ✅ Oui |
| **Logs** | ⚠️ Basique | ✅ Complet |
| **Responsive** | ⚠️ Partiel | ✅ Complet |
| **Recherche** | ⚠️ Basique | ✅ Temps réel |
| **Gestion d'erreurs** | ⚠️ Basique | ✅ Robuste |

---

## 📝 Résumé des commandes

### Installation rapide (SSH)

```bash
cd ~/public_html
git pull origin claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ
mkdir -p v2
cp -r v2_modern/* v2/
chmod -R 755 v2
curl http://dusselle.fr/v2/?health
```

### Migration V1 → V2 (SSH)

```bash
cd ~/public_html
tar -czf backup_v1_$(date +%Y%m%d).tar.gz --exclude='v2' .
cp -r v2/* .
# Éditer .htaccess, views/*.php, assets/app.js (changer /v2/ vers /)
```

---

## 🎉 C'est tout !

Vous avez maintenant **RSS Reader V2** installé et fonctionnel !

**Temps d'installation estimé** :
- Via SSH : 5-10 minutes ⏱️
- Via cPanel : 15-20 minutes ⏱️
- Migration complète : 10-15 minutes ⏱️

---

**Questions ou problèmes ?**
1. Consultez `v2/logs/app.log`
2. Testez le health check : `http://dusselle.fr/v2/?health`
3. Consultez `v2/README.md` pour plus de détails

**Bon lecteur RSS ! 🚀**

---

**Date** : 2025-11-05
**Version** : 2.0.0
**Statut** : Prêt pour production 🎯

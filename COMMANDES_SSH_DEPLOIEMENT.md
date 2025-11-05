# 🚀 Déploiement PHP via SSH - Commandes exactes

## ✅ ÉTAPES À SUIVRE

### 1️⃣ Connexion SSH à o2switch

```bash
ssh votre_user@dusselle.fr
# OU
ssh votre_user@ssh.o2switch.net
```

Remplacez `votre_user` par votre nom d'utilisateur o2switch.

---

### 2️⃣ Aller dans le répertoire de l'application

```bash
# Si l'application est dans public_html :
cd ~/public_html

# OU si dans un sous-dossier :
cd ~/public_html/rss-manager

# OU si dans un domaine séparé :
cd ~/dusselle.fr
```

**Pour trouver le bon chemin :**
```bash
# Lister les dossiers
ls -la ~/

# Chercher où se trouve index.html ou les fichiers Python
find ~ -name "app_v2.py" 2>/dev/null
```

---

### 3️⃣ Vérifier que vous êtes dans le bon dossier

```bash
# Vous devriez voir les fichiers Python actuels
ls -la *.py

# Résultat attendu :
# app_v2.py
# passenger_wsgi.py
# database_v2.py
# etc.
```

---

### 4️⃣ Récupérer les derniers changements depuis GitHub

```bash
# Récupérer la branche avec la version PHP
git pull origin claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ
```

**Si erreur "no such remote" :**
```bash
# Vérifier l'origine
git remote -v

# Ajouter l'origine si nécessaire
git remote add origin https://github.com/max0067/max0067.git

# Puis refaire le pull
git pull origin claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ
```

**Si erreur "uncommitted changes" :**
```bash
# Sauvegarder les changements locaux
git stash

# Puis refaire le pull
git pull origin claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ
```

---

### 5️⃣ Vérifier que les fichiers PHP sont bien là

```bash
# Vérifier que les nouveaux fichiers sont présents
ls -la index.php php/ deploy_php_o2switch.sh

# Résultat attendu :
# -rw-r--r-- index.php
# drwxr-xr-x php/
# -rwxr-xr-x deploy_php_o2switch.sh
```

---

### 6️⃣ Exécuter le script de déploiement

```bash
# Rendre le script exécutable
chmod +x deploy_php_o2switch.sh

# Exécuter le script
./deploy_php_o2switch.sh
```

**Le script va automatiquement :**
- ✅ Sauvegarder l'ancien `.htaccess`
- ✅ Activer le `.htaccess` PHP
- ✅ Désactiver les fichiers Python
- ✅ Créer le dossier `logs/`
- ✅ Configurer les permissions
- ✅ Vérifier que tout est OK

---

### 7️⃣ Vérification manuelle

```bash
# Vérifier que .htaccess est bien PHP
head -5 .htaccess

# Résultat attendu :
# Configuration Apache pour l'application PHP RSS Reader

# Vérifier que les fichiers Python sont désactivés
ls -la *.py.disabled

# Résultat attendu :
# app_v2.py.disabled
# passenger_wsgi.py.disabled
```

---

### 8️⃣ Tester l'application

**Ouvrez votre navigateur et allez sur :**
```
http://dusselle.fr
```

**Vous devriez voir :**
- ✅ La page de login (pas d'erreur "something went wrong")

**Connectez-vous avec :**
- Username : `admin`
- Password : `admin123`

⚠️ **IMPORTANT : Changez le mot de passe immédiatement !**

---

### 9️⃣ Configurer le CRON (mise à jour automatique des flux)

Toujours en SSH :

```bash
# Ouvrir l'éditeur de crontab
crontab -e

# Ajouter cette ligne à la fin du fichier :
*/30 * * * * /usr/bin/php ~/public_html/cron_update_feeds.php >> ~/public_html/logs/cron.log 2>&1

# Sauvegarder et quitter (ESC puis :wq dans vi, ou Ctrl+X dans nano)
```

**⚠️ Ajustez le chemin selon votre configuration :**
- Remplacez `~/public_html/` par le chemin réel de votre application
- Exemple : `~/dusselle.fr/` ou `~/public_html/rss-manager/`

**Pour vérifier le CRON :**
```bash
# Lister les tâches CRON
crontab -l
```

**Pour tester manuellement :**
```bash
# Exécuter le script CRON manuellement
php cron_update_feeds.php

# Résultat attendu :
# === Démarrage de la mise à jour des flux RSS ===
# Date: 2025-11-05 ...
```

---

### 🔟 Personnaliser la configuration (recommandé)

```bash
# Éditer le fichier de configuration
nano php/config.php
# OU
vi php/config.php

# Changer cette ligne :
define('SECRET_KEY', 'votre-cle-tres-secrete-unique-12345');

# Sauvegarder et quitter
```

---

## ✅ RÉSUMÉ : Commandes complètes d'un coup

**Copier-coller ces commandes :**

```bash
# 1. Connexion SSH (à adapter)
ssh votre_user@dusselle.fr

# 2. Aller dans le bon dossier (à adapter)
cd ~/public_html

# 3. Récupérer les changements
git pull origin claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ

# 4. Déployer
chmod +x deploy_php_o2switch.sh
./deploy_php_o2switch.sh

# 5. Configurer le CRON
crontab -e
# Ajouter : */30 * * * * /usr/bin/php ~/public_html/cron_update_feeds.php >> ~/public_html/logs/cron.log 2>&1

# 6. Tester
# Ouvrir http://dusselle.fr dans le navigateur
```

---

## 🆘 Dépannage

### ❌ "git: command not found"

Git n'est pas installé. Téléchargez les fichiers manuellement :

```bash
# Télécharger les fichiers depuis GitHub
wget https://github.com/max0067/max0067/archive/refs/heads/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ.zip

# Décompresser
unzip claude-rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ.zip

# Copier les fichiers PHP
cp -r max0067-claude-rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/php .
cp max0067-claude-rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/index.php .
cp max0067-claude-rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/.htaccess_php .htaccess
cp max0067-claude-rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/cron_update_feeds.php .

# Désactiver Python
mv app_v2.py app_v2.py.disabled
mv passenger_wsgi.py passenger_wsgi.py.disabled

# Créer le dossier logs
mkdir -p logs
chmod 755 logs
```

### ❌ "Permission denied"

```bash
# Donner les permissions d'exécution
chmod +x deploy_php_o2switch.sh

# Si toujours un problème, exécuter avec bash
bash deploy_php_o2switch.sh
```

### ❌ Toujours "something went wrong" après déploiement

```bash
# Vérifier que .htaccess est bien PHP
cat .htaccess | head -5

# Forcer le remplacement
cp -f .htaccess_php .htaccess

# Attendre 2-3 minutes que Apache recharge
# Vider le cache du navigateur (Ctrl+F5)
```

### ❌ Consulter les logs en cas de problème

```bash
# Logs de l'application
tail -50 logs/app.log

# Logs CRON
tail -50 logs/cron.log

# Logs Apache (chemin peut varier)
tail -50 ~/logs/error_log
# OU
tail -50 ~/public_html/error_log
```

---

## 📋 Checklist finale

- [ ] SSH connecté
- [ ] Dans le bon répertoire
- [ ] `git pull` effectué
- [ ] Script de déploiement exécuté
- [ ] Site accessible : http://dusselle.fr
- [ ] Login réussi (admin/admin123)
- [ ] Mot de passe admin changé
- [ ] CRON configuré
- [ ] SECRET_KEY personnalisée dans config.php

---

## 🎉 C'est tout !

Une fois ces étapes terminées, votre site **dusselle.fr** fonctionnera en **PHP pur** !

**Temps estimé : 5-10 minutes** ⏱️

---

**En cas de problème, consultez :**
- `logs/app.log` sur le serveur
- `DEPLOIEMENT_PHP_O2SWITCH.md` pour plus de détails
- Les logs Apache dans cPanel

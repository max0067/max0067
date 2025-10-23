# 🚀 DÉMARRAGE RAPIDE - DUSSELLE.FR

## ⚡ SOLUTION EN 3 ÉTAPES

### 📤 ÉTAPE 1 : Uploader les fichiers

**Via FTP (FileZilla) ou Gestionnaire de fichiers cPanel**

Uploadez TOUS les fichiers dans le dossier :
```
/home/VOTRE_USER/dusselle.fr
```

OU dans :
```
/home/VOTRE_USER/public_html
```

---

### ⚙️ ÉTAPE 2 : Configuration automatique

**Connectez-vous au Terminal dans cPanel, puis :**

```bash
# 1. Aller dans le dossier
cd dusselle.fr
# OU : cd public_html

# 2. Vérifier que les fichiers sont là
ls -la app_v2.py

# 3. Créer l'application Python dans cPanel
# Allez dans : cPanel > "Setup Python App" > "Create Application"
# - Python version : 3.11 ou 3.12
# - Application root : dusselle.fr (ou public_html)
# - Application URL : /
# - Application startup file : passenger_wsgi.py
# - Application Entry point : application

# 4. Activer le virtualenv (commande donnée par cPanel)
source ~/virtualenv/dusselle.fr/3.11/bin/activate

# 5. 🎯 LANCER LE SCRIPT DE CONFIGURATION AUTOMATIQUE
python config_dusselle.py
```

**Ce script va :**
- ✅ Créer `passenger_wsgi.py` avec les bons chemins
- ✅ Créer `.htaccess` avec votre configuration
- ✅ Créer le dossier `tmp` pour les redémarrages
- ✅ Vérifier la base de données

---

### 📦 ÉTAPE 3 : Installation finale

```bash
# 1. Installer les dépendances (si pas déjà fait par le script)
pip install -r requirements.txt

# 2. Initialiser la base de données
python database_v2.py

# 3. Redémarrer l'application
touch tmp/restart.txt
```

**TERMINÉ ! 🎉**

Testez sur : **https://dusselle.fr/login**

Connexion :
- Username : `admin`
- Password : `admin123`

---

## 🔍 SI ÇA NE MARCHE PAS

### Lancez le diagnostic automatique :

```bash
cd dusselle.fr
python verif_deploiement.py
```

Ce script va vérifier :
- ✅ Tous les fichiers nécessaires
- ✅ Les modules Python installés
- ✅ La base de données
- ✅ La configuration passenger_wsgi.py et .htaccess
- ✅ Le virtualenv
- ✅ Les permissions

Et vous dire **exactement** ce qui ne va pas !

---

## 📋 RÉSUMÉ DES SCRIPTS CRÉÉS

### 1️⃣ `config_dusselle.py`
**Utilisation :** Configuration automatique
```bash
python config_dusselle.py
```
- Génère automatiquement tous les fichiers de config
- Détecte votre username et version Python
- Crée les bons chemins

### 2️⃣ `verif_deploiement.py`
**Utilisation :** Diagnostic des problèmes
```bash
python verif_deploiement.py
```
- Vérifie TOUT ce qui peut poser problème
- Donne des instructions précises pour corriger
- Identifie les fichiers manquants

---

## 🆘 PROBLÈMES COURANTS

### Problème : "Index of" - Liste de fichiers

**Solution rapide :**
```bash
cd dusselle.fr
python config_dusselle.py
touch tmp/restart.txt
```

### Problème : Erreur 500

**Solution :**
```bash
cd dusselle.fr
python verif_deploiement.py
```

Puis suivez les instructions données par le script.

### Problème : Module non trouvé

**Solution :**
```bash
cd dusselle.fr
source ~/virtualenv/dusselle.fr/3.11/bin/activate
pip install -r requirements.txt --force-reinstall
touch tmp/restart.txt
```

### Problème : Base de données verrouillée

**Solution :**
```bash
cd dusselle.fr
chmod 666 rss_feeds.db
touch tmp/restart.txt
```

---

## ✅ CHECKLIST ULTRA-RAPIDE

Pour vérifier que tout est bon :

```bash
cd dusselle.fr

# Vérifier les fichiers essentiels
ls -la app_v2.py database_v2.py passenger_wsgi.py .htaccess

# Lancer le diagnostic complet
python verif_deploiement.py

# Si tout est OK, redémarrer
touch tmp/restart.txt
```

---

## 🎯 COMMANDES ESSENTIELLES

```bash
# Configuration automatique (RECOMMANDÉ)
python config_dusselle.py

# Diagnostic complet
python verif_deploiement.py

# Initialiser la DB
python database_v2.py

# Redémarrer l'app
touch tmp/restart.txt

# Voir les logs d'erreur
tail -f ~/logs/error_log
```

---

## 📞 ORDRE D'EXÉCUTION IDÉAL

1. **Uploader les fichiers** (FTP ou cPanel)
2. **Créer l'app Python** dans cPanel
3. **Activer le virtualenv** (commande donnée par cPanel)
4. **`python config_dusselle.py`** (configuration auto)
5. **`pip install -r requirements.txt`** (dépendances)
6. **`python database_v2.py`** (initialiser DB)
7. **`touch tmp/restart.txt`** (redémarrer)
8. **Tester** : https://dusselle.fr/login

**Si problème à n'importe quelle étape :**
```bash
python verif_deploiement.py
```

---

## 🌟 AVANTAGES DES NOUVEAUX SCRIPTS

### Avant (manuel) :
1. Trouver le username avec `whoami`
2. Trouver le chemin du virtualenv
3. Éditer passenger_wsgi.py manuellement
4. Éditer .htaccess manuellement
5. Espérer ne pas faire d'erreur de typo
6. Debugger si ça marche pas

### Maintenant (automatique) :
```bash
python config_dusselle.py
```
**C'EST TOUT !** ✨

---

**💡 TIP :** Gardez ces deux scripts, ils sont utiles pour :
- Reconfigurer rapidement
- Diagnostiquer les problèmes
- Mettre à jour la config après un changement de serveur

---

**Bon déploiement ! 🚀**

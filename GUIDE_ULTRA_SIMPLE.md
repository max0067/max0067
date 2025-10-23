# 🚀 GUIDE SUPER SIMPLE - Déploiement o2switch (SANS SSH)

## Méthode la plus facile - Via cPanel uniquement

### ✅ ÉTAPE 1 : Télécharger les fichiers de l'application

#### Option A : Télécharger depuis GitHub
1. Allez sur votre dépôt GitHub
2. Cliquez sur le bouton vert **"Code"**
3. Cliquez sur **"Download ZIP"**
4. Décompressez le fichier ZIP sur votre ordinateur

#### Option B : Créer un ZIP local
Si vous avez les fichiers sur votre ordinateur :
1. Sélectionnez tous les fichiers du projet
2. Clic droit > **"Compresser"** ou **"Créer une archive"**
3. Nommez le fichier `rss-manager.zip`

---

### ✅ ÉTAPE 2 : Se connecter à cPanel

1. Allez sur : `https://www.votre-domaine.com/cpanel`
   Ou : `https://cpanel.o2switch.net` puis connectez-vous
2. Utilisez vos identifiants o2switch (reçus par email lors de l'inscription)

---

### ✅ ÉTAPE 3 : Créer un sous-domaine (RECOMMANDÉ)

**Pourquoi ?** C'est plus propre et plus facile à gérer.

1. Dans cPanel, cherchez **"Sous-domaines"** (ou "Subdomains")
2. Cliquez dessus
3. Remplissez :
   - **Sous-domaine** : `rss` (vous aurez donc rss.votre-domaine.com)
   - **Domaine** : Sélectionnez votre domaine principal
   - **Racine du document** : Laissez par défaut (ex: `/home/votre_user/rss.votre-domaine.com`)
4. Cliquez sur **"Créer"**

✅ Notez le chemin affiché (exemple : `/home/max0067/rss.votre-domaine.com`)

**OU si vous préférez un dossier dans votre domaine principal :**
- Vous utiliserez : `/home/votre_user/public_html/rss-manager`
- Et l'URL sera : `https://votre-domaine.com/rss-manager`

---

### ✅ ÉTAPE 4 : Uploader les fichiers

1. Dans cPanel, cherchez **"Gestionnaire de fichiers"** (ou "File Manager")
2. Cliquez dessus
3. Naviguez vers le dossier de votre sous-domaine :
   - Si sous-domaine : Allez dans `/home/votre_user/rss.votre-domaine.com`
   - Si dossier : Allez dans `/home/votre_user/public_html` puis créez un dossier `rss-manager`

4. **Supprimer les fichiers par défaut** (s'il y en a) :
   - Sélectionnez tous les fichiers (cgi-bin, index.html, etc.)
   - Cliquez sur **"Supprimer"**

5. **Uploader votre archive ZIP** :
   - Cliquez sur **"Téléverser"** ou **"Upload"** (en haut)
   - Cliquez sur **"Sélectionner un fichier"**
   - Choisissez votre fichier `rss-manager.zip`
   - Attendez la fin de l'upload (barre de progression verte)

6. **Décompresser le fichier** :
   - Retournez dans le gestionnaire de fichiers
   - Cliquez sur le fichier `rss-manager.zip`
   - Cliquez sur **"Extraire"** ou **"Extract"** (en haut)
   - Cliquez sur **"Extract File(s)"**
   - Attendez la fin de l'extraction

7. **Vérifier la structure** :
   - Vous devriez voir les fichiers : `app.py`, `database.py`, `requirements.txt`, etc.
   - Si les fichiers sont dans un sous-dossier (ex: `max0067-main/`), déplacez-les :
     * Entrez dans le sous-dossier
     * Sélectionnez TOUS les fichiers (Ctrl+A)
     * Cliquez sur **"Déplacer"**
     * Remontez d'un niveau (au dossier parent)
     * Cliquez sur **"Move File(s)"**
     * Supprimez le dossier vide et le fichier ZIP

---

### ✅ ÉTAPE 5 : Configurer Python Application

1. **Retournez à la page principale de cPanel**

2. Cherchez **"Setup Python App"** ou **"Application Python"**
   - C'est souvent dans la section "Software" ou "Logiciels"

3. Cliquez sur **"Create Application"** ou **"Créer une application"**

4. **Remplissez le formulaire** :

   📝 **Python version** : Choisissez la plus récente (3.9, 3.10, 3.11, ou 3.12)

   📝 **Application root** :
   - Si sous-domaine : `rss.votre-domaine.com` (ou le nom complet du dossier)
   - Si dossier : `public_html/rss-manager`

   📝 **Application URL** :
   - Si sous-domaine : Laissez vide ou mettez `/`
   - Si dossier : Mettez `rss-manager`

   📝 **Application startup file** : `passenger_wsgi.py`

   📝 **Application Entry point** : `application`

5. Cliquez sur **"Create"** ou **"Créer"**

✅ **IMPORTANT** : Notez la commande pour entrer dans l'environnement virtuel
   Elle ressemble à : `source /home/votre_user/virtualenv/.../.../bin/activate`

---

### ✅ ÉTAPE 6 : Installer les dépendances Python

1. Dans cPanel, cherchez **"Terminal"** (dans la section "Advanced" ou "Avancé")
2. Cliquez dessus pour ouvrir le terminal

3. **Copier-coller les commandes suivantes une par une** :

```bash
# 1. Aller dans le dossier de l'application
cd rss.votre-domaine.com
# OU si vous utilisez un dossier : cd public_html/rss-manager

# 2. Activer l'environnement virtuel
# Collez la commande notée à l'étape 5, par exemple :
source /home/votre_user/virtualenv/rss.votre-domaine.com/3.11/bin/activate

# 3. Mettre à jour pip
pip install --upgrade pip

# 4. Installer les dépendances
pip install -r requirements.txt

# 5. Initialiser la base de données
python database.py
```

4. **Vérifiez que tout s'est bien passé** :
   - Vous devriez voir "Base de données initialisée avec succès!"
   - Aucune erreur rouge

---

### ✅ ÉTAPE 7 : Corriger les chemins dans passenger_wsgi.py

1. Retournez dans le **Gestionnaire de fichiers**
2. Trouvez le fichier `passenger_wsgi.py`
3. Cliquez droit > **"Edit"** ou **"Modifier"**
4. Cherchez cette ligne :
```python
INTERP = os.path.expanduser("~/public_html/rss-manager/venv/bin/python")
```

5. **Remplacez-la** par le bon chemin selon votre installation :

**Si sous-domaine rss.votre-domaine.com :**
```python
INTERP = os.path.expanduser("~/virtualenv/rss.votre-domaine.com/3.11/bin/python")
```

**Si dossier public_html/rss-manager :**
```python
INTERP = os.path.expanduser("~/virtualenv/public_html/rss-manager/3.11/bin/python")
```

⚠️ **IMPORTANT** : Adaptez `3.11` à la version de Python que vous avez choisie (3.9, 3.10, 3.12, etc.)

6. Cliquez sur **"Enregistrer"** ou **"Save Changes"** (en haut à droite)

---

### ✅ ÉTAPE 8 : Corriger le fichier .htaccess

1. Dans le **Gestionnaire de fichiers**, trouvez `.htaccess`
   - Si vous ne le voyez pas, cliquez sur **"Paramètres"** (en haut à droite) et cochez **"Afficher les fichiers cachés"**

2. Cliquez droit > **"Edit"** ou **"Modifier"**

3. Cherchez ces lignes :
```apache
PassengerAppRoot /home/VOTRE_USER/public_html/rss-manager
```

4. **Remplacez VOTRE_USER par votre vrai nom d'utilisateur** et le chemin correct :

**Si sous-domaine :**
```apache
PassengerAppRoot /home/max0067/rss.votre-domaine.com
```

**Si dossier :**
```apache
PassengerAppRoot /home/max0067/public_html/rss-manager
```

5. **Enregistrez** le fichier

---

### ✅ ÉTAPE 9 : Redémarrer l'application

Dans le **Terminal** de cPanel :

```bash
# Aller dans le dossier
cd rss.votre-domaine.com
# OU : cd public_html/rss-manager

# Créer le dossier tmp s'il n'existe pas
mkdir -p tmp

# Redémarrer l'application
touch tmp/restart.txt
```

**OU** dans le **Gestionnaire de fichiers** :
1. Créez un dossier `tmp` (s'il n'existe pas)
2. Dans ce dossier, créez un fichier vide nommé `restart.txt`

---

### ✅ ÉTAPE 10 : Tester l'application

1. Ouvrez votre navigateur
2. Allez sur :
   - **Si sous-domaine** : `https://rss.votre-domaine.com`
   - **Si dossier** : `https://votre-domaine.com/rss-manager`

3. Vous devriez voir l'interface de l'application RSS Manager !

---

## 🆘 PROBLÈMES COURANTS

### ❌ Erreur 500 (Internal Server Error)

**Solution 1 : Vérifier les logs**
1. Dans cPanel > **"Erreurs"** ou **"Error Log"**
2. Regardez la dernière erreur
3. Envoyez-moi le message d'erreur si besoin

**Solution 2 : Vérifier les permissions**
Dans le Terminal :
```bash
cd ~/rss.votre-domaine.com
chmod 755 .
chmod 644 *.py
chmod 666 rss_feeds.db
```

**Solution 3 : Vérifier que Python trouve les modules**
Dans le Terminal :
```bash
cd ~/rss.votre-domaine.com
source /home/votre_user/virtualenv/.../bin/activate
python -c "import flask; print('Flask OK')"
```

---

### ❌ Page blanche ou "Not Found"

**Vérifiez que :**
1. L'URL est correcte
2. Le sous-domaine a bien été créé et propagé (attendre 5-10 minutes)
3. Les fichiers sont bien dans le bon dossier

**Dans cPanel > Setup Python App :**
1. Vérifiez que votre application est bien **"Running"** (en vert)
2. Sinon, cliquez sur **"Restart"**

---

### ❌ "No module named flask" ou autre module

Dans le Terminal :
```bash
cd ~/rss.votre-domaine.com
source /home/votre_user/virtualenv/.../bin/activate
pip install -r requirements.txt --force-reinstall
touch tmp/restart.txt
```

---

### ❌ Erreur avec passenger_wsgi.py

Dans le Terminal :
```bash
cd ~/rss.votre-domaine.com
python passenger_wsgi.py
```

Si vous voyez une erreur, notez-la et envoyez-la moi.

---

## 📞 BESOIN D'AIDE ?

**Envoyez-moi :**
1. L'URL que vous essayez d'ouvrir
2. Le message d'erreur complet (si vous en voyez un)
3. La dernière ligne du fichier de log (cPanel > Erreurs)
4. Une capture d'écran si possible

**Informations utiles :**
- Quel est votre nom de domaine ?
- Avez-vous créé un sous-domaine ou un dossier ?
- À quelle étape êtes-vous bloqué ?

---

## ✅ CHECKLIST FINALE

- [ ] Fichiers uploadés et décompressés dans le bon dossier
- [ ] Application Python créée dans cPanel
- [ ] Dépendances installées via Terminal (`pip install -r requirements.txt`)
- [ ] Base de données initialisée (`python database.py`)
- [ ] Fichier `passenger_wsgi.py` avec le bon chemin
- [ ] Fichier `.htaccess` avec le bon chemin
- [ ] Application redémarrée (`touch tmp/restart.txt`)
- [ ] URL testée dans le navigateur

**Si toutes les cases sont cochées et ça ne marche toujours pas, contactez-moi avec les détails !** 😊

# 🔧 SOLUTION : "Index of" - Configuration Python manquante

## Problème
Vous voyez une liste de fichiers ("Index of") au lieu de l'application.

**Cause** : L'application Python n'est pas configurée dans cPanel.

---

## ✅ SOLUTION EN 5 MINUTES

### ÉTAPE 1 : Aller dans Setup Python App

1. Connectez-vous à **cPanel**
2. Cherchez **"Setup Python App"** ou **"Application Python"**
   - Regardez dans les sections "Software", "Logiciels" ou cherchez avec la barre de recherche en haut
3. Cliquez dessus

---

### ÉTAPE 2 : Créer l'application Python

Cliquez sur le bouton **"+ Create Application"** ou **"Créer une application"**

---

### ÉTAPE 3 : Remplir le formulaire (IMPORTANT)

Voici exactement ce qu'il faut mettre :

#### 1️⃣ **Python version**
Choisissez : **3.11** ou **3.12** (la plus récente disponible)

#### 2️⃣ **Application root** (Très important !)
Regardez où vous avez mis les fichiers :

**Option A** - Si vos fichiers sont dans `public_html` directement :
```
public_html
```

**Option B** - Si vos fichiers sont dans un sous-dossier (par ex: `public_html/rss-manager`) :
```
public_html/rss-manager
```

**Option C** - Si vous avez créé un sous-domaine (par ex: rss.votre-domaine.com) :
```
rss.votre-domaine.com
```

#### 3️⃣ **Application URL**

**Option A** - Si fichiers dans `public_html` directement :
```
/
```

**Option B** - Si dans un sous-dossier `public_html/rss-manager` :
```
rss-manager
```
(sans le slash au début, juste `rss-manager`)

**Option C** - Si sous-domaine :
```
/
```

#### 4️⃣ **Application startup file**
```
passenger_wsgi.py
```

#### 5️⃣ **Application Entry point**
```
application
```

---

### ÉTAPE 4 : Cliquer sur "CREATE"

Attendez quelques secondes. Vous verrez un message de succès.

**✅ NOTEZ CETTE COMMANDE** qui apparaît (très important pour après) :
```bash
source /home/VOTRE_USER/virtualenv/CHEMIN/3.11/bin/activate
```

---

### ÉTAPE 5 : Installer les dépendances

1. Dans cPanel, ouvrez **"Terminal"** (dans Advanced/Avancé)

2. **Copiez-collez ces commandes UNE PAR UNE** :

```bash
# 1. Aller dans le dossier (ADAPTEZ LE CHEMIN)
cd public_html
# OU : cd public_html/rss-manager
# OU : cd rss.votre-domaine.com

# 2. Activer l'environnement virtuel
# COLLEZ LA COMMANDE NOTÉE À L'ÉTAPE 4, par exemple :
source /home/max0067/virtualenv/public_html/3.11/bin/activate

# 3. Installer les dépendances
pip install -r requirements.txt

# 4. Initialiser la base de données
python database.py
```

**Attendez que chaque commande se termine avant de passer à la suivante.**

Vous devriez voir :
- `Successfully installed Flask-3.0.0 ...` (après pip install)
- `Base de données initialisée avec succès!` (après python database.py)

---

### ÉTAPE 6 : Corriger passenger_wsgi.py

1. Retournez dans **Gestionnaire de fichiers**
2. Naviguez vers votre dossier
3. Trouvez `passenger_wsgi.py`
4. Cliquez droit > **Edit**
5. Trouvez cette ligne (ligne 10 environ) :
```python
INTERP = os.path.expanduser("~/public_html/rss-manager/venv/bin/python")
```

6. **REMPLACEZ-LA par** (en utilisant le chemin de l'étape 4) :

**Exemple pour public_html :**
```python
INTERP = os.path.expanduser("~/virtualenv/public_html/3.11/bin/python")
```

**Exemple pour public_html/rss-manager :**
```python
INTERP = os.path.expanduser("~/virtualenv/public_html/rss-manager/3.11/bin/python")
```

**Exemple pour sous-domaine rss.domaine.com :**
```python
INTERP = os.path.expanduser("~/virtualenv/rss.domaine.com/3.11/bin/python")
```

⚠️ **Changez `3.11` par votre version Python choisie** (3.9, 3.10, 3.12, etc.)

7. **Enregistrez** (Save Changes en haut à droite)

---

### ÉTAPE 7 : Corriger .htaccess

1. Dans **Gestionnaire de fichiers**, trouvez `.htaccess`
   - Si vous ne le voyez pas : **Paramètres** (en haut) > Cochez **"Afficher les fichiers cachés"**

2. Cliquez droit > **Edit**

3. Trouvez cette ligne :
```apache
PassengerAppRoot /home/VOTRE_USER/public_html/rss-manager
```

4. **REMPLACEZ VOTRE_USER par votre vrai nom d'utilisateur** :

**Exemple :**
```apache
PassengerAppRoot /home/max0067/public_html
```
ou
```apache
PassengerAppRoot /home/max0067/public_html/rss-manager
```
ou
```apache
PassengerAppRoot /home/max0067/rss.domaine.com
```

5. **Enregistrez**

---

### ÉTAPE 8 : Redémarrer l'application

**Dans le Terminal :**
```bash
# Aller dans votre dossier
cd public_html
# OU : cd public_html/rss-manager

# Créer le dossier tmp
mkdir -p tmp

# Redémarrer
touch tmp/restart.txt
```

**OU dans le Gestionnaire de fichiers :**
1. Créez un dossier `tmp`
2. Dedans, créez un fichier vide `restart.txt`

---

### ÉTAPE 9 : Tester !

Rafraîchissez votre navigateur (F5) ou allez sur :
- `https://votre-domaine.com`
- ou `https://votre-domaine.com/rss-manager`
- ou `https://rss.votre-domaine.com`

**✅ Vous devriez maintenant voir l'application !**

---

## 🆘 SI ÇA NE MARCHE TOUJOURS PAS

### Vérifier que l'application est active

1. Retournez dans **Setup Python App**
2. Vous devriez voir votre application listée
3. Vérifiez qu'elle est **"Running"** (pastille verte)
4. Si ce n'est pas le cas, cliquez sur **"Restart"**

### Voir les erreurs

1. Dans cPanel > **"Errors"** ou **"Erreurs"**
2. Regardez la dernière erreur
3. Copiez-moi le message complet

### Test rapide dans le Terminal

```bash
cd public_html  # Adaptez le chemin
source /home/VOTRE_USER/virtualenv/.../bin/activate
python -c "import flask; print('Flask marche!')"
python -c "from app import app; print('App marche!')"
```

Si vous avez une erreur, copiez-moi le message.

---

## 📝 RÉSUMÉ DES CHEMINS À ADAPTER

**Pour trouver votre nom d'utilisateur :**
Dans le Terminal, tapez : `whoami`

**Structure typique :**
- Nom d'utilisateur : `max0067` (par exemple)
- Chemin complet : `/home/max0067/public_html`
- Environnement virtuel : `/home/max0067/virtualenv/public_html/3.11`

**Dans passenger_wsgi.py :**
```python
INTERP = os.path.expanduser("~/virtualenv/[CHEMIN]/[VERSION]/bin/python")
```

**Dans .htaccess :**
```apache
PassengerAppRoot /home/[UTILISATEUR]/[CHEMIN]
```

---

## ✅ CHECKLIST

- [ ] Application Python créée dans cPanel
- [ ] Dépendances installées (`pip install -r requirements.txt`)
- [ ] Base de données créée (`python database.py`)
- [ ] `passenger_wsgi.py` corrigé avec le bon chemin
- [ ] `.htaccess` corrigé avec le bon nom d'utilisateur
- [ ] Application redémarrée (`touch tmp/restart.txt`)
- [ ] Page rafraîchie dans le navigateur

---

**Dites-moi :**
1. Quel est votre nom de domaine ?
2. Dans quel dossier sont vos fichiers ? (public_html, public_html/rss-manager, ou autre)
3. Avez-vous pu créer l'application Python dans cPanel ?

Je vais vous guider précisément ! 😊

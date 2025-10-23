# 🚀 DÉPLOYER LA V2 SUR DUSSELLE.FR

## 📋 ÉTAPES COMPLÈTES

### 🔍 ÉTAPE 1 : Uploader les fichiers sur o2switch

#### Option A : Via FTP (FileZilla)

1. **Connectez-vous avec FileZilla**
   - Hôte : `ftp.dusselle.fr` (ou l'hôte donné par o2switch)
   - Nom d'utilisateur : Votre user o2switch
   - Mot de passe : Votre password o2switch

2. **Naviguez vers le bon dossier**
   ```
   Si domaine principal : /home/VOTRE_USER/dusselle.fr
   OU
   Si public_html : /home/VOTRE_USER/public_html
   ```

3. **Uploadez TOUS les fichiers**
   - Sélectionnez tous les fichiers de votre projet local
   - Glissez-déposez vers le serveur
   - Attendez la fin du transfert

#### Option B : Via Gestionnaire de fichiers cPanel

1. **Connectez-vous à cPanel**
   - URL : `https://dusselle.fr:2083`
   - Ou : `https://cpanel.o2switch.fr`

2. **Ouvrez "Gestionnaire de fichiers"**

3. **Allez dans le dossier du site**
   - `public_html` pour le domaine principal
   - Ou créez un sous-domaine (ex: `rss.dusselle.fr`)

4. **Uploadez les fichiers**
   - Cliquez sur "Téléverser"
   - Sélectionnez tous les fichiers
   - OU uploadez un ZIP et extrayez-le

---

### ⚙️ ÉTAPE 2 : Configurer l'application Python dans cPanel

1. **Dans cPanel, cherchez "Setup Python App"**
   - Dans la section "Software" ou "Logiciels"

2. **Cliquez sur "Create Application"**

3. **Remplissez le formulaire :**

   **Python version :** Choisissez `3.11` ou `3.12` (la plus récente)

   **Application root :**
   ```
   dusselle.fr
   ```
   OU si vous avez mis dans public_html :
   ```
   public_html
   ```

   **Application URL :**
   ```
   /
   ```

   **Application startup file :**
   ```
   passenger_wsgi.py
   ```

   **Application Entry point :**
   ```
   application
   ```

4. **Cliquez sur "CREATE"**

5. **IMPORTANT : Notez la commande d'activation de l'environnement virtuel**
   Elle ressemble à :
   ```bash
   source /home/VOTRE_USER/virtualenv/dusselle.fr/3.11/bin/activate
   ```

---

### 🔧 ÉTAPE 3 : Installer les dépendances

1. **Ouvrez le Terminal dans cPanel**
   - Cherchez "Terminal" dans cPanel

2. **Exécutez ces commandes UNE PAR UNE :**

```bash
# 1. Aller dans le dossier
cd dusselle.fr
# OU : cd public_html

# 2. Vérifier que les fichiers sont là
ls -la

# Vous devriez voir :
# app_v2.py, database_v2.py, requirements.txt, etc.

# 3. Activer l'environnement virtuel (utilisez LA COMMANDE NOTÉE À L'ÉTAPE 2)
source /home/VOTRE_USER/virtualenv/dusselle.fr/3.11/bin/activate

# 4. Installer les dépendances
pip install -r requirements.txt

# 5. Initialiser la base de données
python database_v2.py
```

Vous devriez voir :
```
✅ Utilisateur admin créé (username: admin, password: admin123)
✅ Base de données initialisée avec succès!
```

---

### 📝 ÉTAPE 4 : Modifier passenger_wsgi.py

1. **Dans Gestionnaire de fichiers, trouvez `passenger_wsgi.py`**

2. **Cliquez droit → Edit**

3. **Modifiez la ligne avec le chemin :**

   **REMPLACEZ :**
   ```python
   INTERP = os.path.expanduser("~/public_html/rss-manager/venv/bin/python")
   ```

   **PAR :**
   ```python
   INTERP = os.path.expanduser("~/virtualenv/dusselle.fr/3.11/bin/python")
   ```

   ⚠️ **Changez `3.11` par votre version Python** (celle que vous avez choisie)

   ⚠️ **Si vous êtes dans public_html, utilisez :**
   ```python
   INTERP = os.path.expanduser("~/virtualenv/public_html/3.11/bin/python")
   ```

4. **À la fin du fichier, changez aussi :**

   **REMPLACEZ :**
   ```python
   from app import app as application
   ```

   **PAR :**
   ```python
   from app_v2 import app as application
   ```

5. **Enregistrez le fichier**

---

### 📝 ÉTAPE 5 : Modifier .htaccess

1. **Dans Gestionnaire de fichiers, trouvez `.htaccess`**
   - Si vous ne le voyez pas : Paramètres (en haut) → Cochez "Afficher fichiers cachés"

2. **Cliquez droit → Edit**

3. **Modifiez le chemin :**

   **REMPLACEZ :**
   ```apache
   PassengerAppRoot /home/VOTRE_USER/public_html/rss-manager
   ```

   **PAR :**
   ```apache
   PassengerAppRoot /home/VOTRE_USER/dusselle.fr
   ```

   ⚠️ **Remplacez VOTRE_USER par votre vrai nom d'utilisateur !**

   Pour trouver votre user, dans le Terminal tapez : `whoami`

4. **Enregistrez**

---

### 🔄 ÉTAPE 6 : Redémarrer l'application

#### Dans le Terminal :
```bash
cd dusselle.fr
mkdir -p tmp
touch tmp/restart.txt
```

#### OU dans Gestionnaire de fichiers :
1. Créez un dossier `tmp`
2. Dedans, créez un fichier vide `restart.txt`

---

### ✅ ÉTAPE 7 : Tester !

1. **Ouvrez votre navigateur**
2. **Allez sur : https://dusselle.fr/login**

Vous devriez voir la page de connexion !

---

## 🆘 PROBLÈMES COURANTS

### Problème 1 : "Index of" - Liste de fichiers

**Cause :** L'application Python n'est pas configurée

**Solution :**
1. Vérifiez dans cPanel > Setup Python App
2. Votre app doit être listée et "Running" (vert)
3. Si elle est rouge, cliquez sur "Restart"

### Problème 2 : Erreur 500

**Vérifiez les logs :**
1. cPanel > "Erreurs" ou "Errors"
2. Regardez la dernière erreur
3. Envoyez-moi le message

**Vérifiez les chemins :**
```bash
# Dans le Terminal
cd dusselle.fr
cat passenger_wsgi.py | grep INTERP
cat .htaccess | grep PassengerAppRoot
```

### Problème 3 : Module non trouvé

```bash
cd dusselle.fr
source /home/VOTRE_USER/virtualenv/dusselle.fr/3.11/bin/activate
pip install -r requirements.txt --force-reinstall
touch tmp/restart.txt
```

### Problème 4 : Base de données verrouillée

```bash
cd dusselle.fr
chmod 666 rss_feeds.db
touch tmp/restart.txt
```

---

## 🔍 VÉRIFICATION RAPIDE

### Dans le Terminal, exécutez :

```bash
cd dusselle.fr

# Vérifier les fichiers
ls -la app_v2.py database_v2.py passenger_wsgi.py

# Vérifier la base de données
ls -la rss_feeds.db

# Vérifier les dépendances
source /home/VOTRE_USER/virtualenv/dusselle.fr/3.11/bin/activate
python -c "import flask; print('Flask OK')"
python -c "import feedparser; print('Feedparser OK')"
```

Tout doit afficher "OK".

---

## 📞 INFORMATIONS NÉCESSAIRES

Pour vous aider précisément, dites-moi :

1. **Quel message d'erreur voyez-vous ?**
   - Page blanche ?
   - Erreur 500 ?
   - "Index of" ?
   - Autre ?

2. **Où sont les fichiers ?**
   - Dans `public_html` ?
   - Dans `dusselle.fr` ?
   - Ailleurs ?

3. **Capture d'écran si possible**

4. **Dernière ligne du log d'erreur**
   - cPanel > Erreurs > Dernière ligne

---

## ✅ CHECKLIST DE VÉRIFICATION

- [ ] Fichiers uploadés sur le serveur
- [ ] Application Python créée dans cPanel
- [ ] Dépendances installées (`pip install -r requirements.txt`)
- [ ] Base de données initialisée (`python database_v2.py`)
- [ ] `passenger_wsgi.py` modifié avec bon chemin
- [ ] `passenger_wsgi.py` : `from app_v2 import app as application`
- [ ] `.htaccess` modifié avec bon chemin
- [ ] Application redémarrée (`touch tmp/restart.txt`)
- [ ] Testé sur https://dusselle.fr/login

---

## 📋 FICHIER passenger_wsgi.py COMPLET POUR DUSSELLE.FR

Créez ce fichier ou remplacez le contenu :

```python
import sys
import os

# ⚠️ MODIFIEZ CE CHEMIN avec votre nom d'utilisateur et version Python
INTERP = "/home/VOTRE_USER/virtualenv/dusselle.fr/3.11/bin/python"

if os.path.isfile(INTERP):
    if sys.executable != INTERP:
        os.execl(INTERP, INTERP, *sys.argv)
else:
    print(f"ERREUR: Python non trouvé à: {INTERP}")

sys.path.insert(0, os.path.dirname(__file__))

# IMPORTANT : Importer app_v2 pour la V2
from app_v2 import app as application
```

---

**Dites-moi exactement où vous êtes bloqué et je vous guide ! 😊**

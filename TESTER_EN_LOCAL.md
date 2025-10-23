# 🚀 TESTER L'APPLICATION EN LOCAL (5 minutes)

Si vous voulez voir l'application fonctionner sur votre ordinateur avant de la mettre en ligne, c'est très facile !

---

## 📋 PRÉREQUIS

Vous devez avoir installé :
- ✅ **Python 3.7 ou plus récent**
- ✅ **pip** (gestionnaire de paquets Python)

### Vérifier si Python est installé

Ouvrez un terminal/invite de commande et tapez :

**Sur Windows :**
```bash
python --version
```

**Sur Mac/Linux :**
```bash
python3 --version
```

Vous devriez voir quelque chose comme : `Python 3.11.0`

---

## 🎬 LANCEMENT RAPIDE

### Étape 1 : Télécharger le projet

Si vous avez Git :
```bash
git clone <url-du-repo>
cd max0067
```

Ou téléchargez le ZIP depuis GitHub et décompressez-le.

---

### Étape 2 : Installer les dépendances

Ouvrez un terminal dans le dossier du projet, puis :

**Sur Windows :**
```bash
# Créer un environnement virtuel (optionnel mais recommandé)
python -m venv venv
venv\Scripts\activate

# Installer les dépendances
pip install -r requirements.txt

# Initialiser la base de données
python database.py
```

**Sur Mac/Linux :**
```bash
# Créer un environnement virtuel (optionnel mais recommandé)
python3 -m venv venv
source venv/bin/activate

# Installer les dépendances
pip install -r requirements.txt

# Initialiser la base de données
python database.py
```

Vous devriez voir : `Base de données initialisée avec succès!`

---

### Étape 3 : Lancer l'application

```bash
python app.py
```

Vous verrez quelque chose comme :
```
 * Serving Flask app 'app'
 * Debug mode: on
WARNING: This is a development server. Do not use it in a production deployment.
 * Running on http://127.0.0.1:5000
 * Running on http://0.0.0.0:5000
Press CTRL+C to quit
```

---

### Étape 4 : Ouvrir dans le navigateur

Ouvrez votre navigateur et allez sur :

**http://localhost:5000**

ou

**http://127.0.0.1:5000**

🎉 **Vous devriez voir l'application !**

---

## 🎯 PREMIÈRE UTILISATION

### 1. L'écran est vide au début

C'est normal ! Vous n'avez pas encore de flux RSS.

### 2. Ajouter votre premier flux

Cliquez sur **"+ Ajouter"** dans la barre latérale.

**Exemples de flux RSS à tester :**

#### 🇫🇷 Flux français
```
Titre : Le Monde - Actualités
URL : https://www.lemonde.fr/rss/une.xml

Titre : France Info
URL : https://www.francetvinfo.fr/titres.rss

Titre : Libération
URL : https://www.liberation.fr/arc/outboundfeeds/rss-all/
```

#### 🇺🇸 Flux anglais
```
Titre : TechCrunch
URL : https://techcrunch.com/feed/

Titre : The Verge
URL : https://www.theverge.com/rss/index.xml

Titre : BBC News
URL : http://feeds.bbci.co.uk/news/rss.xml
```

#### 🎮 Tech et Gaming
```
Titre : Numerama
URL : https://www.numerama.com/feed/

Titre : IGN
URL : https://feeds.ign.com/ign/all

Titre : GameSpot
URL : https://www.gamespot.com/feeds/mashup/
```

### 3. Attendre quelques secondes

L'application va récupérer les derniers articles du flux.
Une notification vous dira "X nouveaux articles récupérés".

### 4. Explorer l'interface

- **Cliquez sur un flux** dans la barre latérale pour voir ses articles
- **Cliquez sur un article** pour le lire en entier
- **Cochez "Non lus seulement"** pour filtrer
- **Cliquez sur "Actualiser"** pour mettre à jour un flux

---

## 🎨 CE QUE VOUS VERREZ

### En-tête (violet/bleu)
```
┌─────────────────────────────────────────────┐
│ 🌐 Gestionnaire de Flux RSS                 │
│ 3 flux | 45 articles | 12 non lus           │
└─────────────────────────────────────────────┘
```

### Barre latérale (gris clair)
```
┌───────────────────┐
│ 📁 Flux RSS       │
│ [+ Ajouter]       │
│                   │
│ ┌───────────────┐ │
│ │ TechCrunch    │ │
│ │ 15 articles   │ │
│ │ [Actualiser]  │ │
│ │ [Modifier]    │ │
│ │ [Supprimer]   │ │
│ └───────────────┘ │
└───────────────────┘
```

### Zone des articles (blanc)
```
┌─────────────────────────────────────────┐
│ Python 3.12 Released                    │
│ TechCrunch • Il y a 2 heures            │
│ The latest version brings new features  │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ New AI Model Announced                  │
│ The Verge • Il y a 5 heures             │
│ OpenAI announces GPT-5 release date...  │
└─────────────────────────────────────────┘
```

---

## 🎬 FONCTIONNALITÉS À TESTER

### ✅ Ajouter plusieurs flux
Ajoutez 3-4 flux différents pour voir l'application bien remplie.

### ✅ Lire des articles
Cliquez sur un article → Une fenêtre s'ouvre avec le contenu complet.

### ✅ Filtrer les non lus
Cochez la case "Non lus seulement" pour voir uniquement les nouveaux articles.

### ✅ Actualiser un flux
Cliquez sur "Actualiser" à côté d'un flux pour récupérer les derniers articles.

### ✅ Actualiser tous les flux
Cliquez sur "Tout mettre à jour" pour mettre à jour tous vos flux en une fois.

### ✅ Modifier un flux
Changez le titre, l'intervalle de mise à jour, ou désactivez un flux.

### ✅ Supprimer un flux
Supprimez un flux que vous ne voulez plus suivre.

### ✅ Mise à jour automatique
Laissez l'application tourner 30 minutes → Elle mettra à jour automatiquement tous les flux !

---

## 🔍 EXPLORER LES DÉTAILS

### Notifications
Chaque action affiche une notification dans le coin supérieur droit :
- 🟢 Succès : "Flux ajouté avec succès"
- 🔴 Erreur : "Impossible de récupérer le flux"
- 🔵 Info : "Mise à jour en cours..."

### Animations
- Les cartes glissent au survol
- Les modals apparaissent avec une animation fluide
- Les notifications glissent depuis la droite

### Statistiques
En haut de la page, vous voyez en temps réel :
- Nombre de flux RSS
- Nombre total d'articles
- Nombre d'articles non lus

### Base de données
Tous les articles sont sauvegardés dans `rss_feeds.db`.
Même si vous fermez l'application, vos flux et articles restent !

---

## 🛑 ARRÊTER L'APPLICATION

Dans le terminal, appuyez sur **Ctrl+C** (ou **Cmd+C** sur Mac).

---

## 🔄 RELANCER L'APPLICATION

Il suffit de refaire :
```bash
python app.py
```

Vos flux et articles seront toujours là ! 😊

---

## 📊 DONNÉES DE TEST

Si vous voulez tester avec beaucoup de données rapidement, ajoutez ces 10 flux :

```python
# Dans le terminal Python
python

>>> from database import add_feed
>>> add_feed("Le Monde", "https://www.lemonde.fr/rss/une.xml")
>>> add_feed("TechCrunch", "https://techcrunch.com/feed/")
>>> add_feed("The Verge", "https://www.theverge.com/rss/index.xml")
>>> add_feed("France Info", "https://www.francetvinfo.fr/titres.rss")
>>> add_feed("BBC News", "http://feeds.bbci.co.uk/news/rss.xml")
>>> exit()

# Puis mettre à jour tous les flux
python -c "from rss_updater import update_all_feeds; update_all_feeds()"
```

---

## 💡 ASTUCES

### Tester avec de vrais flux
Utilisez vos sites d'actualités préférés ! La plupart ont un flux RSS.
Cherchez "RSS" dans le footer du site.

### Voir les logs
Dans le terminal où tourne l'application, vous verrez tous les logs en temps réel.

### Ouvrir plusieurs onglets
Vous pouvez ouvrir l'application dans plusieurs onglets, ça fonctionne !

### Tester la mise à jour automatique
Laissez l'application tourner 30 minutes et vous verrez de nouveaux articles apparaître automatiquement.

---

## 🆘 PROBLÈMES COURANTS

### Port 5000 déjà utilisé
Erreur : `Address already in use`

**Solution :**
```bash
# Windows
netstat -ano | findstr :5000
taskkill /PID <PID> /F

# Mac/Linux
lsof -i :5000
kill -9 <PID>
```

Ou modifiez le port dans `app.py` :
```python
app.run(debug=True, host='0.0.0.0', port=5001)  # Port 5001 au lieu de 5000
```

### Module manquant
Erreur : `ModuleNotFoundError: No module named 'flask'`

**Solution :**
```bash
pip install -r requirements.txt
```

### Base de données verrouillée
Erreur : `database is locked`

**Solution :**
Fermez l'application (Ctrl+C) et relancez-la.

---

## 📸 FAIRE DES CAPTURES D'ÉCRAN

Une fois l'application lancée :
1. Ajoutez 3-4 flux
2. Attendez que les articles soient récupérés
3. Faites des captures d'écran !

Vous pouvez ensuite les partager ou les utiliser comme démo.

---

## 🎯 CHECKLIST DE TEST

- [ ] Application lancée et accessible
- [ ] Au moins un flux RSS ajouté
- [ ] Articles récupérés et affichés
- [ ] Clic sur un article pour le lire
- [ ] Filtre "Non lus seulement" testé
- [ ] Actualisation d'un flux testée
- [ ] Modification d'un flux testée
- [ ] Suppression d'un flux testée
- [ ] Statistiques affichées correctement
- [ ] Notifications visibles

---

**Amusez-vous bien à tester l'application ! 🚀**

Si vous avez des questions ou si quelque chose ne fonctionne pas, n'hésitez pas à demander ! 😊

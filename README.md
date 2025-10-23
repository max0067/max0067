# Gestionnaire de Flux RSS

Une application web complète pour gérer, suivre et lire vos flux RSS favoris avec mise à jour automatique.

## Fonctionnalités

- **Gestion des flux RSS** : Ajoutez, modifiez et supprimez vos flux RSS
- **Mise à jour automatique** : Les flux sont automatiquement mis à jour toutes les 30 minutes
- **Base de données** : Tous les articles sont sauvegardés dans une base de données SQLite
- **Interface moderne** : Interface web intuitive et responsive
- **Lecture d'articles** : Lisez vos articles directement dans l'application
- **Filtres** : Filtrez les articles non lus
- **Statistiques** : Visualisez le nombre de flux, articles et articles non lus

## Technologies utilisées

- **Backend** : Python 3, Flask
- **Base de données** : SQLite
- **Frontend** : HTML5, CSS3, JavaScript (Vanilla)
- **Parser RSS** : feedparser
- **Scheduler** : APScheduler

## Installation

### Prérequis

- Python 3.7 ou supérieur
- pip (gestionnaire de paquets Python)

### Étapes d'installation

1. **Cloner le dépôt** :
```bash
git clone <url-du-depot>
cd max0067
```

2. **Créer un environnement virtuel** (recommandé) :
```bash
python3 -m venv venv
source venv/bin/activate  # Sur Linux/Mac
# ou
venv\Scripts\activate  # Sur Windows
```

3. **Installer les dépendances** :
```bash
pip install -r requirements.txt
```

4. **Initialiser la base de données** :
```bash
python database.py
```

## Déploiement sur o2switch

Pour déployer cette application sur un hébergement o2switch, consultez les guides détaillés :

- **[Guide Rapide o2switch](GUIDE_RAPIDE_O2SWITCH.md)** - Démarrage rapide en quelques étapes
- **[Guide Complet o2switch](DEPLOIEMENT_O2SWITCH.md)** - Documentation détaillée du déploiement

### Installation automatique sur o2switch

```bash
# Connectez-vous en SSH à votre serveur o2switch
ssh votre_utilisateur@ssh.o2switch.net

# Placez-vous dans le bon dossier
cd ~/public_html/rss-manager

# Téléchargez le projet (via Git ou FTP)
# Puis lancez le script de déploiement
chmod +x deploy_o2switch.sh
./deploy_o2switch.sh
```

## Utilisation en local

### Démarrer l'application

```bash
python app.py
```

L'application sera accessible à l'adresse : **http://localhost:5000**

### Ajouter un flux RSS

1. Cliquez sur le bouton **"+ Ajouter"** dans la barre latérale
2. Remplissez les informations :
   - **Titre** : Le nom du flux (ex: "Le Monde - Actualités")
   - **URL** : L'adresse du flux RSS (ex: https://www.lemonde.fr/rss/une.xml)
   - **Description** : Une description optionnelle
   - **Intervalle de mise à jour** : Fréquence de mise à jour en minutes (par défaut: 30)
   - **Flux actif** : Cochez pour activer la mise à jour automatique
3. Cliquez sur **"Enregistrer"**

Le flux sera automatiquement mis à jour et les articles seront récupérés.

### Gérer les flux

- **Voir les articles d'un flux** : Cliquez sur un flux dans la barre latérale
- **Actualiser un flux** : Cliquez sur "Actualiser" dans la carte du flux
- **Modifier un flux** : Cliquez sur "Modifier"
- **Supprimer un flux** : Cliquez sur "Supprimer" (attention: supprime aussi tous les articles)

### Lire les articles

- Cliquez sur un article pour voir son contenu complet
- Les articles sont automatiquement marqués comme lus
- Utilisez la case "Non lus seulement" pour filtrer

### Mise à jour automatique

- Les flux actifs sont automatiquement mis à jour toutes les 30 minutes
- Vous pouvez forcer une mise à jour avec le bouton **"Tout mettre à jour"**

## Structure du projet

```
max0067/
├── app.py                 # Application Flask principale
├── database.py            # Gestion de la base de données
├── rss_updater.py        # Système de mise à jour des flux
├── requirements.txt       # Dépendances Python
├── rss_feeds.db          # Base de données SQLite (créée automatiquement)
├── templates/
│   └── index.html        # Page HTML principale
└── static/
    ├── css/
    │   └── style.css     # Styles CSS
    └── js/
        └── app.js        # JavaScript de l'interface
```

## API REST

L'application expose une API REST complète :

### Flux RSS

- `GET /api/feeds` - Liste tous les flux
- `GET /api/feeds/<id>` - Récupère un flux spécifique
- `POST /api/feeds` - Ajoute un nouveau flux
- `PUT /api/feeds/<id>` - Met à jour un flux
- `DELETE /api/feeds/<id>` - Supprime un flux
- `POST /api/feeds/<id>/update` - Force la mise à jour d'un flux
- `POST /api/feeds/update-all` - Force la mise à jour de tous les flux

### Articles

- `GET /api/articles` - Liste les articles (avec pagination et filtres)
- `PUT /api/articles/<id>/read` - Marque un article comme lu/non lu

### Statistiques

- `GET /api/stats` - Récupère les statistiques globales

## Configuration

Vous pouvez modifier les paramètres dans `app.py` :

- **Port** : Changez `port=5000` dans `app.run()`
- **Intervalle de mise à jour global** : Modifiez `minutes=30` dans la configuration du scheduler
- **Debug mode** : Changez `debug=True` à `debug=False` pour la production

## Exemples de flux RSS populaires

Voici quelques flux RSS que vous pouvez ajouter :

- **Le Monde** : https://www.lemonde.fr/rss/une.xml
- **Le Figaro** : https://www.lefigaro.fr/rss/figaro_actualites.xml
- **Libération** : https://www.liberation.fr/arc/outboundfeeds/rss-all/
- **France Info** : https://www.francetvinfo.fr/titres.rss
- **TechCrunch** : https://techcrunch.com/feed/
- **The Verge** : https://www.theverge.com/rss/index.xml

## Dépannage

### La base de données n'est pas créée

Exécutez manuellement :
```bash
python database.py
```

### Erreur lors de la récupération d'un flux

- Vérifiez que l'URL du flux RSS est correcte
- Certains sites peuvent bloquer les requêtes automatiques
- Vérifiez votre connexion Internet

### L'application ne démarre pas

- Vérifiez que toutes les dépendances sont installées
- Vérifiez que le port 5000 n'est pas déjà utilisé
- Consultez les logs dans le terminal

## Améliorations futures possibles

- Authentification utilisateur
- Support de plusieurs utilisateurs
- Catégorisation des flux
- Recherche dans les articles
- Export des articles (PDF, EPUB)
- Notifications pour les nouveaux articles
- Mode sombre
- Application mobile

## Licence

Ce projet est libre d'utilisation pour un usage personnel ou éducatif.

## Support

Pour toute question ou problème, veuillez ouvrir une issue sur le dépôt GitHub.

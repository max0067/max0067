# Guide de Déploiement sur o2switch

Ce guide vous explique comment déployer l'application de gestion de flux RSS sur votre hébergement o2switch.

## Prérequis

- Un compte d'hébergement o2switch actif
- Accès SSH activé (demander au support si nécessaire)
- Accès à cPanel
- Un nom de domaine ou sous-domaine configuré

## Méthode 1 : Déploiement via SSH (Recommandé)

### Étape 1 : Connexion SSH

Connectez-vous à votre serveur o2switch via SSH :

```bash
ssh votre_utilisateur@votre_domaine.com
# ou
ssh votre_utilisateur@ssh.o2switch.net
```

### Étape 2 : Préparer l'environnement

```bash
# Aller dans le répertoire web
cd ~/public_html

# Créer un sous-dossier pour l'application (optionnel)
mkdir rss-manager
cd rss-manager

# Ou pour un sous-domaine, aller dans son dossier
# cd ~/rss.votre-domaine.com
```

### Étape 3 : Cloner le projet

```bash
# Si vous avez Git sur o2switch
git clone <URL_DE_VOTRE_REPO> .

# OU télécharger via FTP (voir Méthode 2)
```

### Étape 4 : Configurer Python et l'environnement virtuel

O2switch supporte Python. Vérifiez la version disponible :

```bash
python --version
python3 --version
```

Créez un environnement virtuel :

```bash
# Utiliser Python 3
python3 -m venv venv

# Activer l'environnement virtuel
source venv/bin/activate

# Mettre à jour pip
pip install --upgrade pip

# Installer les dépendances
pip install -r requirements.txt
```

### Étape 5 : Initialiser la base de données

```bash
python database.py
```

### Étape 6 : Configurer Passenger WSGI

O2switch utilise Passenger pour exécuter les applications Python. Créez le fichier de configuration :

Créez `passenger_wsgi.py` (déjà fourni dans le projet) et `.htaccess` (déjà fourni).

### Étape 7 : Configurer l'application

Modifiez `app.py` pour le mode production :

- Assurez-vous que `debug=False`
- Le port n'a pas d'importance car Passenger gère cela

### Étape 8 : Redémarrer l'application

```bash
# Passenger redémarre automatiquement quand on touche le fichier
touch tmp/restart.txt

# Ou créer le dossier tmp si nécessaire
mkdir -p tmp
touch tmp/restart.txt
```

### Étape 9 : Accéder à l'application

Accédez à votre application via :
- `https://votre-domaine.com/rss-manager` (si dans un sous-dossier)
- `https://rss.votre-domaine.com` (si sous-domaine)

## Méthode 2 : Déploiement via FTP/cPanel

### Étape 1 : Télécharger les fichiers

1. Connectez-vous au **Gestionnaire de fichiers** dans cPanel
2. Naviguez vers `public_html` ou le dossier de votre sous-domaine
3. Créez un dossier `rss-manager`
4. Téléchargez tous les fichiers du projet via FTP ou le gestionnaire de fichiers

### Étape 2 : Configurer Python via cPanel

1. Dans cPanel, cherchez **"Setup Python App"** ou **"Application Python"**
2. Cliquez sur **"Create Application"**
3. Configurez :
   - **Version Python** : 3.8 ou supérieure
   - **Répertoire de l'application** : `rss-manager` (ou votre dossier)
   - **URL de l'application** : `/rss-manager` ou votre sous-domaine
   - **Point d'entrée** : `passenger_wsgi.py`

4. Cliquez sur **"Create"**

### Étape 3 : Installer les dépendances via cPanel

1. Dans l'interface Python App, trouvez votre application
2. Cliquez sur **"Enter to the virtual environment"** pour obtenir la commande
3. Ouvrez le **Terminal** dans cPanel
4. Exécutez la commande d'activation de l'environnement virtuel
5. Installez les dépendances :

```bash
pip install -r requirements.txt
```

### Étape 4 : Initialiser la base de données

Dans le terminal cPanel :

```bash
cd ~/public_html/rss-manager  # Adaptez le chemin
source venv/bin/activate
python database.py
```

### Étape 5 : Redémarrer l'application

1. Dans l'interface Python App de cPanel
2. Cliquez sur **"Restart"** à côté de votre application

## Configuration spécifique pour o2switch

### Permissions des fichiers

Assurez-vous que les permissions sont correctes :

```bash
chmod 755 ~/public_html/rss-manager
chmod 644 ~/public_html/rss-manager/*.py
chmod 755 ~/public_html/rss-manager/start.sh
chmod 666 ~/public_html/rss-manager/rss_feeds.db  # Base de données accessible en écriture
```

### Variables d'environnement

Si vous avez besoin de définir des variables d'environnement, créez un fichier `.env` :

```bash
FLASK_ENV=production
DATABASE_PATH=/home/votre_user/public_html/rss-manager/rss_feeds.db
```

### Tâches Cron pour les mises à jour automatiques

L'application utilise APScheduler qui fonctionne tant que l'application tourne. Sur un hébergement mutualisé, vous pouvez aussi utiliser les tâches cron de cPanel :

1. Dans cPanel, allez dans **"Tâches Cron"**
2. Ajoutez une nouvelle tâche :

```bash
# Toutes les 30 minutes
*/30 * * * * cd ~/public_html/rss-manager && source venv/bin/activate && python -c "from rss_updater import update_all_feeds; update_all_feeds()"
```

## Structure recommandée sur o2switch

```
/home/votre_user/
├── public_html/
│   ├── rss-manager/              # Votre application
│   │   ├── app.py
│   │   ├── database.py
│   │   ├── rss_updater.py
│   │   ├── passenger_wsgi.py
│   │   ├── .htaccess
│   │   ├── requirements.txt
│   │   ├── rss_feeds.db
│   │   ├── venv/                 # Environnement virtuel
│   │   ├── static/
│   │   ├── templates/
│   │   └── tmp/
│   │       └── restart.txt
│   └── index.html                # Votre site principal
```

## Sous-domaine (Option recommandée)

Pour une meilleure organisation, créez un sous-domaine :

### Via cPanel

1. Allez dans **"Sous-domaines"**
2. Créez un sous-domaine : `rss.votre-domaine.com`
3. Le dossier sera créé automatiquement : `~/rss.votre-domaine.com`
4. Placez les fichiers de l'application dans ce dossier
5. Configurez l'application Python pour ce dossier

## Dépannage

### L'application ne démarre pas

1. Vérifiez les logs d'erreur dans cPanel > **"Erreurs"**
2. Vérifiez que toutes les dépendances sont installées
3. Vérifiez les permissions des fichiers
4. Essayez de redémarrer : `touch tmp/restart.txt`

### Erreur 500

1. Vérifiez le fichier `.htaccess`
2. Vérifiez que `passenger_wsgi.py` est correctement configuré
3. Consultez les logs : `~/logs/` ou dans cPanel

### Base de données non accessible

```bash
# Vérifiez les permissions
chmod 666 rss_feeds.db

# Ou déplacez la base de données hors du répertoire web
# et mettez à jour le chemin dans database.py
```

### Python ou pip non trouvé

```bash
# Utilisez le chemin complet
/usr/bin/python3 --version
/usr/bin/python3 -m pip install -r requirements.txt
```

### Le scheduler APScheduler ne fonctionne pas

Sur un hébergement mutualisé, APScheduler peut avoir des limitations. Utilisez plutôt les tâches cron de cPanel (voir ci-dessus).

## Optimisations pour la production

### 1. Désactiver le mode debug

Dans `app.py`, ligne finale :

```python
if __name__ == '__main__':
    app.run(debug=False, host='0.0.0.0', port=5000)
```

### 2. Utiliser un fichier de configuration

Créez `config.py` :

```python
import os

class Config:
    DEBUG = False
    TESTING = False
    DATABASE_PATH = os.path.join(os.path.dirname(__file__), 'rss_feeds.db')
```

### 3. Logs

Configurez les logs pour la production dans `app.py` :

```python
import logging
from logging.handlers import RotatingFileHandler

if not app.debug:
    file_handler = RotatingFileHandler('logs/app.log', maxBytes=10240, backupCount=10)
    file_handler.setFormatter(logging.Formatter(
        '%(asctime)s %(levelname)s: %(message)s [in %(pathname)s:%(lineno)d]'
    ))
    file_handler.setLevel(logging.INFO)
    app.logger.addHandler(file_handler)
    app.logger.setLevel(logging.INFO)
```

## Support o2switch

Si vous rencontrez des problèmes spécifiques à o2switch :

- **Support technique** : Via votre espace client o2switch
- **Documentation** : https://faq.o2switch.fr/
- **Téléphone** : Contactez le support o2switch

## Sécurité

### HTTPS

o2switch fournit des certificats SSL gratuits via Let's Encrypt :

1. Dans cPanel, allez dans **"SSL/TLS Status"**
2. Activez SSL pour votre domaine/sous-domaine

### Sécuriser la base de données

```bash
# Ne pas mettre la base de données dans public_html
# Déplacez-la dans un dossier privé
mv rss_feeds.db ~/private/
```

Puis modifiez `database.py` :

```python
import os
DATABASE_NAME = os.path.expanduser('~/private/rss_feeds.db')
```

### Fichier .htaccess

Un fichier `.htaccess` de base est fourni pour la sécurité.

## Mise à jour de l'application

Pour mettre à jour l'application :

```bash
# Via SSH
cd ~/public_html/rss-manager
git pull  # Si vous utilisez Git
source venv/bin/activate
pip install -r requirements.txt --upgrade
touch tmp/restart.txt  # Redémarrer
```

## Ressources

- [Documentation o2switch sur Python](https://faq.o2switch.fr/)
- [Documentation Flask](https://flask.palletsprojects.com/)
- [Documentation Passenger](https://www.phusionpassenger.com/)

---

**Note importante** : o2switch est un hébergement mutualisé. Les performances peuvent être limitées par rapport à un VPS. Pour une application avec beaucoup de flux RSS et de trafic, considérez un VPS ou serveur dédié.

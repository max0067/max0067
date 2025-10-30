# Correction de l'erreur 404 sur dusselle.fr

## Problème identifié et corrigé

J'ai identifié et corrigé **2 problèmes majeurs** qui causaient l'erreur 404 sur votre site :

### 1. Pages d'erreur manquantes
Le fichier `.htaccess` référençait des pages d'erreur qui n'existaient pas :
- ✅ **Créé** : `404.html` - Page d'erreur 404 avec design moderne
- ✅ **Créé** : `500.html` - Page d'erreur serveur avec design moderne

### 2. Mauvais fichier d'application chargé (PROBLÈME PRINCIPAL)
Le fichier `passenger_wsgi.py` importait le mauvais fichier d'application :
- ❌ **Avant** : `from app import app` (version basique sans authentification)
- ✅ **Après** : `from app_v2 import app` (version complète avec toutes les fonctionnalités)

**app_v2.py** contient toutes les routes essentielles :
- `/login` et `/register` (authentification)
- `/` (page d'accueil)
- `/dashboard` (tableau de bord)
- `/feeds` (gestion des flux RSS)
- `/admin` (panneau d'administration)

## Actions requises sur votre serveur o2switch

Pour que les corrections prennent effet, vous devez :

### 1. Mettre à jour les fichiers sur le serveur
```bash
cd /home/VOTRE_USER/dusselle.fr  # Ou le chemin de votre installation
git pull origin main  # Ou la branche appropriée
```

### 2. Vérifier la configuration .htaccess
Ouvrez le fichier `.htaccess` et **modifiez la ligne 7** pour correspondre à votre installation réelle :

```apache
PassengerAppRoot /home/VOTRE_USER/dusselle.fr
```

Remplacez `VOTRE_USER` par votre nom d'utilisateur o2switch (celui que vous voyez avec `whoami` dans le terminal).

### 3. Vérifier passenger_wsgi.py
Si vous avez un environnement virtuel, modifiez la ligne 11 de `passenger_wsgi.py` :

```python
INTERP = os.path.expanduser("~/dusselle.fr/venv/bin/python")
```

Ou si vous n'utilisez pas de virtualenv, utilisez le fichier simplifié :
```bash
cp passenger_wsgi_simple.py passenger_wsgi.py
```

### 4. Redémarrer l'application
Pour redémarrer l'application Passenger :
```bash
mkdir -p tmp
touch tmp/restart.txt
```

Ou via cPanel :
1. Allez dans "Setup Python App"
2. Cliquez sur "Restart" à côté de votre application

## Vérification

Après ces étapes, testez votre site :
1. Visitez `https://dusselle.fr` - Vous devriez voir la page de connexion
2. Si vous voyez toujours une erreur 404, vérifiez les logs d'erreur dans cPanel

## Fichiers de logs à consulter

Dans cPanel > Setup Python App > Application logs, vous devriez voir :
- Les erreurs de démarrage de l'application
- Les problèmes d'importation
- Les erreurs de base de données

## Besoin d'aide supplémentaire ?

Si le problème persiste, fournissez-moi :
1. Le contenu complet des logs d'erreur
2. La sortie de `whoami` dans le terminal
3. Le chemin complet de votre installation (résultat de `pwd`)

Cela me permettra d'identifier précisément le problème de configuration.

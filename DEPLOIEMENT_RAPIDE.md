# Déploiement rapide sur dusselle.fr (o2switch)

## Problème rencontré
- Python 3.6 sur le serveur (ne supporte pas Flask 3.0.0)
- Impossibilité de créer un environnement virtuel
- Branches git divergentes

## Solution : Déploiement sans environnement virtuel

### 1. Résoudre le problème git

```bash
cd ~/dusselle.fr

# Forcer le checkout de la branche de correction
git fetch origin claude/fix-dusselle-app-restart-011CUphNxPtUdQsiftAvyzck
git checkout -B claude/fix-dusselle-app-restart-011CUphNxPtUdQsiftAvyzck origin/claude/fix-dusselle-app-restart-011CUphNxPtUdQsiftAvyzck
```

### 2. Installer les dépendances compatibles Python 3.6

```bash
# Installer les dépendances dans votre espace utilisateur
pip install --user -r requirements_py36.txt
```

### 3. Vérifier la configuration

```bash
# Vérifier que le .htaccess est correct
cat .htaccess | grep PassengerAppRoot
# Doit afficher : PassengerAppRoot /home/wrbh3411/dusselle.fr

# Vérifier que passenger_wsgi.py est présent
ls -la passenger_wsgi.py
```

### 4. Redémarrer l'application

```bash
# Créer le dossier tmp si nécessaire
mkdir -p tmp

# Redémarrer Passenger
touch tmp/restart.txt

# Vérifier les logs (si disponibles)
tail -f ~/logs/dusselle.fr-error_log
```

### 5. Tester

Ouvrez https://dusselle.fr dans votre navigateur.

## En cas d'erreur

Si vous voyez une erreur au démarrage, l'application affichera automatiquement :
- Le message d'erreur détaillé
- La version de Python utilisée
- Les instructions pour corriger

## Versions des dépendances (Python 3.6)

- Flask 2.0.3 (au lieu de 3.0.0)
- APScheduler 3.9.1 (au lieu de 3.10.4)
- Werkzeug 2.0.3
- Toutes les autres dépendances compatibles

## Note importante

Si vous avez accès à Python 3.8+ sur votre serveur o2switch :
1. Utilisez `python3.8` ou `python3.9` au lieu de `python3`
2. Vous pourrez alors utiliser `requirements.txt` (Flask 3.0.0)

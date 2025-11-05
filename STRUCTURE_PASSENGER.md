# Structure Passenger pour o2switch

## Problème identifié
Apache et PHP fonctionnent correctement, mais Passenger ne démarre pas l'application Python.

## Solution : Structure avec dossier public/

Sur o2switch, Passenger nécessite cette structure :

```
/home/wrbh3411/dusselle.fr/
├── app_v2.py                    # Application Flask
├── database_v2.py               # Base de données
├── rss_updater.py              # Updater RSS
├── passenger_wsgi.py           # Point d'entrée Passenger
├── requirements_py36.txt       # Dépendances
├── rss_manager.db              # Base de données SQLite
├── static/                     # Fichiers statiques
├── templates/                  # Templates HTML
├── tmp/                        # Pour restart.txt
└── public/                     # ← NOUVEAU : Dossier public pour Passenger
    └── .htaccess               # Configuration Passenger
```

## Commandes pour créer la structure

```bash
cd ~/dusselle.fr

# Créer le dossier public
mkdir -p public

# Déplacer le .htaccess dans public/
cp .htaccess public/.htaccess

# Modifier le .htaccess dans public/
```

## Configuration .htaccess dans public/

Le `.htaccess` doit pointer vers le répertoire parent :

```apache
PassengerEnabled on
PassengerAppRoot /home/wrbh3411/dusselle.fr
PassengerStartupFile passenger_wsgi.py
PassengerAppType wsgi
```

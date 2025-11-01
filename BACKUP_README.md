# 📦 Guide de Sauvegarde et Restauration - RSS Manager

## 🎯 Vue d'ensemble

Ce dossier contient deux scripts essentiels pour sauvegarder et restaurer votre application RSS Manager :

- **`backup.sh`** : Crée une sauvegarde complète de l'application
- **`restore.sh`** : Restaure une sauvegarde précédente

## 📋 Contenu des sauvegardes

Chaque sauvegarde inclut :

- ✅ **Base de données** (rss_manager.db) - tous vos utilisateurs, flux, articles, dossiers
- ✅ **Code Python** (app_v2.py, database_v2.py, etc.)
- ✅ **Templates HTML** (tous les fichiers du dossier templates/)
- ✅ **Configuration** (passenger_wsgi.py, requirements.txt, etc.)
- ✅ **Scripts** (tous les fichiers .sh)
- ✅ **Fichier d'information** (statistiques et instructions)

## 🚀 Comment faire une sauvegarde

### Sur votre serveur SSH :

```bash
# 1. Se placer dans le bon dossier
cd ~/www

# 2. Télécharger le script de sauvegarde (première fois seulement)
curl -o backup.sh https://raw.githubusercontent.com/max0067/max0067/claude/fix-dusselle-app-issue-011CUdP9LbM4J3fMkcLWJWuc/backup.sh

# 3. Rendre le script exécutable
chmod +x backup.sh

# 4. Lancer la sauvegarde
./backup.sh
```

### Résultat :

Le script va créer un fichier `backup_YYYYMMDD_HHMMSS.tar.gz` (ex: `backup_20251101_143022.tar.gz`)

## 📥 Télécharger la sauvegarde sur votre ordinateur

**IMPORTANT** : Téléchargez toujours vos sauvegardes localement !

```bash
# Sur votre ordinateur (dans un terminal)
scp wrbh3411@ssh.wrbh3411.odns.fr:~/www/backup_*.tar.gz ~/Desktop/
```

Ou utilisez FileZilla/WinSCP pour télécharger le fichier.

## 🔄 Comment restaurer une sauvegarde

### En cas de problème avec l'application :

```bash
# 1. Télécharger le script de restauration
curl -o restore.sh https://raw.githubusercontent.com/max0067/max0067/claude/fix-dusselle-app-issue-011CUdP9LbM4J3fMkcLWJWuc/restore.sh

# 2. Rendre le script exécutable
chmod +x restore.sh

# 3. Lister les sauvegardes disponibles
ls -lht backup_*.tar.gz

# 4. Restaurer une sauvegarde spécifique
./restore.sh backup_20251101_143022.tar.gz

# 5. Suivre les instructions à l'écran
# Le script va vous demander confirmation avant de restaurer
```

### Le script de restauration va :

1. ✅ Sauvegarder l'état actuel avant de restaurer
2. ✅ Extraire l'archive de sauvegarde
3. ✅ Copier tous les fichiers au bon endroit
4. ✅ Définir les bonnes permissions
5. ✅ Redémarrer l'application automatiquement

## 📅 Fréquence recommandée

| Fréquence | Quand |
|-----------|-------|
| **Quotidienne** | Si vous avez beaucoup d'utilisateurs actifs |
| **Hebdomadaire** | Pour un usage normal |
| **Avant chaque mise à jour** | OBLIGATOIRE avant de modifier le code |
| **Après ajout de fonctionnalités** | Une fois que tout fonctionne bien |

## 🔧 Automatiser les sauvegardes (optionnel)

### Créer une sauvegarde automatique tous les jours à 3h du matin :

```bash
# Éditer le crontab
crontab -e

# Ajouter cette ligne :
0 3 * * * cd ~/www && ./backup.sh > /dev/null 2>&1

# Sauvegarder et quitter
```

### Nettoyer les vieilles sauvegardes (garder les 30 dernières) :

```bash
# Ajouter aussi cette ligne au crontab :
0 4 * * * cd ~/www && ls -t backup_*.tar.gz | tail -n +31 | xargs -r rm

# Cette commande garde les 30 sauvegardes les plus récentes
```

## 📊 Vérifier le contenu d'une sauvegarde

```bash
# Extraire l'archive
tar -xzf backup_20251101_143022.tar.gz

# Lire les informations
cd backup_20251101_143022
cat BACKUP_INFO.txt

# Voir la liste des fichiers
ls -lah
```

## 🆘 Scénarios de restauration

### Scénario 1 : L'application ne démarre plus

```bash
cd ~/www
./restore.sh backup_20251101_143022.tar.gz
# Suivre les instructions
```

### Scénario 2 : La base de données est corrompue

```bash
# Restaurer uniquement la base de données
tar -xzf backup_20251101_143022.tar.gz
cp backup_20251101_143022/rss_manager.db ~/www/
touch ~/www/tmp/restart.txt
```

### Scénario 3 : Un template est cassé

```bash
# Restaurer uniquement les templates
tar -xzf backup_20251101_143022.tar.gz
cp -r backup_20251101_143022/templates/* ~/www/templates/
touch ~/www/tmp/restart.txt
```

### Scénario 4 : Tout réinstaller depuis zéro

```bash
# Supprimer tout et restaurer
rm -rf ~/www
mkdir -p ~/www
cd ~/www
# Uploader votre sauvegarde .tar.gz
./restore.sh backup_20251101_143022.tar.gz
```

## 💡 Conseils

1. **Toujours faire une sauvegarde avant de modifier le code**
2. **Télécharger les sauvegardes sur votre ordinateur** (ne pas les garder uniquement sur le serveur)
3. **Tester vos sauvegardes** de temps en temps pour vérifier qu'elles fonctionnent
4. **Garder plusieurs versions** (au moins les 7 derniers jours)
5. **Documenter vos changements** dans le fichier BACKUP_INFO.txt si nécessaire

## 🔐 Sécurité

- Les sauvegardes contiennent des données sensibles (mots de passe hashés, emails)
- **Ne partagez jamais vos fichiers de sauvegarde publiquement**
- Stockez-les dans un endroit sécurisé
- Utilisez un mot de passe pour protéger les archives si nécessaire :

```bash
# Créer une archive protégée par mot de passe
tar -czf - backup_20251101_143022/ | openssl enc -aes-256-cbc -e > backup_20251101_143022_encrypted.tar.gz.enc

# Déchiffrer
openssl enc -aes-256-cbc -d -in backup_20251101_143022_encrypted.tar.gz.enc | tar xz
```

## 📞 Support

En cas de problème avec les scripts de sauvegarde/restauration, vérifiez :

1. Les permissions des scripts (`chmod +x backup.sh restore.sh`)
2. Que vous êtes dans le bon dossier (`cd ~/www`)
3. Que vous avez assez d'espace disque (`df -h`)
4. Les logs d'erreur si quelque chose échoue

---

**Créé le** : 2025-11-01
**Version** : 1.0
**Application** : RSS Manager

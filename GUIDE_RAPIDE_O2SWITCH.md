# Guide Rapide - Déploiement sur o2switch

## Méthode Rapide (Recommandée)

### 1. Connexion SSH

```bash
ssh votre_utilisateur@ssh.o2switch.net
```

### 2. Positionnement

```bash
# Pour un dossier dans public_html
cd ~/public_html
mkdir rss-manager
cd rss-manager

# OU pour un sous-domaine (recommandé)
# Créez d'abord le sous-domaine dans cPanel (ex: rss.votre-domaine.com)
# cd ~/rss.votre-domaine.com
```

### 3. Téléchargement du projet

```bash
# Option A: Via Git (si disponible)
git clone <URL_DU_REPO> .

# Option B: Via wget/curl
wget <URL_DU_ZIP>
unzip <NOM_DU_ZIP>

# Option C: Utilisez FileZilla ou le gestionnaire de fichiers cPanel
```

### 4. Installation automatique

```bash
chmod +x deploy_o2switch.sh
./deploy_o2switch.sh
```

Le script va automatiquement :
- Créer l'environnement virtuel
- Installer les dépendances
- Initialiser la base de données
- Configurer les permissions

### 5. Configuration dans cPanel

1. Allez dans **cPanel** > **Setup Python App**
2. Cliquez sur **"Create Application"**
3. Configurez :
   - **Python version**: 3.8+ (choisissez la plus récente)
   - **Application root**: `rss-manager` (ou votre dossier)
   - **Application URL**: `/rss-manager` (ou votre sous-domaine)
   - **Application startup file**: `passenger_wsgi.py`
   - **Application Entry point**: `application`

4. Cliquez sur **"Create"**

### 6. Vérification

```bash
# Créer/toucher le fichier de restart pour démarrer l'application
touch tmp/restart.txt
```

### 7. Accès

Ouvrez votre navigateur :
- **Sous-dossier**: `https://votre-domaine.com/rss-manager`
- **Sous-domaine**: `https://rss.votre-domaine.com`

---

## Tâche Cron (Mise à jour automatique)

Dans **cPanel** > **Tâches Cron**, ajoutez :

```bash
*/30 * * * * cd ~/public_html/rss-manager && source venv/bin/activate && python -c "from rss_updater import update_all_feeds; update_all_feeds()" >> logs/cron.log 2>&1
```

**Adaptez le chemin** selon votre installation.

---

## Commandes Utiles

### Redémarrer l'application
```bash
touch tmp/restart.txt
```

### Mettre à jour l'application
```bash
cd ~/public_html/rss-manager
git pull  # Si vous utilisez Git
source venv/bin/activate
pip install -r requirements.txt --upgrade
touch tmp/restart.txt
```

### Mettre à jour les flux manuellement
```bash
cd ~/public_html/rss-manager
source venv/bin/activate
python -c "from rss_updater import update_all_feeds; update_all_feeds()"
```

### Voir les logs
```bash
tail -f logs/app.log      # Logs de l'application
tail -f logs/cron.log     # Logs des tâches cron
```

### Sauvegarder la base de données
```bash
cp rss_feeds.db rss_feeds.db.backup-$(date +%Y%m%d)
```

---

## Dépannage Rapide

### Erreur 500
```bash
# Vérifiez les logs Apache
tail -f ~/logs/error_log

# Vérifiez les permissions
chmod 755 ~/public_html/rss-manager
chmod 666 ~/public_html/rss-manager/rss_feeds.db
```

### L'application ne démarre pas
```bash
# Vérifiez que l'environnement virtuel existe
ls -la venv/bin/python

# Recréez l'environnement si nécessaire
rm -rf venv
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

### Base de données verrouillée
```bash
# Vérifiez les permissions
chmod 666 rss_feeds.db

# Si problème persistant, déplacez la DB hors du web
mkdir -p ~/private
mv rss_feeds.db ~/private/
# Puis modifiez database.py pour pointer vers ~/private/rss_feeds.db
```

---

## Modification pour Production

### Désactiver le debug

Éditez `app.py` :

```python
if __name__ == '__main__':
    app.run(debug=False, host='0.0.0.0', port=5000)
```

### Utiliser la version production

```bash
# Renommez l'application de production
mv app.py app_dev.py
mv app_production.py app.py
touch tmp/restart.txt
```

---

## Sécurité

### Activer SSL (HTTPS)

1. Dans **cPanel** > **SSL/TLS Status**
2. Activez SSL pour votre domaine/sous-domaine
3. L'application sera automatiquement accessible en HTTPS

### Déplacer la base de données

```bash
# Créer un dossier privé
mkdir -p ~/private

# Déplacer la base de données
mv ~/public_html/rss-manager/rss_feeds.db ~/private/

# Modifier database.py
nano ~/public_html/rss-manager/database.py
```

Changez la ligne :
```python
DATABASE_NAME = 'rss_feeds.db'
```

En :
```python
import os
DATABASE_NAME = os.path.expanduser('~/private/rss_feeds.db')
```

Puis redémarrez :
```bash
touch ~/public_html/rss-manager/tmp/restart.txt
```

---

## Support

- **Documentation complète**: Voir `DEPLOIEMENT_O2SWITCH.md`
- **Support o2switch**: https://www.o2switch.fr/support/
- **FAQ o2switch**: https://faq.o2switch.fr/

---

## Checklist de Déploiement

- [ ] Connexion SSH établie
- [ ] Projet téléchargé dans le bon dossier
- [ ] Environnement virtuel créé
- [ ] Dépendances installées
- [ ] Base de données initialisée
- [ ] Application Python configurée dans cPanel
- [ ] Permissions correctes (755 pour dossiers, 644 pour fichiers)
- [ ] Application redémarrée (touch tmp/restart.txt)
- [ ] Application accessible via navigateur
- [ ] SSL/HTTPS activé
- [ ] Tâche cron configurée (optionnel)
- [ ] Premier flux RSS ajouté et testé

**Félicitations! Votre application est déployée!** 🎉

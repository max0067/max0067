# RSS Reader V2 - Installation Complete ✅

## 🎉 Installation réussie !

**Date** : 5 novembre 2025
**URL** : http://dusselle.fr/
**Version** : 2.0.0

---

## ✅ Ce qui a été installé

### 🎨 Interface moderne (Gmail-style)
- Design épuré et professionnel
- Responsive (mobile, tablette, desktop)
- Recherche en temps réel
- Compteurs de non lus
- Panneau de lecture d'articles

### 🔧 Backend robuste
- Auto-initialisation complète
- Migration automatique de base de données
- Health check intégré : http://dusselle.fr/?health
- Logs détaillés dans `/logs/`

### 📡 Mise à jour automatique RSS
- **Script** : `update_rss.php`
- **CRON** : Toutes les 30 minutes
- **Logs** : `~/public_html/logs/rss_update.log`
- **Conservation** : Les articles restent **en permanence** (jamais supprimés)

### 🗄️ Base de données
- **Fichier** : `rss_feeds.db` (SQLite)
- **Mode** : WAL (optimisé)
- **Données** : Conservées depuis V1

---

## 🌐 Accès

**URL principale** : http://dusselle.fr/

**Connexion par défaut** :
- Username : `admin`
- Password : `admin123`

⚠️ **IMPORTANT** : Changez le mot de passe dès votre première connexion !

---

## 📊 Configuration CRON

Le CRON suivant est configuré :

```bash
*/30 * * * * /usr/bin/php /home/wrbh3411/public_html/update_rss.php >> /home/wrbh3411/public_html/logs/rss_update.log 2>&1
```

**Fréquence** : Toutes les 30 minutes
**Action** : Récupère les nouveaux articles de tous les flux RSS
**Logs** : Sauvegardés dans `logs/rss_update.log`

### Modifier la fréquence

Pour changer la fréquence de mise à jour :

```bash
# Éditer le crontab
crontab -e

# Exemples de fréquences :
# */15 * * * *  → Toutes les 15 minutes
# */60 * * * *  → Toutes les heures
# 0 */2 * * *   → Toutes les 2 heures
```

---

## 📂 Structure des fichiers

```
public_html/
├── index.php              # Point d'entrée
├── api.php                # API REST
├── config.php             # Configuration
├── Database.php           # Gestion base de données
├── Session.php            # Gestion sessions
├── update_rss.php         # Script mise à jour RSS
├── .htaccess              # Configuration Apache
├── views/
│   ├── login.php          # Page de connexion
│   └── dashboard.php      # Dashboard principal
├── assets/
│   ├── style.css          # Styles CSS
│   └── app.js             # JavaScript
├── data/
│   └── (fichiers générés automatiquement)
├── logs/
│   ├── app.log            # Logs applicatifs
│   └── rss_update.log     # Logs mise à jour RSS
└── cache/
    └── (cache futur)
```

---

## 📝 Fichiers sauvegardés (V1)

En cas de besoin de restauration :

- `backup_v1_YYYYMMDD_HHMMSS.tar.gz` - Sauvegarde complète V1
- `.htaccess_v1_backup` - Ancien .htaccess
- `app_v2.py.disabled` - Application Python V1
- `passenger_wsgi.py.disabled` - WSGI V1
- `index.html.old` - Ancienne page d'accueil

---

## 🔧 Maintenance

### Mise à jour manuelle des flux

```bash
cd ~/public_html
php update_rss.php
```

### Consulter les logs

```bash
# Logs applicatifs
tail -50 ~/public_html/logs/app.log

# Logs mise à jour RSS
tail -50 ~/public_html/logs/rss_update.log
```

### Vérifier l'état du système

```bash
curl http://dusselle.fr/?health
```

Résultat attendu :
```json
{
  "success": true,
  "data": {
    "status": "ok",
    "database": "connected",
    "version": "2.0.0"
  }
}
```

### Nettoyer les anciens logs (optionnel)

```bash
# Nettoyer les logs de plus de 30 jours
find ~/public_html/logs/ -name "*.log" -mtime +30 -delete
```

---

## 🎯 Fonctionnalités

### Interface utilisateur

- ✅ Connexion sécurisée
- ✅ Dashboard Gmail-style
- ✅ Liste des flux RSS avec compteurs
- ✅ Recherche en temps réel
- ✅ Lecteur d'articles avec panneau latéral
- ✅ Marquage lu/non lu
- ✅ Ajout/suppression de flux
- ✅ Responsive (mobile, tablette, desktop)

### API REST

- `POST /api/auth/login` - Connexion
- `POST /api/auth/logout` - Déconnexion
- `GET /api/auth/me` - Informations utilisateur
- `GET /api/feeds` - Liste des flux
- `POST /api/feeds` - Ajouter un flux
- `DELETE /api/feeds/{id}` - Supprimer un flux
- `GET /api/articles` - Liste des articles
- `PUT /api/articles/{id}/read` - Marquer comme lu
- `GET /api/stats` - Statistiques

### Sécurité

- ✅ Sessions sécurisées
- ✅ Protection CSRF
- ✅ Protection des fichiers sensibles (.db, .log)
- ✅ Échappement HTML (XSS)
- ✅ Requêtes préparées (SQL injection)
- ✅ Headers de sécurité

---

## 🆘 Dépannage

### Les flux ne se mettent pas à jour

```bash
# Vérifier le CRON
crontab -l | grep update_rss

# Tester manuellement
php ~/public_html/update_rss.php

# Vérifier les logs
tail -30 ~/public_html/logs/rss_update.log
```

### Erreur 500

```bash
# Vérifier les permissions
chmod 755 ~/public_html
chmod 644 ~/public_html/*.php
chmod 755 ~/public_html/data ~/public_html/logs

# Vérifier .htaccess
cat ~/public_html/.htaccess
```

### Page blanche

```bash
# Vérifier les erreurs PHP
php -l ~/public_html/index.php

# Vérifier la base de données
ls -la ~/public_html/rss_feeds.db
```

### Articles ne s'affichent pas

```bash
# Vérifier la base de données
sqlite3 ~/public_html/rss_feeds.db "SELECT COUNT(*) FROM articles;"

# Forcer une mise à jour
php ~/public_html/update_rss.php
```

---

## 📈 Améliorations futures possibles

- [ ] Mode sombre (dark mode)
- [ ] Notifications push
- [ ] PWA (Progressive Web App)
- [ ] Import/Export OPML
- [ ] Catégories/Tags
- [ ] Partage sur réseaux sociaux
- [ ] Raccourcis clavier avancés
- [ ] Multi-utilisateurs
- [ ] API externe

---

## 📞 Support

Pour toute question ou problème :

1. **Consulter les logs** : `~/public_html/logs/`
2. **Tester le health check** : `curl http://dusselle.fr/?health`
3. **Vérifier le CRON** : `crontab -l`

---

## 🎊 Félicitations !

Votre **RSS Reader V2** est maintenant **opérationnel** et configuré pour :

✅ Mettre à jour automatiquement les flux RSS toutes les 30 minutes
✅ Conserver tous les articles en permanence
✅ Fonctionner de manière robuste sans bugs
✅ Offrir une interface moderne et agréable

**Profitez de votre nouveau lecteur RSS !** 🚀

---

**Version** : 2.0.0
**Date d'installation** : 5 novembre 2025
**Serveur** : o2switch (dusselle.fr)

# 🔄 Basculer entre Python et PHP

## 📊 État actuel

✅ **Version PHP activée** (recommandée)
🔒 Version Python désactivée (backup disponible)

## 🚀 Version active : PHP

L'application fonctionne actuellement en **PHP pur**.

### Fichiers actifs
- `.htaccess` → Configuration Apache pour PHP
- `index.php` → Point d'entrée PHP
- `php/` → Backend PHP complet

### Fichiers désactivés (backup)
- `.htaccess_python_backup` → Ancienne config Python
- `app_v2.py.disabled` → Application Flask
- `passenger_wsgi.py.disabled` → WSGI handler

---

## 🔧 Pour revenir à Python (si nécessaire)

Si vous voulez revenir à la version Python :

```bash
# 1. Restaurer le .htaccess Python
cp .htaccess_python_backup .htaccess

# 2. Réactiver les fichiers Python
mv app_v2.py.disabled app_v2.py
mv passenger_wsgi.py.disabled passenger_wsgi.py

# 3. Renommer index.php pour éviter les conflits
mv index.php index.php.disabled

# 4. Redémarrer Apache (si accès)
# touch tmp/restart.txt  # Pour Passenger
```

---

## ✨ Pour utiliser PHP (configuration actuelle)

C'est déjà fait ! L'application fonctionne en PHP.

### Vérifications

```bash
# 1. Vérifier que .htaccess pointe vers PHP
head -5 .htaccess
# Devrait afficher : "Configuration Apache pour l'application PHP RSS Reader"

# 2. Vérifier que les fichiers Python sont désactivés
ls -la *.py.disabled
# Devrait lister : app_v2.py.disabled, passenger_wsgi.py.disabled

# 3. Vérifier que index.php existe
ls -la index.php
```

---

## 📋 Comparaison rapide

| Aspect | Python (Flask) | PHP (actif) |
|--------|----------------|-------------|
| **Déploiement** | Complexe (Passenger, virtualenv) | Simple (Apache natif) |
| **Dépendances** | pip, Flask, feedparser, etc. | Aucune (PHP natif) |
| **Performances** | Bon | Équivalent ou meilleur |
| **Maintenance** | Plus complexe | Plus simple |
| **Compatibilité** | Nécessite Passenger | Fonctionne partout |
| **Base de données** | SQLite (compatible) | SQLite (même fichier !) |

---

## 💾 Base de données

**Important** : Les deux versions utilisent le **même fichier** `rss_feeds.db`.

- ✅ Vous pouvez basculer sans perdre de données
- ✅ Les utilisateurs, flux et articles sont préservés
- ✅ Aucune migration nécessaire

---

## 🆘 En cas de problème

### L'application ne fonctionne pas en PHP

```bash
# Vérifier les logs
tail -f logs/app.log

# Vérifier les permissions
chmod 755 php/
chmod 644 php/*.php
chmod 755 index.php

# Vérifier que mod_rewrite est actif
# (sur votre hébergement, c'est normalement activé)
```

### Je veux revenir à Python temporairement

```bash
# Sauvegarde rapide de la config PHP
cp .htaccess .htaccess_php_backup

# Restaurer Python
cp .htaccess_python_backup .htaccess
mv app_v2.py.disabled app_v2.py
mv passenger_wsgi.py.disabled passenger_wsgi.py
mv index.php index.php.disabled

# Attendre quelques secondes que Passenger redémarre
```

### Les deux versions ne marchent pas

```bash
# Vérifier les logs Apache
# (sur o2switch : Panel > Logs > Error Log)

# Vérifier que la base de données n'est pas verrouillée
ls -la rss_feeds.db
# Si le fichier n'existe pas, rechargez l'application (il sera créé)
```

---

## 📝 Notes importantes

1. **Recommandation** : Utilisez PHP (version active)
   - Plus simple à maintenir
   - Fonctionne sur tous les hébergements
   - Pas de dépendances à gérer

2. **Sauvegarde** : Les fichiers Python sont conservés
   - Vous pouvez toujours revenir en arrière
   - Aucune perte de code

3. **Données** : Base de données partagée
   - Pas de migration nécessaire
   - Basculement instantané

4. **CRON** : N'oubliez pas de configurer
   ```bash
   */30 * * * * /usr/bin/php /chemin/vers/cron_update_feeds.php
   ```

---

## ✅ État après migration

```
Version active : PHP ✅
Fichiers Python : Sauvegardés (*.disabled) 📦
Base de données : Intacte et compatible 💾
Templates HTML : Identiques (pas de changement) 🎨
Fichiers static : Identiques (CSS, JS) 📄
Données utilisateur : Préservées 👤
```

---

**Date de basculement** : 2025-11-05
**Version PHP** : 1.0.0
**Version Python sauvegardée** : 2.0

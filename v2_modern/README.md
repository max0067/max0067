# RSS Reader V2 - Version Moderne

## 🎨 Caractéristiques

- **Interface Gmail-style** : Design moderne et épuré inspiré de Gmail
- **Auto-initialisation** : Pas besoin de setup manuel, tout se crée automatiquement
- **Robuste** : Gestion d'erreurs complète, ne plante pas au redémarrage
- **Migration automatique** : Le système migre automatiquement la base de données
- **Health check** : Endpoint de monitoring intégré
- **Responsive** : Fonctionne sur desktop, tablette et mobile

## 📁 Structure

```
v2_modern/
├── index.php              # Routeur principal
├── api.php                # API REST complète
├── config.php             # Configuration et helpers
├── Database.php           # Classe database avec auto-migration
├── Session.php            # Gestion des sessions
├── views/
│   ├── login.php          # Page de connexion moderne
│   └── dashboard.php      # Dashboard Gmail-style
├── assets/
│   ├── style.css          # Styles modernes
│   └── app.js             # JavaScript frontend
└── README.md              # Ce fichier
```

## 🚀 Installation

### Méthode 1 : Installation automatique en parallèle

1. Le V2 s'installe dans `/v2/` pour tester sans casser le V1
2. Uploadez le dossier `v2_modern/` sur le serveur
3. Renommez-le en `v2/`
4. Accédez à `http://dusselle.fr/v2/`

### Méthode 2 : Via SSH

```bash
cd ~/public_html
mkdir -p v2
cp -r v2_modern/* v2/
chmod -R 755 v2
```

### Méthode 3 : Via cPanel File Manager

1. Allez dans File Manager
2. Créez un dossier `v2`
3. Uploadez tous les fichiers de `v2_modern/` dans `v2/`
4. Définissez les permissions 755 pour les dossiers

## 🔧 Configuration

Le V2 utilise les mêmes données que le V1 :
- Base de données : `../rss_feeds.db` (partagée avec V1)
- Auto-création des dossiers nécessaires
- Migration automatique du schéma si besoin

### Configuration personnalisée

Éditez `config.php` :

```php
// Clé secrète pour les sessions
define('SECRET_KEY', 'changez-moi');

// Intervalle de mise à jour (en minutes)
define('UPDATE_INTERVAL', 30);
```

## 🎯 Fonctionnalités

### Interface
- ✅ Dashboard Gmail-style
- ✅ Sidebar avec liste des flux
- ✅ Liste d'articles avec aperçu
- ✅ Panneau de lecture d'article
- ✅ Recherche en temps réel
- ✅ Compteurs non lus
- ✅ Modal d'ajout de flux

### API REST
- ✅ `POST /api/auth/login` - Connexion
- ✅ `POST /api/auth/logout` - Déconnexion
- ✅ `GET /api/auth/me` - Info utilisateur
- ✅ `GET /api/feeds` - Liste des flux
- ✅ `POST /api/feeds` - Ajouter un flux
- ✅ `DELETE /api/feeds/{id}` - Supprimer un flux
- ✅ `GET /api/articles` - Liste des articles
- ✅ `PUT /api/articles/{id}/read` - Marquer comme lu
- ✅ `GET /api/stats` - Statistiques

### Auto-initialisation
- ✅ Crée automatiquement les dossiers `data/`, `logs/`, `cache/`
- ✅ Crée automatiquement la base de données si elle n'existe pas
- ✅ Migre automatiquement le schéma de la base
- ✅ Gestion des erreurs et logs détaillés

### Health Check
Accédez à `http://dusselle.fr/v2/?health` pour vérifier l'état du système :

```json
{
  "status": "ok",
  "database": "connected",
  "version": "2.0.0"
}
```

## 🧪 Tests

### 1. Tester l'installation

```bash
# Via curl
curl http://dusselle.fr/v2/?health

# Résultat attendu
{"status":"ok","database":"connected","version":"2.0.0"}
```

### 2. Tester la connexion

1. Allez sur `http://dusselle.fr/v2/`
2. Connectez-vous avec : `admin` / `admin123`
3. Le dashboard devrait s'afficher

### 3. Tester les fonctionnalités

- [ ] Ajouter un flux RSS
- [ ] Voir la liste des articles
- [ ] Cliquer sur un article pour le lire
- [ ] Marquer un article comme lu
- [ ] Utiliser la recherche
- [ ] Se déconnecter

## 🔄 Migration de V1 vers V2

### Étape 1 : Tester V2 en parallèle

1. V2 est installé dans `/v2/`
2. V1 reste dans `/` (racine)
3. Les deux versions utilisent la même base de données
4. Testez V2 : `http://dusselle.fr/v2/`

### Étape 2 : Basculer vers V2 (si tout fonctionne)

```bash
# Sauvegarder V1
mv public_html public_html_v1_backup

# Déplacer V2 vers la racine
mv public_html/v2/* public_html/
```

Ou via cPanel File Manager :
1. Renommer le dossier racine en `public_html_v1_backup`
2. Renommer `v2/` en `public_html/`

## 🛠️ Dépannage

### Erreur 500

1. Vérifier les permissions :
   ```bash
   chmod -R 755 v2
   chmod 644 v2/*.php
   ```

2. Consulter les logs :
   ```bash
   tail -50 logs/app.log
   ```

### Page blanche

1. Vérifier que PHP 7.4+ est actif
2. Vérifier que mod_rewrite est activé
3. Vérifier les logs Apache

### Base de données introuvable

Le V2 crée automatiquement `data/rss_feeds.db` s'il ne trouve pas `../rss_feeds.db`

### Styles ne se chargent pas

Vérifier le chemin dans les templates :
```html
<link rel="stylesheet" href="/v2/assets/style.css">
```

## 📊 Performances

- **Optimisations SQLite** : WAL mode activé
- **Cache** : Dossier `cache/` pour les futures optimisations
- **Logs** : Système de logs avec rotation automatique

## 🔐 Sécurité

- ✅ Protection des fichiers sensibles (.db, .log, config.php)
- ✅ Échappement HTML pour prévenir XSS
- ✅ Requêtes préparées pour prévenir SQL injection
- ✅ Sessions sécurisées
- ✅ Headers de sécurité (CSP, X-Frame-Options, etc.)

## 📝 TODO (Améliorations futures)

- [ ] Mode sombre (dark mode)
- [ ] Notifications push
- [ ] Offline mode (PWA)
- [ ] Import/Export OPML
- [ ] Catégories/Tags
- [ ] Partage sur réseaux sociaux
- [ ] Raccourcis clavier avancés

## 🆘 Support

En cas de problème :

1. **Consulter les logs** : `logs/app.log`
2. **Health check** : `http://dusselle.fr/v2/?health`
3. **Logs Apache** : cPanel > Logs > Error Log

## 📄 Licence

Application développée pour dusselle.fr

---

**Version** : 2.0.0
**Date** : 2025-11-05
**Statut** : Prêt pour tests 🚀

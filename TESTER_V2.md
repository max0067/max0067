# 🚀 TESTER LA VERSION 2.0

## ⚡ Démarrage rapide (30 secondes)

```bash
# 1. Initialiser la base de données V2
python database_v2.py

# 2. Lancer l'application V2
python app_v2.py

# 3. Ouvrir dans le navigateur
http://localhost:5000/login
```

## 🔐 Se connecter

### Compte admin par défaut
```
Username: admin
Password: admin123
```

⚠️ **Changez ce mot de passe après la première connexion !**

## 📝 Créer un compte utilisateur

1. Allez sur http://localhost:5000/register
2. Remplissez le formulaire
3. Connectez-vous avec vos identifiants

## ✨ Nouvelles fonctionnalités à tester

### 1. Multi-utilisateurs
- Créez plusieurs comptes
- Chaque utilisateur a ses propres flux
- Les données sont isolées

### 2. Recherche
- Utilisez la barre de recherche en haut
- Recherche dans titres, descriptions et contenu
- Résultats en temps réel

### 3. Articles favoris
- Cliquez sur l'étoile pour marquer un favori
- Accédez à vos favoris via le menu

### 4. Dashboard
- Allez sur `/dashboard`
- Visualisez vos statistiques
- Nombre de flux, articles, non lus, favoris

### 5. Panel Admin (compte admin uniquement)
- Allez sur `/admin`
- Gérez tous les utilisateurs
- Créez/modifiez/supprimez des comptes
- Voyez les stats globales

## 🎯 Parcours de test complet

1. **Connexion**
   - [ ] Se connecter avec admin/admin123
   - [ ] Vérifier la redirection vers l'accueil

2. **Ajouter des flux**
   - [ ] Ajouter un flux RSS
   - [ ] Vérifier que les articles sont récupérés

3. **Recherche**
   - [ ] Taper un mot-clé dans la barre de recherche
   - [ ] Vérifier les résultats

4. **Favoris**
   - [ ] Marquer 2-3 articles comme favoris
   - [ ] Accéder à la vue Favoris
   - [ ] Retirer un favori

5. **Dashboard**
   - [ ] Aller sur /dashboard
   - [ ] Vérifier les statistiques affichées

6. **Panel Admin**
   - [ ] Aller sur /admin
   - [ ] Créer un nouvel utilisateur
   - [ ] Modifier un utilisateur
   - [ ] Voir les stats globales

7. **Déconnexion**
   - [ ] Se déconnecter
   - [ ] Vérifier la redirection vers /login

8. **Nouveau compte**
   - [ ] S'inscrire avec un nouveau compte
   - [ ] Se connecter
   - [ ] Ajouter des flux
   - [ ] Vérifier l'isolation des données

## 🐛 Problèmes courants

### Base de données verrouillée
```bash
# Arrêtez l'application (Ctrl+C)
# Relancez
python app_v2.py
```

### Erreur d'import
```bash
# Vérifiez que vous utilisez app_v2.py et non app.py
python app_v2.py
```

### Session expirée
```bash
# Reconnectez-vous
# Les sessions durent 7 jours par défaut
```

## 📊 Comparer V1 et V2

| Fonctionnalité | V1 | V2 |
|---|---|---|
| Authentification | ❌ | ✅ |
| Multi-utilisateurs | ❌ | ✅ |
| Recherche | ❌ | ✅ |
| Favoris | ❌ | ✅ |
| Dashboard | ❌ | ✅ |
| Panel Admin | ❌ | ✅ |
| Articles par utilisateur | ❌ | ✅ |
| Rôles (user/admin) | ❌ | ✅ |

## 🔄 Revenir à la V1

Si vous voulez revenir à la V1 :

```bash
# Lancer l'ancienne version
python app.py

# Utiliser l'ancienne base de données
# (rss_feeds.db de la V1)
```

## 📁 Structure des fichiers

```
V1 (ancienne):
- app.py
- database.py
- rss_feeds.db

V2 (nouvelle):
- app_v2.py          ← Nouvelle application
- database_v2.py     ← Nouvelle base de données
- templates/
  - login.html       ← Connexion
  - register.html    ← Inscription
  - index_v2.html    ← Interface principale
  - dashboard.html   ← Dashboard
  - admin.html       ← Panel admin
- static/
  - css/
    - auth.css       ← Styles auth
    - style_v2.css   ← Nouveaux styles
  - js/
    - app_v2.js      ← Nouveau JavaScript
```

## ✅ Checklist avant production

- [ ] Changer le mot de passe admin
- [ ] Définir une SECRET_KEY sécurisée
- [ ] Désactiver le mode debug
- [ ] Tester tous les parcours utilisateur
- [ ] Sauvegarder la base de données
- [ ] Configurer les mises à jour automatiques

## 🆘 Besoin d'aide ?

- Consultez `NOUVELLES_FONCTIONNALITES.md` pour la documentation complète
- Lancez `python diagnostic.py` pour diagnostiquer les problèmes
- Vérifiez les logs dans le terminal

---

**Bon test ! 🎉**

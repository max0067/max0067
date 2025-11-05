# RSS Reader V2 - Guide Rapide

## 🚀 Installation en 3 Étapes

```bash
cd ~/public_html

# 1. Corriger la base de données
wget https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/fix_database.php
php fix_database.php

# 2. Télécharger l'interface de gestion
wget https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/manage.php

# 3. Télécharger le script d'ajout simple
wget https://raw.githubusercontent.com/max0067/max0067/claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ/add_feed_simple.php
```

## 🌐 Utilisation

### Interface Web (Recommandé) ⭐

1. **Gestion des flux**: http://dusselle.fr/manage.php
   - Login: `admin` / Password: `admin123`
   - Ajoutez des flux RSS
   - Cliquez sur "⟳ Actualiser tous les flux"

2. **Lecture**: http://dusselle.fr/
   - Lisez vos articles

### Ligne de Commande

```bash
# Ajouter un flux + récupérer articles
php add_feed_simple.php
```

## 📡 Flux RSS Populaires

- Le Monde: `https://www.lemonde.fr/rss/une.xml`
- France 24: `https://www.france24.com/fr/rss`
- Le Figaro: `https://www.lefigaro.fr/rss/figaro_actualites.xml`

## 🐛 Dépannage

```bash
# Problème d'affichage?
php fix_database.php
php add_feed_simple.php

# Diagnostiquer
php debug.php
```

## ✨ Fonctionnalités V2

- ✅ Interface Gmail-style moderne
- ✅ Gestion web complète (manage.php)
- ✅ Responsive mobile/desktop
- ✅ Auto-détection schéma DB
- ✅ Pas de dépendances Python

---

**Version**: 2.0 | **Site**: http://dusselle.fr/ | **Gestion**: http://dusselle.fr/manage.php

# 🔧 FIX : Erreur "Phusion Passenger"

## ❌ Symptôme

Vous voyez ce message :
```
We're sorry, but something went wrong.
This website is powered by Phusion Passenger®
```

## 🎯 Cause

**Passenger** (le serveur d'application Python) est **toujours actif** et essaie de charger l'application Python, même si on a désactivé les fichiers Python.

---

## ✅ SOLUTION RAPIDE (2 minutes via SSH)

### Étape 1 : Connexion SSH

```bash
ssh votre_user@dusselle.fr
```

### Étape 2 : Aller dans le dossier

```bash
cd ~/public_html  # ou ~/dusselle.fr selon votre config
```

### Étape 3 : Vérifier le .htaccess

```bash
head -10 .htaccess
```

**Si vous voyez :**
```
PassengerEnabled on
```

**C'est le problème ! Continuez ci-dessous.**

---

### Étape 4 : Télécharger le .htaccess corrigé

```bash
# Récupérer les derniers changements
git pull origin claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ

# Copier le .htaccess corrigé
cp .htaccess_php .htaccess
```

**Vérifier que c'est bon :**
```bash
head -10 .htaccess
```

**Vous devriez maintenant voir :**
```
# Configuration Apache pour l'application PHP RSS Reader

# IMPORTANT : Désactiver Passenger (serveur Python)
PassengerEnabled off
```

---

### Étape 5 : Redémarrer Passenger

```bash
# Créer le dossier tmp s'il n'existe pas
mkdir -p tmp

# Redémarrer Passenger
touch tmp/restart.txt

echo "✅ Passenger redémarré !"
echo "⏱️  Attendez 10-15 secondes..."
```

---

### Étape 6 : Attendre et tester

**Attendez 10-15 secondes** que Passenger redémarre et lise la nouvelle configuration.

**Puis testez :**
```
http://dusselle.fr
```

✅ **Vous devriez maintenant voir la page de login PHP !**

---

## 🔧 Solution Alternative (si git pull ne marche pas)

### Modifier le .htaccess manuellement

```bash
# Éditer le .htaccess
nano .htaccess
# OU
vi .htaccess
```

**Au début du fichier, changer :**
```apache
# Remplacer cette ligne :
PassengerEnabled on

# Par :
PassengerEnabled off
```

**Sauvegarder et quitter :**
- Dans `nano` : Ctrl+X puis Y puis Entrée
- Dans `vi` : ESC puis :wq

**Puis redémarrer :**
```bash
mkdir -p tmp
touch tmp/restart.txt
```

**Attendre 10-15 secondes et tester.**

---

## 🆘 Si ça ne marche toujours pas

### Option 1 : Supprimer complètement le .htaccess et le recréer

```bash
# Sauvegarder l'ancien
cp .htaccess .htaccess_OLD

# Supprimer
rm .htaccess

# Créer un nouveau .htaccess minimal
cat > .htaccess << 'EOF'
# Configuration Apache pour PHP
PassengerEnabled off

RewriteEngine On
RewriteBase /

RewriteCond %{REQUEST_URI} !^/static/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
EOF

# Redémarrer
touch tmp/restart.txt

# Attendre 15 secondes
sleep 15

# Tester
curl -I http://dusselle.fr
```

---

### Option 2 : Désactiver Passenger globalement (via cPanel)

Si vous avez accès à cPanel :

1. **Allez dans cPanel**
2. **Cherchez "Python" ou "Application Python"**
3. **Désactivez l'application Python** pour ce domaine
4. **Sauvegardez**
5. **Attendez 1 minute**
6. **Testez le site**

---

### Option 3 : Contacter le support o2switch

Si rien ne fonctionne :

**Message à envoyer au support :**

```
Bonjour,

Je souhaite désactiver Phusion Passenger sur mon domaine dusselle.fr
et utiliser PHP natif à la place.

J'ai déjà ajouté "PassengerEnabled off" dans mon .htaccess mais
Passenger continue de s'activer.

Pouvez-vous m'aider à désactiver complètement Passenger pour ce domaine ?

Merci !
```

---

## 📋 Résumé : Commandes complètes

**Copier-coller ces commandes :**

```bash
# 1. Connexion
ssh votre_user@dusselle.fr

# 2. Aller dans le dossier (ajuster selon votre config)
cd ~/public_html

# 3. Récupérer les changements
git pull origin claude/rebuild-dusselle-pandas-011CUpvL1qSEyWraht2rYQiJ

# 4. Copier le .htaccess corrigé
cp .htaccess_php .htaccess

# 5. Vérifier
head -10 .htaccess | grep -i passenger

# Doit afficher : PassengerEnabled off

# 6. Redémarrer Passenger
mkdir -p tmp
touch tmp/restart.txt

# 7. Attendre
echo "Attente de 15 secondes..."
sleep 15

# 8. Tester
echo "✅ Testez maintenant : http://dusselle.fr"
```

---

## ✅ Comment savoir si c'est réparé ?

### Avant (erreur) :
```
We're sorry, but something went wrong.
This website is powered by Phusion Passenger®
```

### Après (succès) :
- Vous voyez la **page de login** de l'application PHP
- Formulaire avec "Username" et "Password"
- Pas de message d'erreur Passenger

---

## 🔍 Vérifier que PHP est actif

```bash
# Créer un fichier test
echo "<?php phpinfo(); ?>" > test_php.php

# Tester dans le navigateur
# http://dusselle.fr/test_php.php

# Devrait afficher les informations PHP

# Supprimer le fichier de test
rm test_php.php
```

---

## 📝 Note importante

**Pourquoi ce problème ?**

- Passenger est un serveur d'application pour Python/Ruby/Node.js
- Il intercepte les requêtes **avant** qu'Apache ne les traite
- Même si on désactive les fichiers Python, Passenger continue de tourner
- Il faut **explicitement désactiver Passenger** avec `PassengerEnabled off`
- Et **redémarrer** avec `touch tmp/restart.txt`

**Une fois désactivé :**
- Apache gère directement les requêtes
- PHP est servi normalement
- L'application fonctionne en PHP pur

---

**Temps estimé : 2 minutes** ⏱️

**Si ça marche, n'oubliez pas de :**
1. ✅ Changer le mot de passe admin (admin/admin123)
2. ✅ Configurer le CRON
3. ✅ Personnaliser SECRET_KEY dans php/config.php

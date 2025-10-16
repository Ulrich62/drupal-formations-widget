# Drupal Formations Widget

Site Drupal avec widget de chat intelligent pour les formations.

## 🚀 Démarrage rapide avec GitHub Codespaces

### Option 1 : Codespaces (Recommandé)
1. Cliquez sur le bouton **"Code"** sur GitHub
2. Sélectionnez **"Codespaces"**
3. Cliquez **"Create codespace on main"**
4. Attendez que l'environnement se configure (2-3 minutes)
5. Votre site sera accessible via l'URL générée

### Option 2 : Développement local
```bash
# Cloner le repository
git clone https://github.com/Ulrich62/drupal-formations-widget.git
cd drupal-formations-widget

# Installer les dépendances
composer install
npm install

# Démarrer le serveur local
php -S localhost:8000 -t web
```

## 🎯 Fonctionnalités

- ✅ Widget de chat intelligent
- ✅ Intégration avec API de formations
- ✅ Interface moderne et responsive
- ✅ Support Markdown dans les réponses
- ✅ Gestion des sessions et formations

## 🔧 Configuration

### Variables d'environnement
- `OPENAI_API_KEY` : Clé API OpenAI
- `MISTRAL_API_KEY` : Clé API Mistral (optionnel)
- `FASTAPI_BASE_URL` : URL de l'API FastAPI

### Modules Drupal
- `formations_widget` : Module personnalisé principal

## 📱 Accès client

Une fois déployé sur Codespaces :
1. Partagez l'URL publique générée
2. Le client peut accéder directement au site
3. Mises à jour automatiques via Git

## 🛠️ Développement

- **Frontend** : HTML/CSS/JavaScript vanilla
- **Backend** : Drupal 11 + PHP 8.2
- **API** : FastAPI (Python)
- **Base de données** : MySQL/PostgreSQL

## 📞 Support

Pour toute question ou problème, contactez l'équipe de développement.
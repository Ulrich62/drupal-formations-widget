# 🤖 Formations Widget - Chat IA avec RAG

Un widget Drupal intelligent qui permet aux utilisateurs de poser des questions sur les formations et sessions via un chat IA utilisant la technologie RAG (Retrieval-Augmented Generation).

## 🎯 Fonctionnalités

- **Chat IA intelligent** : Réponses contextuelles basées sur les données OO2
- **RAG vectoriel** : Recherche sémantique dans 1,680+ sessions et 50+ formations
- **Interface moderne** : Widget flottant avec design responsive
- **Sources citées** : Chaque réponse inclut ses sources
- **Configuration simple** : Interface d'administration intégrée

## 🏗️ Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Drupal         │    │   PostgreSQL    │
│   (Widget JS)   │◄──►│   (Module)       │◄──►│   + pgvector    │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                │
                                ▼
                       ┌──────────────────┐
                       │   OpenAI API     │
                       │   (GPT-4o Mini)  │
                       └──────────────────┘
```

## 📋 Prérequis

- **Drupal 11.x**
- **PostgreSQL 14+** avec extension `pgvector`
- **Clé API OpenAI** (https://platform.openai.com/api-keys)
- **PHP 8.1+**

## 🚀 Installation

### 1. Installation des dépendances

```bash
# Installer PostgreSQL et pgvector
brew install postgresql@14
brew install pgvector

# Démarrer PostgreSQL
brew services start postgresql@14
```

### 2. Configuration de la base de données

```bash
# Créer la base de données principale
psql -h localhost -p 5432 -U postgres -c "CREATE DATABASE drupal_formations;"

# Créer la base de données RAG
psql -h localhost -p 5432 -U postgres -c "CREATE DATABASE drupal_formations_rag;"

# Activer l'extension pgvector pour le RAG
psql -h localhost -p 5432 -U postgres -d drupal_formations_rag -c "CREATE EXTENSION vector;"
```

### 3. Configuration Drupal

Le fichier `web/sites/default/settings.php` doit contenir :

```php
// Configuration PostgreSQL principale
$databases['default']['default'] = array (
  'database' => 'drupal_formations',
  'username' => 'postgres',
  'password' => 'postgres',
  'host' => 'localhost',
  'port' => '5432',
  'driver' => 'pgsql',
  'namespace' => 'Drupal\\pgsql\\Driver\\Database\\pgsql',
  'autoload' => 'core/modules/pgsql/src/Driver/Database/pgsql/',
);

// Configuration PostgreSQL pour le RAG vectoriel
$databases['rag']['default'] = array (
  'database' => 'drupal_formations_rag',
  'username' => 'postgres',
  'password' => 'postgres',
  'host' => 'localhost',
  'port' => '5432',
  'driver' => 'pgsql',
  'namespace' => 'Drupal\\pgsql\\Driver\\Database\\pgsql',
  'autoload' => 'core/modules/pgsql/src/Driver/Database/pgsql/',
);

// Configuration des hôtes de confiance (SÉCURITÉ)
$settings['trusted_host_patterns'] = [
  '^localhost$',
  '^127\.0\.0\.1$',
  '^::1$',
  '^localhost:8888$',
  '^127\.0\.0\.1:8888$',
  '^localhost:8080$',
  '^127\.0\.0\.1:8080$',
  // Ajoutez vos domaines de production ici
  // '^votre-domaine\.com$',
  // '^www\.votre-domaine\.com$',
];
```

### 4. Activation du module

```bash
# Désactiver le module SQLite (si activé)
vendor/bin/drush pm:uninstall sqlite -y

# Activer le module PostgreSQL
vendor/bin/drush en pgsql -y

# Activer le module formations_widget
vendor/bin/drush en formations_widget -y

# Vider le cache
vendor/bin/drush cache:rebuild
```

## ⚙️ Configuration

### 1. Clé API OpenAI

```bash
# Configurer votre clé API OpenAI
vendor/bin/drush config:set formations_widget.settings openai_api_key "sk-your-api-key-here" -y
```

### 2. Interface d'administration

Accédez à `/admin/config/services/formations-widget` pour :
- Configurer la clé API OpenAI
- Choisir le modèle (GPT-4o Mini recommandé)
- Configurer l'authentification OO2

### 3. Modèles disponibles

| Modèle | Prix | Usage |
|--------|------|-------|
| **GPT-4o Mini** | $0.15/1M tokens | ✅ Recommandé |
| **GPT-3.5 Turbo** | $0.5/1M tokens | 💰 Économique |
| **GPT-4o** | $5/1M tokens | 🚀 Premium |

## 📊 Indexation des données

### 1. Synchroniser toutes les données (16 pages)

```bash
# Forcer la synchronisation complète (16 pages de formations)
curl -X POST http://votre-site/formations-widget/force-sync

# Indexer les données pour le RAG
curl -X POST http://votre-site/formations-widget/index-data
```

**Note** : La synchronisation complète récupère toutes les 16 pages de formations (800+ formations) au lieu des 3 premières pages seulement.

### 2. Vérifier l'indexation

```bash
# Voir les statistiques
curl http://votre-site/formations-widget/index-stats

# Réponse attendue
{
  "total_documents": 1730,
  "formations_count": 50,
  "sessions_count": 1680
}
```

## 🎮 Utilisation

### 1. Widget automatique

Le widget s'affiche automatiquement sur toutes les pages (sauf admin) :
- **Bouton flottant** en bas à droite
- **Interface de chat** moderne et responsive
- **Messages en temps réel** avec l'IA

### 2. API de chat

```bash
# Tester le chat via API
curl -X POST http://votre-site/formations-widget/chat \
  -H "Content-Type: application/json" \
  -d '{"question": "Formation maintenance minière"}'
```

### 3. Exemples de questions

- *"Quelles formations sont disponibles en maintenance ?"*
- *"Session e-learning avec certification"*
- *"Formation courte en développement"*
- *"Prix des formations Drupal"*

## 🔧 Maintenance

### 1. Réindexation périodique

```bash
# Synchroniser toutes les données (recommandé : 1x/semaine)
curl -X POST http://votre-site/formations-widget/force-sync

# Réindexer pour le RAG
curl -X POST http://votre-site/formations-widget/index-data
```

### 2. Surveillance des coûts

- **Embeddings** : ~$0.02/1M tokens
- **Chat** : ~$0.15/1M tokens (GPT-4o Mini)
- **Estimation** : $5-15/mois pour 1000 questions

### 3. Logs et monitoring

```bash
# Voir les logs
vendor/bin/drush watchdog:show --type=formations_widget

# Vider les logs
vendor/bin/drush watchdog:delete all
```

## 🐛 Dépannage

### Problème : Widget ne s'affiche pas

```bash
# Vérifier que le module est activé
vendor/bin/drush pm:list --status=enabled | grep formations

# Vérifier les logs
vendor/bin/drush watchdog:show --count=10
```

### Problème : Erreur d'indexation

```bash
# Vérifier la connexion PostgreSQL
psql -h localhost -p 5432 -U postgres -d drupal_formations_rag -c "SELECT version();"

# Vérifier l'extension pgvector
psql -h localhost -p 5432 -U postgres -d drupal_formations_rag -c "SELECT * FROM pg_extension WHERE extname = 'vector';"
```

### Problème : Réponses vides

```bash
# Vérifier la clé API
vendor/bin/drush config:get formations_widget.settings openai_api_key

# Tester l'API OpenAI
curl -H "Authorization: Bearer sk-your-key" https://api.openai.com/v1/models
```

## 📁 Structure du projet

```
formations_widget/
├── formations_widget.info.yml          # Métadonnées du module
├── formations_widget.services.yml      # Services Drupal
├── formations_widget.routing.yml       # Routes API
├── formations_widget.schema.yml        # Schéma de configuration
├── src/
│   ├── Controller/
│   │   └── FormationsWidgetController.php  # Contrôleur principal
│   ├── Form/
│   │   └── SettingsForm.php               # Formulaire de configuration
│   └── Service/
│       ├── Oo2Client.php                   # Client API OO2
│       └── VectorRagService.php            # Service RAG vectoriel
└── README.md
```

## 🔒 Sécurité

### Configuration des hôtes de confiance

**⚠️ IMPORTANT** : Configurez toujours les hôtes de confiance pour éviter les attaques HTTP HOST Header.

```php
// Dans settings.php
$settings['trusted_host_patterns'] = [
  '^localhost$',
  '^127\.0\.0\.1$',
  '^::1$',
  '^localhost:8888$',
  '^127\.0\.0\.1:8888$',
  // Ajoutez vos domaines de production
  '^votre-domaine\.com$',
  '^www\.votre-domaine\.com$',
];
```

### Autres mesures de sécurité

- **Clés API** : Stockées de manière sécurisée dans Drupal
- **Authentification** : Basique pour l'API OO2
- **CORS** : Configuré pour les requêtes cross-origin
- **Validation** : Toutes les entrées sont validées
- **HTTPS** : Recommandé en production

## 📈 Performance

- **Cache** : Mise en cache des embeddings et réponses
- **Index HNSW** : Recherche vectorielle optimisée
- **Pagination** : Limitation des résultats pour éviter la surcharge
- **Timeout** : 30s maximum par requête

## 🤝 Support

Pour toute question ou problème :

1. Vérifiez les logs Drupal
2. Consultez la documentation OpenAI
3. Testez la connectivité PostgreSQL
4. Vérifiez la configuration des clés API

## 📄 Licence

Ce module est développé pour un usage interne. Tous droits réservés.

---

**Développé avec ❤️ pour optimiser l'expérience utilisateur des formations OO2**

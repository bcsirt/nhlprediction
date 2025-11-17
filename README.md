# 🏒 NHL Prediction Platform

> Plateforme de prédiction intelligente pour les matchs de la NHL utilisant le Deep Learning et l'apprentissage auto-supervisé

[![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com)
[![Python](https://img.shields.io/badge/Python-3.11-blue.svg)](https://python.org)
[![PyTorch](https://img.shields.io/badge/PyTorch-2.0+-orange.svg)](https://pytorch.org)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## 📋 Table des Matières

- [Vue d'ensemble](#vue-densemble)
- [Fonctionnalités](#fonctionnalités)
- [Architecture](#architecture)
- [Technologies](#technologies)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [API Documentation](#api-documentation)
- [Machine Learning](#machine-learning)
- [Développement](#développement)
- [Tests](#tests)
- [Déploiement](#déploiement)
- [Contribution](#contribution)
- [License](#license)

## 🎯 Vue d'ensemble

Cette plateforme utilise des algorithmes de Machine Learning avancés pour prédire les résultats des matchs NHL avec une précision optimale. Le système combine :

- **Deep Learning** : Transformers, Graph Neural Networks
- **Apprentissage auto-supervisé** : Pre-training sur données non étiquetées
- **Statistiques avancées** : Corsi, Fenwick, Expected Goals (xG)
- **Analyse contextuelle** : Blessures, calendrier, voyages

### Objectifs

- ✅ Prédire les probabilités de victoire pour chaque équipe
- ✅ Calculer les expected goals et le total de buts
- ✅ Fournir des prédictions en temps réel pendant les matchs
- ✅ Analyser les tendances et patterns historiques
- ✅ Identifier les opportunités de paris à valeur

## ✨ Fonctionnalités

### Prédictions
- 🎲 Prédictions pré-match avec probabilités détaillées
- ⚡ Prédictions live mises à jour en temps réel
- 📊 Analyse de confiance et facteurs clés
- 🔮 Prédiction d'Over/Under sur le total de buts
- 📈 Historique et tracking de précision

### Analytics
- 📉 Statistiques avancées (Corsi, Fenwick, xG, PDO)
- 🎯 Analyse de performance des modèles
- 📊 Tableaux de bord interactifs
- 🔍 Comparaisons d'équipes et joueurs
- 📱 Alertes personnalisables

### API
- 🚀 REST API complète
- 🔐 Authentification JWT
- 📝 Documentation OpenAPI/Swagger
- ⚡ Cache intelligent (Redis)
- 🌐 Rate limiting configurable

## 🏗️ Architecture
````
┌─────────────────────────────────────────────────────────────┐
│                    Frontend Layer                            │
│  React/Next.js + Inertia.js + Tailwind CSS                  │
└─────────────────────────────────────────────────────────────┘
                            ↕
┌─────────────────────────────────────────────────────────────┐
│                  Laravel Application                         │
│  API • Services • Jobs • Events • Cache                     │
└─────────────────────────────────────────────────────────────┘
                            ↕
┌──────────────────┬──────────────────┬─────────────────────┐
│ PostgreSQL       │ Redis            │ Python ML Service   │
│ (Données)        │ (Cache/Queue)    │ (Inférence)         │
└──────────────────┴──────────────────┴─────────────────────┘
````

### Architecture Détaillée
````
app/
├── Domains/                    # Domain-Driven Design
│   ├── Prediction/            # Logique de prédiction
│   ├── DataIngestion/         # Collecte de données NHL
│   ├── Features/              # Feature engineering
│   ├── MachineLearning/       # Gestion des modèles ML
│   ├── Betting/               # Analyse de paris
│   └── User/                  # Gestion utilisateurs
├── Http/                      # Controllers & Resources
├── Jobs/                      # Background jobs
├── Services/                  # Services transversaux
└── Console/                   # Commands CLI
````

## 🛠️ Technologies

### Backend
- **Laravel 10.x** - Framework PHP
- **PostgreSQL 15** - Base de données principale
- **Redis 7** - Cache & Queues
- **Python 3.11** - ML Service

### Frontend
- **React 18** - UI Library
- **Next.js 14** - Framework React
- **Inertia.js** - SPA sans API
- **Tailwind CSS** - Styling
- **Recharts** - Visualisations

### Machine Learning
- **PyTorch 2.0+** - Deep Learning
- **PyTorch Lightning** - Training framework
- **Transformers** - Architecture principale
- **PyTorch Geometric** - Graph Neural Networks
- **XGBoost** - Gradient Boosting
- **Scikit-learn** - ML classique

### DevOps
- **Docker** - Containerization
- **Docker Compose** - Orchestration locale
- **GitHub Actions** - CI/CD
- **Nginx** - Reverse proxy

## 📦 Prérequis

### Système
- **PHP** >= 8.2
- **Composer** >= 2.5
- **Node.js** >= 18.x
- **Python** >= 3.11
- **PostgreSQL** >= 15
- **Redis** >= 7.0
- **Docker** >= 24.0 (optionnel)

### Extensions PHP Requises
````bash
php -m | grep -E 'pdo_pgsql|redis|mbstring|xml|bcmath|curl|zip|gd'
````

### Packages Python Requis
````bash
pytorch>=2.0.0
pytorch-lightning>=2.0.0
transformers>=4.30.0
torch-geometric>=2.3.0
xgboost>=2.0.0
fastapi>=0.100.0
````

## 🚀 Installation

### 1. Cloner le Repository
````bash
git clone https://github.com/votre-org/nhl-prediction.git
cd nhl-prediction
````

### 2. Installation avec Docker (Recommandé)
````bash
# Copier le fichier d'environnement
cp .env.example .env

# Démarrer les services
docker-compose up -d

# Installer les dépendances
docker-compose exec app composer install
docker-compose exec app npm install

# Générer la clé d'application
docker-compose exec app php artisan key:generate

# Migrer la base de données
docker-compose exec app php artisan migrate --seed

# Compiler les assets
docker-compose exec app npm run build
````

### 3. Installation Manuelle

#### Backend Laravel
````bash
# Installer les dépendances PHP
composer install

# Copier et configurer l'environnement
cp .env.example .env
php artisan key:generate

# Configurer la base de données dans .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nhl_prediction
DB_USERNAME=laravel
DB_PASSWORD=secret

# Migrer et seed
php artisan migrate --seed

# Installer les dépendances NPM
npm install
npm run build
````

#### Service ML Python
````bash
cd ml-service

# Créer un environnement virtuel
python -m venv venv
source venv/bin/activate  # Linux/Mac
# ou
venv\Scripts\activate     # Windows

# Installer les dépendances
pip install -r requirements.txt

# Télécharger les modèles pré-entraînés
python scripts/download_models.py
````

## ⚙️ Configuration

### Variables d'Environnement
````bash
# Application
APP_NAME="NHL Prediction"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://nhl-prediction.com

# Base de données
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=nhl_prediction
DB_USERNAME=laravel
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Python ML Service
PYTHON_ML_SERVICE_URL=http://ml-service:8000
PYTHON_ML_TIMEOUT=30

# NHL API
NHL_API_URL=https://api-web.nhle.com/v1
NHL_STATS_API_URL=https://api.nhle.com/stats/rest

# Cache
CACHE_DRIVER=redis
PREDICTION_CACHE_TTL=300

# Machine Learning
ML_EPOCHS=100
ML_BATCH_SIZE=32
ML_LEARNING_RATE=0.001
````

### Configuration des Modèles ML
````php
// config/ml.php

return [
    'python_service' => [
        'url' => env('PYTHON_ML_SERVICE_URL', 'http://localhost:8000'),
        'timeout' => env('PYTHON_ML_TIMEOUT', 30),
    ],

    'prediction' => [
        'cache_ttl' => 300,
        'confidence_threshold' => 0.6,
        'ensemble_weights' => [
            'transformer' => 0.4,
            'gnn' => 0.3,
            'xgboost' => 0.3,
        ],
    ],

    'training' => [
        'epochs' => env('ML_EPOCHS', 100),
        'batch_size' => 32,
        'validation_split' => 0.2,
    ],
];
````

## 💻 Utilisation

### Démarrer les Services

#### Avec Docker
````bash
# Démarrer tous les services
docker-compose up -d

# Voir les logs
docker-compose logs -f

# Arrêter les services
docker-compose down
````

#### Sans Docker
````bash
# Terminal 1 - Laravel
php artisan serve

# Terminal 2 - Queue Worker
php artisan queue:work

# Terminal 3 - Scheduler
php artisan schedule:work

# Terminal 4 - WebSocket Server
php artisan reverb:start

# Terminal 5 - Python ML Service
cd ml-service
python -m uvicorn app.main:app --reload

# Terminal 6 - Frontend (dev)
npm run dev
````

### Commandes Artisan Principales
````bash
# Récupérer les données NHL
php artisan nhl:fetch-games today
php artisan nhl:fetch-games 2024-01-15

# Calculer les features
php artisan features:calculate --team=all
php artisan features:calculate --game=12345

# Générer les prédictions
php artisan predictions:generate today
php artisan predictions:generate --game-id=12345

# Entraîner un modèle
php artisan ml:train --model=ensemble
php artisan ml:train --model=transformer --epochs=200

# Évaluer le modèle
php artisan ml:evaluate
php artisan ml:evaluate --model-version=v2.1.0

# Déployer un modèle
php artisan ml:deploy v2.1.0

# Maintenance
php artisan predictions:cleanup --days=90
php artisan cache:predictions:warm
````

### Ingestion de Données
````bash
# Première installation - Seed données historiques
php artisan db:seed --class=HistoricalDataSeeder

# Import massif de données
php artisan nhl:import-season 2023-2024
php artisan nhl:import-season 2022-2023

# Mise à jour quotidienne (automatique via scheduler)
php artisan nhl:daily-update
````

## 📚 API Documentation

### Authentification
````bash
# Obtenir un token
POST /api/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

# Utiliser le token
Authorization: Bearer {token}
````

### Endpoints Principaux

#### Prédictions
````bash
# Prédictions du jour
GET /api/v1/predictions/today

# Prédiction pour un match spécifique
GET /api/v1/predictions/game/{gameId}

# Prédiction live
GET /api/v1/predictions/game/{gameId}/live

# Créer une prédiction personnalisée
POST /api/v1/predictions
{
  "home_team_id": 1,
  "away_team_id": 2,
  "game_date": "2024-01-20"
}
````

#### Équipes & Statistiques
````bash
# Liste des équipes
GET /api/v1/teams

# Détails d'une équipe
GET /api/v1/teams/{teamId}

# Statistiques d'équipe
GET /api/v1/teams/{teamId}/stats?season=2023-2024

# Comparaison d'équipes
GET /api/v1/teams/compare?team1=1&team2=2
````

#### Analytics
````bash
# Performance du modèle
GET /api/v1/analytics/model-performance

# Accuracy par période
GET /api/v1/analytics/accuracy?start_date=2024-01-01&end_date=2024-01-31

# Tendances
GET /api/v1/analytics/trends
````

### Exemples de Réponses
````json
// GET /api/v1/predictions/game/12345
{
  "data": {
    "id": 67890,
    "game_id": 12345,
    "game": {
      "id": 12345,
      "home_team": {
        "id": 1,
        "name": "Montreal Canadiens",
        "abbreviation": "MTL"
      },
      "away_team": {
        "id": 2,
        "name": "Toronto Maple Leafs",
        "abbreviation": "TOR"
      },
      "game_date": "2024-01-20T19:00:00Z",
      "venue": "Bell Centre"
    },
    "home_win_probability": 0.5623,
    "away_win_probability": 0.3891,
    "overtime_probability": 0.0486,
    "total_goals_prediction": 6.2,
    "confidence_score": 0.78,
    "model_version": "v2.1.0",
    "key_factors": [
      {
        "factor": "Home team recent form",
        "impact": 0.23,
        "description": "MTL has won 7 of last 10 games"
      },
      {
        "factor": "Head-to-head record",
        "impact": 0.18,
        "description": "MTL 3-1 vs TOR this season"
      },
      {
        "factor": "Expected Goals differential",
        "impact": 0.15,
        "description": "MTL +0.8 xGF/game advantage"
      }
    ],
    "predicted_at": "2024-01-20T10:30:00Z"
  }
}
````

### Documentation Interactive

Une fois l'application lancée :
- **Swagger UI** : http://localhost:8000/api/documentation
- **Redoc** : http://localhost:8000/api/redoc

## 🤖 Machine Learning

### Architecture des Modèles

Le système utilise une approche d'ensemble combinant :

1. **Temporal Fusion Transformer**
    - Gère les séquences temporelles
    - Attention multi-têtes
    - Variable selection

2. **Graph Neural Network**
    - Modélise les interactions joueurs
    - Représentations d'équipe
    - Message passing

3. **XGBoost**
    - Features tabulaires
    - Haute performance
    - Interprétabilité

4. **Meta-Learner**
    - Combine les prédictions
    - Stacking ensemble
    - Optimise les poids

### Entraînement
````bash
# Phase 1 : Pre-training auto-supervisé
php artisan ml:pretrain --task=masked_prediction
php artisan ml:pretrain --task=contrastive_learning

# Phase 2 : Fine-tuning supervisé
php artisan ml:train --model=transformer --pretrained
php artisan ml:train --model=gnn --pretrained
php artisan ml:train --model=xgboost

# Phase 3 : Ensemble training
php artisan ml:train --model=ensemble --use-pretrained

# Hyperparameter tuning
php artisan ml:tune --trials=100
````

### Évaluation
````bash
# Évaluation complète
php artisan ml:evaluate --split=test

# Métriques spécifiques
php artisan ml:evaluate --metric=accuracy
php artisan ml:evaluate --metric=brier_score
php artisan ml:evaluate --metric=log_loss

# Backtesting
php artisan ml:backtest --start-date=2024-01-01 --end-date=2024-01-31
````

### Features Engineering

Le système calcule automatiquement :

**Statistiques de base**
- Goals For/Against per game
- Shots, Saves, Power Play %
- Penalty Kill %

**Statistiques avancées**
- Corsi For % (shot attempts)
- Fenwick For % (unblocked shots)
- Expected Goals (xG)
- PDO (save% + shooting%)
- High Danger Chances

**Features contextuelles**
- Days of rest / Back-to-back
- Travel distance
- Home/Away splits
- Injuries (WAR impact)
- Head-to-head records

## 👨‍💻 Développement

### Standards de Code
````bash
# PHP CS Fixer
vendor/bin/pint

# PHPStan
vendor/bin/phpstan analyse

# ESLint + Prettier
npm run lint
npm run format
````

### Workflow Git
````bash
# Créer une branche feature
git checkout -b feature/nouvelle-fonctionnalite

# Commits conventionnels
git commit -m "feat: ajouter prédictions live"
git commit -m "fix: corriger calcul de Corsi"
git commit -m "docs: mettre à jour README"

# Push et PR
git push origin feature/nouvelle-fonctionnalite
````

### Architecture DDD
````php
// Exemple de structure DDD

namespace App\Domains\Prediction;

// 1. Model (Entity)
class Prediction extends Model { }

// 2. DTO (Data Transfer Object)
class PredictionData
{
    public function __construct(
        public int $gameId,
        public float $homeWinProbability,
        // ...
    ) {}
}

// 3. Action (Use Case)
class GenerateGamePrediction
{
    public function execute(Game $game): PredictionData
    {
        // Logique métier
    }
}

// 4. Service (Orchestration)
class PredictionEngine
{
    public function predictGame(Game $game): PredictionData
    {
        return app(GenerateGamePrediction::class)->execute($game);
    }
}

// 5. Repository (Data Access)
class PredictionRepository
{
    public function findByGame(int $gameId): ?Prediction { }
}
````

## 🧪 Tests

### Lancer les Tests
````bash
# Tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter=PredictionTest
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Avec couverture
php artisan test --coverage
php artisan test --coverage-html=coverage
````

### Structure des Tests
````php
// tests/Feature/PredictionTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domains\DataIngestion\Models\Game;

class PredictionTest extends TestCase
{
    public function test_can_generate_prediction_for_game()
    {
        $game = Game::factory()->create();

        $response = $this->getJson("/api/v1/predictions/game/{$game->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'home_win_probability',
                    'away_win_probability',
                    'confidence_score',
                ]
            ]);
    }
}
````

### Tests ML
````bash
# Tests Python
cd ml-service
pytest tests/
pytest tests/ --cov=app
````

## 🚀 Déploiement

### Production avec Docker
````bash
# Build images de production
docker-compose -f docker-compose.prod.yml build

# Déployer
docker-compose -f docker-compose.prod.yml up -d

# Migrer la base de données
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Optimisations
docker-compose exec app php artisan optimize
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
````

### Variables de Production
````bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com

# Optimisations
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Sécurité
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=votre-domaine.com
````

### CI/CD avec GitHub Actions

Le fichier `.github/workflows/deploy.yml` gère :
- ✅ Tests automatiques
- ✅ Build & Push Docker images
- ✅ Déploiement automatique
- ✅ Notifications

## 📊 Monitoring

### Logs
````bash
# Laravel logs
tail -f storage/logs/laravel.log

# Queue logs
php artisan queue:failed

# ML Service logs
docker-compose logs -f ml-service
````

### Métriques

- **Application** : Laravel Telescope
- **Performance** : New Relic / DataDog
- **Erreurs** : Sentry
- **Uptime** : UptimeRobot

## 🤝 Contribution

Les contributions sont les bienvenues !

1. Fork le projet
2. Créer une branche (`git checkout -b feature/amazing`)
3. Commit (`git commit -m 'feat: ajouter feature amazing'`)
4. Push (`git push origin feature/amazing`)
5. Ouvrir une Pull Request

### Guidelines

- Suivre PSR-12 pour PHP
- Tests obligatoires pour nouvelles features
- Documentation à jour
- Commits conventionnels

## 📄 License

Ce projet est sous licence MIT. Voir [LICENSE](LICENSE) pour plus de détails.

## 👤 Auteur

**Laurent - IT Director @ CARA ÉNERGIE**

## 🙏 Remerciements

- NHL API pour les données
- PyTorch team
- Laravel community
- Contributeurs open-source

---

**Made with ❤️ and ☕ in Martinique** 🇲🇶

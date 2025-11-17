# 🤖 Guide Contextuel pour Assistants IA/LLM

> Document de référence pour les assistants IA travaillant sur le projet NHL Prediction Platform

## 📋 À Propos de ce Document

Ce document fournit le contexte nécessaire aux LLMs (Claude, ChatGPT, etc.) pour comprendre le projet et fournir une assistance optimale. **Veuillez lire ce document en entier avant de répondre à toute question sur le projet.**

---

## 🎯 Vue d'Ensemble du Projet

### Objectif Principal
Créer une plateforme de prédiction de matchs NHL utilisant du Deep Learning et l'apprentissage auto-supervisé pour fournir des prédictions précises avec des probabilités détaillées.

### Propriétaire & Contexte
- **Propriétaire** : Laurent, IT Director @ CARA ÉNERGIE (Martinique, France)
- **Expertise** : Laravel, PHP, Python, Deep Learning, Dolibarr ERP
- **Contexte** : Projet personnel de prédiction sportive
- **Langue principale** : Français (documentation en français)

---

## 🏗️ Architecture Technique

### Stack Technologique Principale

#### Backend
- **Framework** : Laravel 10.x (PHP 8.2+)
- **Base de données** : PostgreSQL 15
- **Cache/Queue** : Redis 7
- **Architecture** : Domain-Driven Design (DDD)

#### Machine Learning
- **Service** : Python 3.11 + FastAPI
- **Framework ML** : PyTorch 2.0+, PyTorch Lightning
- **Modèles** :
    - Temporal Fusion Transformer
    - Graph Neural Networks (PyTorch Geometric)
    - XGBoost
    - Ensemble (stacking)

#### Frontend
- **Framework** : React 18 + Next.js 14
- **Bridge** : Inertia.js (SPA sans API REST)
- **Styling** : Tailwind CSS
- **Charts** : Recharts

### Principes Architecturaux

1. **Séparation des Responsabilités**
    - Laravel gère : API, Business Logic, Data Ingestion, Feature Engineering
    - Python gère : Training ML, Inference ML uniquement

2. **Domain-Driven Design**
````
   app/Domains/
   ├── Prediction/        # Logique de prédiction
   ├── DataIngestion/     # Collecte données NHL
   ├── Features/          # Feature engineering
   ├── MachineLearning/   # Gestion modèles ML
   └── Betting/           # Analyse de paris
````

3. **Communication Inter-Services**
    - Laravel → Python : HTTP REST (via Guzzle/HTTP Client)
    - Pas de gRPC, pas de message queue complexe
    - Simple et maintenable

---

## 🔑 Concepts Clés à Comprendre

### 1. Apprentissage Auto-Supervisé (Self-Supervised Learning)

**Définition** : Technique où le modèle apprend des représentations à partir de données non étiquetées en créant ses propres "labels".

**Application dans ce projet** :
````python
# Tâche 1 : Masked Prediction
# Masquer des statistiques et apprendre à les prédire
masked_stats = mask_random_features(game_stats)
prediction = model.predict_masked(masked_stats)
loss = mse_loss(prediction, original_stats)

# Tâche 2 : Contrastive Learning
# Apprendre à distinguer matchs similaires vs différents
anchor = encode_game(game_a)
positive = encode_game(similar_game)  # Même équipe, contexte similaire
negative = encode_game(different_game)
loss = contrastive_loss(anchor, positive, negative)

# Tâche 3 : Temporal Prediction
# Prédire performances futures à partir de l'historique
future_performance = model.predict_next(historical_sequence)
````

**Pourquoi c'est important** :
- ✅ Exploite toutes les données NHL disponibles (pas besoin de labels)
- ✅ Apprend des représentations riches avant fine-tuning
- ✅ Améliore la généralisation du modèle

### 2. Statistiques Avancées NHL

#### Corsi
````php
// Corsi = Tentatives de tir (shots + blocked + missed)
$corsiFor = $shots + $blockedShots + $missedShots;
$corsiAgainst = $opponentShots + $opponentBlocked + $opponentMissed;
$corsiPercentage = ($corsiFor / ($corsiFor + $corsiAgainst)) * 100;
````

#### Fenwick
````php
// Fenwick = Tentatives de tir non bloquées
$fenwickFor = $shots + $missedShots;
$fenwickAgainst = $opponentShots + $opponentMissed;
$fenwickPercentage = ($fenwickFor / ($fenwickFor + $fenwickAgainst)) * 100;
````

#### Expected Goals (xG)
````php
// Probabilité qu'un tir devienne un but basé sur :
// - Distance du filet
// - Angle de tir
// - Type de tir (wrist, slap, tip-in, etc.)
// - Contexte (rebound, rush, power play)

function calculateShotXG($distance, $angle, $shotType, $isRebound) {
    $baseProbability = exp(-0.09 * $distance + 2.3);
    $angleMultiplier = 1 - (abs($angle) / 90) * 0.3;
    $shotMultiplier = SHOT_MULTIPLIERS[$shotType];
    $reboundMultiplier = $isRebound ? 2.0 : 1.0;

    return min($baseProbability * $angleMultiplier * $shotMultiplier * $reboundMultiplier, 0.95);
}
````

#### PDO
````php
// PDO = Save% + Shooting%
// Indicateur de "chance" - tend à revenir à 100
$pdo = ($savePercentage + $shootingPercentage) * 100;
// PDO > 102 : équipe "chanceuse" (régressera)
// PDO < 98 : équipe "malchanceuse" (s'améliorera)
````

### 3. Architecture des Modèles ML

#### Temporal Fusion Transformer (TFT)
````
Avantages :
- ✅ Gère séries temporelles complexes
- ✅ Attention multi-têtes pour pondérer l'importance
- ✅ Variable selection automatique
- ✅ Interprétabilité (feature importance)

Utilisation :
- Séquences de matchs
- Features temporelles (forme récente, tendances)
- Prédictions avec horizon temporel
````

#### Graph Neural Networks (GNN)
````
Avantages :
- ✅ Modélise relations joueurs/équipes comme graphe
- ✅ Capture interactions complexes (passes, combinaisons)
- ✅ Embeddings de joueurs/équipes

Structure du graphe :
Noeuds = Joueurs
Arêtes = Interactions (ligne, paire défensive, chimie)
````

#### Ensemble (Stacking)
````
Niveau 1 (Base Models):
├── Temporal Fusion Transformer (40%)
├── Graph Neural Network (30%)
└── XGBoost (30%)
         ↓
Niveau 2 (Meta-Learner):
└── Neural Network (combine les prédictions)
````

---

## 📝 Conventions de Code

### PHP/Laravel
````php
// ✅ GOOD : Utiliser le type hinting strict
public function predictGame(Game $game): PredictionData
{
    return new PredictionData(
        gameId: $game->id,
        homeWinProbability: $prediction['home_win'],
        // ...
    );
}

// ✅ GOOD : DTOs pour transfert de données
class PredictionData
{
    public function __construct(
        public readonly int $gameId,
        public readonly float $homeWinProbability,
        public readonly float $confidence,
    ) {}
}

// ✅ GOOD : Services pour logique métier
class PredictionEngine
{
    public function __construct(
        private FeatureExtractor $featureExtractor,
        private PythonMLBridge $mlBridge,
    ) {}
}

// ❌ BAD : Logique métier dans le controller
public function predict(Request $request)
{
    $features = [...]; // NON !
    $prediction = Http::post(...); // NON !
}

// ✅ GOOD : Controller mince
public function predict(Game $game)
{
    $prediction = $this->predictionEngine->predictGame($game);
    return new PredictionResource($prediction);
}
````

### Python/ML
````python
# ✅ GOOD : Type hints
def predict(self, features: torch.Tensor) -> Dict[str, float]:
    with torch.no_grad():
        output = self.model(features)
    return {
        'home_win': float(output[0]),
        'away_win': float(output[1]),
    }

# ✅ GOOD : Docstrings détaillés
def calculate_corsi(
    self,
    shots_for: int,
    blocked_for: int,
    missed_for: int,
) -> float:
    """
    Calculate Corsi percentage.

    Corsi measures shot attempts differential and is a strong
    indicator of puck possession and offensive pressure.

    Args:
        shots_for: Shots on goal by the team
        blocked_for: Blocked shots by the team
        missed_for: Missed shots by the team

    Returns:
        Corsi percentage (0-100)
    """
    pass
````

---

## 🚨 Points d'Attention pour les Assistants IA

### 1. Ne PAS Proposer de Réarchitecturer Sans Raison

❌ **Mauvais conseil** :
> "Je recommande de migrer vers une architecture microservices avec Kubernetes..."

✅ **Bon conseil** :
> "La structure actuelle Laravel + Python est adaptée. Voici comment optimiser X..."

**Pourquoi** : Laurent a choisi cette architecture consciemment. Elle est simple, maintenable et suffisante.

### 2. Privilégier Laravel Sur Python Quand Possible

❌ **Mauvais** :
````python
# Créer un service Python pour le feature engineering
class FeatureEngineer:
    def calculate_advanced_stats(self):
        # ...
````

✅ **Bon** :
````php
// Feature engineering en PHP/Laravel
class AdvancedStatsCalculator
{
    public function calculateCorsi(Team $team): float
    {
        // PHP peut faire ça très bien
    }
}
````

**Règle** : Python uniquement pour Training ML et Inference ML. Tout le reste en Laravel.

### 3. Respecter le Domain-Driven Design

❌ **Mauvais** :
````php
app/Services/PredictionService.php  // Trop vague
app/Helpers/FeatureHelper.php       // Anti-pattern
````

✅ **Bon** :
````php
app/Domains/Prediction/Services/PredictionEngine.php
app/Domains/Features/Calculators/CorsiCalculator.php
````

### 4. Toujours Proposer des Tests

Quand tu proposes du code, inclus TOUJOURS :
````php
// Code de production
class PredictionEngine { }

// + Test correspondant
class PredictionEngineTest extends TestCase
{
    public function test_predicts_game_correctly() { }
}
````

### 5. Documentation en Français

- ✅ Commentaires en français
- ✅ Noms de variables en anglais (convention)
- ✅ Messages utilisateur en français
````php
// ✅ GOOD
/**
 * Calcule le Corsi pour une équipe donnée.
 * Le Corsi mesure les tentatives de tir et est un indicateur
 * de possession de rondelle.
 */
public function calculateCorsi(Team $team): float
{
    $shotsFor = $team->shots;
    $blockedFor = $team->blocked_shots;
    // ...
}
````

---

## 🔍 Questions Fréquentes des Assistants IA

### Q1 : "Dois-je utiliser un ORM comme Eloquent ?"
**R** : OUI, absolument. Laravel Eloquent est le standard et doit être utilisé.

### Q2 : "Dois-je proposer des tests unitaires ET d'intégration ?"
**R** : OUI. Toujours les deux. Feature tests pour les flows, Unit tests pour la logique métier.

### Q3 : "Quelle version de Python/Laravel/etc. ?"
**R** : Voir les badges en haut du README. Toujours utiliser les dernières versions stables.

### Q4 : "Dois-je expliquer les concepts ML en détail ?"
**R** : OUI, Laurent apprécie les explications détaillées avec des exemples concrets.

### Q5 : "Dois-je proposer des alternatives ou une seule solution ?"
**R** : Proposer LA solution recommandée + mentionner 1-2 alternatives si pertinent.

### Q6 : "Comment gérer le cache ?"
**R** : Redis pour tout (cache applicatif, sessions, queues). TTL courts pour prédictions (5 min).

---

## 📚 Ressources Complémentaires

### Documentation Technique
- [Laravel Docs](https://laravel.com/docs/10.x)
- [PyTorch Docs](https://pytorch.org/docs/stable/index.html)
- [PyTorch Lightning](https://lightning.ai/docs/pytorch/stable/)
- [NHL API](https://api-web.nhle.com/v1)

### Statistiques Hockey
- [Evolving Hockey](https://evolving-hockey.com/) - Advanced stats reference
- [MoneyPuck](https://moneypuck.com/) - xG models & analytics
- [Natural Stat Trick](https://www.naturalstattrick.com/) - Stats database

### Papers ML Pertinents
- "Temporal Fusion Transformers for Interpretable Multi-horizon Time Series Forecasting"
- "Graph Neural Networks: A Review of Methods and Applications"
- "A Simple Framework for Contrastive Learning of Visual Representations" (SimCLR)

---

## 🎯 Checklist pour Répondre aux Questions

Avant de répondre, vérifie :

- [ ] J'ai compris le contexte (Laravel-first, DDD, simplicité)
- [ ] Ma solution utilise les technologies du stack existant
- [ ] J'ai fourni des exemples de code concrets
- [ ] J'ai inclus des tests si applicable
- [ ] J'ai documenté en français avec clarté
- [ ] J'ai expliqué le "pourquoi" pas juste le "comment"
- [ ] J'ai mentionné les edge cases / limitations
- [ ] Ma réponse est actionnelle (Laurent peut l'implémenter)

---

## 🚀 Templates de Réponse

### Pour une Feature Complète
````markdown
# Feature : [Nom de la feature]

## 🎯 Objectif
[Description courte]

## 📋 Composants à Créer

### 1. Migration
```php
[Code migration]
```

### 2. Model
```php
[Code model]
```

### 3. Service
```php
[Code service]
```

### 4. Controller
```php
[Code controller]
```

### 5. Tests
```php
[Code tests]
```

## 🔧 Configuration
[Config nécessaire]

## 📝 Utilisation
[Exemples d'utilisation]

## ⚠️ Points d'Attention
[Warnings, limitations, edge cases]
````

### Pour du Code ML
````markdown
# Modèle : [Nom du modèle]

## 🎯 Architecture
[Explication de l'architecture]

## 📊 Pourquoi ce modèle ?
[Justification du choix]

## 💻 Implémentation

### Python (ml-service)
```python
[Code Python]
```

### Laravel (bridge)
```php
[Code PHP]
```

## 🧪 Validation
[Comment valider que ça marche]

## 📈 Métriques Attendues
[Accuracy, Brier Score, etc.]
````

---

## ⚡ Snippets Utiles

### Créer un nouveau Domain
````bash
# Structure
app/Domains/MonDomain/
├── Models/
├── Services/
├── Actions/
├── DTOs/
├── Events/
├── Jobs/
└── Repositories/
````

### Ajouter une Feature au ML
````php
// 1. Calculator
class MyFeatureCalculator
{
    public function calculate(Game $game): float { }
}

// 2. Ajouter au FeatureExtractor
private function extractMyFeature(Game $game): array
{
    return [
        'my_feature' => $this->myCalculator->calculate($game),
    ];
}

// 3. Test
public function test_calculates_my_feature_correctly() { }
````

---

## 🎓 Ton et Style de Réponse

- **Professionnel mais accessible** : Pas trop formel, pas trop casual
- **Détaillé mais structuré** : Utiliser headers, listes, code blocks
- **Pédagogique** : Expliquer le "pourquoi" derrière les choix
- **Actionnable** : Fournir du code ready-to-use
- **Honnête** : Mentionner limitations et alternatives

**Exemple de bon ton** :
> "Pour implémenter cette feature, je te recommande d'utiliser un Service dans le domaine Prediction. Voici pourquoi : [explication]. Le code ressemblerait à ça : [code]. Note que cette approche a l'avantage de [avantage] mais nécessite [considération]."

---

## 📞 Support

Si tu as besoin de clarifications sur le projet en tant qu'assistant IA, demande :
- "Peux-tu clarifier si [X] s'applique à ce contexte ?"
- "Je vois deux approches possibles : [A] et [B]. Laquelle préfères-tu ?"
- "Avant de proposer une solution, as-tu déjà implémenté [Y] ?"

---

## ✅ Validation Finale

Avant d'envoyer ta réponse :

1. ✅ Code compilable/syntaxe correcte
2. ✅ Suit l'architecture DDD
3. ✅ Utilise le stack existant
4. ✅ Inclut tests si applicable
5. ✅ Documentation claire en français
6. ✅ Exemples concrets fournis
7. ✅ Edge cases mentionnés

---

**Document mis à jour** : Novembre 2025
**Version** : 1.0.0

---

*Ce document doit être lu par tout assistant IA avant de travailler sur le projet NHL Prediction Platform.*

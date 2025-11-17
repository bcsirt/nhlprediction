# 🤖 Domaine AI - Intégration Claude (Anthropic)

> Intégration de Claude AI pour l'analyse intelligente des matchs NHL, génération de rapports et conseils stratégiques.

## 📋 Table des Matières

- [Vue d'ensemble](#vue-densemble)
- [Architecture](#architecture)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Types d'Analyses](#types-danalyses)
- [Modèles Claude](#modèles-claude)
- [Coûts & Limites](#coûts--limites)
- [Exemples](#exemples)

## 🎯 Vue d'ensemble

Le domaine AI fournit une interface complète pour utiliser Claude AI d'Anthropic afin de générer des analyses en langage naturel sur :

- **Analyses de matchs** : Analyse approfondie des matchs NHL avec statistiques avancées
- **Comparaison d'équipes** : Comparaisons détaillées entre équipes
- **Explication de value bets** : Explications pédagogiques des opportunités de paris
- **Conseils stratégiques** : Recommandations sur la gestion de bankroll et stratégie
- **Chat interactif** : Assistant conversationnel pour répondre aux questions

## 🏗️ Architecture

### Structure du Domaine

````
app/Domains/AI/
├── Models/              # Modèles Eloquent (à créer)
├── Services/
│   ├── ClaudeService.php              # Service principal API Claude
│   ├── ClaudeAnalysisService.php      # Service d'analyse haut niveau
│   ├── PromptBuilder.php              # Construction des prompts
│   ├── ClaudeReportGenerator.php      # Génération de rapports
│   ├── ClaudeStrategyAdvisor.php      # Conseils stratégiques
│   └── ClaudeChatService.php          # Service de chat
├── Jobs/                # Jobs asynchrones
├── DTOs/
│   ├── ClaudeRequest.php              # DTO requête
│   ├── ClaudeResponse.php             # DTO réponse
│   └── AnalysisResult.php             # DTO résultat d'analyse
├── Enums/
│   ├── ClaudeModel.php                # Modèles disponibles
│   ├── AnalysisType.php               # Types d'analyses
│   └── ConversationRole.php           # Rôles conversation
└── Repositories/        # Repositories (à créer)
````

### Services Principaux

#### ClaudeService
Service de bas niveau pour communiquer avec l'API Claude :
- Gestion des requêtes HTTP via Guzzle
- Retry logic avec exponential backoff
- Caching des réponses
- Logging et tracking d'utilisation

#### ClaudeAnalysisService
Service de haut niveau pour les analyses :
- Analyse de matchs NHL
- Comparaison d'équipes
- Explication de value bets
- Génération de conseils stratégiques

#### PromptBuilder
Construction intelligente des prompts :
- Templates pour chaque type d'analyse
- Formatage des données
- Contexte et guidelines

## ⚙️ Configuration

### Variables d'Environnement

````bash
# API Anthropic
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_API_URL=https://api.anthropic.com/v1
ANTHROPIC_API_VERSION=2023-06-01
ANTHROPIC_TIMEOUT=60

# Modèle par défaut
CLAUDE_DEFAULT_MODEL=claude-3-5-sonnet-20241022

# Features
CLAUDE_GAME_ANALYSIS=true
CLAUDE_REPORTS=true
CLAUDE_STRATEGY=true
CLAUDE_CHAT=true
````

### Fichier de Configuration

Le fichier `config/claude.php` contient toute la configuration :

- Modèles et leurs caractéristiques
- Paramètres par défaut (max_tokens, temperature, etc.)
- Configuration des analyses
- Prompts système
- Limites et quotas
- Caching
- Logging

## 💻 Utilisation

### Analyser un Match

````php
use App\Domains\AI\Services\ClaudeAnalysisService;
use App\Domains\DataIngestion\Models\Game;

$analysisService = app(ClaudeAnalysisService::class);
$game = Game::find(1);

$result = $analysisService->analyzeGame($game);

echo $result->content;  // Analyse en markdown
// Accès aux métadonnées
echo "Coût: " . $result->claudeResponse->getTotalCost() . " USD";
echo "Tokens: " . $result->claudeResponse->getTotalTokens();
````

### Comparer Deux Équipes

````php
$result = $analysisService->compareTeams(
    team1Id: 1,  // Canadiens
    team2Id: 2   // Maple Leafs
);

echo $result->content;
````

### Expliquer un Value Bet

````php
$result = $analysisService->explainValueBet(
    valueBetId: 123,
    predictionData: [
        'home_win_probability' => 62.5,
        'confidence' => 0.78,
    ],
    oddsData: [
        'home_odds' => 1.85,
        'bookmaker' => 'Bet365',
    ]
);

echo $result->content;  // Explication pédagogique
````

### Utilisation Directe du ClaudeService

````php
use App\Domains\AI\Services\ClaudeService;
use App\Domains\AI\DTOs\ClaudeRequest;
use App\Domains\AI\Enums\ClaudeModel;

$claudeService = app(ClaudeService::class);

$request = new ClaudeRequest(
    prompt: "Explique-moi le Corsi en hockey.",
    model: ClaudeModel::HAIKU,
    maxTokens: 1024,
    temperature: 0.7,
);

$response = $claudeService->sendRequest($request);

if (!$response->isError()) {
    echo $response->content;
}
````

## 📊 Types d'Analyses

### 1. Analyse de Match (GAME_ANALYSIS)
- **Modèle recommandé** : Sonnet
- **Max tokens** : 4096
- **Inclut** : Stats avancées, forme récente, H2H, prédiction
- **Cache TTL** : 1 heure

### 2. Conseil Stratégique (STRATEGY_ADVICE)
- **Modèle recommandé** : Opus
- **Max tokens** : 4096
- **Inclut** : Gestion bankroll, sélection paris, risk management
- **Cache TTL** : 30 minutes

### 3. Rapport de Performance (PERFORMANCE_REPORT)
- **Modèle recommandé** : Sonnet
- **Max tokens** : 8192
- **Inclut** : ROI, tendances, analyse approfondie
- **Cache TTL** : 24 heures

### 4. Explication Value Bet (VALUE_BET_EXPLANATION)
- **Modèle recommandé** : Haiku
- **Max tokens** : 2048
- **Inclut** : Calcul de value, facteurs, recommandations
- **Cache TTL** : 5 minutes

## 🤖 Modèles Claude

### Claude 3 Opus
- **ID** : `claude-3-opus-20240229`
- **Usage** : Analyses complexes, conseils stratégiques
- **Coût** : $15/1M input tokens, $75/1M output tokens
- **Performance** : Meilleure qualité

### Claude 3.5 Sonnet ⭐ (Recommandé)
- **ID** : `claude-3-5-sonnet-20241022`
- **Usage** : Analyses de matchs, rapports
- **Coût** : $3/1M input tokens, $15/1M output tokens
- **Performance** : Excellent rapport qualité/prix

### Claude 3.5 Haiku
- **ID** : `claude-3-5-haiku-20241022`
- **Usage** : Chat, résumés rapides
- **Coût** : $0.80/1M input tokens, $4/1M output tokens
- **Performance** : Rapide et économique

## 💰 Coûts & Limites

### Coûts Estimés

Pour une analyse de match typique (Sonnet) :
- Input : ~2000 tokens ≈ $0.006
- Output : ~1500 tokens ≈ $0.0225
- **Total par analyse** : ~$0.03

### Limites Configurées

````php
// config/claude.php

'limits' => [
    'per_user' => [
        'daily' => 100,   // 100 requêtes/jour/utilisateur
        'hourly' => 20,   // 20 requêtes/heure/utilisateur
    ],
    'global' => [
        'daily' => 1000,  // 1000 requêtes/jour global
        'hourly' => 200,  // 200 requêtes/heure global
    ],
],
````

### Vérifier les Limites

````php
$claudeService = app(ClaudeService::class);

if ($claudeService->isRateLimited()) {
    throw new \Exception('Limite quotidienne atteinte');
}

// Obtenir les stats d'utilisation
$stats = $claudeService->getUsageStats();
// [
//     'date' => '2025-01-15',
//     'requests' => 47,
//     'tokens' => ['input' => 94000, 'output' => 70500],
//     'cost' => 1.34
// ]
````

## 📝 Exemples

### Job Asynchrone pour Analyser un Match

````php
use App\Domains\AI\Jobs\GenerateGameAnalysis;

// Dispatcher le job
GenerateGameAnalysis::dispatch($game);

// Le job analysera le match en arrière-plan
````

### Génération de Rapport Personnalisé

````php
use App\Domains\AI\Services\ClaudeReportGenerator;

$reportGenerator = app(ClaudeReportGenerator::class);

$report = $reportGenerator->generatePerformanceReport([
    'user_id' => 1,
    'period' => 'last_30_days',
    'include_recommendations' => true,
]);

echo $report->content;  // Rapport en markdown
````

### Conversation Interactive

````php
use App\Domains\AI\Services\ClaudeChatService;

$chatService = app(ClaudeChatService::class);

$response = $chatService->sendMessage(
    userId: 1,
    message: "Explique-moi pourquoi cette prédiction a un niveau de confiance de 78%",
    conversationId: 'conv_123'
);

echo $response->content;
````

## 🔒 Sécurité & Bonnes Pratiques

### 1. Protection de la Clé API
````bash
# Ne jamais commit la clé API
# Utiliser .env et ne pas la partager
ANTHROPIC_API_KEY=sk-ant-...
````

### 2. Rate Limiting
````php
// Vérifier avant chaque requête
if ($claudeService->isRateLimited()) {
    return response()->json(['error' => 'Rate limit exceeded'], 429);
}
````

### 3. Gestion des Erreurs
````php
try {
    $result = $analysisService->analyzeGame($game);
} catch (\RuntimeException $e) {
    Log::error('Claude analysis failed', ['error' => $e->getMessage()]);
    // Fallback ou message d'erreur utilisateur
}
````

### 4. Caching Intelligent
Le cache est automatique mais peut être contrôlé :

````php
// Forcer le bypass du cache
config(['claude.cache.enabled' => false]);
$result = $analysisService->analyzeGame($game);

// Effacer le cache
$claudeService->clearCache('game_analysis');
````

## 🧪 Tests

````php
// tests/Feature/AI/ClaudeServiceTest.php

public function test_can_send_request_to_claude()
{
    $service = app(ClaudeService::class);

    $request = ClaudeRequest::forChat('Bonjour!');
    $response = $service->sendRequest($request);

    $this->assertFalse($response->isError());
    $this->assertNotEmpty($response->content);
}
````

## 📚 Ressources

- [Documentation Anthropic Claude](https://docs.anthropic.com/)
- [API Reference](https://docs.anthropic.com/en/api)
- [Best Practices](https://docs.anthropic.com/en/docs/build-with-claude/prompt-engineering)
- [Rate Limits](https://docs.anthropic.com/en/api/rate-limits)

---

**Maintenu par** : Laurent - IT Director @ CARA ÉNERGIE
**Dernière mise à jour** : Novembre 2025

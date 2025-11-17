<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Anthropic Claude API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'intégration avec l'API Claude d'Anthropic.
    | Claude est utilisé pour l'analyse intelligente des matchs,
    | la génération de rapports et les conseils de stratégie.
    |
    */

    'api' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'url' => env('ANTHROPIC_API_URL', 'https://api.anthropic.com/v1'),
        'version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
        'timeout' => env('ANTHROPIC_TIMEOUT', 60),
        'max_retries' => 3,
        'retry_delay' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration des modèles Claude disponibles.
    |
    */

    'models' => [
        // Modèle par défaut
        'default' => env('CLAUDE_DEFAULT_MODEL', 'claude-3-5-sonnet-20241022'),

        // Modèles disponibles
        'available' => [
            'opus' => 'claude-3-opus-20240229',
            'sonnet' => 'claude-3-5-sonnet-20241022',
            'haiku' => 'claude-3-5-haiku-20241022',
        ],

        // Utilisation par type de tâche
        'task_models' => [
            'game_analysis' => 'claude-3-5-sonnet-20241022',
            'report_generation' => 'claude-3-5-sonnet-20241022',
            'strategy_advice' => 'claude-3-opus-20240229',
            'chat' => 'claude-3-5-haiku-20241022',
            'quick_summary' => 'claude-3-5-haiku-20241022',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Parameters
    |--------------------------------------------------------------------------
    |
    | Paramètres par défaut pour les requêtes à Claude.
    |
    */

    'defaults' => [
        'max_tokens' => 4096,
        'temperature' => 0.7,
        'top_p' => 1.0,
        'top_k' => null,
        'stream' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Analysis Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour les différents types d'analyses.
    |
    */

    'analysis' => [
        // Analyse de match
        'game' => [
            'enabled' => true,
            'max_tokens' => 4096,
            'temperature' => 0.5,
            'cache_ttl' => 3600, // 1 heure
            'include_stats' => true,
            'include_trends' => true,
            'include_matchup_history' => true,
        ],

        // Génération de rapports
        'report' => [
            'enabled' => true,
            'max_tokens' => 8192,
            'temperature' => 0.6,
            'formats' => ['markdown', 'html', 'json'],
            'default_format' => 'markdown',
        ],

        // Conseils de stratégie
        'strategy' => [
            'enabled' => true,
            'max_tokens' => 4096,
            'temperature' => 0.7,
            'include_risk_assessment' => true,
            'include_bankroll_advice' => true,
        ],

        // Chat interactif
        'chat' => [
            'enabled' => true,
            'max_tokens' => 2048,
            'temperature' => 0.8,
            'max_conversation_history' => 20,
            'context_window' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt Templates
    |--------------------------------------------------------------------------
    |
    | Configuration des templates de prompts.
    |
    */

    'prompts' => [
        'system' => [
            'game_analyst' => "Tu es un expert en analyse de hockey NHL avec une connaissance approfondie des statistiques avancées (Corsi, Fenwick, xG, PDO). Tu fournis des analyses détaillées et objectives basées sur les données.",

            'strategy_advisor' => "Tu es un conseiller en stratégie de paris sportifs spécialisé en NHL. Tu utilises les principes du Kelly Criterion et de la gestion de bankroll pour fournir des conseils prudents et réfléchis.",

            'report_generator' => "Tu es un analyste sportif professionnel qui génère des rapports clairs, structurés et informatifs sur les performances NHL.",

            'chat_assistant' => "Tu es un assistant conversationnel expert en NHL qui aide les utilisateurs à comprendre les prédictions, les statistiques et les stratégies de paris.",
        ],

        'guidelines' => [
            'always_factual' => true,
            'cite_statistics' => true,
            'explain_reasoning' => true,
            'warn_about_risks' => true,
            'use_french' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage Limits & Quotas
    |--------------------------------------------------------------------------
    |
    | Gestion des limites d'utilisation.
    |
    */

    'limits' => [
        // Requests par utilisateur
        'per_user' => [
            'daily' => 100,
            'hourly' => 20,
        ],

        // Requests globales
        'global' => [
            'daily' => 1000,
            'hourly' => 200,
        ],

        // Alertes
        'alerts' => [
            'threshold' => 0.8, // 80% du quota
            'notify' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Configuration du cache pour les réponses Claude.
    |
    */

    'cache' => [
        'enabled' => true,
        'driver' => 'redis',
        'prefix' => 'claude',

        'ttl' => [
            'game_analysis' => 3600, // 1 heure
            'report' => 86400, // 24 heures
            'strategy' => 1800, // 30 minutes
            'chat' => 300, // 5 minutes
        ],

        // Cache des conversations
        'conversation_ttl' => 604800, // 7 jours
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configuration du logging des interactions avec Claude.
    |
    */

    'logging' => [
        'enabled' => true,
        'log_requests' => true,
        'log_responses' => true,
        'log_errors' => true,
        'log_usage' => true,

        'channels' => [
            'default' => 'claude',
            'error' => 'claude-errors',
        ],

        // Anonymisation pour la confidentialité
        'anonymize' => [
            'user_data' => true,
            'sensitive_info' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Activation/désactivation des fonctionnalités.
    |
    */

    'features' => [
        'game_analysis' => env('CLAUDE_GAME_ANALYSIS', true),
        'report_generation' => env('CLAUDE_REPORTS', true),
        'strategy_advice' => env('CLAUDE_STRATEGY', true),
        'interactive_chat' => env('CLAUDE_CHAT', true),
        'batch_analysis' => env('CLAUDE_BATCH', false),
        'streaming' => env('CLAUDE_STREAMING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Safety & Moderation
    |--------------------------------------------------------------------------
    |
    | Paramètres de sécurité et modération.
    |
    */

    'safety' => [
        // Filtrage des contenus
        'content_filtering' => true,

        // Limites de responsabilité
        'disclaimer' => [
            'enabled' => true,
            'message' => "Avertissement : Les analyses et conseils fournis par l'IA sont informatifs uniquement. Ne constituent pas des conseils financiers. Pariez de manière responsable.",
        ],

        // Détection d'abus
        'abuse_detection' => [
            'enabled' => true,
            'max_requests_per_minute' => 10,
            'ban_duration' => 3600, // 1 heure
        ],
    ],

];

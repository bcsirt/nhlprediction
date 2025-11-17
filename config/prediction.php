<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Prediction Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour le système de prédiction ML du projet NHL.
    |
    */

    // Modèles ML
    'models' => [
        'default' => env('PREDICTION_DEFAULT_MODEL', 'ensemble'),

        'storage_path' => storage_path('app/ml_models'),

        // Configuration Python
        'python' => [
            'path' => env('PYTHON_PATH', '/usr/bin/python3'),
            'venv_path' => env('PYTHON_VENV_PATH', base_path('ml_models/venv')),
            'script_path' => base_path('ml_models/scripts'),
        ],

        // Types de modèles disponibles
        'types' => [
            'random_forest',
            'gradient_boosting',
            'xgboost',
            'neural_network',
            'ensemble',
            'statistical',
            'ai_powered',
        ],
    ],

    // Feature Engineering
    'features' => [
        // Nombre de matchs pour rolling averages
        'rolling_windows' => [5, 10, 20],

        // Features à calculer
        'enabled' => [
            'rolling_averages' => true,
            'advanced_stats' => true, // Corsi, Fenwick, xG
            'form_indicators' => true,
            'h2h_stats' => true,
            'special_teams' => true,
            'rest_advantage' => true,
            'injury_impact' => false, // À implémenter manuellement
        ],

        // Poids des features pour ensemble
        'weights' => [
            'recent_form' => 0.3,
            'advanced_stats' => 0.25,
            'h2h' => 0.15,
            'home_ice' => 0.1,
            'special_teams' => 0.1,
            'rest' => 0.05,
            'injuries' => 0.05,
        ],
    ],

    // Backtesting
    'backtest' => [
        // Périodes par défaut
        'periods' => [
            'short' => '1 month',
            'medium' => '3 months',
            'long' => '1 year',
            'full_season' => '8 months',
        ],

        // Train/Test split
        'train_test_split' => 0.8, // 80% train, 20% test

        // Cross-validation
        'cv_folds' => 5,

        // Métriques à calculer
        'metrics' => [
            'accuracy',
            'precision',
            'recall',
            'f1_score',
            'auc_roc',
            'brier_score',
            'log_loss',
        ],
    ],

    // Prédictions
    'predictions' => [
        // Types de prédictions activées
        'enabled_types' => [
            'winner',
            'over_under',
            'total_goals',
        ],

        // Seuils de confiance
        'confidence_thresholds' => [
            'very_high' => 90,
            'high' => 75,
            'medium' => 55,
            'low' => 40,
        ],

        // Mise à jour automatique
        'auto_update' => [
            'enabled' => env('PREDICTION_AUTO_UPDATE', true),
            'frequency' => '1 hour', // Fréquence de mise à jour
            'before_game_hours' => 2, // Heures avant le match
        ],

        // Cache
        'cache' => [
            'enabled' => true,
            'ttl' => 3600, // 1 heure
        ],
    ],

    // Intégration Claude AI
    'ai' => [
        'enabled' => env('PREDICTION_AI_ENABLED', true),

        // Utiliser Claude pour analyses qualitatives
        'use_for_analysis' => true,

        // Combiner ML + AI
        'ensemble_with_ml' => true,
        'ai_weight' => 0.3, // Poids de l'IA dans l'ensemble

        // Prompts
        'analysis_depth' => 'detailed', // 'quick', 'detailed', 'comprehensive'
    ],

    // Performance & Optimization
    'performance' => [
        // Parallélisme pour calculs
        'parallel_processing' => env('PREDICTION_PARALLEL', true),
        'max_workers' => env('PREDICTION_MAX_WORKERS', 4),

        // Batch processing
        'batch_size' => 100,

        // Memory limits
        'memory_limit' => '2G',
    ],

    // Logging & Monitoring
    'logging' => [
        'enabled' => true,
        'channel' => env('PREDICTION_LOG_CHANNEL', 'daily'),
        'level' => env('PREDICTION_LOG_LEVEL', 'info'),

        // Log predictions
        'log_predictions' => true,
        'log_errors' => true,
        'log_performance' => true,
    ],

    // Alertes
    'alerts' => [
        // Alerte si accuracy drops
        'low_accuracy_threshold' => 0.5,
        'notify_on_low_accuracy' => true,

        // Alerte si model outdated
        'max_model_age_days' => 30,
        'notify_on_old_model' => true,
    ],
];

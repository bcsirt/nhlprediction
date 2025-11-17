<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Python ML Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour la communication avec le service Python ML.
    |
    */

    'python_service' => [
        'url' => env('PYTHON_ML_SERVICE_URL', 'http://ml-service:8000'),
        'timeout' => env('PYTHON_ML_TIMEOUT', 30),
        'retry_times' => 2,
        'retry_delay' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration des modèles de Machine Learning.
    |
    */

    'models' => [
        // Modèles disponibles
        'available' => [
            'transformer' => 'Temporal Fusion Transformer',
            'gnn' => 'Graph Neural Network',
            'xgboost' => 'XGBoost',
            'ensemble' => 'Ensemble (Stacking)',
        ],

        // Modèle par défaut pour les prédictions
        'default' => 'ensemble',

        // Chemin de stockage des modèles
        'storage_path' => storage_path('app/ml_models'),

        // Version du modèle en production
        'production_version' => env('ML_MODEL_VERSION', 'v1.0.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Training Configuration
    |--------------------------------------------------------------------------
    |
    | Paramètres d'entraînement des modèles.
    |
    */

    'training' => [
        'epochs' => env('ML_EPOCHS', 100),
        'batch_size' => env('ML_BATCH_SIZE', 32),
        'learning_rate' => env('ML_LEARNING_RATE', 0.001),
        'validation_split' => 0.2,
        'test_split' => 0.1,
        'early_stopping_patience' => 10,
        'reduce_lr_patience' => 5,

        // Minimum de données requises pour l'entraînement
        'min_training_samples' => 1000,

        // Fréquence de ré-entraînement (en jours)
        'retrain_frequency' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Self-Supervised Learning
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'apprentissage auto-supervisé.
    |
    */

    'self_supervised' => [
        'enabled' => true,

        // Tâches de pre-training
        'tasks' => [
            'masked_prediction' => true,
            'contrastive_learning' => true,
            'temporal_prediction' => true,
        ],

        // Paramètres de masking
        'masking' => [
            'probability' => 0.15,
            'random_features' => 0.1,
        ],

        // Paramètres contrastive learning
        'contrastive' => [
            'temperature' => 0.07,
            'negative_samples' => 10,
        ],

        // Epochs de pre-training
        'pretrain_epochs' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ensemble Configuration
    |--------------------------------------------------------------------------
    |
    | Poids et configuration de l'ensemble de modèles.
    |
    */

    'ensemble' => [
        'method' => 'stacking', // 'averaging', 'stacking', 'weighted'

        // Poids des modèles (pour méthode 'weighted')
        'weights' => [
            'transformer' => 0.4,
            'gnn' => 0.3,
            'xgboost' => 0.3,
        ],

        // Meta-learner pour stacking
        'meta_learner' => 'neural_network',
    ],

    /*
    |--------------------------------------------------------------------------
    | Prediction Configuration
    |--------------------------------------------------------------------------
    |
    | Paramètres des prédictions.
    |
    */

    'prediction' => [
        // TTL du cache pour les prédictions (en secondes)
        'cache_ttl' => env('PREDICTION_CACHE_TTL', 300),

        // Seuil de confiance minimum
        'confidence_threshold' => env('PREDICTION_CONFIDENCE_THRESHOLD', 0.6),

        // Nombre minimum de features requises
        'min_features' => 50,

        // Prédictions en temps réel
        'live' => [
            'enabled' => true,
            'update_interval' => 60, // secondes
            'min_game_time' => 300, // 5 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Engineering
    |--------------------------------------------------------------------------
    |
    | Configuration des features utilisées par les modèles.
    |
    */

    'features' => [
        // Catégories de features activées
        'categories' => [
            'team_basic' => true,
            'team_advanced' => true,
            'player_stats' => true,
            'goalie_stats' => true,
            'matchup_history' => true,
            'contextual' => true,
            'temporal' => true,
        ],

        // Fenêtre temporelle pour les features (en jours)
        'temporal_window' => 30,

        // Scaling method
        'scaler' => 'standard', // 'standard', 'minmax', 'robust'

        // Gestion des valeurs manquantes
        'missing_strategy' => 'mean', // 'mean', 'median', 'forward_fill', 'drop'
    ],

    /*
    |--------------------------------------------------------------------------
    | Evaluation Metrics
    |--------------------------------------------------------------------------
    |
    | Métriques utilisées pour évaluer les modèles.
    |
    */

    'metrics' => [
        'primary' => 'accuracy',

        'tracked' => [
            'accuracy',
            'precision',
            'recall',
            'f1_score',
            'brier_score',
            'log_loss',
            'roc_auc',
        ],

        // Seuils de performance minimum
        'minimum_performance' => [
            'accuracy' => 0.55,
            'brier_score' => 0.25, // Lower is better
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hyperparameter Tuning
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'optimisation des hyperparamètres.
    |
    */

    'hyperparameter_tuning' => [
        'method' => 'optuna', // 'grid_search', 'random_search', 'optuna'
        'n_trials' => 100,
        'timeout' => 3600, // 1 heure
        'parallel_jobs' => 4,
    ],

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Odds API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'accès aux APIs de cotes de paris.
    |
    */

    'odds_api' => [
        'key' => env('ODDS_API_KEY'),
        'url' => env('ODDS_API_URL', 'https://api.the-odds-api.com/v4'),
        'timeout' => 30,
        'rate_limit' => 500, // requests per month (free tier)
    ],

    /*
    |--------------------------------------------------------------------------
    | Bookmakers
    |--------------------------------------------------------------------------
    |
    | Liste des bookmakers à tracker.
    |
    */

    'bookmakers' => [
        'enabled' => [
            'bet365',
            'betclic',
            'unibet',
            'pinnacle',
            'williamhill',
        ],

        // Bookmaker de référence pour le CLV (Closing Line Value)
        'reference' => 'pinnacle',

        // Délai minimum entre deux fetches d'odds (en minutes)
        'fetch_interval' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Value Bet Detection
    |--------------------------------------------------------------------------
    |
    | Paramètres pour la détection des paris à valeur.
    |
    */

    'value_bets' => [
        // Seuil de valeur minimum (en %)
        'min_value_threshold' => 5.0,

        // Seuil de confiance minimum du modèle
        'min_confidence' => 0.65,

        // Différence minimum entre notre prédiction et les cotes (edge)
        'min_edge' => 0.03,

        // Catégories de value
        'value_categories' => [
            'low' => ['min' => 5, 'max' => 10],
            'medium' => ['min' => 10, 'max' => 20],
            'high' => ['min' => 20, 'max' => 100],
        ],

        // TTL du cache pour value bets (en secondes)
        'cache_ttl' => 300,

        // Expiration des value bets avant le début du match (en minutes)
        'expiration_before_game' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Staking Strategy
    |--------------------------------------------------------------------------
    |
    | Configuration de la gestion de la bankroll et des mises.
    |
    */

    'staking' => [
        // Méthode de calcul des mises
        'method' => env('STAKING_METHOD', 'kelly'), // 'flat', 'kelly', 'kelly_fractional', 'percentage'

        // Kelly Criterion
        'kelly' => [
            'fraction' => 0.25, // Fraction du Kelly (conservative)
            'max_stake_percent' => 5, // Maximum 5% de la bankroll par pari
            'min_stake_percent' => 1, // Minimum 1% de la bankroll par pari
        ],

        // Flat staking
        'flat' => [
            'amount' => 10, // Montant fixe en unités
        ],

        // Percentage staking
        'percentage' => [
            'percent' => 2, // 2% de la bankroll
        ],

        // Limits
        'max_stake' => 100, // Mise maximum en unités
        'min_stake' => 1, // Mise minimum en unités
    ],

    /*
    |--------------------------------------------------------------------------
    | Bankroll Management
    |--------------------------------------------------------------------------
    |
    | Gestion de la bankroll.
    |
    */

    'bankroll' => [
        // Bankroll initiale
        'initial' => env('INITIAL_BANKROLL', 1000),

        // Alertes
        'alerts' => [
            'low_bankroll_threshold' => 0.5, // 50% de la bankroll initiale
            'high_loss_streak' => 5, // Alerte après 5 pertes consécutives
        ],

        // Stop-loss
        'stop_loss' => [
            'enabled' => true,
            'daily_loss_limit' => 5, // % de la bankroll
            'weekly_loss_limit' => 15, // % de la bankroll
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bet Types
    |--------------------------------------------------------------------------
    |
    | Types de paris supportés.
    |
    */

    'bet_types' => [
        'moneyline' => true, // Victoire directe
        'spread' => false, // Handicap
        'totals' => true, // Over/Under
        'period' => false, // Paris par période
        'props' => false, // Props (joueurs, etc.)
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Tracking
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'analyse et le suivi des paris.
    |
    */

    'analytics' => [
        // Métriques à tracker
        'metrics' => [
            'roi' => true,
            'yield' => true,
            'hit_rate' => true,
            'average_odds' => true,
            'clv' => true, // Closing Line Value
            'variance' => true,
            'sharpe_ratio' => true,
        ],

        // Périodes d'analyse
        'periods' => [
            'daily' => true,
            'weekly' => true,
            'monthly' => true,
            'seasonal' => true,
        ],

        // Groupement des stats
        'grouping' => [
            'by_bet_type' => true,
            'by_bookmaker' => true,
            'by_team' => true,
            'by_confidence_level' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backtesting
    |--------------------------------------------------------------------------
    |
    | Configuration pour les backtests.
    |
    */

    'backtesting' => [
        // Période minimale pour un backtest (en jours)
        'min_period' => 30,

        // Commission/vig moyenne des bookmakers
        'default_vig' => 0.05, // 5%

        // Simulations Monte Carlo
        'monte_carlo_iterations' => 1000,

        // Walk-forward analysis
        'walk_forward' => [
            'enabled' => true,
            'training_window' => 180, // jours
            'test_window' => 30, // jours
            'step_size' => 7, // jours
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration des notifications pour les value bets.
    |
    */

    'notifications' => [
        'channels' => [
            'database' => true,
            'email' => false,
            'webhook' => false,
            'slack' => false,
        ],

        // Seuil de valeur pour notifier
        'notify_value_threshold' => 10, // %

        // Filtres de notifications
        'filters' => [
            'min_confidence' => 0.7,
            'max_odds' => 3.0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Management
    |--------------------------------------------------------------------------
    |
    | Gestion des risques.
    |
    */

    'risk_management' => [
        // Diversification
        'max_simultaneous_bets' => 5,
        'max_exposure_per_game' => 3, // % de la bankroll

        // Règles de sécurité
        'avoid_heavy_favorites' => true, // Éviter les cotes < 1.30
        'max_favorite_odds' => 1.30,

        'avoid_big_underdogs' => true, // Éviter les cotes > 5.00
        'max_underdog_odds' => 5.00,

        // Cool-off period après une losing streak
        'cooloff_after_losses' => 3,
        'cooloff_duration' => 24, // heures
    ],

];

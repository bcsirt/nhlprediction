<?php

return [

    /*
    |--------------------------------------------------------------------------
    | NHL API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'accès aux APIs NHL officielles.
    | L'API NHL fournit les données en temps réel des matchs, équipes,
    | joueurs et statistiques.
    |
    */

    'api' => [
        'base_url' => env('NHL_API_URL', 'https://api-web.nhle.com/v1'),
        'stats_url' => env('NHL_STATS_API_URL', 'https://api.nhle.com/stats/rest'),
        'timeout' => env('NHL_API_TIMEOUT', 30),
        'retry_times' => 3,
        'retry_sleep' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Saisons
    |--------------------------------------------------------------------------
    |
    | Configuration des saisons NHL à tracker.
    |
    */

    'seasons' => [
        'current' => env('NHL_CURRENT_SEASON', '20242025'),
        'track_preseason' => false,
        'track_playoffs' => true,
        'track_allstar' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Ingestion
    |--------------------------------------------------------------------------
    |
    | Paramètres de collecte de données depuis l'API NHL.
    |
    */

    'ingestion' => [
        // Fréquence de mise à jour pour les matchs en cours (en secondes)
        'live_update_frequency' => 30,

        // Fréquence de mise à jour pour les stats d'équipes (en heures)
        'team_stats_update_frequency' => 24,

        // Fréquence de mise à jour pour les stats de joueurs (en heures)
        'player_stats_update_frequency' => 24,

        // Nombre de jours d'historique à conserver
        'historical_days' => 365 * 5, // 5 ans

        // Batch size pour l'import massif
        'batch_size' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | TTL pour différents types de données NHL.
    |
    */

    'cache' => [
        'teams' => 60 * 60 * 24, // 24 heures
        'players' => 60 * 60 * 24, // 24 heures
        'games_schedule' => 60 * 60, // 1 heure
        'live_game' => 30, // 30 secondes
        'team_stats' => 60 * 60 * 6, // 6 heures
        'player_stats' => 60 * 60 * 6, // 6 heures
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Stats Configuration
    |--------------------------------------------------------------------------
    |
    | Paramètres pour le calcul des statistiques avancées.
    |
    */

    'advanced_stats' => [
        // Fenêtre de calcul pour les stats récentes (en jours)
        'recent_window' => 10,

        // Poids des stats récentes vs stats de saison
        'recent_weight' => 0.6,
        'season_weight' => 0.4,

        // Facteurs de pondération pour Corsi/Fenwick
        'corsi_weight' => 0.5,
        'fenwick_weight' => 0.3,
        'shots_weight' => 0.2,

        // Paramètres xG (Expected Goals)
        'xg' => [
            'distance_decay' => 0.09,
            'angle_factor' => 0.3,
            'shot_type_multipliers' => [
                'wrist' => 1.0,
                'slap' => 1.1,
                'snap' => 1.05,
                'tip' => 1.3,
                'deflection' => 1.25,
                'backhand' => 0.9,
                'wrap' => 0.85,
            ],
            'situation_multipliers' => [
                'even_strength' => 1.0,
                'power_play' => 1.2,
                'short_handed' => 0.7,
                'empty_net' => 3.0,
            ],
            'rebound_multiplier' => 2.0,
            'rush_multiplier' => 1.3,
        ],
    ],

];

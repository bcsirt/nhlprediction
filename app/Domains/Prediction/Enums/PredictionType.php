<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Enums;

enum PredictionType: string
{
    case WINNER = 'winner';
    case OVER_UNDER = 'over_under';
    case SPREAD = 'spread';
    case EXACT_SCORE = 'exact_score';
    case TOTAL_GOALS = 'total_goals';
    case BOTH_TEAMS_SCORE = 'both_teams_score';
    case FIRST_GOAL = 'first_goal';

    /**
     * Obtenir le libellé en français.
     */
    public function label(): string
    {
        return match($this) {
            self::WINNER => 'Vainqueur',
            self::OVER_UNDER => 'Plus/Moins',
            self::SPREAD => 'Handicap',
            self::EXACT_SCORE => 'Score exact',
            self::TOTAL_GOALS => 'Total de buts',
            self::BOTH_TEAMS_SCORE => 'Les deux équipes marquent',
            self::FIRST_GOAL => 'Premier but',
        };
    }

    /**
     * Description détaillée du type de prédiction.
     */
    public function description(): string
    {
        return match($this) {
            self::WINNER => 'Prédire l\'équipe qui remportera le match',
            self::OVER_UNDER => 'Prédire si le total de buts sera au-dessus ou en-dessous d\'une ligne',
            self::SPREAD => 'Prédire le vainqueur avec un handicap de buts',
            self::EXACT_SCORE => 'Prédire le score exact du match',
            self::TOTAL_GOALS => 'Prédire le nombre total de buts marqués',
            self::BOTH_TEAMS_SCORE => 'Prédire si les deux équipes marqueront',
            self::FIRST_GOAL => 'Prédire quelle équipe marquera en premier',
        };
    }

    /**
     * Difficulté de la prédiction (1=facile, 5=très difficile).
     */
    public function difficulty(): int
    {
        return match($this) {
            self::WINNER => 2,
            self::OVER_UNDER => 3,
            self::SPREAD => 3,
            self::EXACT_SCORE => 5,
            self::TOTAL_GOALS => 3,
            self::BOTH_TEAMS_SCORE => 2,
            self::FIRST_GOAL => 4,
        };
    }

    /**
     * Vérifier si ce type nécessite des probabilités continues.
     */
    public function requiresContinuousProbability(): bool
    {
        return in_array($this, [
            self::EXACT_SCORE,
            self::TOTAL_GOALS,
        ]);
    }

    /**
     * Vérifier si ce type est binaire (oui/non, over/under).
     */
    public function isBinary(): bool
    {
        return in_array($this, [
            self::OVER_UNDER,
            self::BOTH_TEAMS_SCORE,
        ]);
    }

    /**
     * Obtenir les types supportés pour une classe de modèle donnée.
     */
    public static function supportedByModel(string $modelClass): array
    {
        // Tous les modèles supportent au moins winner
        $base = [self::WINNER];

        return match($modelClass) {
            'regression' => array_merge($base, [self::TOTAL_GOALS, self::EXACT_SCORE]),
            'classification' => array_merge($base, [self::OVER_UNDER, self::SPREAD]),
            'ensemble' => self::cases(), // Tous les types
            default => $base,
        };
    }
}

<?php

namespace App\Domains\DataIngestion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant les statistiques d'une équipe.
 */
class TeamStats extends Model
{
    protected $fillable = [
        'team_id',
        'season_id',
        'games_played',
        'wins',
        'losses',
        'ot_losses',
        'points',
        'points_percentage',
        'goals_for',
        'goals_against',
        'goals_per_game',
        'goals_against_per_game',
        'shots_for',
        'shots_against',
        'shots_per_game',
        'shooting_percentage',
        'save_percentage',
        'power_play_goals',
        'power_play_opportunities',
        'power_play_percentage',
        'penalty_kill_goals_against',
        'penalty_kill_opportunities',
        'penalty_kill_percentage',
        'corsi_for',
        'corsi_against',
        'corsi_for_percentage',
        'fenwick_for',
        'fenwick_against',
        'fenwick_for_percentage',
        'expected_goals_for',
        'expected_goals_against',
        'pdo',
        'faceoffs_won',
        'faceoffs_lost',
        'faceoff_percentage',
        'penalty_minutes',
        'split_type',
    ];

    protected $casts = [
        'points_percentage' => 'decimal:3',
        'goals_per_game' => 'decimal:2',
        'goals_against_per_game' => 'decimal:2',
        'shots_per_game' => 'decimal:2',
        'shooting_percentage' => 'decimal:2',
        'save_percentage' => 'decimal:3',
        'power_play_percentage' => 'decimal:2',
        'penalty_kill_percentage' => 'decimal:2',
        'corsi_for_percentage' => 'decimal:2',
        'fenwick_for_percentage' => 'decimal:2',
        'expected_goals_for' => 'decimal:2',
        'expected_goals_against' => 'decimal:2',
        'pdo' => 'decimal:3',
        'faceoff_percentage' => 'decimal:2',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}

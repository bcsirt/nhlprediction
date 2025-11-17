<?php

namespace App\Domains\DataIngestion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant les statistiques d'un match pour une équipe.
 */
class GameStats extends Model
{
    protected $fillable = [
        'game_id',
        'team_id',
        'is_home',
        'shots',
        'blocked_shots',
        'missed_shots',
        'goals',
        'power_play_goals',
        'short_handed_goals',
        'saves',
        'save_percentage',
        'penalties',
        'penalty_minutes',
        'faceoffs_won',
        'faceoffs_lost',
        'faceoff_percentage',
        'hits',
        'blocked_shots_against',
        'giveaways',
        'takeaways',
        'corsi_for',
        'corsi_against',
        'corsi_for_percentage',
        'fenwick_for',
        'fenwick_against',
        'fenwick_for_percentage',
        'expected_goals',
        'power_play_opportunities',
        'power_play_percentage',
        'penalty_kill_opportunities',
        'penalty_kill_percentage',
        'raw_data',
    ];

    protected $casts = [
        'is_home' => 'boolean',
        'save_percentage' => 'decimal:3',
        'faceoff_percentage' => 'decimal:2',
        'corsi_for_percentage' => 'decimal:2',
        'fenwick_for_percentage' => 'decimal:2',
        'expected_goals' => 'decimal:2',
        'power_play_percentage' => 'decimal:2',
        'penalty_kill_percentage' => 'decimal:2',
        'raw_data' => 'array',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}

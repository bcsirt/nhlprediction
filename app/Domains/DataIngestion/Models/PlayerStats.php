<?php

namespace App\Domains\DataIngestion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant les statistiques d'un joueur.
 */
class PlayerStats extends Model
{
    protected $fillable = [
        'player_id',
        'team_id',
        'season_id',
        'games_played',
        'goals',
        'assists',
        'points',
        'plus_minus',
        'penalty_minutes',
        'shots',
        'shooting_percentage',
        'game_winning_goals',
        'overtime_goals',
        'power_play_goals',
        'power_play_points',
        'short_handed_goals',
        'short_handed_points',
        'time_on_ice_seconds',
        'time_on_ice_per_game',
        'even_strength_toi',
        'power_play_toi',
        'short_handed_toi',
        'blocked_shots',
        'hits',
        'faceoffs_won',
        'faceoffs_lost',
        'faceoff_percentage',
        'takeaways',
        'giveaways',
    ];

    protected $casts = [
        'shooting_percentage' => 'decimal:2',
        'time_on_ice_per_game' => 'decimal:2',
        'even_strength_toi' => 'decimal:2',
        'power_play_toi' => 'decimal:2',
        'short_handed_toi' => 'decimal:2',
        'faceoff_percentage' => 'decimal:2',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}

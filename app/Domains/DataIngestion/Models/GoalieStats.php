<?php

namespace App\Domains\DataIngestion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant les statistiques d'un gardien.
 */
class GoalieStats extends Model
{
    protected $fillable = [
        'player_id',
        'team_id',
        'season_id',
        'games_played',
        'games_started',
        'wins',
        'losses',
        'ot_losses',
        'shutouts',
        'shots_against',
        'saves',
        'goals_against',
        'save_percentage',
        'goals_against_average',
        'quality_starts',
        'quality_start_percentage',
        'goals_saved_above_average',
        'high_danger_saves',
        'high_danger_shots_against',
        'high_danger_save_percentage',
        'time_on_ice_seconds',
        'time_on_ice_per_game',
        'even_strength_saves',
        'even_strength_shots_against',
        'even_strength_save_percentage',
        'power_play_saves',
        'power_play_shots_against',
        'power_play_save_percentage',
        'short_handed_saves',
        'short_handed_shots_against',
        'short_handed_save_percentage',
    ];

    protected $casts = [
        'save_percentage' => 'decimal:3',
        'goals_against_average' => 'decimal:2',
        'quality_start_percentage' => 'decimal:2',
        'goals_saved_above_average' => 'decimal:2',
        'high_danger_save_percentage' => 'decimal:3',
        'time_on_ice_per_game' => 'decimal:2',
        'even_strength_save_percentage' => 'decimal:3',
        'power_play_save_percentage' => 'decimal:3',
        'short_handed_save_percentage' => 'decimal:3',
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

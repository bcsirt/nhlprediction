<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Models;

use App\Domains\DataIngestion\Models\Season;
use App\Domains\DataIngestion\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Features calculées pour une équipe à une date donnée.
 *
 * @property int $id
 * @property int $team_id
 * @property int $season_id
 * @property \Carbon\Carbon $calculated_at
 * @property float|null $rolling_goals_for_5
 * @property float|null $rolling_goals_against_5
 * @property float|null $wins_last_5
 * @property float|null $home_win_percentage
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class TeamFeatures extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'season_id',
        'calculated_at',
        'rolling_goals_for_5',
        'rolling_goals_against_5',
        'rolling_shots_for_5',
        'rolling_shots_against_5',
        'rolling_save_percentage_5',
        'rolling_goals_for_10',
        'rolling_goals_against_10',
        'rolling_shots_for_10',
        'rolling_shots_against_10',
        'avg_corsi_for',
        'avg_corsi_against',
        'avg_fenwick_for',
        'avg_expected_goals_for',
        'avg_expected_goals_against',
        'wins_last_5',
        'wins_last_10',
        'current_streak',
        'streak_type',
        'points_percentage',
        'home_win_percentage',
        'away_win_percentage',
        'home_goals_avg',
        'away_goals_avg',
        'power_play_percentage',
        'penalty_kill_percentage',
        'days_since_last_game',
        'is_back_to_back',
        'key_players_injured',
        'injury_impact_score',
    ];

    protected $casts = [
        'calculated_at' => 'date',
        'rolling_goals_for_5' => 'decimal:2',
        'rolling_goals_against_5' => 'decimal:2',
        'rolling_save_percentage_5' => 'decimal:3',
        'points_percentage' => 'decimal:2',
        'home_win_percentage' => 'decimal:2',
        'is_back_to_back' => 'boolean',
    ];

    /**
     * Relation avec l'équipe.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Relation avec la saison.
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Obtenir la goal differential (buts pour - buts contre).
     */
    public function getGoalDifferentialAttribute(): float
    {
        return ($this->rolling_goals_for_5 ?? 0) - ($this->rolling_goals_against_5 ?? 0);
    }

    /**
     * Obtenir le score de forme global (0-100).
     */
    public function getFormScoreAttribute(): float
    {
        $winsScore = ($this->wins_last_5 / 5) * 40; // Max 40 points
        $ppScore = ($this->points_percentage ?? 0) * 0.3; // Max 30 points
        $gdScore = min(max($this->goal_differential + 5, 0), 10) * 3; // Max 30 points

        return min($winsScore + $ppScore + $gdScore, 100);
    }

    /**
     * Vérifier si l'équipe est en forme.
     */
    public function isInGoodForm(): bool
    {
        return $this->form_score >= 65;
    }

    /**
     * Scope: Features récentes pour une équipe.
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope: Features pour une saison.
     */
    public function scopeForSeason($query, int $seasonId)
    {
        return $query->where('season_id', $seasonId);
    }

    /**
     * Scope: Features à une date donnée.
     */
    public function scopeAt($query, string $date)
    {
        return $query->whereDate('calculated_at', '<=', $date)
            ->orderBy('calculated_at', 'desc');
    }

    /**
     * Obtenir les features les plus récentes pour une équipe.
     */
    public static function latestForTeam(int $teamId, int $seasonId): ?self
    {
        return self::where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->orderBy('calculated_at', 'desc')
            ->first();
    }

    /**
     * Obtenir toutes les features comme array pour ML.
     */
    public function toFeatureArray(): array
    {
        return [
            'rolling_goals_for_5' => $this->rolling_goals_for_5 ?? 0,
            'rolling_goals_against_5' => $this->rolling_goals_against_5 ?? 0,
            'rolling_shots_for_5' => $this->rolling_shots_for_5 ?? 0,
            'rolling_shots_against_5' => $this->rolling_shots_against_5 ?? 0,
            'wins_last_5' => $this->wins_last_5 ?? 0,
            'wins_last_10' => $this->wins_last_10 ?? 0,
            'points_percentage' => $this->points_percentage ?? 0,
            'home_win_percentage' => $this->home_win_percentage ?? 0,
            'away_win_percentage' => $this->away_win_percentage ?? 0,
            'power_play_percentage' => $this->power_play_percentage ?? 0,
            'penalty_kill_percentage' => $this->penalty_kill_percentage ?? 0,
            'avg_corsi_for' => $this->avg_corsi_for ?? 0,
            'avg_expected_goals_for' => $this->avg_expected_goals_for ?? 0,
            'current_streak' => $this->current_streak ?? 0,
            'days_since_last_game' => $this->days_since_last_game ?? 2,
            'is_back_to_back' => $this->is_back_to_back ? 1 : 0,
        ];
    }
}

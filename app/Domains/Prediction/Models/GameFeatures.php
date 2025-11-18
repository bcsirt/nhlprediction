<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Models;

use App\Domains\DataIngestion\Models\Game;
use App\Domains\DataIngestion\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Features calculées pour un match spécifique.
 *
 * @property int $id
 * @property int $game_id
 * @property int $home_team_id
 * @property int $away_team_id
 * @property float|null $goal_diff_advantage
 * @property float|null $overall_matchup_score
 */
class GameFeatures extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'home_team_id',
        'away_team_id',
        'goal_diff_advantage',
        'shot_diff_advantage',
        'corsi_diff_advantage',
        'expected_goals_diff',
        'form_diff_5',
        'form_diff_10',
        'streak_advantage',
        'pp_advantage',
        'pk_advantage',
        'rest_advantage',
        'home_back_to_back',
        'away_back_to_back',
        'home_ice_factor',
        'h2h_wins_home',
        'h2h_wins_away',
        'h2h_games_count',
        'h2h_avg_total_goals',
        'home_momentum_score',
        'away_momentum_score',
        'injury_impact_diff',
        'offense_matchup_score',
        'defense_matchup_score',
        'goaltending_matchup_score',
        'overall_matchup_score',
    ];

    protected $casts = [
        'goal_diff_advantage' => 'decimal:2',
        'expected_goals_diff' => 'decimal:2',
        'home_ice_factor' => 'decimal:3',
        'overall_matchup_score' => 'decimal:2',
        'home_back_to_back' => 'boolean',
        'away_back_to_back' => 'boolean',
    ];

    /**
     * Relation avec le match.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Relation avec l'équipe à domicile.
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * Relation avec l'équipe à l'extérieur.
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * Obtenir l'équipe favorite basée sur le matchup score.
     */
    public function getFavoriteAttribute(): string
    {
        if ($this->overall_matchup_score === null) {
            return 'unknown';
        }
        return $this->overall_matchup_score > 0 ? 'home' : 'away';
    }

    /**
     * Obtenir la force de l'avantage (0-100).
     */
    public function getAdvantageStrengthAttribute(): float
    {
        return min(abs($this->overall_matchup_score ?? 0), 100);
    }

    /**
     * Vérifier si le domicile a l'avantage du repos.
     */
    public function homeHasRestAdvantage(): bool
    {
        return ($this->rest_advantage ?? 0) > 0 && !$this->home_back_to_back;
    }

    /**
     * Vérifier si c'est un match à haut score prévu.
     */
    public function isHighScoringMatchup(): bool
    {
        return ($this->h2h_avg_total_goals ?? 5.5) > 6.0;
    }

    /**
     * Scope: Features pour un match.
     */
    public function scopeForGame($query, int $gameId)
    {
        return $query->where('game_id', $gameId);
    }

    /**
     * Trouver les features pour un match.
     */
    public static function findByGameId(int $gameId): ?self
    {
        return self::where('game_id', $gameId)->first();
    }

    /**
     * Convertir en array pour ML.
     */
    public function toFeatureArray(): array
    {
        return [
            'goal_diff_advantage' => $this->goal_diff_advantage ?? 0,
            'shot_diff_advantage' => $this->shot_diff_advantage ?? 0,
            'corsi_diff_advantage' => $this->corsi_diff_advantage ?? 0,
            'expected_goals_diff' => $this->expected_goals_diff ?? 0,
            'form_diff_5' => $this->form_diff_5 ?? 0,
            'form_diff_10' => $this->form_diff_10 ?? 0,
            'streak_advantage' => $this->streak_advantage ?? 0,
            'pp_advantage' => $this->pp_advantage ?? 0,
            'pk_advantage' => $this->pk_advantage ?? 0,
            'rest_advantage' => $this->rest_advantage ?? 0,
            'home_back_to_back' => $this->home_back_to_back ? 1 : 0,
            'away_back_to_back' => $this->away_back_to_back ? 1 : 0,
            'home_ice_factor' => $this->home_ice_factor ?? 0.54,
            'h2h_wins_home' => $this->h2h_wins_home ?? 0,
            'h2h_wins_away' => $this->h2h_wins_away ?? 0,
            'home_momentum_score' => $this->home_momentum_score ?? 50,
            'away_momentum_score' => $this->away_momentum_score ?? 50,
        ];
    }
}

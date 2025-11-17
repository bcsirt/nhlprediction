<?php

namespace App\Domains\DataIngestion\Models;

use App\Domains\DataIngestion\Enums\GameStatus;
use App\Domains\DataIngestion\Enums\GameType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle représentant un match NHL.
 *
 * @property int $id
 * @property int $nhl_id
 * @property int $season_id
 * @property string $game_type
 * @property \Carbon\Carbon $game_date
 * @property int $home_team_id
 * @property int $away_team_id
 * @property int|null $home_score
 * @property int|null $away_score
 * @property string $status
 */
class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'nhl_id',
        'season_id',
        'game_type',
        'game_date',
        'venue',
        'home_team_id',
        'away_team_id',
        'home_score',
        'away_score',
        'status',
        'period',
        'time_remaining',
        'winning_team_id',
        'overtime',
        'shootout',
        'raw_data',
    ];

    protected $casts = [
        'game_date' => 'datetime',
        'time_remaining' => 'datetime',
        'overtime' => 'boolean',
        'shootout' => 'boolean',
        'raw_data' => 'array',
    ];

    /**
     * Obtenir la saison du match.
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Obtenir l'équipe à domicile.
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * Obtenir l'équipe à l'extérieur.
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * Obtenir l'équipe gagnante.
     */
    public function winningTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winning_team_id');
    }

    /**
     * Obtenir les statistiques du match.
     */
    public function gameStats(): HasMany
    {
        return $this->hasMany(GameStats::class);
    }

    /**
     * Obtenir les stats de l'équipe à domicile.
     */
    public function homeTeamStats(): ?GameStats
    {
        return $this->gameStats()
            ->where('team_id', $this->home_team_id)
            ->where('is_home', true)
            ->first();
    }

    /**
     * Obtenir les stats de l'équipe à l'extérieur.
     */
    public function awayTeamStats(): ?GameStats
    {
        return $this->gameStats()
            ->where('team_id', $this->away_team_id)
            ->where('is_home', false)
            ->first();
    }

    /**
     * Obtenir le statut typé.
     */
    public function getStatusEnumAttribute(): GameStatus
    {
        return GameStatus::from($this->status);
    }

    /**
     * Obtenir le type de match typé.
     */
    public function getGameTypeEnumAttribute(): GameType
    {
        return GameType::from($this->game_type);
    }

    /**
     * Vérifier si le match est terminé.
     */
    public function isFinished(): bool
    {
        return $this->getStatusEnumAttribute()->isFinished();
    }

    /**
     * Vérifier si le match est en cours.
     */
    public function isLive(): bool
    {
        return $this->getStatusEnumAttribute()->isLive();
    }

    /**
     * Vérifier si le match est programmé.
     */
    public function isScheduled(): bool
    {
        return $this->getStatusEnumAttribute()->isScheduled();
    }

    /**
     * Obtenir le score total.
     */
    public function getTotalScoreAttribute(): int
    {
        return ($this->home_score ?? 0) + ($this->away_score ?? 0);
    }

    /**
     * Obtenir la différence de buts.
     */
    public function getScoreDifferenceAttribute(): int
    {
        return abs(($this->home_score ?? 0) - ($this->away_score ?? 0));
    }

    /**
     * Scope pour les matchs d'aujourd'hui.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('game_date', today());
    }

    /**
     * Scope pour les matchs terminés.
     */
    public function scopeFinished($query)
    {
        return $query->whereIn('status', ['final', 'final_ot', 'final_so']);
    }

    /**
     * Scope pour les matchs en direct.
     */
    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    /**
     * Scope pour les matchs programmés.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope pour une équipe donnée.
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where(function($q) use ($teamId) {
            $q->where('home_team_id', $teamId)
              ->orWhere('away_team_id', $teamId);
        });
    }

    /**
     * Trouver un match par son ID NHL.
     */
    public static function findByNHLId(int $nhlId): ?self
    {
        return static::where('nhl_id', $nhlId)->first();
    }
}

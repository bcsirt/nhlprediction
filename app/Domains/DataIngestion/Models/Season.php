<?php

namespace App\Domains\DataIngestion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle représentant une saison NHL.
 *
 * @property int $id
 * @property string $season_id
 * @property int $start_year
 * @property int $end_year
 * @property bool $is_current
 */
class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id',
        'start_year',
        'end_year',
        'regular_season_start_date',
        'regular_season_end_date',
        'playoff_start_date',
        'playoff_end_date',
        'total_games',
        'is_current',
    ];

    protected $casts = [
        'regular_season_start_date' => 'date',
        'regular_season_end_date' => 'date',
        'playoff_start_date' => 'date',
        'playoff_end_date' => 'date',
        'is_current' => 'boolean',
    ];

    /**
     * Obtenir les matchs de la saison.
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    /**
     * Obtenir les statistiques des équipes pour cette saison.
     */
    public function teamStats(): HasMany
    {
        return $this->hasMany(TeamStats::class);
    }

    /**
     * Obtenir les statistiques des joueurs pour cette saison.
     */
    public function playerStats(): HasMany
    {
        return $this->hasMany(PlayerStats::class);
    }

    /**
     * Scope pour la saison actuelle.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Obtenir le label de la saison.
     */
    public function getLabelAttribute(): string
    {
        return "{$this->start_year}-{$this->end_year}";
    }

    /**
     * Obtenir la saison actuelle.
     */
    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }

    /**
     * Trouver une saison par son ID.
     */
    public static function findBySeasonId(string $seasonId): ?self
    {
        return static::where('season_id', $seasonId)->first();
    }
}

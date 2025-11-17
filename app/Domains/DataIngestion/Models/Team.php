<?php

namespace App\Domains\DataIngestion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle représentant une équipe NHL.
 *
 * @property int $id
 * @property int $nhl_id
 * @property string $name
 * @property string $abbreviation
 * @property string $team_name
 * @property string $location_name
 * @property string|null $venue
 * @property int|null $conference_id
 * @property string|null $division
 * @property bool $active
 */
class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'nhl_id',
        'name',
        'abbreviation',
        'team_name',
        'location_name',
        'venue',
        'conference_id',
        'division',
        'franchise_id',
        'active',
        'website',
        'official_site_url',
        'first_year_of_play',
    ];

    protected $casts = [
        'active' => 'boolean',
        'first_year_of_play' => 'integer',
    ];

    /**
     * Obtenir la conférence de l'équipe.
     */
    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class);
    }

    /**
     * Obtenir les joueurs actuels de l'équipe.
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'current_team_id');
    }

    /**
     * Obtenir les matchs à domicile.
     */
    public function homeGames(): HasMany
    {
        return $this->hasMany(Game::class, 'home_team_id');
    }

    /**
     * Obtenir les matchs à l'extérieur.
     */
    public function awayGames(): HasMany
    {
        return $this->hasMany(Game::class, 'away_team_id');
    }

    /**
     * Obtenir toutes les statistiques de l'équipe.
     */
    public function stats(): HasMany
    {
        return $this->hasMany(TeamStats::class);
    }

    /**
     * Obtenir les statistiques pour une saison donnée.
     */
    public function statsForSeason(string $seasonId): ?TeamStats
    {
        return $this->stats()
            ->whereHas('season', fn($q) => $q->where('season_id', $seasonId))
            ->where('split_type', 'overall')
            ->first();
    }

    /**
     * Obtenir le nom complet de l'équipe.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->location_name} {$this->team_name}";
    }

    /**
     * Scope pour obtenir les équipes actives.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Trouver une équipe par son ID NHL.
     */
    public static function findByNHLId(int $nhlId): ?self
    {
        return static::where('nhl_id', $nhlId)->first();
    }
}

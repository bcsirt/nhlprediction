<?php

namespace App\Domains\DataIngestion\Models;

use App\Domains\DataIngestion\Enums\PlayerPosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle représentant un joueur NHL.
 *
 * @property int $id
 * @property int $nhl_id
 * @property string $first_name
 * @property string $last_name
 * @property string $full_name
 * @property int|null $current_team_id
 * @property string $position
 * @property string|null $position_code
 * @property int|null $jersey_number
 * @property bool $active
 */
class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'nhl_id',
        'first_name',
        'last_name',
        'full_name',
        'current_team_id',
        'position',
        'position_code',
        'jersey_number',
        'birth_date',
        'birth_city',
        'birth_country',
        'nationality',
        'height_cm',
        'weight_kg',
        'shoots_catches',
        'draft_year',
        'draft_round',
        'draft_pick',
        'draft_team_id',
        'active',
        'rookie',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'active' => 'boolean',
        'rookie' => 'boolean',
    ];

    /**
     * Obtenir l'équipe actuelle du joueur.
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * Obtenir l'équipe qui a drafté le joueur.
     */
    public function draftTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'draft_team_id');
    }

    /**
     * Obtenir les statistiques du joueur.
     */
    public function stats(): HasMany
    {
        return $this->hasMany(PlayerStats::class);
    }

    /**
     * Obtenir les statistiques de gardien (si applicable).
     */
    public function goalieStats(): HasMany
    {
        return $this->hasMany(GoalieStats::class);
    }

    /**
     * Obtenir la position typée.
     */
    public function getPositionEnumAttribute(): ?PlayerPosition
    {
        return $this->position_code
            ? PlayerPosition::tryFrom($this->position_code)
            : null;
    }

    /**
     * Vérifier si le joueur est un gardien.
     */
    public function isGoalie(): bool
    {
        return $this->position === 'G' || $this->position_code === 'G';
    }

    /**
     * Vérifier si le joueur est un attaquant.
     */
    public function isForward(): bool
    {
        return in_array($this->position, ['F', 'C', 'LW', 'RW']);
    }

    /**
     * Vérifier si le joueur est un défenseur.
     */
    public function isDefense(): bool
    {
        return $this->position === 'D';
    }

    /**
     * Obtenir l'âge du joueur.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    /**
     * Scope pour obtenir les joueurs actifs.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope pour obtenir les joueurs d'une équipe.
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('current_team_id', $teamId);
    }

    /**
     * Trouver un joueur par son ID NHL.
     */
    public static function findByNHLId(int $nhlId): ?self
    {
        return static::where('nhl_id', $nhlId)->first();
    }
}

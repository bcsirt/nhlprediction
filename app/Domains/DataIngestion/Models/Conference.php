<?php

namespace App\Domains\DataIngestion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle représentant une conférence NHL.
 *
 * @property int $id
 * @property string $name
 * @property string $abbreviation
 * @property int $nhl_id
 * @property bool $active
 */
class Conference extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'abbreviation',
        'nhl_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Obtenir les équipes de cette conférence.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Scope pour les conférences actives.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}

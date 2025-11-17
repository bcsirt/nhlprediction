<?php

namespace App\Domains\DataIngestion\Enums;

enum GameType: string
{
    case PRESEASON = 'PR';
    case REGULAR = 'R';
    case PLAYOFF = 'P';
    case ALL_STAR = 'A';

    /**
     * Obtenir le libellé en français.
     */
    public function label(): string
    {
        return match($this) {
            self::PRESEASON => 'Pré-saison',
            self::REGULAR => 'Saison régulière',
            self::PLAYOFF => 'Séries éliminatoires',
            self::ALL_STAR => 'Match des étoiles',
        };
    }

    /**
     * Vérifier si c'est la saison régulière.
     */
    public function isRegularSeason(): bool
    {
        return $this === self::REGULAR;
    }

    /**
     * Vérifier si ce sont les playoffs.
     */
    public function isPlayoff(): bool
    {
        return $this === self::PLAYOFF;
    }

    /**
     * Obtenir le poids pour les stats (certains matchs comptent moins).
     */
    public function weight(): float
    {
        return match($this) {
            self::REGULAR => 1.0,
            self::PLAYOFF => 1.2, // Les playoffs comptent plus
            self::PRESEASON => 0.5,
            self::ALL_STAR => 0.0, // Ne compte pas
        };
    }

    /**
     * Vérifier si ce type de match doit être utilisé pour les stats.
     */
    public function countsForStats(): bool
    {
        return in_array($this, [self::REGULAR, self::PLAYOFF]);
    }
}

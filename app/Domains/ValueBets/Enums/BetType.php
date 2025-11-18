<?php

declare(strict_types=1);

namespace App\Domains\ValueBets\Enums;

enum BetType: string
{
    case MONEYLINE = 'moneyline';
    case SPREAD = 'spread';
    case OVER_UNDER = 'over_under';
    case PROP = 'prop';
    case PARLAY = 'parlay';
    case TEASER = 'teaser';

    /**
     * Obtenir le libellé en français.
     */
    public function label(): string
    {
        return match($this) {
            self::MONEYLINE => 'Moneyline',
            self::SPREAD => 'Handicap',
            self::OVER_UNDER => 'Plus/Moins',
            self::PROP => 'Proposition',
            self::PARLAY => 'Combiné',
            self::TEASER => 'Teaser',
        };
    }

    /**
     * Description du type de pari.
     */
    public function description(): string
    {
        return match($this) {
            self::MONEYLINE => 'Pari sur le vainqueur du match',
            self::SPREAD => 'Pari avec handicap de buts',
            self::OVER_UNDER => 'Pari sur le total de buts',
            self::PROP => 'Pari sur un événement spécifique',
            self::PARLAY => 'Combinaison de plusieurs paris',
            self::TEASER => 'Combiné avec lignes ajustées',
        };
    }

    /**
     * Vérifier si ce type nécessite une ligne.
     */
    public function requiresLine(): bool
    {
        return in_array($this, [self::SPREAD, self::OVER_UNDER]);
    }

    /**
     * Vérifier si c'est un pari simple.
     */
    public function isSingleBet(): bool
    {
        return in_array($this, [self::MONEYLINE, self::SPREAD, self::OVER_UNDER, self::PROP]);
    }

    /**
     * Vérifier si c'est un pari combiné.
     */
    public function isMultipleBet(): bool
    {
        return in_array($this, [self::PARLAY, self::TEASER]);
    }

    /**
     * Risque relatif du type de pari (1=faible, 5=élevé).
     */
    public function riskLevel(): int
    {
        return match($this) {
            self::MONEYLINE => 2,
            self::SPREAD => 3,
            self::OVER_UNDER => 3,
            self::PROP => 4,
            self::PARLAY => 5,
            self::TEASER => 4,
        };
    }

    /**
     * Obtenir les sélections possibles pour ce type.
     */
    public function possibleSelections(): array
    {
        return match($this) {
            self::MONEYLINE => ['home', 'away'],
            self::SPREAD => ['home', 'away'],
            self::OVER_UNDER => ['over', 'under'],
            self::PROP => ['yes', 'no', 'over', 'under'],
            self::PARLAY, self::TEASER => ['win', 'lose'],
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\ValueBets\Enums;

enum BetStatus: string
{
    case PENDING = 'pending';
    case WON = 'won';
    case LOST = 'lost';
    case PUSH = 'push';
    case CANCELLED = 'cancelled';
    case VOID = 'void';

    /**
     * Obtenir le libellé en français.
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::WON => 'Gagné',
            self::LOST => 'Perdu',
            self::PUSH => 'Égalité',
            self::CANCELLED => 'Annulé',
            self::VOID => 'Nul',
        };
    }

    /**
     * Obtenir la couleur pour l'affichage.
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::WON => 'green',
            self::LOST => 'red',
            self::PUSH => 'gray',
            self::CANCELLED => 'gray',
            self::VOID => 'gray',
        };
    }

    /**
     * Obtenir l'icône.
     */
    public function icon(): string
    {
        return match($this) {
            self::PENDING => '⏳',
            self::WON => '✅',
            self::LOST => '❌',
            self::PUSH => '🔄',
            self::CANCELLED => '🚫',
            self::VOID => '⚪',
        };
    }

    /**
     * Vérifier si le pari est résolu.
     */
    public function isSettled(): bool
    {
        return in_array($this, [self::WON, self::LOST, self::PUSH, self::VOID]);
    }

    /**
     * Vérifier si le pari compte dans les statistiques.
     */
    public function countsForStats(): bool
    {
        return in_array($this, [self::WON, self::LOST]);
    }

    /**
     * Vérifier si le pari est gagnant.
     */
    public function isWin(): bool
    {
        return $this === self::WON;
    }

    /**
     * Obtenir le multiplicateur de profit/perte.
     */
    public function profitMultiplier(): float
    {
        return match($this) {
            self::WON => 1.0,
            self::LOST => -1.0,
            self::PUSH, self::VOID, self::CANCELLED => 0.0,
            self::PENDING => 0.0,
        };
    }
}

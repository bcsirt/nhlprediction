<?php

namespace App\Domains\DataIngestion\Enums;

enum GameStatus: string
{
    case SCHEDULED = 'scheduled';
    case LIVE = 'live';
    case FINAL = 'final';
    case FINAL_OT = 'final_ot';
    case FINAL_SO = 'final_so';
    case POSTPONED = 'postponed';
    case CANCELLED = 'cancelled';

    /**
     * Obtenir le libellé en français.
     */
    public function label(): string
    {
        return match($this) {
            self::SCHEDULED => 'Programmé',
            self::LIVE => 'En cours',
            self::FINAL => 'Terminé',
            self::FINAL_OT => 'Terminé (Prolongation)',
            self::FINAL_SO => 'Terminé (Tirs de barrage)',
            self::POSTPONED => 'Reporté',
            self::CANCELLED => 'Annulé',
        };
    }

    /**
     * Vérifier si le match est terminé.
     */
    public function isFinished(): bool
    {
        return in_array($this, [
            self::FINAL,
            self::FINAL_OT,
            self::FINAL_SO,
        ]);
    }

    /**
     * Vérifier si le match est en cours.
     */
    public function isLive(): bool
    {
        return $this === self::LIVE;
    }

    /**
     * Vérifier si le match est programmé.
     */
    public function isScheduled(): bool
    {
        return $this === self::SCHEDULED;
    }

    /**
     * Obtenir la couleur pour l'affichage.
     */
    public function color(): string
    {
        return match($this) {
            self::SCHEDULED => 'gray',
            self::LIVE => 'green',
            self::FINAL, self::FINAL_OT, self::FINAL_SO => 'blue',
            self::POSTPONED => 'yellow',
            self::CANCELLED => 'red',
        };
    }
}

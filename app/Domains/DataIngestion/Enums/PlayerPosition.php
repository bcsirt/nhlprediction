<?php

namespace App\Domains\DataIngestion\Enums;

enum PlayerPosition: string
{
    case CENTER = 'C';
    case LEFT_WING = 'LW';
    case RIGHT_WING = 'RW';
    case DEFENSE = 'D';
    case GOALIE = 'G';

    /**
     * Obtenir le libellé en français.
     */
    public function label(): string
    {
        return match($this) {
            self::CENTER => 'Centre',
            self::LEFT_WING => 'Ailier gauche',
            self::RIGHT_WING => 'Ailier droit',
            self::DEFENSE => 'Défenseur',
            self::GOALIE => 'Gardien',
        };
    }

    /**
     * Obtenir la catégorie générale.
     */
    public function category(): string
    {
        return match($this) {
            self::CENTER, self::LEFT_WING, self::RIGHT_WING => 'F', // Forward
            self::DEFENSE => 'D',
            self::GOALIE => 'G',
        };
    }

    /**
     * Vérifier si c'est un attaquant.
     */
    public function isForward(): bool
    {
        return in_array($this, [self::CENTER, self::LEFT_WING, self::RIGHT_WING]);
    }

    /**
     * Vérifier si c'est un défenseur.
     */
    public function isDefense(): bool
    {
        return $this === self::DEFENSE;
    }

    /**
     * Vérifier si c'est un gardien.
     */
    public function isGoalie(): bool
    {
        return $this === self::GOALIE;
    }

    /**
     * Créer depuis le code NHL API.
     */
    public static function fromNHLCode(string $code): self
    {
        return match(strtoupper($code)) {
            'C' => self::CENTER,
            'LW', 'L' => self::LEFT_WING,
            'RW', 'R' => self::RIGHT_WING,
            'D' => self::DEFENSE,
            'G' => self::GOALIE,
            default => self::CENTER,
        };
    }
}

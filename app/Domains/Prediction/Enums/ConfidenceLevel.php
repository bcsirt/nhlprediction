<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Enums;

enum ConfidenceLevel: string
{
    case VERY_LOW = 'very_low';
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case VERY_HIGH = 'very_high';

    /**
     * Obtenir le libellé en français.
     */
    public function label(): string
    {
        return match($this) {
            self::VERY_LOW => 'Très faible',
            self::LOW => 'Faible',
            self::MEDIUM => 'Moyenne',
            self::HIGH => 'Élevée',
            self::VERY_HIGH => 'Très élevée',
        };
    }

    /**
     * Obtenir le niveau de confiance depuis un score (0-100).
     */
    public static function fromScore(float $score): self
    {
        return match(true) {
            $score >= 90 => self::VERY_HIGH,
            $score >= 75 => self::HIGH,
            $score >= 55 => self::MEDIUM,
            $score >= 40 => self::LOW,
            default => self::VERY_LOW,
        };
    }

    /**
     * Obtenir le niveau de confiance depuis une probabilité (0.0-1.0).
     */
    public static function fromProbability(float $probability): self
    {
        return self::fromScore($probability * 100);
    }

    /**
     * Obtenir la plage de score min-max pour ce niveau.
     */
    public function scoreRange(): array
    {
        return match($this) {
            self::VERY_HIGH => [90, 100],
            self::HIGH => [75, 89],
            self::MEDIUM => [55, 74],
            self::LOW => [40, 54],
            self::VERY_LOW => [0, 39],
        };
    }

    /**
     * Obtenir la couleur pour l'affichage.
     */
    public function color(): string
    {
        return match($this) {
            self::VERY_HIGH => 'green',
            self::HIGH => 'lime',
            self::MEDIUM => 'yellow',
            self::LOW => 'orange',
            self::VERY_LOW => 'red',
        };
    }

    /**
     * Obtenir l'icône pour l'affichage.
     */
    public function icon(): string
    {
        return match($this) {
            self::VERY_HIGH => '🟢',
            self::HIGH => '🟡',
            self::MEDIUM => '🟠',
            self::LOW => '🔴',
            self::VERY_LOW => '⚫',
        };
    }

    /**
     * Recommandation de mise (fraction de Kelly Criterion).
     */
    public function kellyFraction(): float
    {
        return match($this) {
            self::VERY_HIGH => 1.0,     // 100% de Kelly
            self::HIGH => 0.5,           // 50% de Kelly (plus conservateur)
            self::MEDIUM => 0.25,        // 25% de Kelly
            self::LOW => 0.1,            // 10% de Kelly
            self::VERY_LOW => 0.0,       // Pas de mise recommandée
        };
    }

    /**
     * Vérifier si le niveau justifie une recommandation de pari.
     */
    public function shouldBet(): bool
    {
        return in_array($this, [self::HIGH, self::VERY_HIGH]);
    }

    /**
     * Description détaillée du niveau de confiance.
     */
    public function description(): string
    {
        return match($this) {
            self::VERY_HIGH => 'Prédiction très fiable, forte recommandation',
            self::HIGH => 'Prédiction fiable, recommandation modérée',
            self::MEDIUM => 'Prédiction incertaine, à surveiller',
            self::LOW => 'Prédiction peu fiable, déconseillé',
            self::VERY_LOW => 'Prédiction très incertaine, fortement déconseillé',
        };
    }

    /**
     * Obtenir le multiplicateur de risque.
     */
    public function riskMultiplier(): float
    {
        return match($this) {
            self::VERY_HIGH => 0.5,  // Faible risque
            self::HIGH => 0.75,
            self::MEDIUM => 1.0,     // Risque normal
            self::LOW => 1.5,
            self::VERY_LOW => 2.0,   // Haut risque
        };
    }
}

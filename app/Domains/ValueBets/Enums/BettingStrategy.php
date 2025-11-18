<?php

declare(strict_types=1);

namespace App\Domains\ValueBets\Enums;

enum BettingStrategy: string
{
    case KELLY = 'kelly';
    case HALF_KELLY = 'half_kelly';
    case QUARTER_KELLY = 'quarter_kelly';
    case FLAT = 'flat';
    case PERCENTAGE = 'percentage';
    case FIBONACCI = 'fibonacci';
    case MARTINGALE = 'martingale';

    /**
     * Obtenir le libellé.
     */
    public function label(): string
    {
        return match($this) {
            self::KELLY => 'Kelly Criterion',
            self::HALF_KELLY => 'Demi-Kelly',
            self::QUARTER_KELLY => 'Quart-Kelly',
            self::FLAT => 'Mise fixe',
            self::PERCENTAGE => 'Pourcentage fixe',
            self::FIBONACCI => 'Fibonacci',
            self::MARTINGALE => 'Martingale',
        };
    }

    /**
     * Description de la stratégie.
     */
    public function description(): string
    {
        return match($this) {
            self::KELLY => 'Mise optimale basée sur l\'avantage et les cotes',
            self::HALF_KELLY => 'Kelly divisé par 2 pour réduire la volatilité',
            self::QUARTER_KELLY => 'Kelly divisé par 4, très conservateur',
            self::FLAT => 'Même montant à chaque pari',
            self::PERCENTAGE => 'Pourcentage fixe du bankroll',
            self::FIBONACCI => 'Progression selon la suite de Fibonacci',
            self::MARTINGALE => 'Doubler après chaque perte (risqué)',
        };
    }

    /**
     * Niveau de risque (1=conservateur, 5=agressif).
     */
    public function riskLevel(): int
    {
        return match($this) {
            self::QUARTER_KELLY => 1,
            self::HALF_KELLY => 2,
            self::FLAT => 2,
            self::PERCENTAGE => 3,
            self::KELLY => 3,
            self::FIBONACCI => 4,
            self::MARTINGALE => 5,
        };
    }

    /**
     * Vérifier si la stratégie est recommandée.
     */
    public function isRecommended(): bool
    {
        return in_array($this, [
            self::KELLY,
            self::HALF_KELLY,
            self::QUARTER_KELLY,
            self::FLAT,
            self::PERCENTAGE,
        ]);
    }

    /**
     * Vérifier si la stratégie utilise une progression.
     */
    public function usesProgression(): bool
    {
        return in_array($this, [self::FIBONACCI, self::MARTINGALE]);
    }

    /**
     * Obtenir le multiplicateur Kelly pour cette stratégie.
     */
    public function kellyMultiplier(): float
    {
        return match($this) {
            self::KELLY => 1.0,
            self::HALF_KELLY => 0.5,
            self::QUARTER_KELLY => 0.25,
            default => 0.0,
        };
    }

    /**
     * Calculer la mise recommandée.
     */
    public function calculateStake(
        float $bankroll,
        float $probability,
        float $odds,
        float $percentage = 0.02
    ): float {
        return match($this) {
            self::KELLY, self::HALF_KELLY, self::QUARTER_KELLY => $this->kellyStake($bankroll, $probability, $odds),
            self::FLAT => $bankroll * $percentage,
            self::PERCENTAGE => $bankroll * $percentage,
            default => $bankroll * $percentage,
        };
    }

    private function kellyStake(float $bankroll, float $probability, float $odds): float
    {
        // Kelly Formula: f* = (bp - q) / b
        // b = odds - 1 (potential profit per unit)
        // p = probability of winning
        // q = probability of losing (1 - p)

        $b = $odds - 1;
        $p = $probability;
        $q = 1 - $p;

        $kellyFraction = ($b * $p - $q) / $b;

        // Appliquer le multiplicateur de la stratégie
        $kellyFraction *= $this->kellyMultiplier();

        // Ne pas parier si Kelly est négatif
        if ($kellyFraction <= 0) {
            return 0;
        }

        return $bankroll * $kellyFraction;
    }
}

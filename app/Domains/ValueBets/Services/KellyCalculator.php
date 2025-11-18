<?php

declare(strict_types=1);

namespace App\Domains\ValueBets\Services;

/**
 * Service pour calculer les mises optimales selon le Kelly Criterion.
 */
class KellyCalculator
{
    /**
     * Calculer la fraction Kelly optimale.
     *
     * @param float $probability Probabilité estimée de gain (0-1)
     * @param float $odds Cotes décimales
     * @return float Fraction du bankroll à miser (0-1)
     */
    public function calculate(float $probability, float $odds): float
    {
        // Validation
        if ($probability <= 0 || $probability >= 1) {
            return 0;
        }
        if ($odds <= 1) {
            return 0;
        }

        // Kelly Formula: f* = (bp - q) / b
        // b = odds - 1 (profit potentiel par unité)
        // p = probabilité de gain
        // q = probabilité de perte (1 - p)

        $b = $odds - 1;
        $p = $probability;
        $q = 1 - $p;

        $kelly = ($b * $p - $q) / $b;

        // Ne pas parier si Kelly est négatif ou nul
        return max(0, $kelly);
    }

    /**
     * Calculer avec une fraction de Kelly (plus conservateur).
     */
    public function calculateFractional(
        float $probability,
        float $odds,
        float $fraction = 0.25
    ): float {
        return $this->calculate($probability, $odds) * $fraction;
    }

    /**
     * Calculer la mise recommandée en montant.
     */
    public function calculateStake(
        float $bankroll,
        float $probability,
        float $odds,
        float $fraction = 1.0,
        ?float $maxStake = null
    ): float {
        $kellyFraction = $this->calculate($probability, $odds) * $fraction;
        $stake = $bankroll * $kellyFraction;

        if ($maxStake !== null) {
            $stake = min($stake, $maxStake);
        }

        return round($stake, 2);
    }

    /**
     * Calculer Kelly pour plusieurs paris simultanés.
     *
     * @param array $bets Array of ['probability' => float, 'odds' => float]
     * @return array Kelly fractions for each bet
     */
    public function calculateMultiple(array $bets): array
    {
        $results = [];

        foreach ($bets as $key => $bet) {
            $kelly = $this->calculate($bet['probability'], $bet['odds']);
            $results[$key] = [
                'kelly_fraction' => $kelly,
                'edge' => $this->calculateEdge($bet['probability'], $bet['odds']),
            ];
        }

        // Normaliser si la somme dépasse 1
        $totalKelly = array_sum(array_column($results, 'kelly_fraction'));
        if ($totalKelly > 1) {
            foreach ($results as &$result) {
                $result['kelly_fraction'] /= $totalKelly;
            }
        }

        return $results;
    }

    /**
     * Calculer l'avantage (edge) en pourcentage.
     */
    public function calculateEdge(float $probability, float $odds): float
    {
        $impliedProbability = 1 / $odds;
        return (($probability - $impliedProbability) / $impliedProbability) * 100;
    }

    /**
     * Vérifier si un pari a de la valeur.
     */
    public function hasValue(float $probability, float $odds): bool
    {
        return $this->calculate($probability, $odds) > 0;
    }

    /**
     * Obtenir la cote minimum pour avoir de la valeur.
     */
    public function getMinimumOdds(float $probability): float
    {
        if ($probability <= 0 || $probability >= 1) {
            return PHP_FLOAT_MAX;
        }

        // Cote fair = 1 / probabilité
        return 1 / $probability;
    }

    /**
     * Calculer la probabilité minimum pour avoir de la valeur avec ces cotes.
     */
    public function getMinimumProbability(float $odds): float
    {
        if ($odds <= 1) {
            return 1;
        }

        return 1 / $odds;
    }

    /**
     * Simuler la croissance du bankroll sur plusieurs paris.
     *
     * @param float $initialBankroll
     * @param array $bets Array of ['probability' => float, 'odds' => float, 'won' => bool]
     * @param float $fraction Kelly fraction à utiliser
     * @return array Évolution du bankroll
     */
    public function simulateGrowth(
        float $initialBankroll,
        array $bets,
        float $fraction = 0.25
    ): array {
        $bankroll = $initialBankroll;
        $history = [$bankroll];

        foreach ($bets as $bet) {
            $stake = $this->calculateStake(
                $bankroll,
                $bet['probability'],
                $bet['odds'],
                $fraction
            );

            if ($bet['won']) {
                $bankroll += $stake * ($bet['odds'] - 1);
            } else {
                $bankroll -= $stake;
            }

            $history[] = round($bankroll, 2);
        }

        return [
            'final_bankroll' => round($bankroll, 2),
            'growth' => round((($bankroll - $initialBankroll) / $initialBankroll) * 100, 2),
            'history' => $history,
        ];
    }

    /**
     * Calculer le niveau de risque d'un pari.
     */
    public function getRiskLevel(float $kellyFraction): string
    {
        return match (true) {
            $kellyFraction <= 0 => 'no_bet',
            $kellyFraction <= 0.02 => 'very_low',
            $kellyFraction <= 0.05 => 'low',
            $kellyFraction <= 0.10 => 'medium',
            $kellyFraction <= 0.20 => 'high',
            default => 'very_high',
        };
    }
}

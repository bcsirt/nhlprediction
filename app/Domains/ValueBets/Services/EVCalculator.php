<?php

declare(strict_types=1);

namespace App\Domains\ValueBets\Services;

/**
 * Service pour calculer l'Expected Value (valeur espérée) des paris.
 */
class EVCalculator
{
    /**
     * Calculer l'Expected Value d'un pari.
     *
     * @param float $probability Probabilité estimée de gain (0-1)
     * @param float $odds Cotes décimales
     * @param float $stake Mise
     * @return float EV (positif = profitable)
     */
    public function calculate(float $probability, float $odds, float $stake = 1.0): float
    {
        // EV = (probability * potential_profit) - (1 - probability) * stake
        // Où potential_profit = stake * (odds - 1)

        $potentialProfit = $stake * ($odds - 1);
        $expectedWin = $probability * $potentialProfit;
        $expectedLoss = (1 - $probability) * $stake;

        return $expectedWin - $expectedLoss;
    }

    /**
     * Calculer l'EV en pourcentage du stake.
     */
    public function calculatePercentage(float $probability, float $odds): float
    {
        $ev = $this->calculate($probability, $odds, 1);
        return $ev * 100;
    }

    /**
     * Calculer l'EV avec la marge du bookmaker incluse.
     *
     * @param float $probability Notre probabilité estimée
     * @param float $odds Cotes proposées
     * @param float $oppositeOdds Cotes de l'autre côté (pour calculer la marge)
     */
    public function calculateWithMargin(
        float $probability,
        float $odds,
        float $oppositeOdds
    ): array {
        // Calculer la marge (vig/juice)
        $impliedProb = 1 / $odds;
        $oppositeImpliedProb = 1 / $oppositeOdds;
        $totalImplied = $impliedProb + $oppositeImpliedProb;
        $margin = ($totalImplied - 1) * 100;

        // Cotes sans marge (fair odds)
        $noVigOdds = $odds * $totalImplied;

        return [
            'ev' => $this->calculate($probability, $odds),
            'ev_percentage' => $this->calculatePercentage($probability, $odds),
            'margin' => round($margin, 2),
            'fair_odds' => round($noVigOdds, 3),
            'fair_probability' => round($impliedProb / $totalImplied, 4),
        ];
    }

    /**
     * Calculer l'EV pour un pari Over/Under.
     */
    public function calculateOverUnder(
        float $overProbability,
        float $overOdds,
        float $underOdds
    ): array {
        $underProbability = 1 - $overProbability;

        return [
            'over' => [
                'probability' => $overProbability,
                'odds' => $overOdds,
                'ev' => $this->calculate($overProbability, $overOdds),
                'ev_percentage' => $this->calculatePercentage($overProbability, $overOdds),
            ],
            'under' => [
                'probability' => $underProbability,
                'odds' => $underOdds,
                'ev' => $this->calculate($underProbability, $underOdds),
                'ev_percentage' => $this->calculatePercentage($underProbability, $underOdds),
            ],
            'best_bet' => $this->calculatePercentage($overProbability, $overOdds) >
                $this->calculatePercentage($underProbability, $underOdds) ? 'over' : 'under',
        ];
    }

    /**
     * Calculer l'EV pour un pari Spread.
     */
    public function calculateSpread(
        float $homeCoverProbability,
        float $homeOdds,
        float $awayOdds,
        float $spread
    ): array {
        $awayCoverProbability = 1 - $homeCoverProbability;

        return [
            'home' => [
                'spread' => $spread,
                'probability' => $homeCoverProbability,
                'odds' => $homeOdds,
                'ev' => $this->calculate($homeCoverProbability, $homeOdds),
                'ev_percentage' => $this->calculatePercentage($homeCoverProbability, $homeOdds),
            ],
            'away' => [
                'spread' => -$spread,
                'probability' => $awayCoverProbability,
                'odds' => $awayOdds,
                'ev' => $this->calculate($awayCoverProbability, $awayOdds),
                'ev_percentage' => $this->calculatePercentage($awayCoverProbability, $awayOdds),
            ],
            'best_bet' => $this->calculatePercentage($homeCoverProbability, $homeOdds) >
                $this->calculatePercentage($awayCoverProbability, $awayOdds) ? 'home' : 'away',
        ];
    }

    /**
     * Trouver les cotes minimum pour avoir un EV positif.
     */
    public function getBreakevenOdds(float $probability): float
    {
        if ($probability <= 0 || $probability >= 1) {
            return PHP_FLOAT_MAX;
        }

        return 1 / $probability;
    }

    /**
     * Vérifier si un pari est +EV (Expected Value positive).
     */
    public function isPositiveEV(float $probability, float $odds): bool
    {
        return $this->calculate($probability, $odds) > 0;
    }

    /**
     * Comparer plusieurs opportunités de paris.
     *
     * @param array $opportunities Array of ['name' => string, 'probability' => float, 'odds' => float]
     * @return array Sorted by EV
     */
    public function compareOpportunities(array $opportunities): array
    {
        $results = [];

        foreach ($opportunities as $opp) {
            $ev = $this->calculate($opp['probability'], $opp['odds']);
            $evPercentage = $this->calculatePercentage($opp['probability'], $opp['odds']);

            $results[] = [
                'name' => $opp['name'],
                'probability' => $opp['probability'],
                'odds' => $opp['odds'],
                'ev' => round($ev, 4),
                'ev_percentage' => round($evPercentage, 2),
                'is_value' => $ev > 0,
            ];
        }

        // Trier par EV décroissant
        usort($results, fn($a, $b) => $b['ev'] <=> $a['ev']);

        return $results;
    }

    /**
     * Calculer l'EV cumulé pour un parlay.
     *
     * @param array $legs Array of ['probability' => float, 'odds' => float]
     */
    public function calculateParlay(array $legs): array
    {
        $combinedProbability = 1;
        $combinedOdds = 1;

        foreach ($legs as $leg) {
            $combinedProbability *= $leg['probability'];
            $combinedOdds *= $leg['odds'];
        }

        return [
            'combined_probability' => round($combinedProbability, 6),
            'combined_odds' => round($combinedOdds, 3),
            'ev' => $this->calculate($combinedProbability, $combinedOdds),
            'ev_percentage' => $this->calculatePercentage($combinedProbability, $combinedOdds),
            'legs_count' => count($legs),
        ];
    }

    /**
     * Calculer le ROI attendu sur un ensemble de paris.
     *
     * @param array $bets Array of ['probability' => float, 'odds' => float, 'stake' => float]
     */
    public function calculateExpectedROI(array $bets): array
    {
        $totalStaked = 0;
        $totalEV = 0;

        foreach ($bets as $bet) {
            $stake = $bet['stake'] ?? 1;
            $totalStaked += $stake;
            $totalEV += $this->calculate($bet['probability'], $bet['odds'], $stake);
        }

        return [
            'total_staked' => round($totalStaked, 2),
            'total_ev' => round($totalEV, 2),
            'expected_roi' => $totalStaked > 0
                ? round(($totalEV / $totalStaked) * 100, 2)
                : 0,
            'bets_count' => count($bets),
        ];
    }
}

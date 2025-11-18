<?php

declare(strict_types=1);

namespace App\Domains\ValueBets\Services;

use App\Domains\DataIngestion\Models\Game;
use App\Domains\Prediction\Enums\ConfidenceLevel;
use App\Domains\Prediction\Models\Prediction;
use App\Domains\ValueBets\Enums\BetStatus;
use App\Domains\ValueBets\Enums\BetType;
use App\Domains\ValueBets\Models\Bankroll;
use App\Domains\ValueBets\Models\Bet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service principal pour la gestion des value bets.
 */
class ValueBetService
{
    public function __construct(
        private readonly KellyCalculator $kellyCalculator,
        private readonly EVCalculator $evCalculator,
    ) {}

    /**
     * Analyser une opportunité de pari.
     */
    public function analyzeOpportunity(
        float $estimatedProbability,
        float $odds,
        ?float $bankroll = null
    ): array {
        $impliedProbability = 1 / $odds;
        $edge = $this->kellyCalculator->calculateEdge($estimatedProbability, $odds);
        $kelly = $this->kellyCalculator->calculate($estimatedProbability, $odds);
        $ev = $this->evCalculator->calculate($estimatedProbability, $odds);
        $evPercentage = $this->evCalculator->calculatePercentage($estimatedProbability, $odds);

        $analysis = [
            'estimated_probability' => round($estimatedProbability, 4),
            'implied_probability' => round($impliedProbability, 4),
            'odds' => $odds,
            'edge_percentage' => round($edge, 2),
            'kelly_fraction' => round($kelly, 4),
            'ev' => round($ev, 4),
            'ev_percentage' => round($evPercentage, 2),
            'is_value_bet' => $ev > 0,
            'recommendation' => $this->getRecommendation($edge, $kelly),
        ];

        if ($bankroll !== null && $kelly > 0) {
            $analysis['recommended_stake'] = round($bankroll * $kelly * 0.25, 2); // Quart-Kelly
            $analysis['max_stake'] = round($bankroll * $kelly, 2); // Full Kelly
        }

        return $analysis;
    }

    /**
     * Trouver les value bets pour une prédiction.
     */
    public function findValueBets(Prediction $prediction, array $availableOdds): array
    {
        $valueBets = [];

        foreach ($availableOdds as $bookmaker => $odds) {
            // Pour la sélection "home"
            if ($prediction->predicted_outcome === 'home') {
                $homeAnalysis = $this->analyzeOpportunity(
                    $prediction->home_win_probability,
                    $odds['home'] ?? 0
                );

                if ($homeAnalysis['is_value_bet']) {
                    $valueBets[] = [
                        'bookmaker' => $bookmaker,
                        'selection' => 'home',
                        'odds' => $odds['home'],
                        'analysis' => $homeAnalysis,
                    ];
                }
            } else {
                $awayAnalysis = $this->analyzeOpportunity(
                    $prediction->away_win_probability,
                    $odds['away'] ?? 0
                );

                if ($awayAnalysis['is_value_bet']) {
                    $valueBets[] = [
                        'bookmaker' => $bookmaker,
                        'selection' => 'away',
                        'odds' => $odds['away'],
                        'analysis' => $awayAnalysis,
                    ];
                }
            }
        }

        // Trier par edge décroissant
        usort($valueBets, fn($a, $b) => $b['analysis']['edge_percentage'] <=> $a['analysis']['edge_percentage']);

        return $valueBets;
    }

    /**
     * Créer un pari depuis une prédiction.
     */
    public function createBetFromPrediction(
        Bankroll $bankroll,
        Prediction $prediction,
        float $odds,
        BetType $betType = BetType::MONEYLINE,
        ?float $customStake = null,
        ?string $bookmaker = null
    ): Bet {
        $probability = $prediction->predicted_outcome === 'home'
            ? $prediction->home_win_probability
            : $prediction->away_win_probability;

        $analysis = $this->analyzeOpportunity($probability, $odds, $bankroll->current_amount);

        $stake = $customStake ?? $analysis['recommended_stake'] ?? $bankroll->min_bet_amount;
        $potentialPayout = $stake * $odds;

        $bet = new Bet([
            'bankroll_id' => $bankroll->id,
            'game_id' => $prediction->game_id,
            'prediction_id' => $prediction->id,
            'bet_type' => $betType->value,
            'selection' => $prediction->predicted_outcome,
            'odds_decimal' => $odds,
            'odds_american' => Bet::decimalToAmerican($odds),
            'stake' => $stake,
            'potential_payout' => $potentialPayout,
            'implied_probability' => 1 / $odds,
            'estimated_probability' => $probability,
            'expected_value' => $analysis['ev'],
            'edge_percentage' => $analysis['edge_percentage'],
            'kelly_fraction' => $analysis['kelly_fraction'],
            'kelly_stake' => $analysis['max_stake'] ?? 0,
            'confidence_score' => $prediction->confidence_score,
            'confidence_level' => $prediction->confidence_level,
            'bookmaker' => $bookmaker,
            'status' => BetStatus::PENDING->value,
            'placed_at' => now(),
        ]);

        $bet->save();
        $bankroll->recordBet($bet);

        return $bet;
    }

    /**
     * Résoudre les paris pour un match terminé.
     */
    public function settleBetsForGame(Game $game): array
    {
        if ($game->status !== 'final') {
            return ['error' => 'Game is not finished'];
        }

        $actualWinner = $game->home_score > $game->away_score ? 'home' : 'away';
        $totalGoals = $game->home_score + $game->away_score;
        $spread = $game->home_score - $game->away_score;

        $bets = Bet::where('game_id', $game->id)
            ->where('status', BetStatus::PENDING->value)
            ->get();

        $results = [
            'settled' => 0,
            'won' => 0,
            'lost' => 0,
            'push' => 0,
        ];

        foreach ($bets as $bet) {
            $result = $this->determineBetResult($bet, $actualWinner, $totalGoals, $spread);
            $bet->settle($result);

            $results['settled']++;
            $results[strtolower($result->value)]++;
        }

        return $results;
    }

    /**
     * Déterminer le résultat d'un pari.
     */
    private function determineBetResult(
        Bet $bet,
        string $actualWinner,
        int $totalGoals,
        int $spread
    ): BetStatus {
        $betType = BetType::from($bet->bet_type);

        return match ($betType) {
            BetType::MONEYLINE => $bet->selection === $actualWinner
                ? BetStatus::WON
                : BetStatus::LOST,

            BetType::OVER_UNDER => $this->evaluateOverUnder($bet, $totalGoals),

            BetType::SPREAD => $this->evaluateSpread($bet, $spread),

            default => $bet->selection === $actualWinner
                ? BetStatus::WON
                : BetStatus::LOST,
        };
    }

    private function evaluateOverUnder(Bet $bet, int $totalGoals): BetStatus
    {
        $line = $bet->line ?? 5.5;

        if ($totalGoals == $line) {
            return BetStatus::PUSH;
        }

        if ($bet->selection === 'over') {
            return $totalGoals > $line ? BetStatus::WON : BetStatus::LOST;
        } else {
            return $totalGoals < $line ? BetStatus::WON : BetStatus::LOST;
        }
    }

    private function evaluateSpread(Bet $bet, int $spread): BetStatus
    {
        $line = $bet->line ?? 0;
        $adjustedSpread = $bet->selection === 'home' ? $spread : -$spread;

        if ($adjustedSpread + $line == 0) {
            return BetStatus::PUSH;
        }

        return ($adjustedSpread + $line) > 0 ? BetStatus::WON : BetStatus::LOST;
    }

    /**
     * Obtenir une recommandation basée sur l'edge et Kelly.
     */
    private function getRecommendation(float $edge, float $kelly): string
    {
        if ($kelly <= 0 || $edge <= 0) {
            return 'skip';
        }

        if ($edge >= 10 && $kelly >= 0.05) {
            return 'strong_bet';
        }

        if ($edge >= 5 && $kelly >= 0.02) {
            return 'bet';
        }

        if ($edge >= 2) {
            return 'small_bet';
        }

        return 'skip';
    }

    /**
     * Obtenir les statistiques d'un bankroll.
     */
    public function getBankrollStats(Bankroll $bankroll): array
    {
        $bets = $bankroll->bets()->settled()->get();

        if ($bets->isEmpty()) {
            return $bankroll->getSummary();
        }

        $byType = $bets->groupBy('bet_type')->map(function ($typeBets) {
            $won = $typeBets->where('status', BetStatus::WON->value)->count();
            $total = $typeBets->count();
            return [
                'total' => $total,
                'won' => $won,
                'win_rate' => $total > 0 ? round(($won / $total) * 100, 1) : 0,
                'profit' => $typeBets->sum('profit_loss'),
            ];
        });

        $byConfidence = $bets->groupBy('confidence_level')->map(function ($confBets) {
            $won = $confBets->where('status', BetStatus::WON->value)->count();
            $total = $confBets->count();
            return [
                'total' => $total,
                'won' => $won,
                'win_rate' => $total > 0 ? round(($won / $total) * 100, 1) : 0,
                'profit' => $confBets->sum('profit_loss'),
            ];
        });

        return array_merge($bankroll->getSummary(), [
            'by_type' => $byType,
            'by_confidence' => $byConfidence,
            'avg_odds' => round($bets->avg('odds_decimal'), 3),
            'avg_stake' => round($bets->avg('stake'), 2),
            'best_bet' => $bets->sortByDesc('profit_loss')->first()?->profit_loss,
            'worst_bet' => $bets->sortBy('profit_loss')->first()?->profit_loss,
        ]);
    }
}

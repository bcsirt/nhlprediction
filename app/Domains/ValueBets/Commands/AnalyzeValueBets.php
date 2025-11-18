<?php

declare(strict_types=1);

namespace App\Domains\ValueBets\Commands;

use App\Domains\Prediction\Models\Prediction;
use App\Domains\ValueBets\Services\ValueBetService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AnalyzeValueBets extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'valuebets:analyze
                            {--date= : Date to analyze (YYYY-MM-DD)}
                            {--min-edge=5 : Minimum edge percentage}
                            {--min-confidence=high : Minimum confidence level}';

    /**
     * The console command description.
     */
    protected $description = 'Analyze predictions to find value betting opportunities';

    public function __construct(
        private readonly ValueBetService $valueBetService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : now();

        $minEdge = (float) $this->option('min-edge');
        $minConfidence = $this->option('min-confidence');

        $this->info("Analyzing value bets for {$date->toDateString()}...");

        // Récupérer les prédictions avec haute confiance
        $predictions = Prediction::whereHas('game', function ($query) use ($date) {
            $query->whereDate('game_date', $date)
                ->where('status', 'scheduled');
        })
            ->where('confidence_level', '>=', $minConfidence)
            ->with(['game.homeTeam', 'game.awayTeam'])
            ->get();

        if ($predictions->isEmpty()) {
            $this->warn('No predictions found for analysis');
            return self::SUCCESS;
        }

        $this->info("Found {$predictions->count()} predictions to analyze");
        $this->newLine();

        // Simuler des cotes (dans la réalité, elles viendraient d'une API)
        $opportunities = [];

        foreach ($predictions as $prediction) {
            /** @var \App\Domains\DataIngestion\Models\Game $game */
            $game = $prediction->game;
            /** @var \App\Domains\DataIngestion\Models\Team $homeTeam */
            $homeTeam = $game->homeTeam;
            /** @var \App\Domains\DataIngestion\Models\Team $awayTeam */
            $awayTeam = $game->awayTeam;

            // Simuler les cotes basées sur les probabilités implicites
            $homeOdds = $this->simulateOdds($prediction->home_win_probability);
            $awayOdds = $this->simulateOdds($prediction->away_win_probability);

            $probability = $prediction->predicted_outcome === 'home'
                ? $prediction->home_win_probability
                : $prediction->away_win_probability;
            $odds = $prediction->predicted_outcome === 'home' ? $homeOdds : $awayOdds;

            $analysis = $this->valueBetService->analyzeOpportunity($probability, $odds);

            if ($analysis['edge_percentage'] >= $minEdge) {
                $opportunities[] = [
                    'game' => "{$awayTeam->abbreviation} @ {$homeTeam->abbreviation}",
                    'selection' => $prediction->predicted_outcome === 'home'
                        ? $homeTeam->abbreviation
                        : $awayTeam->abbreviation,
                    'odds' => $odds,
                    'prob' => round($probability * 100, 1) . '%',
                    'edge' => round($analysis['edge_percentage'], 1) . '%',
                    'kelly' => round($analysis['kelly_fraction'] * 100, 2) . '%',
                    'ev' => round($analysis['ev_percentage'], 1) . '%',
                    'rec' => $analysis['recommendation'],
                ];
            }
        }

        if (empty($opportunities)) {
            $this->warn("No value bets found with edge >= {$minEdge}%");
            return self::SUCCESS;
        }

        // Trier par edge
        usort($opportunities, fn($a, $b) =>
            (float) str_replace('%', '', $b['edge']) <=> (float) str_replace('%', '', $a['edge'])
        );

        $this->table(
            ['Game', 'Pick', 'Odds', 'Prob', 'Edge', 'Kelly', 'EV', 'Rec'],
            $opportunities
        );

        $this->newLine();
        $this->info('Found ' . count($opportunities) . ' value bet opportunities');

        return self::SUCCESS;
    }

    /**
     * Simuler des cotes avec une marge de bookmaker.
     */
    private function simulateOdds(float $probability): float
    {
        // Ajouter une marge de bookmaker (~5%)
        $margin = 1.05;
        $fairOdds = 1 / $probability;

        // Varier légèrement les cotes (±10%)
        $variation = (rand(-10, 10) / 100);
        $odds = $fairOdds * $margin * (1 + $variation);

        return round($odds, 2);
    }
}

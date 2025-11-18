<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Commands;

use App\Domains\Prediction\Models\Prediction;
use App\Domains\Prediction\Services\PredictionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EvaluatePredictions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'prediction:evaluate
                            {--date= : Date for which to evaluate predictions (YYYY-MM-DD)}
                            {--from= : Start date for range evaluation}
                            {--to= : End date for range evaluation}
                            {--model= : Evaluate specific model ID}';

    /**
     * The console command description.
     */
    protected $description = 'Evaluate past predictions against actual game results';

    public function __construct(
        private readonly PredictionService $predictionService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('from') && $this->option('to')) {
            return $this->evaluateRange(
                Carbon::parse($this->option('from')),
                Carbon::parse($this->option('to'))
            );
        }

        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : now()->subDay();

        return $this->evaluateDate($date);
    }

    private function evaluateDate(Carbon $date): int
    {
        $this->info("Evaluating predictions for {$date->toDateString()}...");

        $results = $this->predictionService->evaluatePredictions($date);

        if ($results['total'] === 0) {
            $this->warn('No predictions found to evaluate');
            return self::SUCCESS;
        }

        $this->displayResults($results);

        return self::SUCCESS;
    }

    private function evaluateRange(Carbon $from, Carbon $to): int
    {
        $this->info("Evaluating predictions from {$from->toDateString()} to {$to->toDateString()}...");

        $totalResults = [
            'total' => 0,
            'evaluated' => 0,
            'correct' => 0,
            'incorrect' => 0,
        ];

        $current = $from->copy();
        while ($current <= $to) {
            $dayResults = $this->predictionService->evaluatePredictions($current);

            $totalResults['total'] += $dayResults['total'];
            $totalResults['evaluated'] += $dayResults['evaluated'];
            $totalResults['correct'] += $dayResults['correct'];
            $totalResults['incorrect'] += $dayResults['incorrect'];

            $current->addDay();
        }

        if ($totalResults['total'] === 0) {
            $this->warn('No predictions found to evaluate');
            return self::SUCCESS;
        }

        $totalResults['accuracy'] = $totalResults['evaluated'] > 0
            ? round($totalResults['correct'] / $totalResults['evaluated'], 4)
            : 0;

        $this->displayResults($totalResults);

        // Afficher les statistiques détaillées
        $this->displayDetailedStats($from, $to);

        return self::SUCCESS;
    }

    private function displayResults(array $results): void
    {
        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Predictions', $results['total']],
                ['Evaluated', $results['evaluated']],
                ['Correct', $results['correct']],
                ['Incorrect', $results['incorrect']],
                ['Accuracy', round($results['accuracy'] * 100, 2) . '%'],
            ]
        );

        // Afficher un indicateur visuel
        $accuracy = $results['accuracy'] * 100;
        $indicator = match (true) {
            $accuracy >= 60 => '🟢 Excellent',
            $accuracy >= 55 => '🟡 Good',
            $accuracy >= 50 => '🟠 Average',
            default => '🔴 Poor',
        };

        $this->newLine();
        $this->info("Performance: {$indicator}");
    }

    private function displayDetailedStats(Carbon $from, Carbon $to): void
    {
        $query = Prediction::query()
            ->whereNotNull('is_correct')
            ->whereHas('game', function ($q) use ($from, $to) {
                $q->whereBetween('game_date', [$from, $to]);
            });

        if ($this->option('model')) {
            $query->where('prediction_model_id', (int) $this->option('model'));
        }

        // Stats par niveau de confiance
        $this->newLine();
        $this->info("Accuracy by Confidence Level:");

        $confidenceLevels = ['very_high', 'high', 'medium', 'low', 'very_low'];
        $confidenceStats = [];

        foreach ($confidenceLevels as $level) {
            $levelQuery = (clone $query)->where('confidence_level', $level);
            $total = $levelQuery->count();
            $correct = (clone $levelQuery)->where('is_correct', true)->count();

            if ($total > 0) {
                $confidenceStats[] = [
                    ucfirst(str_replace('_', ' ', $level)),
                    $total,
                    $correct,
                    round(($correct / $total) * 100, 1) . '%',
                ];
            }
        }

        if (!empty($confidenceStats)) {
            $this->table(
                ['Confidence', 'Total', 'Correct', 'Accuracy'],
                $confidenceStats
            );
        }

        // ROI simulé
        $this->newLine();
        $this->info("Simulated ROI (flat betting):");

        $bettablePredictions = (clone $query)
            ->whereIn('confidence_level', ['high', 'very_high'])
            ->get();

        if ($bettablePredictions->isNotEmpty()) {
            $totalBets = $bettablePredictions->count();
            $wins = $bettablePredictions->where('is_correct', true)->count();
            $losses = $totalBets - $wins;

            // Assuming average odds of 1.91 (-110)
            $avgOdds = 1.91;
            $profit = ($wins * ($avgOdds - 1)) - $losses;
            $roi = ($profit / $totalBets) * 100;

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Bets', $totalBets],
                    ['Wins', $wins],
                    ['Losses', $losses],
                    ['Profit (units)', round($profit, 2)],
                    ['ROI', round($roi, 2) . '%'],
                ]
            );
        } else {
            $this->warn('No high-confidence predictions to calculate ROI');
        }
    }
}

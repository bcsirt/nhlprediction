<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Commands;

use App\Domains\DataIngestion\Models\Game;
use App\Domains\Prediction\DTOs\PredictionRequest;
use App\Domains\Prediction\Enums\PredictionType;
use App\Domains\Prediction\Services\PredictionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PredictGames extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'prediction:predict
                            {--date= : Date for which to make predictions (YYYY-MM-DD)}
                            {--game= : Predict a specific game by ID}
                            {--type=winner : Prediction type (winner, over_under, spread)}
                            {--model= : Use a specific model ID}
                            {--no-ai : Disable AI predictions}';

    /**
     * The console command description.
     */
    protected $description = 'Generate predictions for NHL games';

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
        $type = PredictionType::tryFrom($this->option('type')) ?? PredictionType::WINNER;

        if ($this->option('game')) {
            return $this->predictSingleGame((int) $this->option('game'), $type);
        }

        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : now();

        return $this->predictGamesForDate($date, $type);
    }

    private function predictSingleGame(int $gameId, PredictionType $type): int
    {
        $game = Game::with(['homeTeam', 'awayTeam'])->find($gameId);
        if (!$game) {
            $this->error("Game {$gameId} not found");
            return self::FAILURE;
        }

        /** @var \App\Domains\DataIngestion\Models\Team $homeTeam */
        $homeTeam = $game->homeTeam;
        /** @var \App\Domains\DataIngestion\Models\Team $awayTeam */
        $awayTeam = $game->awayTeam;

        $this->info("Predicting: {$awayTeam->abbreviation} @ {$homeTeam->abbreviation}");

        $request = new PredictionRequest(
            gameId: $gameId,
            predictionType: $type,
            modelId: $this->option('model') ? (int) $this->option('model') : null,
            useEnsemble: !$this->option('no-ai'),
        );

        $response = $this->predictionService->predict($request);

        if (!$response->isSuccessful()) {
            $this->error("Prediction failed: {$response->error}");
            return self::FAILURE;
        }

        $this->displayPrediction($game, $response);

        return self::SUCCESS;
    }

    private function predictGamesForDate(Carbon $date, PredictionType $type): int
    {
        $games = Game::whereDate('game_date', $date)
            ->where('status', 'scheduled')
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('game_date')
            ->get();

        if ($games->isEmpty()) {
            $this->warn("No scheduled games found for {$date->toDateString()}");
            return self::SUCCESS;
        }

        $this->info("Predicting {$games->count()} games for {$date->toDateString()}");
        $this->newLine();

        $results = [];
        $success = 0;
        $failed = 0;

        foreach ($games as $game) {
            $request = new PredictionRequest(
                gameId: $game->id,
                predictionType: $type,
                modelId: $this->option('model') ? (int) $this->option('model') : null,
                useEnsemble: !$this->option('no-ai'),
            );

            $response = $this->predictionService->predict($request);

            if ($response->isSuccessful()) {
                $success++;
                $results[] = $this->formatResultRow($game, $response);
            } else {
                $failed++;
                $this->warn("Failed to predict game {$game->id}: {$response->error}");
            }
        }

        if (!empty($results)) {
            $this->table(
                ['Game', 'Prediction', 'Probability', 'Confidence', 'Bet?'],
                $results
            );
        }

        $this->newLine();
        $this->info("Predictions completed: {$success} success, {$failed} failed");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function displayPrediction(Game $game, $response): void
    {
        /** @var \App\Domains\DataIngestion\Models\Team $homeTeam */
        $homeTeam = $game->homeTeam;
        /** @var \App\Domains\DataIngestion\Models\Team $awayTeam */
        $awayTeam = $game->awayTeam;

        $winnerName = $response->predictedOutcome === 'home'
            ? $homeTeam->name
            : $awayTeam->name;

        $this->newLine();
        $this->info("=== Prediction Result ===");
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Predicted Winner', $winnerName],
                ['Win Probability', round($response->probability * 100, 1) . '%'],
                ['Confidence Score', $response->confidenceScore . '/100'],
                ['Confidence Level', $response->confidenceLevel->label()],
                ['Should Bet', $response->shouldBet() ? 'Yes' : 'No'],
                ['Kelly Fraction', ($response->kellyFraction * 100) . '%'],
            ]
        );

        if ($response->explanation) {
            $this->newLine();
            $this->info("Analysis:");
            $this->line($response->explanation);
        }

        // Afficher les probabilités par modèle
        if (!empty($response->modelScores)) {
            $this->newLine();
            $this->info("Model Scores:");
            foreach ($response->modelScores as $model => $score) {
                $outcomeLabel = $score['outcome'] === 'home' ? $homeTeam->abbreviation : $awayTeam->abbreviation;
                $this->line("  - {$model}: {$outcomeLabel} ({$score['probability']}%)");
            }
        }
    }

    private function formatResultRow(Game $game, $response): array
    {
        /** @var \App\Domains\DataIngestion\Models\Team $homeTeam */
        $homeTeam = $game->homeTeam;
        /** @var \App\Domains\DataIngestion\Models\Team $awayTeam */
        $awayTeam = $game->awayTeam;

        $matchup = "{$awayTeam->abbreviation} @ {$homeTeam->abbreviation}";
        $winner = $response->predictedOutcome === 'home'
            ? $homeTeam->abbreviation
            : $awayTeam->abbreviation;
        $prob = round($response->probability * 100, 1) . '%';
        $confidence = $response->confidenceLevel->icon() . ' ' . $response->confidenceLevel->label();
        $bet = $response->shouldBet() ? 'YES' : '-';

        return [$matchup, $winner, $prob, $confidence, $bet];
    }
}

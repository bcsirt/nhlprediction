<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Commands;

use App\Domains\DataIngestion\Models\Game;
use App\Domains\DataIngestion\Models\Team;
use App\Domains\Prediction\Services\FeatureExtractor;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ExtractFeatures extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'prediction:extract-features
                            {--date= : Date for which to extract features (YYYY-MM-DD)}
                            {--team= : Extract features for a specific team ID}
                            {--season= : Season ID to use}
                            {--all : Extract features for all scheduled games}';

    /**
     * The console command description.
     */
    protected $description = 'Extract features for games and teams for ML predictions';

    public function __construct(
        private readonly FeatureExtractor $featureExtractor
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

        $this->info("Extracting features for {$date->toDateString()}...");

        if ($this->option('team')) {
            return $this->extractForTeam((int) $this->option('team'), $date);
        }

        if ($this->option('all')) {
            return $this->extractForAllScheduled();
        }

        return $this->extractForDate($date);
    }

    private function extractForDate(Carbon $date): int
    {
        $games = Game::whereDate('game_date', $date)
            ->where('status', 'scheduled')
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        if ($games->isEmpty()) {
            $this->warn("No scheduled games found for {$date->toDateString()}");
            return self::SUCCESS;
        }

        $this->info("Found {$games->count()} games to process");

        $progressBar = $this->output->createProgressBar($games->count());
        $progressBar->start();

        $success = 0;
        $failed = 0;

        foreach ($games as $game) {
            try {
                $this->featureExtractor->extractGameFeatures($game);
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed to extract features for game {$game->id}: {$e->getMessage()}");
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Features extracted: {$success} success, {$failed} failed");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function extractForTeam(int $teamId, Carbon $date): int
    {
        $team = Team::find($teamId);
        if (!$team) {
            $this->error("Team {$teamId} not found");
            return self::FAILURE;
        }

        $seasonId = $this->option('season')
            ? (int) $this->option('season')
            : $this->getCurrentSeasonId();

        $this->info("Extracting features for {$team->name} (Season: {$seasonId})");

        try {
            $features = $this->featureExtractor->extractTeamFeatures($team, $seasonId, $date);

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Goals For (5)', $features->rolling_goals_for_5 ?? 'N/A'],
                    ['Goals Against (5)', $features->rolling_goals_against_5 ?? 'N/A'],
                    ['Wins Last 5', $features->wins_last_5 ?? 'N/A'],
                    ['Wins Last 10', $features->wins_last_10 ?? 'N/A'],
                    ['Current Streak', $features->current_streak ?? 'N/A'],
                    ['Points %', $features->points_percentage ?? 'N/A'],
                    ['Home Win %', $features->home_win_percentage ?? 'N/A'],
                    ['Away Win %', $features->away_win_percentage ?? 'N/A'],
                ]
            );

            $this->info('Features extracted successfully');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to extract features: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function extractForAllScheduled(): int
    {
        $games = Game::where('status', 'scheduled')
            ->whereDate('game_date', '>=', now())
            ->orderBy('game_date')
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        if ($games->isEmpty()) {
            $this->warn('No scheduled games found');
            return self::SUCCESS;
        }

        $this->info("Found {$games->count()} scheduled games to process");

        $progressBar = $this->output->createProgressBar($games->count());
        $progressBar->start();

        $success = 0;
        $failed = 0;

        foreach ($games as $game) {
            try {
                $this->featureExtractor->extractGameFeatures($game);
                $success++;
            } catch (\Exception $e) {
                $failed++;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Features extracted: {$success} success, {$failed} failed");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function getCurrentSeasonId(): int
    {
        // TODO: Récupérer dynamiquement la saison actuelle
        return (int) date('Y');
    }
}

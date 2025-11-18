<?php

declare(strict_types=1);

namespace App\Domains\ValueBets\Commands;

use App\Domains\DataIngestion\Models\Game;
use App\Domains\ValueBets\Services\ValueBetService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SettleBets extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'valuebets:settle
                            {--date= : Date to settle bets for (YYYY-MM-DD)}
                            {--game= : Settle bets for a specific game ID}';

    /**
     * The console command description.
     */
    protected $description = 'Settle pending bets for finished games';

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
        if ($this->option('game')) {
            return $this->settleForGame((int) $this->option('game'));
        }

        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : now()->subDay();

        return $this->settleForDate($date);
    }

    private function settleForGame(int $gameId): int
    {
        $game = Game::with(['homeTeam', 'awayTeam'])->find($gameId);

        if (!$game) {
            $this->error("Game {$gameId} not found");
            return self::FAILURE;
        }

        if ($game->status !== 'final') {
            $this->error("Game {$gameId} is not finished yet");
            return self::FAILURE;
        }

        /** @var \App\Domains\DataIngestion\Models\Team $homeTeam */
        $homeTeam = $game->homeTeam;
        /** @var \App\Domains\DataIngestion\Models\Team $awayTeam */
        $awayTeam = $game->awayTeam;

        $this->info("Settling bets for {$awayTeam->abbreviation} @ {$homeTeam->abbreviation}");
        $this->info("Final score: {$awayTeam->abbreviation} {$game->away_score} - {$game->home_score} {$homeTeam->abbreviation}");

        $results = $this->valueBetService->settleBetsForGame($game);

        if (isset($results['error'])) {
            $this->error($results['error']);
            return self::FAILURE;
        }

        $this->displayResults($results);

        return self::SUCCESS;
    }

    private function settleForDate(Carbon $date): int
    {
        $this->info("Settling bets for games on {$date->toDateString()}...");

        $games = Game::whereDate('game_date', $date)
            ->where('status', 'final')
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        if ($games->isEmpty()) {
            $this->warn('No finished games found for this date');
            return self::SUCCESS;
        }

        $this->info("Found {$games->count()} finished games");

        $totalResults = [
            'settled' => 0,
            'won' => 0,
            'lost' => 0,
            'push' => 0,
        ];

        foreach ($games as $game) {
            $results = $this->valueBetService->settleBetsForGame($game);

            if (!isset($results['error'])) {
                foreach ($results as $key => $value) {
                    $totalResults[$key] += $value;
                }
            }
        }

        $this->newLine();
        $this->displayResults($totalResults);

        return self::SUCCESS;
    }

    private function displayResults(array $results): void
    {
        if ($results['settled'] === 0) {
            $this->warn('No bets to settle');
            return;
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Settled', $results['settled']],
                ['Won', $results['won']],
                ['Lost', $results['lost']],
                ['Push', $results['push']],
                ['Win Rate', $results['settled'] > 0
                    ? round(($results['won'] / $results['settled']) * 100, 1) . '%'
                    : '0%'],
            ]
        );
    }
}

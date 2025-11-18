<?php

declare(strict_types=1);

namespace App\Domains\User\Commands;

use App\Domains\User\Enums\StatsPeriod;
use App\Domains\User\Services\UserStatsService;
use App\Models\User;
use Illuminate\Console\Command;

class UserStatsReport extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'user:stats
                            {user : User ID}
                            {--period=monthly : Period type (daily, weekly, monthly, all_time)}';

    /**
     * The console command description.
     */
    protected $description = 'Show user betting statistics';

    public function __construct(
        private readonly UserStatsService $statsService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->argument('user');
        $user = User::find($userId);

        if (!$user) {
            $this->error("User {$userId} not found");
            return self::FAILURE;
        }

        $period = StatsPeriod::tryFrom($this->option('period')) ?? StatsPeriod::MONTHLY;

        $this->info("Statistics for {$user->name} - {$period->label()}");
        $this->newLine();

        $stats = $this->statsService->getStatsForPeriod($user, $period);

        if (!$stats) {
            $this->warn('No statistics found for this period');
            return self::SUCCESS;
        }

        $summary = $stats->getSummary();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Period', $summary['period']],
                ['Dates', $summary['dates']],
                ['Total Bets', $summary['total_bets']],
                ['Record', $summary['record']],
                ['Win Rate', $summary['win_rate']],
                ['Profit', $summary['profit']],
                ['ROI', $summary['roi']],
                ['Avg Odds', $summary['avg_odds']],
            ]
        );

        // Comparison with previous period
        $comparison = $this->statsService->compareToPreviousPeriod($user, $period);

        if (!empty($comparison)) {
            $this->newLine();
            $this->info('Comparison with previous period:');

            $rows = [];
            foreach ($comparison as $key => $value) {
                $indicator = $value > 0 ? '↑' : ($value < 0 ? '↓' : '→');
                $formatted = ($value > 0 ? '+' : '') . $value;
                $rows[] = [str_replace('_', ' ', ucfirst($key)), "{$indicator} {$formatted}"];
            }

            $this->table(['Metric', 'Change'], $rows);
        }

        return self::SUCCESS;
    }
}

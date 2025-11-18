<?php

declare(strict_types=1);

namespace App\Domains\ValueBets\Commands;

use App\Domains\ValueBets\Models\Bankroll;
use App\Domains\ValueBets\Services\ValueBetService;
use Illuminate\Console\Command;

class BankrollStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'valuebets:bankroll
                            {--id= : Bankroll ID to show}
                            {--create : Create a new bankroll}
                            {--name= : Name for new bankroll}
                            {--amount= : Initial amount for new bankroll}';

    /**
     * The console command description.
     */
    protected $description = 'Show bankroll status and statistics';

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
        if ($this->option('create')) {
            return $this->createBankroll();
        }

        if ($this->option('id')) {
            return $this->showBankroll((int) $this->option('id'));
        }

        return $this->listBankrolls();
    }

    private function createBankroll(): int
    {
        $name = $this->option('name') ?? $this->ask('Bankroll name?', 'NHL Betting');
        $amount = $this->option('amount') ?? $this->ask('Initial amount?', '1000');

        $bankroll = Bankroll::create([
            'name' => $name,
            'initial_amount' => (float) $amount,
            'current_amount' => (float) $amount,
            'peak_amount' => (float) $amount,
            'betting_strategy' => 'kelly',
            'kelly_fraction' => 0.25,
            'max_bet_percentage' => 5,
            'min_bet_amount' => 10,
        ]);

        $this->info("Bankroll '{$name}' created with ID {$bankroll->id}");
        $this->info("Initial amount: \${$amount}");

        return self::SUCCESS;
    }

    private function showBankroll(int $id): int
    {
        $bankroll = Bankroll::find($id);

        if (!$bankroll) {
            $this->error("Bankroll {$id} not found");
            return self::FAILURE;
        }

        $this->info("=== {$bankroll->name} ===");
        $this->newLine();

        $stats = $this->valueBetService->getBankrollStats($bankroll);

        // Informations principales
        $this->table(
            ['Metric', 'Value'],
            [
                ['Current Bankroll', '$' . number_format($stats['bankroll'], 2)],
                ['Initial Bankroll', '$' . number_format($stats['initial'], 2)],
                ['Total Profit', ($stats['profit'] >= 0 ? '+' : '') . '$' . number_format($stats['profit'], 2)],
                ['ROI', $stats['roi']],
                ['Win Rate', $stats['win_rate']],
                ['Record', $stats['record']],
                ['Current Streak', $stats['streak']],
                ['Max Drawdown', $stats['max_drawdown']],
            ]
        );

        // Stats par type si disponibles
        if (isset($stats['by_type']) && !empty($stats['by_type'])) {
            $this->newLine();
            $this->info('Performance by Bet Type:');

            $typeRows = [];
            foreach ($stats['by_type'] as $type => $typeStats) {
                $typeRows[] = [
                    ucfirst($type),
                    $typeStats['total'],
                    $typeStats['won'],
                    $typeStats['win_rate'] . '%',
                    ($typeStats['profit'] >= 0 ? '+' : '') . '$' . number_format($typeStats['profit'], 2),
                ];
            }

            $this->table(['Type', 'Total', 'Won', 'Win Rate', 'Profit'], $typeRows);
        }

        // Stats par confiance si disponibles
        if (isset($stats['by_confidence']) && !empty($stats['by_confidence'])) {
            $this->newLine();
            $this->info('Performance by Confidence:');

            $confRows = [];
            foreach ($stats['by_confidence'] as $conf => $confStats) {
                $confRows[] = [
                    ucfirst(str_replace('_', ' ', $conf)),
                    $confStats['total'],
                    $confStats['won'],
                    $confStats['win_rate'] . '%',
                    ($confStats['profit'] >= 0 ? '+' : '') . '$' . number_format($confStats['profit'], 2),
                ];
            }

            $this->table(['Confidence', 'Total', 'Won', 'Win Rate', 'Profit'], $confRows);
        }

        return self::SUCCESS;
    }

    private function listBankrolls(): int
    {
        $bankrolls = Bankroll::active()->get();

        if ($bankrolls->isEmpty()) {
            $this->warn('No bankrolls found. Create one with --create');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($bankrolls as $bankroll) {
            $rows[] = [
                $bankroll->id,
                $bankroll->name,
                '$' . number_format($bankroll->current_amount, 2),
                ($bankroll->total_profit >= 0 ? '+' : '') . '$' . number_format($bankroll->total_profit, 2),
                round($bankroll->roi_percentage, 2) . '%',
                "{$bankroll->winning_bets}W-{$bankroll->losing_bets}L",
                $bankroll->total_bets,
            ];
        }

        $this->table(
            ['ID', 'Name', 'Bankroll', 'Profit', 'ROI', 'Record', 'Bets'],
            $rows
        );

        return self::SUCCESS;
    }
}

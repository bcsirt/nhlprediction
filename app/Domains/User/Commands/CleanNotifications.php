<?php

declare(strict_types=1);

namespace App\Domains\User\Commands;

use App\Domains\User\Services\NotificationService;
use Illuminate\Console\Command;

class CleanNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'user:clean-notifications
                            {--days=30 : Number of days to keep notifications}';

    /**
     * The console command description.
     */
    protected $description = 'Clean old read notifications';

    public function __construct(
        private readonly NotificationService $notificationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info("Cleaning notifications older than {$days} days...");

        $deleted = $this->notificationService->cleanOldNotifications($days);

        $this->info("Deleted {$deleted} old notifications.");

        return self::SUCCESS;
    }
}

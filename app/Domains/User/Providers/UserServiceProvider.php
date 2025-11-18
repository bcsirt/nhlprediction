<?php

declare(strict_types=1);

namespace App\Domains\User\Providers;

use App\Domains\User\Commands\CleanNotifications;
use App\Domains\User\Commands\UserStatsReport;
use App\Domains\User\Services\NotificationService;
use App\Domains\User\Services\UserStatsService;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(NotificationService::class, function () {
            return new NotificationService();
        });

        $this->app->singleton(UserStatsService::class, function () {
            return new UserStatsService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanNotifications::class,
                UserStatsReport::class,
            ]);
        }
    }
}

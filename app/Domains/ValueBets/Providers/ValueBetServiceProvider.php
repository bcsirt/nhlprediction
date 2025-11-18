<?php

declare(strict_types=1);

namespace App\Domains\ValueBets\Providers;

use App\Domains\ValueBets\Commands\AnalyzeValueBets;
use App\Domains\ValueBets\Commands\BankrollStatus;
use App\Domains\ValueBets\Commands\SettleBets;
use App\Domains\ValueBets\Services\EVCalculator;
use App\Domains\ValueBets\Services\KellyCalculator;
use App\Domains\ValueBets\Services\ValueBetService;
use Illuminate\Support\ServiceProvider;

class ValueBetServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(KellyCalculator::class, function () {
            return new KellyCalculator();
        });

        $this->app->singleton(EVCalculator::class, function () {
            return new EVCalculator();
        });

        $this->app->singleton(ValueBetService::class, function ($app) {
            return new ValueBetService(
                $app->make(KellyCalculator::class),
                $app->make(EVCalculator::class),
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AnalyzeValueBets::class,
                SettleBets::class,
                BankrollStatus::class,
            ]);
        }
    }
}

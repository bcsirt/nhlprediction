<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Providers;

use App\Domains\Prediction\Commands\EvaluatePredictions;
use App\Domains\Prediction\Commands\ExtractFeatures;
use App\Domains\Prediction\Commands\PredictGames;
use App\Domains\Prediction\Services\FeatureExtractor;
use App\Domains\Prediction\Services\PredictionService;
use Illuminate\Support\ServiceProvider;

class PredictionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Enregistrer les services
        $this->app->singleton(FeatureExtractor::class, function ($app) {
            return new FeatureExtractor();
        });

        $this->app->singleton(PredictionService::class, function ($app) {
            return new PredictionService(
                $app->make(FeatureExtractor::class),
                $app->make(\App\Domains\AI\Services\ClaudeService::class),
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
                ExtractFeatures::class,
                PredictGames::class,
                EvaluatePredictions::class,
            ]);
        }
    }
}

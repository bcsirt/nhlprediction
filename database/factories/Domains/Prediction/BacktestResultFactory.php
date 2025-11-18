<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Prediction;

use App\Domains\Prediction\Models\BacktestResult;
use App\Domains\Prediction\Models\PredictionModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BacktestResult>
 */
class BacktestResultFactory extends Factory
{
    protected $model = BacktestResult::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $totalGames = fake()->numberBetween(100, 500);
        $accuracy = fake()->randomFloat(4, 0.50, 0.65);
        $correct = (int) round($totalGames * $accuracy);
        $incorrect = $totalGames - $correct;

        $initialBankroll = 1000;
        $roi = fake()->randomFloat(2, -10, 15);
        $profit = $initialBankroll * ($roi / 100);

        return [
            'prediction_model_id' => PredictionModel::factory(),
            'start_date' => fake()->dateTimeBetween('-1 year', '-3 months'),
            'end_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'total_games' => $totalGames,
            'correct_predictions' => $correct,
            'incorrect_predictions' => $incorrect,
            'accuracy' => $accuracy,
            'precision_score' => fake()->randomFloat(4, 0.50, 0.65),
            'recall' => fake()->randomFloat(4, 0.50, 0.65),
            'f1_score' => fake()->randomFloat(4, 0.50, 0.65),
            'log_loss' => fake()->randomFloat(6, 0.5, 0.8),
            'brier_score' => fake()->randomFloat(6, 0.15, 0.30),
            'roi_percentage' => $roi,
            'simulated_profit' => round($profit, 2),
            'initial_bankroll' => $initialBankroll,
            'final_bankroll' => $initialBankroll + $profit,
            'max_drawdown' => fake()->randomFloat(2, 5, 25),
            'sharpe_ratio' => fake()->randomFloat(4, -0.5, 2.0),
            'winning_streak' => fake()->numberBetween(3, 10),
            'losing_streak' => fake()->numberBetween(3, 8),
            'average_odds' => fake()->randomFloat(3, 1.80, 2.10),
            'confusion_matrix' => [
                [['TN', $incorrect / 2], ['FP', $incorrect / 2]],
                [['FN', $incorrect / 2], ['TP', $correct]],
            ],
            'calibration_data' => [
                ['bin' => '0.5-0.6', 'predicted' => 0.55, 'actual' => 0.52],
                ['bin' => '0.6-0.7', 'predicted' => 0.65, 'actual' => 0.63],
            ],
            'feature_importance' => [
                'rolling_goals_for_5' => 0.15,
                'points_percentage' => 0.12,
                'current_streak' => 0.10,
                'home_win_percentage' => 0.08,
            ],
        ];
    }

    /**
     * Backtest profitable.
     */
    public function profitable(): static
    {
        return $this->state(function (array $attributes) {
            $roi = fake()->randomFloat(2, 5, 20);
            $profit = ($attributes['initial_bankroll'] ?? 1000) * ($roi / 100);

            return [
                'roi_percentage' => $roi,
                'simulated_profit' => round($profit, 2),
                'final_bankroll' => ($attributes['initial_bankroll'] ?? 1000) + $profit,
                'accuracy' => fake()->randomFloat(4, 0.55, 0.65),
            ];
        });
    }

    /**
     * Backtest non profitable.
     */
    public function unprofitable(): static
    {
        return $this->state(function (array $attributes) {
            $roi = fake()->randomFloat(2, -15, -2);
            $profit = ($attributes['initial_bankroll'] ?? 1000) * ($roi / 100);

            return [
                'roi_percentage' => $roi,
                'simulated_profit' => round($profit, 2),
                'final_bankroll' => ($attributes['initial_bankroll'] ?? 1000) + $profit,
            ];
        });
    }

    /**
     * Backtest statistiquement significatif.
     */
    public function significant(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_games' => fake()->numberBetween(200, 500),
        ]);
    }

    /**
     * Backtest bien calibré.
     */
    public function wellCalibrated(): static
    {
        return $this->state(fn (array $attributes) => [
            'brier_score' => fake()->randomFloat(6, 0.15, 0.20),
        ]);
    }
}

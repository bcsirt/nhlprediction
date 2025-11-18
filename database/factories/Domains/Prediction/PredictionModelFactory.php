<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Prediction;

use App\Domains\Prediction\Enums\ModelStatus;
use App\Domains\Prediction\Enums\ModelType;
use App\Domains\Prediction\Models\PredictionModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PredictionModel>
 */
class PredictionModelFactory extends Factory
{
    protected $model = PredictionModel::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $modelType = fake()->randomElement(ModelType::cases());

        return [
            'name' => fake()->unique()->words(3, true) . ' Model',
            'version' => fake()->semver(),
            'model_type' => $modelType->value,
            'status' => ModelStatus::PRODUCTION->value,
            'description' => fake()->paragraph(),
            'features_used' => fake()->randomElements([
                'rolling_goals_for_5',
                'rolling_goals_against_5',
                'wins_last_5',
                'points_percentage',
                'home_win_percentage',
                'current_streak',
            ], 4),
            'hyperparameters' => $modelType->defaultHyperparameters(),
            'training_date' => fake()->dateTimeBetween('-6 months', '-1 month'),
            'accuracy' => fake()->randomFloat(4, 0.50, 0.70),
            'precision_score' => fake()->randomFloat(4, 0.50, 0.70),
            'recall' => fake()->randomFloat(4, 0.50, 0.70),
            'f1_score' => fake()->randomFloat(4, 0.50, 0.70),
            'log_loss' => fake()->randomFloat(6, 0.4, 0.8),
            'roc_auc' => fake()->randomFloat(4, 0.60, 0.85),
            'training_samples' => fake()->numberBetween(500, 5000),
            'test_samples' => fake()->numberBetween(100, 1000),
        ];
    }

    /**
     * Model en production.
     */
    public function production(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ModelStatus::PRODUCTION->value,
            'accuracy' => fake()->randomFloat(4, 0.55, 0.70),
        ]);
    }

    /**
     * Model en entraînement.
     */
    public function training(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ModelStatus::TRAINING->value,
            'accuracy' => null,
            'f1_score' => null,
        ]);
    }

    /**
     * Model échoué.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ModelStatus::FAILED->value,
        ]);
    }

    /**
     * Model obsolète.
     */
    public function deprecated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ModelStatus::DEPRECATED->value,
        ]);
    }

    /**
     * Model Random Forest.
     */
    public function randomForest(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => ModelType::RANDOM_FOREST->value,
            'hyperparameters' => ModelType::RANDOM_FOREST->defaultHyperparameters(),
        ]);
    }

    /**
     * Model XGBoost.
     */
    public function xgboost(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => ModelType::XGBOOST->value,
            'hyperparameters' => ModelType::XGBOOST->defaultHyperparameters(),
        ]);
    }

    /**
     * Model performant.
     */
    public function performant(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ModelStatus::PRODUCTION->value,
            'accuracy' => fake()->randomFloat(4, 0.60, 0.70),
            'f1_score' => fake()->randomFloat(4, 0.60, 0.70),
        ]);
    }
}

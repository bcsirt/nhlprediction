<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Prediction\Models;

use App\Domains\Prediction\Enums\ModelStatus;
use App\Domains\Prediction\Enums\ModelType;
use App\Domains\Prediction\Models\BacktestResult;
use App\Domains\Prediction\Models\PredictionModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_backtest_results(): void
    {
        $model = PredictionModel::factory()->create();
        BacktestResult::factory()->count(3)->create([
            'prediction_model_id' => $model->id,
        ]);

        $this->assertCount(3, $model->backtestResults);
    }

    public function test_can_predict_returns_true_for_testing_and_production(): void
    {
        $testing = PredictionModel::factory()->create([
            'status' => ModelStatus::TESTING->value,
        ]);
        $production = PredictionModel::factory()->create([
            'status' => ModelStatus::PRODUCTION->value,
        ]);
        $training = PredictionModel::factory()->create([
            'status' => ModelStatus::TRAINING->value,
        ]);

        $this->assertTrue($testing->canPredict());
        $this->assertTrue($production->canPredict());
        $this->assertFalse($training->canPredict());
    }

    public function test_is_performant(): void
    {
        $performant = PredictionModel::factory()->create([
            'accuracy' => 0.58,
            'f1_score' => 0.60,
        ]);
        $notPerformant = PredictionModel::factory()->create([
            'accuracy' => 0.48,
            'f1_score' => 0.45,
        ]);

        $this->assertTrue($performant->isPerformant(0.55, 0.55));
        $this->assertFalse($notPerformant->isPerformant(0.55, 0.55));
    }

    public function test_scope_active(): void
    {
        PredictionModel::factory()->create(['status' => ModelStatus::PRODUCTION->value]);
        PredictionModel::factory()->create(['status' => ModelStatus::TRAINING->value]);
        PredictionModel::factory()->create(['status' => ModelStatus::DEPRECATED->value]);
        PredictionModel::factory()->create(['status' => ModelStatus::FAILED->value]);

        $active = PredictionModel::active()->get();

        $this->assertCount(2, $active);
    }

    public function test_scope_production(): void
    {
        PredictionModel::factory()->create(['status' => ModelStatus::PRODUCTION->value]);
        PredictionModel::factory()->create(['status' => ModelStatus::TESTING->value]);

        $production = PredictionModel::production()->get();

        $this->assertCount(1, $production);
    }

    public function test_scope_performant(): void
    {
        PredictionModel::factory()->create([
            'accuracy' => 0.60,
            'f1_score' => 0.58,
        ]);
        PredictionModel::factory()->create([
            'accuracy' => 0.50,
            'f1_score' => 0.48,
        ]);

        $performant = PredictionModel::performant(0.55, 0.55)->get();

        $this->assertCount(1, $performant);
    }

    public function test_model_type_enum_cast(): void
    {
        $model = PredictionModel::factory()->create([
            'model_type' => ModelType::XGBOOST->value,
        ]);

        $this->assertEquals('xgboost', $model->model_type);
    }

    public function test_status_enum_cast(): void
    {
        $model = PredictionModel::factory()->create([
            'status' => ModelStatus::PRODUCTION->value,
        ]);

        $this->assertEquals('production', $model->status);
    }

    public function test_hyperparameters_is_array(): void
    {
        $model = PredictionModel::factory()->create([
            'hyperparameters' => ['n_estimators' => 100, 'max_depth' => 10],
        ]);

        $this->assertIsArray($model->hyperparameters);
        $this->assertEquals(100, $model->hyperparameters['n_estimators']);
    }

    public function test_features_used_is_array(): void
    {
        $model = PredictionModel::factory()->create([
            'features_used' => ['feature_a', 'feature_b', 'feature_c'],
        ]);

        $this->assertIsArray($model->features_used);
        $this->assertCount(3, $model->features_used);
    }
}

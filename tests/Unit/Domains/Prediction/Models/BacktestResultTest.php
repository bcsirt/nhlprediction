<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Prediction\Models;

use App\Domains\Prediction\Models\BacktestResult;
use App\Domains\Prediction\Models\PredictionModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacktestResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_belongs_to_prediction_model(): void
    {
        $model = PredictionModel::factory()->create();
        $backtest = BacktestResult::factory()->create([
            'prediction_model_id' => $model->id,
        ]);

        $this->assertInstanceOf(PredictionModel::class, $backtest->predictionModel);
        $this->assertEquals($model->id, $backtest->predictionModel->id);
    }

    public function test_it_calculates_period_days(): void
    {
        $backtest = BacktestResult::factory()->create([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $this->assertEquals(30, $backtest->period_days);
    }

    public function test_it_calculates_bankroll_growth(): void
    {
        $backtest = BacktestResult::factory()->create([
            'initial_bankroll' => 1000,
            'final_bankroll' => 1150,
        ]);

        $this->assertEquals(15.0, $backtest->bankroll_growth);
    }

    public function test_bankroll_growth_returns_zero_when_no_initial(): void
    {
        $backtest = BacktestResult::factory()->create([
            'initial_bankroll' => 0,
            'final_bankroll' => 1000,
        ]);

        $this->assertEquals(0, $backtest->bankroll_growth);
    }

    public function test_it_calculates_win_loss_ratio(): void
    {
        $backtest = BacktestResult::factory()->create([
            'correct_predictions' => 60,
            'incorrect_predictions' => 40,
        ]);

        $this->assertEquals(1.5, $backtest->win_loss_ratio);
    }

    public function test_win_loss_ratio_handles_zero_losses(): void
    {
        $backtest = BacktestResult::factory()->create([
            'correct_predictions' => 10,
            'incorrect_predictions' => 0,
        ]);

        $this->assertEquals(999.99, $backtest->win_loss_ratio);
    }

    public function test_is_profitable(): void
    {
        $profitable = BacktestResult::factory()->create([
            'roi_percentage' => 5.5,
        ]);
        $unprofitable = BacktestResult::factory()->create([
            'roi_percentage' => -3.2,
        ]);

        $this->assertTrue($profitable->isProfitable());
        $this->assertFalse($unprofitable->isProfitable());
    }

    public function test_is_statistically_significant(): void
    {
        $significant = BacktestResult::factory()->create([
            'total_games' => 150,
        ]);
        $notSignificant = BacktestResult::factory()->create([
            'total_games' => 50,
        ]);

        $this->assertTrue($significant->isStatisticallySignificant());
        $this->assertFalse($notSignificant->isStatisticallySignificant());
    }

    public function test_is_well_calibrated(): void
    {
        $calibrated = BacktestResult::factory()->create([
            'brier_score' => 0.20,
        ]);
        $notCalibrated = BacktestResult::factory()->create([
            'brier_score' => 0.30,
        ]);

        $this->assertTrue($calibrated->isWellCalibrated());
        $this->assertFalse($notCalibrated->isWellCalibrated());
    }

    public function test_grade_calculation(): void
    {
        // Excellent backtest
        $excellent = BacktestResult::factory()->create([
            'accuracy' => 0.65,
            'f1_score' => 0.65,
            'roi_percentage' => 10,
            'brier_score' => 0.18,
            'sharpe_ratio' => 1.5,
        ]);

        $this->assertMatchesRegularExpression('/^[A-F][+-]?$/', $excellent->grade);
    }

    public function test_get_top_features(): void
    {
        $backtest = BacktestResult::factory()->create([
            'feature_importance' => [
                'feature_a' => 0.25,
                'feature_b' => 0.15,
                'feature_c' => 0.10,
                'feature_d' => 0.05,
            ],
        ]);

        $topFeatures = $backtest->getTopFeatures(2);

        $this->assertCount(2, $topFeatures);
        $this->assertArrayHasKey('feature_a', $topFeatures);
        $this->assertArrayHasKey('feature_b', $topFeatures);
    }

    public function test_get_top_features_empty_when_no_importance(): void
    {
        $backtest = BacktestResult::factory()->create([
            'feature_importance' => null,
        ]);

        $this->assertEmpty($backtest->getTopFeatures());
    }

    public function test_get_summary(): void
    {
        $backtest = BacktestResult::factory()->create([
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-31',
            'total_games' => 200,
            'accuracy' => 0.58,
            'f1_score' => 0.57,
            'roi_percentage' => 8.5,
            'simulated_profit' => 850,
            'max_drawdown' => 15,
            'sharpe_ratio' => 1.2,
        ]);

        $summary = $backtest->getSummary();

        $this->assertArrayHasKey('period', $summary);
        $this->assertArrayHasKey('total_games', $summary);
        $this->assertArrayHasKey('accuracy', $summary);
        $this->assertArrayHasKey('roi', $summary);
        $this->assertArrayHasKey('grade', $summary);
        $this->assertEquals(200, $summary['total_games']);
    }

    public function test_scope_profitable(): void
    {
        BacktestResult::factory()->create(['roi_percentage' => 5]);
        BacktestResult::factory()->create(['roi_percentage' => -3]);
        BacktestResult::factory()->create(['roi_percentage' => 10]);

        $profitable = BacktestResult::profitable()->get();

        $this->assertCount(2, $profitable);
    }

    public function test_scope_significant(): void
    {
        BacktestResult::factory()->create(['total_games' => 150]);
        BacktestResult::factory()->create(['total_games' => 50]);

        $significant = BacktestResult::significant()->get();

        $this->assertCount(1, $significant);
    }

    public function test_scope_recent(): void
    {
        BacktestResult::factory()->create(['created_at' => now()->subDays(10)]);
        BacktestResult::factory()->create(['created_at' => now()->subDays(60)]);

        $recent = BacktestResult::recent(30)->get();

        $this->assertCount(1, $recent);
    }

    public function test_best_for_model(): void
    {
        $model = PredictionModel::factory()->create();

        BacktestResult::factory()->create([
            'prediction_model_id' => $model->id,
            'total_games' => 100,
            'f1_score' => 0.55,
            'roi_percentage' => 5,
        ]);

        BacktestResult::factory()->create([
            'prediction_model_id' => $model->id,
            'total_games' => 100,
            'f1_score' => 0.62,
            'roi_percentage' => 8,
        ]);

        $best = BacktestResult::bestForModel($model->id);

        $this->assertNotNull($best);
        $this->assertEquals(0.62, (float) $best->f1_score);
    }

    public function test_casts_are_correct(): void
    {
        $backtest = BacktestResult::factory()->create([
            'confusion_matrix' => [['TP' => 50], ['FN' => 20]],
            'feature_importance' => ['feature_a' => 0.5],
        ]);

        $this->assertIsArray($backtest->confusion_matrix);
        $this->assertIsArray($backtest->feature_importance);
    }
}

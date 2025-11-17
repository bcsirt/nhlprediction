<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('backtest_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prediction_model_id')->constrained('prediction_models')->onDelete('cascade');

            // Backtest period
            $table->string('backtest_name');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('games_count');

            // Overall performance
            $table->integer('predictions_made');
            $table->integer('predictions_correct');
            $table->decimal('accuracy', 5, 4);
            $table->decimal('precision', 5, 4)->nullable();
            $table->decimal('recall', 5, 4)->nullable();
            $table->decimal('f1_score', 5, 4)->nullable();

            // Probability calibration
            $table->decimal('brier_score', 5, 4)->nullable();
            $table->decimal('log_loss', 8, 6)->nullable();
            $table->boolean('is_well_calibrated')->nullable();

            // Confidence breakdown
            $table->integer('very_high_conf_count')->default(0);
            $table->integer('very_high_conf_correct')->default(0);
            $table->integer('high_conf_count')->default(0);
            $table->integer('high_conf_correct')->default(0);
            $table->integer('medium_conf_count')->default(0);
            $table->integer('medium_conf_correct')->default(0);

            // Prediction type breakdown
            $table->json('accuracy_by_type')->nullable(); // {'winner': 0.65, 'over_under': 0.58}
            $table->json('accuracy_by_team')->nullable();
            $table->json('accuracy_by_month')->nullable();

            // Betting simulation (si applicable)
            $table->decimal('initial_bankroll', 10, 2)->nullable();
            $table->decimal('final_bankroll', 10, 2)->nullable();
            $table->decimal('roi_percentage', 8, 4)->nullable();
            $table->decimal('max_drawdown', 8, 4)->nullable();
            $table->decimal('sharpe_ratio', 8, 4)->nullable();
            $table->integer('profitable_bets')->nullable();
            $table->integer('losing_bets')->nullable();

            // Errors & Analysis
            $table->decimal('mean_absolute_error', 5, 2)->nullable();
            $table->decimal('root_mean_squared_error', 5, 2)->nullable();
            $table->json('worst_predictions')->nullable(); // Array des pires prédictions
            $table->json('best_predictions')->nullable();

            // Metadata
            $table->json('parameters_used')->nullable(); // Paramètres du backtest
            $table->text('summary')->nullable();
            $table->timestamp('executed_at');

            $table->timestamps();

            // Index
            $table->index(['prediction_model_id', 'period_start', 'period_end']);
            $table->index('executed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backtest_results');
    }
};

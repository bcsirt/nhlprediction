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
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->onDelete('cascade');
            $table->foreignId('prediction_model_id')->nullable()->constrained('prediction_models');

            // Prediction type
            $table->string('prediction_type', 50); // 'winner', 'over_under', 'spread', 'exact_score'
            $table->string('model_type', 50); // 'ml', 'statistical', 'ensemble', 'ai'

            // Predicted outcome
            $table->string('predicted_winner', 20)->nullable(); // 'home', 'away', 'draw'
            $table->decimal('predicted_home_score', 5, 2)->nullable();
            $table->decimal('predicted_away_score', 5, 2)->nullable();
            $table->decimal('predicted_total_goals', 5, 2)->nullable();

            // Probabilities
            $table->decimal('home_win_probability', 5, 4)->nullable(); // 0.0000 à 1.0000
            $table->decimal('away_win_probability', 5, 4)->nullable();
            $table->decimal('draw_probability', 5, 4)->nullable(); // Overtime/Shootout
            $table->decimal('over_probability', 5, 4)->nullable();
            $table->decimal('under_probability', 5, 4)->nullable();

            // Confidence & Metrics
            $table->string('confidence_level', 20); // 'very_low', 'low', 'medium', 'high', 'very_high'
            $table->decimal('confidence_score', 5, 2)->nullable(); // 0-100
            $table->decimal('expected_value', 8, 4)->nullable(); // Pour paris
            $table->decimal('kelly_criterion', 5, 4)->nullable(); // Fraction de bankroll

            // Model performance indicators
            $table->decimal('feature_importance_score', 5, 2)->nullable();
            $table->integer('features_used_count')->nullable();
            $table->decimal('model_accuracy_estimate', 5, 2)->nullable();

            // Actual outcome (pour backtesting)
            $table->string('actual_winner', 20)->nullable();
            $table->integer('actual_home_score')->nullable();
            $table->integer('actual_away_score')->nullable();
            $table->integer('actual_total_goals')->nullable();
            $table->boolean('prediction_correct')->nullable();
            $table->decimal('prediction_error', 5, 2)->nullable(); // MAE ou MSE

            // Metadata
            $table->timestamp('predicted_at');
            $table->timestamp('game_starts_at')->nullable();
            $table->text('prediction_explanation')->nullable(); // JSON ou texte
            $table->json('feature_values')->nullable(); // Features utilisées
            $table->json('model_metadata')->nullable(); // Infos modèle

            $table->timestamps();

            // Index pour analyses
            $table->index(['game_id', 'prediction_type']);
            $table->index(['prediction_type', 'model_type']);
            $table->index('predicted_at');
            $table->index('confidence_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};

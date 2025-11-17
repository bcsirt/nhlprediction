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
        Schema::create('prediction_models', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 'RandomForest_v1', 'NeuralNet_v2', etc.
            $table->string('version', 50); // '1.0.0', '2.1.3'
            $table->string('model_type', 50); // 'random_forest', 'neural_network', 'gradient_boosting', 'ensemble'
            $table->string('framework', 50)->nullable(); // 'scikit-learn', 'pytorch', 'tensorflow'

            // Model description
            $table->text('description')->nullable();
            $table->json('hyperparameters')->nullable(); // Config du modèle

            // Training info
            $table->date('trained_at')->nullable();
            $table->integer('training_samples_count')->nullable();
            $table->string('training_season', 20)->nullable(); // '20232024'
            $table->integer('training_duration_seconds')->nullable();

            // Performance metrics
            $table->decimal('accuracy', 5, 4)->nullable();
            $table->decimal('precision', 5, 4)->nullable();
            $table->decimal('recall', 5, 4)->nullable();
            $table->decimal('f1_score', 5, 4)->nullable();
            $table->decimal('auc_roc', 5, 4)->nullable();
            $table->decimal('log_loss', 8, 6)->nullable();
            $table->decimal('brier_score', 5, 4)->nullable();

            // Cross-validation
            $table->integer('cv_folds')->nullable();
            $table->decimal('cv_mean_score', 5, 4)->nullable();
            $table->decimal('cv_std_score', 5, 4)->nullable();

            // Feature engineering
            $table->json('features_used')->nullable(); // Liste des features
            $table->integer('features_count')->nullable();
            $table->json('feature_importance')->nullable();

            // File paths
            $table->string('model_file_path')->nullable(); // storage/ml_models/model.pkl
            $table->string('scaler_file_path')->nullable();
            $table->string('encoder_file_path')->nullable();

            // Status
            $table->boolean('is_active')->default(false);
            $table->boolean('is_production')->default(false);
            $table->string('status', 20)->default('training'); // 'training', 'testing', 'production', 'deprecated'

            // Metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['model_type', 'is_active']);
            $table->index('is_production');
            $table->unique(['name', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_models');
    }
};

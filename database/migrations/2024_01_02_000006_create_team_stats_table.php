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
        Schema::create('team_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();

            // Statistiques de base
            $table->integer('games_played')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('ot_losses')->default(0);
            $table->integer('points')->default(0);
            $table->decimal('points_percentage', 5, 3)->nullable();

            // Goals
            $table->integer('goals_for')->default(0);
            $table->integer('goals_against')->default(0);
            $table->decimal('goals_per_game', 5, 2)->nullable();
            $table->decimal('goals_against_per_game', 5, 2)->nullable();

            // Shots
            $table->integer('shots_for')->default(0);
            $table->integer('shots_against')->default(0);
            $table->decimal('shots_per_game', 5, 2)->nullable();
            $table->decimal('shooting_percentage', 5, 2)->nullable();
            $table->decimal('save_percentage', 5, 3)->nullable();

            // Special Teams
            $table->integer('power_play_goals')->default(0);
            $table->integer('power_play_opportunities')->default(0);
            $table->decimal('power_play_percentage', 5, 2)->nullable();
            $table->integer('penalty_kill_goals_against')->default(0);
            $table->integer('penalty_kill_opportunities')->default(0);
            $table->decimal('penalty_kill_percentage', 5, 2)->nullable();

            // Advanced Stats
            $table->integer('corsi_for')->default(0);
            $table->integer('corsi_against')->default(0);
            $table->decimal('corsi_for_percentage', 5, 2)->nullable();
            $table->integer('fenwick_for')->default(0);
            $table->integer('fenwick_against')->default(0);
            $table->decimal('fenwick_for_percentage', 5, 2)->nullable();
            $table->decimal('expected_goals_for', 8, 2)->nullable();
            $table->decimal('expected_goals_against', 8, 2)->nullable();
            $table->decimal('pdo', 6, 3)->nullable();

            // Faceoffs
            $table->integer('faceoffs_won')->default(0);
            $table->integer('faceoffs_lost')->default(0);
            $table->decimal('faceoff_percentage', 5, 2)->nullable();

            // Penalties
            $table->integer('penalty_minutes')->default(0);

            // Home/Away splits
            $table->string('split_type', 20)->default('overall'); // overall, home, away

            $table->timestamps();

            $table->unique(['team_id', 'season_id', 'split_type']);
            $table->index(['team_id', 'season_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_stats');
    }
};

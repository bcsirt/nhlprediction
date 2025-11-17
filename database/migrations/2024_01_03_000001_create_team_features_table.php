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
        Schema::create('team_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('season_id')->constrained('seasons')->onDelete('cascade');
            $table->date('calculated_at'); // Date de calcul des features

            // Rolling Averages (5 derniers matchs)
            $table->decimal('rolling_goals_for_5', 5, 2)->nullable();
            $table->decimal('rolling_goals_against_5', 5, 2)->nullable();
            $table->decimal('rolling_shots_for_5', 5, 2)->nullable();
            $table->decimal('rolling_shots_against_5', 5, 2)->nullable();
            $table->decimal('rolling_save_percentage_5', 5, 3)->nullable();

            // Rolling Averages (10 derniers matchs)
            $table->decimal('rolling_goals_for_10', 5, 2)->nullable();
            $table->decimal('rolling_goals_against_10', 5, 2)->nullable();
            $table->decimal('rolling_shots_for_10', 5, 2)->nullable();
            $table->decimal('rolling_shots_against_10', 5, 2)->nullable();

            // Advanced Stats (moyennes)
            $table->decimal('avg_corsi_for', 5, 2)->nullable();
            $table->decimal('avg_corsi_against', 5, 2)->nullable();
            $table->decimal('avg_fenwick_for', 5, 2)->nullable();
            $table->decimal('avg_expected_goals_for', 5, 2)->nullable();
            $table->decimal('avg_expected_goals_against', 5, 2)->nullable();

            // Form & Momentum
            $table->integer('wins_last_5')->default(0);
            $table->integer('wins_last_10')->default(0);
            $table->integer('current_streak')->default(0); // Positif = victoires, négatif = défaites
            $table->string('streak_type', 10)->nullable(); // 'W' ou 'L'
            $table->decimal('points_percentage', 5, 2)->nullable();

            // Home/Away splits
            $table->decimal('home_win_percentage', 5, 2)->nullable();
            $table->decimal('away_win_percentage', 5, 2)->nullable();
            $table->decimal('home_goals_avg', 5, 2)->nullable();
            $table->decimal('away_goals_avg', 5, 2)->nullable();

            // Special Teams
            $table->decimal('power_play_percentage', 5, 2)->nullable();
            $table->decimal('penalty_kill_percentage', 5, 2)->nullable();

            // Rest Days
            $table->integer('days_since_last_game')->nullable();
            $table->boolean('is_back_to_back')->default(false);

            // Injuries Impact (à calculer manuellement ou via scraping)
            $table->integer('key_players_injured')->default(0);
            $table->decimal('injury_impact_score', 5, 2)->nullable();

            $table->timestamps();

            // Index pour recherches rapides
            $table->index(['team_id', 'season_id', 'calculated_at']);
            $table->unique(['team_id', 'season_id', 'calculated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_features');
    }
};

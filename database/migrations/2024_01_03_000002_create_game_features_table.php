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
        Schema::create('game_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->onDelete('cascade');
            $table->foreignId('home_team_id')->constrained('teams');
            $table->foreignId('away_team_id')->constrained('teams');

            // Features relatives (différences entre équipes)
            $table->decimal('goal_diff_advantage', 5, 2)->nullable(); // Différence moyenne buts
            $table->decimal('shot_diff_advantage', 5, 2)->nullable();
            $table->decimal('corsi_diff_advantage', 5, 2)->nullable();
            $table->decimal('expected_goals_diff', 5, 2)->nullable();

            // Form comparison
            $table->decimal('form_diff_5', 5, 2)->nullable(); // Différence forme 5 matchs
            $table->decimal('form_diff_10', 5, 2)->nullable();
            $table->decimal('streak_advantage', 5, 2)->nullable();

            // Special teams advantage
            $table->decimal('pp_advantage', 5, 2)->nullable(); // Power play
            $table->decimal('pk_advantage', 5, 2)->nullable(); // Penalty kill

            // Rest advantage
            $table->integer('rest_advantage')->nullable(); // Jours repos domicile - extérieur
            $table->boolean('home_back_to_back')->default(false);
            $table->boolean('away_back_to_back')->default(false);

            // Home ice advantage
            $table->decimal('home_ice_factor', 5, 3)->nullable(); // Calculé depuis historique

            // Head to head
            $table->integer('h2h_wins_home')->default(0); // Victoires domicile vs visiteur
            $table->integer('h2h_wins_away')->default(0);
            $table->integer('h2h_games_count')->default(0);
            $table->decimal('h2h_avg_total_goals', 5, 2)->nullable();

            // Momentum indicators
            $table->decimal('home_momentum_score', 5, 2)->nullable();
            $table->decimal('away_momentum_score', 5, 2)->nullable();

            // Injury impact differential
            $table->decimal('injury_impact_diff', 5, 2)->nullable();

            // Calculated composite scores
            $table->decimal('offense_matchup_score', 5, 2)->nullable();
            $table->decimal('defense_matchup_score', 5, 2)->nullable();
            $table->decimal('goaltending_matchup_score', 5, 2)->nullable();
            $table->decimal('overall_matchup_score', 5, 2)->nullable();

            $table->timestamps();

            // Index pour recherches rapides
            $table->unique('game_id');
            $table->index(['home_team_id', 'away_team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_features');
    }
};

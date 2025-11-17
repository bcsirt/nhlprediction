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
        Schema::create('player_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();

            // Statistiques de base
            $table->integer('games_played')->default(0);
            $table->integer('goals')->default(0);
            $table->integer('assists')->default(0);
            $table->integer('points')->default(0);
            $table->integer('plus_minus')->default(0);
            $table->integer('penalty_minutes')->default(0);

            // Shots & Shooting
            $table->integer('shots')->default(0);
            $table->decimal('shooting_percentage', 5, 2)->nullable();
            $table->integer('game_winning_goals')->default(0);
            $table->integer('overtime_goals')->default(0);

            // Special Teams
            $table->integer('power_play_goals')->default(0);
            $table->integer('power_play_points')->default(0);
            $table->integer('short_handed_goals')->default(0);
            $table->integer('short_handed_points')->default(0);

            // Time on Ice
            $table->integer('time_on_ice_seconds')->default(0); // En secondes
            $table->decimal('time_on_ice_per_game', 6, 2)->nullable(); // Minutes par match
            $table->decimal('even_strength_toi', 6, 2)->nullable();
            $table->decimal('power_play_toi', 6, 2)->nullable();
            $table->decimal('short_handed_toi', 6, 2)->nullable();

            // Advanced Stats
            $table->integer('blocked_shots')->default(0);
            $table->integer('hits')->default(0);
            $table->integer('faceoffs_won')->default(0);
            $table->integer('faceoffs_lost')->default(0);
            $table->decimal('faceoff_percentage', 5, 2)->nullable();
            $table->integer('takeaways')->default(0);
            $table->integer('giveaways')->default(0);

            $table->timestamps();

            $table->unique(['player_id', 'team_id', 'season_id']);
            $table->index(['player_id', 'season_id']);
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_stats');
    }
};

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
        Schema::create('goalie_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();

            // Statistiques de base
            $table->integer('games_played')->default(0);
            $table->integer('games_started')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('ot_losses')->default(0);
            $table->integer('shutouts')->default(0);

            // Saves & Goals
            $table->integer('shots_against')->default(0);
            $table->integer('saves')->default(0);
            $table->integer('goals_against')->default(0);
            $table->decimal('save_percentage', 5, 3)->nullable();
            $table->decimal('goals_against_average', 5, 2)->nullable();

            // Quality Starts
            $table->integer('quality_starts')->default(0);
            $table->decimal('quality_start_percentage', 5, 2)->nullable();

            // Advanced Stats
            $table->decimal('goals_saved_above_average', 6, 2)->nullable();
            $table->integer('high_danger_saves')->default(0);
            $table->integer('high_danger_shots_against')->default(0);
            $table->decimal('high_danger_save_percentage', 5, 3)->nullable();

            // Time
            $table->integer('time_on_ice_seconds')->default(0);
            $table->decimal('time_on_ice_per_game', 6, 2)->nullable();

            // Special Situations
            $table->integer('even_strength_saves')->default(0);
            $table->integer('even_strength_shots_against')->default(0);
            $table->decimal('even_strength_save_percentage', 5, 3)->nullable();
            $table->integer('power_play_saves')->default(0);
            $table->integer('power_play_shots_against')->default(0);
            $table->decimal('power_play_save_percentage', 5, 3)->nullable();
            $table->integer('short_handed_saves')->default(0);
            $table->integer('short_handed_shots_against')->default(0);
            $table->decimal('short_handed_save_percentage', 5, 3)->nullable();

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
        Schema::dropIfExists('goalie_stats');
    }
};

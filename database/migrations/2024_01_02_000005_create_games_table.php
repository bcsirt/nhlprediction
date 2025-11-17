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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->integer('nhl_id')->unique();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->string('game_type', 10); // Regular, Playoff, Preseason
            $table->dateTime('game_date');
            $table->string('venue')->nullable();

            // Équipes
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();

            // Scores
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();

            // État du match
            $table->string('status', 20)->default('scheduled'); // scheduled, live, final, postponed
            $table->string('period')->nullable(); // 1st, 2nd, 3rd, OT, SO
            $table->time('time_remaining')->nullable();

            // Résultat
            $table->foreignId('winning_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->boolean('overtime')->default(false);
            $table->boolean('shootout')->default(false);

            // Données brutes NHL API
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->index('nhl_id');
            $table->index('season_id');
            $table->index('game_date');
            $table->index('status');
            $table->index(['home_team_id', 'away_team_id']);
            $table->index(['game_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};

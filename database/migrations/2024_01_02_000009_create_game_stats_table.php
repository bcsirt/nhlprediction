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
        Schema::create('game_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->boolean('is_home')->default(true);

            // Shots
            $table->integer('shots')->default(0);
            $table->integer('blocked_shots')->default(0);
            $table->integer('missed_shots')->default(0);

            // Goals
            $table->integer('goals')->default(0);
            $table->integer('power_play_goals')->default(0);
            $table->integer('short_handed_goals')->default(0);

            // Saves
            $table->integer('saves')->default(0);
            $table->decimal('save_percentage', 5, 3)->nullable();

            // Penalties
            $table->integer('penalties')->default(0);
            $table->integer('penalty_minutes')->default(0);

            // Faceoffs
            $table->integer('faceoffs_won')->default(0);
            $table->integer('faceoffs_lost')->default(0);
            $table->decimal('faceoff_percentage', 5, 2)->nullable();

            // Hits & Blocks
            $table->integer('hits')->default(0);
            $table->integer('blocked_shots_against')->default(0);

            // Giveaways & Takeaways
            $table->integer('giveaways')->default(0);
            $table->integer('takeaways')->default(0);

            // Advanced Stats
            $table->integer('corsi_for')->default(0);
            $table->integer('corsi_against')->default(0);
            $table->decimal('corsi_for_percentage', 5, 2)->nullable();
            $table->integer('fenwick_for')->default(0);
            $table->integer('fenwick_against')->default(0);
            $table->decimal('fenwick_for_percentage', 5, 2)->nullable();
            $table->decimal('expected_goals', 6, 2)->nullable();

            // Power Play / Penalty Kill
            $table->integer('power_play_opportunities')->default(0);
            $table->decimal('power_play_percentage', 5, 2)->nullable();
            $table->integer('penalty_kill_opportunities')->default(0);
            $table->decimal('penalty_kill_percentage', 5, 2)->nullable();

            // Données brutes
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->unique(['game_id', 'team_id']);
            $table->index('game_id');
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_stats');
    }
};

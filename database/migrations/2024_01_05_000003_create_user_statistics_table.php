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
        Schema::create('user_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Période
            $table->string('period_type'); // daily, weekly, monthly, all_time
            $table->date('period_start');
            $table->date('period_end');

            // Statistiques de paris
            $table->integer('total_bets')->default(0);
            $table->integer('winning_bets')->default(0);
            $table->integer('losing_bets')->default(0);
            $table->decimal('win_rate', 6, 4)->default(0);

            // Financier
            $table->decimal('total_staked', 14, 2)->default(0);
            $table->decimal('total_profit', 14, 2)->default(0);
            $table->decimal('roi_percentage', 8, 4)->default(0);
            $table->decimal('average_odds', 6, 3)->default(0);
            $table->decimal('average_stake', 10, 2)->default(0);

            // Par type de pari
            $table->json('stats_by_bet_type')->nullable();

            // Par niveau de confiance
            $table->json('stats_by_confidence')->nullable();

            // Par équipe
            $table->json('stats_by_team')->nullable();

            // Séries
            $table->integer('best_streak')->default(0);
            $table->integer('worst_streak')->default(0);
            $table->decimal('max_drawdown', 8, 2)->default(0);

            // Prédictions suivies
            $table->integer('predictions_viewed')->default(0);
            $table->integer('value_bets_found')->default(0);
            $table->integer('value_bets_taken')->default(0);

            $table->timestamps();

            // Index
            $table->unique(['user_id', 'period_type', 'period_start']);
            $table->index(['user_id', 'period_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_statistics');
    }
};

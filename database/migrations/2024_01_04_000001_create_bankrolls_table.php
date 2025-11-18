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
        Schema::create('bankrolls', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('initial_amount', 12, 2);
            $table->decimal('current_amount', 12, 2);
            $table->string('currency', 3)->default('USD');

            // Stratégie de mise
            $table->string('betting_strategy')->default('kelly'); // kelly, flat, percentage
            $table->decimal('kelly_fraction', 4, 2)->default(0.25); // Fraction de Kelly (0.25 = 25%)
            $table->decimal('max_bet_percentage', 5, 2)->default(5.00); // Max 5% du bankroll par pari
            $table->decimal('min_bet_amount', 10, 2)->default(10.00);
            $table->decimal('max_bet_amount', 10, 2)->nullable();

            // Statistiques
            $table->integer('total_bets')->default(0);
            $table->integer('winning_bets')->default(0);
            $table->integer('losing_bets')->default(0);
            $table->integer('pending_bets')->default(0);
            $table->decimal('total_wagered', 14, 2)->default(0);
            $table->decimal('total_profit', 14, 2)->default(0);
            $table->decimal('roi_percentage', 8, 4)->default(0);

            // Risque
            $table->decimal('max_drawdown', 8, 2)->default(0);
            $table->decimal('current_drawdown', 8, 2)->default(0);
            $table->decimal('peak_amount', 12, 2);

            // Séries
            $table->integer('current_streak')->default(0); // + pour wins, - pour losses
            $table->integer('best_streak')->default(0);
            $table->integer('worst_streak')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bankrolls');
    }
};

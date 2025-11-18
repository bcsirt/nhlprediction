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
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bankroll_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('prediction_id')->nullable()->constrained()->onDelete('set null');

            // Type de pari
            $table->string('bet_type'); // moneyline, spread, over_under, prop
            $table->string('selection'); // home, away, over, under
            $table->text('description')->nullable();

            // Cotes et montants
            $table->decimal('odds_decimal', 8, 3); // Cotes décimales
            $table->decimal('odds_american', 8, 2)->nullable(); // Cotes américaines
            $table->decimal('stake', 10, 2); // Mise
            $table->decimal('potential_payout', 12, 2); // Gain potentiel
            $table->decimal('actual_payout', 12, 2)->nullable(); // Gain réel

            // Probabilités et valeur
            $table->decimal('implied_probability', 6, 4); // Probabilité implicite des cotes
            $table->decimal('estimated_probability', 6, 4); // Notre probabilité estimée
            $table->decimal('expected_value', 10, 4); // EV = (prob * payout) - stake
            $table->decimal('edge_percentage', 8, 4); // Avantage en %

            // Kelly Criterion
            $table->decimal('kelly_fraction', 6, 4)->nullable(); // Fraction Kelly calculée
            $table->decimal('kelly_stake', 10, 2)->nullable(); // Mise Kelly recommandée

            // Confiance
            $table->decimal('confidence_score', 5, 2);
            $table->string('confidence_level'); // very_low, low, medium, high, very_high

            // Lignes (pour spread/over_under)
            $table->decimal('line', 6, 2)->nullable(); // Ex: -1.5 pour spread

            // Bookmaker
            $table->string('bookmaker')->nullable();
            $table->string('bet_slip_id')->nullable(); // ID externe

            // Statut
            $table->string('status')->default('pending'); // pending, won, lost, push, cancelled
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('settled_at')->nullable();

            // Résultat
            $table->decimal('profit_loss', 12, 2)->nullable();
            $table->boolean('is_correct')->nullable();

            // Métadonnées
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Index
            $table->index(['bankroll_id', 'status']);
            $table->index(['game_id', 'bet_type']);
            $table->index('placed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};

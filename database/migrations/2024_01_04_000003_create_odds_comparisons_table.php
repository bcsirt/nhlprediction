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
        Schema::create('odds_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');

            // Type de marché
            $table->string('market_type'); // moneyline, spread, total
            $table->string('selection'); // home, away, over, under
            $table->decimal('line', 6, 2)->nullable(); // Ligne pour spread/total

            // Bookmaker
            $table->string('bookmaker');
            $table->string('bookmaker_url')->nullable();

            // Cotes
            $table->decimal('odds_decimal', 8, 3);
            $table->decimal('odds_american', 8, 2)->nullable();
            $table->decimal('odds_fractional_num', 6, 2)->nullable();
            $table->decimal('odds_fractional_den', 6, 2)->nullable();

            // Probabilité implicite
            $table->decimal('implied_probability', 6, 4);
            $table->decimal('no_vig_probability', 6, 4)->nullable(); // Sans marge
            $table->decimal('vig_percentage', 6, 4)->nullable(); // Marge du bookmaker

            // Comparaison
            $table->boolean('is_best_odds')->default(false);
            $table->decimal('odds_movement', 6, 3)->nullable(); // Mouvement depuis ouverture

            // Timestamps
            $table->timestamp('fetched_at');
            $table->timestamp('odds_updated_at')->nullable();

            $table->timestamps();

            // Index
            $table->index(['game_id', 'market_type', 'selection']);
            $table->index(['bookmaker', 'market_type']);
            $table->index('is_best_odds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odds_comparisons');
    }
};

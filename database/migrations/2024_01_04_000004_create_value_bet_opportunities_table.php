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
        Schema::create('value_bet_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('prediction_id')->nullable()->constrained()->onDelete('set null');

            // Type
            $table->string('bet_type'); // moneyline, spread, over_under
            $table->string('selection'); // home, away, over, under
            $table->decimal('line', 6, 2)->nullable();

            // Meilleures cotes trouvées
            $table->string('best_bookmaker');
            $table->decimal('best_odds', 8, 3);
            $table->decimal('average_odds', 8, 3);

            // Probabilités
            $table->decimal('implied_probability', 6, 4);
            $table->decimal('estimated_probability', 6, 4);

            // Valeur
            $table->decimal('expected_value', 10, 4);
            $table->decimal('edge_percentage', 8, 4);
            $table->decimal('kelly_fraction', 6, 4);

            // Confiance
            $table->decimal('confidence_score', 5, 2);
            $table->string('confidence_level');

            // Recommandation
            $table->decimal('recommended_stake_percentage', 5, 2);
            $table->string('recommendation'); // strong_bet, bet, skip, avoid
            $table->text('reasoning')->nullable();

            // Timing
            $table->boolean('is_live')->default(false);
            $table->timestamp('expires_at');
            $table->boolean('is_actioned')->default(false);
            $table->timestamp('actioned_at')->nullable();

            $table->timestamps();

            // Index
            $table->index(['game_id', 'bet_type']);
            $table->index('edge_percentage');
            $table->index(['is_actioned', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('value_bet_opportunities');
    }
};

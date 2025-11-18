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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Notifications
            $table->boolean('notify_predictions')->default(true);
            $table->boolean('notify_value_bets')->default(true);
            $table->boolean('notify_results')->default(true);
            $table->boolean('notify_bankroll_alerts')->default(true);

            // Canaux de notification
            $table->boolean('email_enabled')->default(true);
            $table->boolean('push_enabled')->default(false);
            $table->boolean('sms_enabled')->default(false);

            // Préférences de prédiction
            $table->decimal('min_confidence_alert', 5, 2)->default(70); // Score minimum pour alertes
            $table->decimal('min_edge_alert', 5, 2)->default(5); // Edge minimum pour alertes
            $table->json('favorite_teams')->nullable(); // IDs des équipes favorites
            $table->json('excluded_teams')->nullable(); // IDs des équipes à ignorer

            // Préférences de paris
            $table->string('default_bet_type')->default('moneyline');
            $table->string('preferred_odds_format')->default('decimal');
            $table->decimal('default_stake_percentage', 5, 2)->default(2);
            $table->json('preferred_bookmakers')->nullable();

            // Affichage
            $table->string('timezone')->default('America/New_York');
            $table->string('language')->default('fr');
            $table->string('theme')->default('light');
            $table->boolean('show_advanced_stats')->default(false);

            // Limites et alertes
            $table->decimal('daily_loss_limit', 10, 2)->nullable();
            $table->decimal('weekly_loss_limit', 10, 2)->nullable();
            $table->decimal('monthly_loss_limit', 10, 2)->nullable();
            $table->decimal('max_drawdown_alert', 5, 2)->default(20);

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};

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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Type et contenu
            $table->string('type'); // prediction, value_bet, result, bankroll_alert, system
            $table->string('title');
            $table->text('message');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();

            // Données associées
            $table->string('notifiable_type')->nullable(); // Game, Prediction, Bet, etc.
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->json('data')->nullable(); // Données supplémentaires

            // Actions
            $table->string('action_url')->nullable();
            $table->string('action_text')->nullable();

            // Canaux envoyés
            $table->boolean('sent_email')->default(false);
            $table->boolean('sent_push')->default(false);
            $table->boolean('sent_sms')->default(false);

            // Statut
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_important')->default(false);

            $table->timestamps();

            // Index
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

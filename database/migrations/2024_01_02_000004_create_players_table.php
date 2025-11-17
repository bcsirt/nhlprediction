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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->integer('nhl_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name');
            $table->foreignId('current_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('position'); // F, D, G
            $table->string('position_code', 5)->nullable(); // C, LW, RW, D, G
            $table->integer('jersey_number')->nullable();

            // Informations personnelles
            $table->date('birth_date')->nullable();
            $table->string('birth_city')->nullable();
            $table->string('birth_country')->nullable();
            $table->string('nationality')->nullable();
            $table->integer('height_cm')->nullable();
            $table->integer('weight_kg')->nullable();
            $table->string('shoots_catches', 1)->nullable(); // L, R

            // Carrière
            $table->integer('draft_year')->nullable();
            $table->integer('draft_round')->nullable();
            $table->integer('draft_pick')->nullable();
            $table->foreignId('draft_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->boolean('rookie')->default(false);

            $table->timestamps();

            $table->index('nhl_id');
            $table->index('current_team_id');
            $table->index(['last_name', 'first_name']);
            $table->index('position');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};

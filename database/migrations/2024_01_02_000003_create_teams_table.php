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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->integer('nhl_id')->unique();
            $table->string('name');
            $table->string('abbreviation', 10);
            $table->string('team_name'); // Ex: "Canadiens"
            $table->string('location_name'); // Ex: "Montréal"
            $table->string('venue')->nullable();
            $table->foreignId('conference_id')->nullable()->constrained('conferences')->nullOnDelete();
            $table->string('division')->nullable();
            $table->string('franchise_id')->nullable();
            $table->boolean('active')->default(true);

            // Informations supplémentaires
            $table->string('website')->nullable();
            $table->string('official_site_url')->nullable();
            $table->integer('first_year_of_play')->nullable();

            $table->timestamps();

            $table->index('nhl_id');
            $table->index('abbreviation');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};

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
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('season_id', 8)->unique(); // Ex: 20232024
            $table->integer('start_year');
            $table->integer('end_year');
            $table->date('regular_season_start_date')->nullable();
            $table->date('regular_season_end_date')->nullable();
            $table->date('playoff_start_date')->nullable();
            $table->date('playoff_end_date')->nullable();
            $table->integer('total_games')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index('season_id');
            $table->index('is_current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};

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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uid');
            $table->unsignedBigInteger('sport_id');
            $table->foreign('sport_id')->references('id')->on('sports')->onDelete('cascade');
            $table->unsignedBigInteger('season_id');
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
            $table->string('api_date');
            $table->timestamp('date')->nullable();
            $table->string('long_status');
            $table->string('short_status');
            $table->unsignedBigInteger('home_team_id')->nullable();
            $table->foreign('home_team_id')->references('id')->on('teams')->onDelete('set null');
            $table->unsignedBigInteger('away_team_id')->nullable();
            $table->foreign('away_team_id')->references('id')->on('teams')->onDelete('set null');
            $table->json('teams')->nullable();
            $table->json('goals')->nullable();
            $table->json('score')->nullable();
            $table->json('venue')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};

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
        Schema::create('publisher_nes_game', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('nes_game_id')->constrained('nes_games')->cascadeOnDelete();
            $table->foreignUuid('publisher_id')->constrained('publishers')->cascadeOnDelete();
            $table->json('regions')->nullable();
            $table->timestamps();

            $table->unique(['nes_game_id', 'publisher_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publisher_nes_game');
    }
};

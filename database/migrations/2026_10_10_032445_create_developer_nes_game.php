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
        Schema::create('developer_nes_game', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('nes_game_id')->constrained('nes_games')->cascadeOnDelete();
            $table->foreignUuid('developer_id')->constrained('developers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['nes_game_id', 'developer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('developer_nes_game');
    }
};

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
        Schema::create('nes_games', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 300)->index();
            $table->string('slug',180)->unique();
            $table->string('image',200)->nullable();
            $table->json('alternate_names')->nullable();
            $table->json('release_dates')->nullable();
            $table->text('description')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nes_games');
    }
};

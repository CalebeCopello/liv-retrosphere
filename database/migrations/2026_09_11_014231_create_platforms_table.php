<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platforms', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name', 250)->unique();
            $table->string('slug', 120)->unique();
            $table->string('short_name', 30)->nullable();

            // home_console, handheld, hybrid, computer, etc.
            $table->string('type', 30)->default('unknown');

            $table->unsignedTinyInteger('generation')->nullable();
            $table->unsignedSmallInteger('initial_release_year')->nullable();

            $table->text('description')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('image', 120)->nullable();

            $table->boolean('is_shown')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platforms');
    }
};
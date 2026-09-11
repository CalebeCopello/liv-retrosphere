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
        Schema::create('platforms', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('name', 250)->unique();
            $table->string('slug', 120)->unique();
            $table->string('short_name', 30)->nullable();

            // home_console, handheld, arcade, computer...
            $table->string('type', 30);

            $table->unsignedTinyInteger('generation')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platforms');
    }
};

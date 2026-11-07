<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['platform_master_system_game', 'platform_id', 'platforms', 'ms_game_platform_uq'],
            ['developer_master_system_game', 'developer_id', 'developers', 'ms_game_developer_uq'],
            ['publisher_master_system_game', 'publisher_id', 'publishers', 'ms_game_publisher_uq'],
        ] as [$tableName, $foreignKey, $foreignTable, $uniqueName]) {
            // A prior attempt may have created the table before MySQL rejected its index name.
            if (! Schema::hasTable($tableName)) {
                Schema::create($tableName, function (Blueprint $table) use ($tableName, $foreignKey, $foreignTable) {
                    $table->uuid('id')->primary();
                    $table->foreignUuid('master_system_game_id')->constrained('master_system_games')->cascadeOnDelete();
                    $table->foreignUuid($foreignKey)->constrained($foreignTable)->cascadeOnDelete();
                    if ($tableName === 'publisher_master_system_game') {
                        $table->json('regions')->nullable();
                    }
                    $table->timestamps();
                });
            }

            if (! Schema::hasIndex($tableName, $uniqueName)) {
                Schema::table($tableName, function (Blueprint $table) use ($foreignKey, $uniqueName) {
                    $table->unique(['master_system_game_id', $foreignKey], $uniqueName);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('publisher_master_system_game');
        Schema::dropIfExists('developer_master_system_game');
        Schema::dropIfExists('platform_master_system_game');
    }
};

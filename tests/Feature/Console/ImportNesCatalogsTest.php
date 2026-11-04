<?php

namespace Tests\Feature\Console;

use App\Models\Developer;
use App\Models\NesGame;
use App\Models\Platform;
use App\Models\Publisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportNesCatalogsTest extends TestCase
{
    use RefreshDatabase;

    private const PLATFORM_ID = '11111111-1111-4111-8111-111111111111';

    private const DEVELOPER_ID = '22222222-2222-4222-8222-222222222222';

    private const OTHER_DEVELOPER_ID = '33333333-3333-4333-8333-333333333333';

    private const PUBLISHER_ID = '44444444-4444-4444-8444-444444444444';

    private const OTHER_PUBLISHER_ID = '55555555-5555-4555-8555-555555555555';

    private const GAME_ID = '66666666-6666-4666-8666-666666666666';

    public function test_nes_developer_and_publisher_imports_create_records_and_dry_runs_do_not_persist(): void
    {
        $developerCatalog = $this->catalogFile([[
            'id' => self::DEVELOPER_ID,
            'name' => 'Test NES Developer',
            'slug' => 'test-nes-developer',
            'source_url' => null,
        ]]);
        $publisherCatalog = $this->catalogFile([[
            'id' => self::PUBLISHER_ID,
            'name' => 'Test NES Publisher',
            'slug' => 'test-nes-publisher',
            'source_url' => null,
        ]]);

        $this->artisan('nes-developers:import', ['path' => $developerCatalog])
            ->assertSuccessful();
        $this->artisan('nes-publishers:import', ['path' => $publisherCatalog])
            ->assertSuccessful();

        $this->assertDatabaseHas('developers', ['id' => self::DEVELOPER_ID, 'slug' => 'test-nes-developer']);
        $this->assertDatabaseHas('publishers', ['id' => self::PUBLISHER_ID, 'slug' => 'test-nes-publisher']);

        $dryRunCatalog = $this->catalogFile([[
            'id' => self::OTHER_DEVELOPER_ID,
            'name' => 'Dry Run Developer',
            'slug' => 'dry-run-developer',
            'source_url' => null,
        ]]);
        $this->artisan('nes-developers:import', ['path' => $dryRunCatalog, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('developers', ['id' => self::OTHER_DEVELOPER_ID]);
    }

    public function test_nes_game_import_syncs_platform_developer_and_publisher_links(): void
    {
        $this->createDependencies();

        $this->artisan('nes-games:import', ['path' => $this->catalogFile([$this->gameRecord()])])
            ->assertSuccessful();

        $this->assertDatabaseHas('nes_games', ['id' => self::GAME_ID, 'slug' => 'test-nes-game']);
        $this->assertDatabaseHas('platform_nes_game', [
            'nes_game_id' => self::GAME_ID,
            'platform_id' => self::PLATFORM_ID,
        ]);
        $this->assertDatabaseHas('developer_nes_game', [
            'nes_game_id' => self::GAME_ID,
            'developer_id' => self::DEVELOPER_ID,
        ]);
        $this->assertDatabaseHas('publisher_nes_game', [
            'nes_game_id' => self::GAME_ID,
            'publisher_id' => self::PUBLISHER_ID,
        ]);

        $publisher = NesGame::findOrFail(self::GAME_ID)->publishers()->firstOrFail();
        $this->assertSame(['JP', 'PAL'], $publisher->pivot->regions);

        $updated = $this->gameRecord();
        $updated['name'] = 'Updated Test NES Game';
        $updated['developer_ids'] = [self::OTHER_DEVELOPER_ID];
        $updated['publisher_ids'] = [self::OTHER_PUBLISHER_ID];
        $updated['publisher_regions'] = [self::OTHER_PUBLISHER_ID => []];

        $this->artisan('nes-games:import', ['path' => $this->catalogFile([$updated])])
            ->assertSuccessful();

        $this->assertDatabaseMissing('developer_nes_game', [
            'nes_game_id' => self::GAME_ID,
            'developer_id' => self::DEVELOPER_ID,
        ]);
        $this->assertDatabaseHas('developer_nes_game', [
            'nes_game_id' => self::GAME_ID,
            'developer_id' => self::OTHER_DEVELOPER_ID,
        ]);
        $this->assertDatabaseMissing('publisher_nes_game', [
            'nes_game_id' => self::GAME_ID,
            'publisher_id' => self::PUBLISHER_ID,
        ]);

        $publisher = NesGame::findOrFail(self::GAME_ID)->publishers()->firstOrFail();
        $this->assertSame(self::OTHER_PUBLISHER_ID, $publisher->getKey());
        $this->assertSame([], $publisher->pivot->regions);
    }

    public function test_nes_game_import_allows_a_game_without_a_verified_developer(): void
    {
        $this->createDependencies();
        $game = $this->gameRecord();
        $game['developer_ids'] = [];

        $this->artisan('nes-games:import', ['path' => $this->catalogFile([$game])])
            ->assertSuccessful();

        $this->assertDatabaseHas('nes_games', ['id' => self::GAME_ID]);
        $this->assertDatabaseMissing('developer_nes_game', ['nes_game_id' => self::GAME_ID]);
    }

    private function createDependencies(): void
    {
        $platform = new Platform;
        $platform->id = self::PLATFORM_ID;
        $platform->fill([
            'name' => 'Test NES Platform',
            'slug' => 'test-nes-platform',
            'type' => 'console',
            'generation' => 3,
            'initial_release_year' => 1983,
        ]);
        $platform->save();

        foreach ([
            [self::DEVELOPER_ID, 'Test NES Developer', 'test-nes-developer'],
            [self::OTHER_DEVELOPER_ID, 'Other NES Developer', 'other-nes-developer'],
        ] as [$id, $name, $slug]) {
            Developer::query()->create(compact('id', 'name', 'slug'));
        }

        foreach ([
            [self::PUBLISHER_ID, 'Test NES Publisher', 'test-nes-publisher'],
            [self::OTHER_PUBLISHER_ID, 'Other NES Publisher', 'other-nes-publisher'],
        ] as [$id, $name, $slug]) {
            Publisher::query()->create(compact('id', 'name', 'slug'));
        }
    }

    /** @return array<string, mixed> */
    private function gameRecord(): array
    {
        return [
            'id' => self::GAME_ID,
            'name' => 'Test NES Game',
            'slug' => 'test-nes-game',
            'image' => 'test-nes-game.png',
            'alternate_names' => [['name' => 'Test Game PAL', 'regions' => ['PAL']]],
            'platform_ids' => [self::PLATFORM_ID],
            'initial_release_year' => 1985,
            'first_release_date' => '1985-10',
            'first_release_regions' => ['JP', 'PAL'],
            'release_dates' => ['JP' => '1985-10', 'PAL' => '1986'],
            'description' => null,
            'source_url' => null,
            'developer_ids' => [self::DEVELOPER_ID],
            'publisher_ids' => [self::PUBLISHER_ID],
            'publisher_regions' => [self::PUBLISHER_ID => ['JP', 'PAL']],
            'data_issues' => [],
        ];
    }

    /** @param array<int, array<string, mixed>> $records */
    private function catalogFile(array $records): string
    {
        $path = tempnam(sys_get_temp_dir(), 'nes-import-');
        $this->assertNotFalse($path);
        file_put_contents($path, json_encode($records, JSON_THROW_ON_ERROR));
        $this->beforeApplicationDestroyed(static fn () => unlink($path));

        return $path;
    }
}

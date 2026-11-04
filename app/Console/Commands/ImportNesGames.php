<?php

namespace App\Console\Commands;

use App\Models\NesGame;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class ImportNesGames extends Command
{
    protected $signature = 'nes-games:import
        {path=database/data/nes-game-catalog.json : Path relative to the project root, or an absolute path}
        {--dry-run : Validate and process the file without keeping changes}';

    protected $description = 'Import NES games and their platform, developer, and publisher relationships from a JSON file';

    public function handle(): int
    {
        try {
            $games = $this->validateGames($this->readJson($this->resolvePath((string) $this->argument('path'))));
            $statistics = $this->importGames($games);

            $this->option('dry-run')
                ? $this->warn('Dry run completed. No changes were saved.')
                : $this->info('NES games imported successfully.');
            $this->table(['Result', 'Total'], [
                ['Created', $statistics['created']],
                ['Updated', $statistics['updated']],
                ['Unchanged', $statistics['unchanged']],
                ['Platform links added', $statistics['platform_links_added']],
                ['Platform links removed', $statistics['platform_links_removed']],
                ['Developer links added', $statistics['developer_links_added']],
                ['Developer links removed', $statistics['developer_links_removed']],
                ['Publisher links added', $statistics['publisher_links_added']],
                ['Publisher links removed', $statistics['publisher_links_removed']],
                ['Publisher links updated', $statistics['publisher_links_updated']],
            ]);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function resolvePath(string $path): string
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }

    /** @return array<int, mixed> */
    private function readJson(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("The JSON file was not found: {$path}");
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("The JSON file could not be read: {$path}");
        }
        $games = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (! str_starts_with(ltrim($contents), '[') || ! is_array($games) || ! array_is_list($games)) {
            throw new RuntimeException('The JSON root must be an array of NES games.');
        }

        return $games;
    }

    /**
     * Validate catalog fields, including metadata that is not yet represented in nes_games.
     *
     * @param  array<int, mixed>  $games
     * @return array<int, array<string, mixed>>
     */
    private function validateGames(array $games): array
    {
        $rules = [
            'games' => ['required', 'array', 'min:1'],
            'games.*' => ['required', 'array:id,name,slug,image,alternate_names,platform_ids,initial_release_year,first_release_date,first_release_regions,release_dates,description,source_url,developer_ids,publisher_ids,publisher_regions,data_issues'],
            'games.*.id' => ['required', 'uuid', 'distinct:ignore_case'],
            // Different regional releases can share a title; the catalog slug is the unique identifier.
            'games.*.name' => ['required', 'string', 'max:300'],
            'games.*.slug' => ['required', 'string', 'max:180', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', 'distinct'],
            'games.*.image' => ['present', 'nullable', 'string', 'max:200', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\.png\z/'],
            'games.*.alternate_names' => ['present', 'nullable', 'array', 'list'],
            'games.*.alternate_names.*' => ['required', 'array:name,regions'],
            'games.*.alternate_names.*.name' => ['required', 'string', 'max:300'],
            'games.*.alternate_names.*.regions' => ['present', 'array', 'list'],
            // The catalog uses both country codes (JP, AU) and regional codes (PAL, AUS, ESP).
            'games.*.alternate_names.*.regions.*' => ['required', 'string', 'regex:/\A[A-Z]{2,3}\z/'],
            'games.*.platform_ids' => ['required', 'array', 'list', 'min:1'],
            'games.*.initial_release_year' => ['required', 'integer', 'between:1970,2100'],
            'games.*.first_release_date' => ['present', 'nullable', 'string', 'regex:/\A\d{4}(?:-\d{2}(?:-\d{2})?)?\z/'],
            'games.*.first_release_regions' => ['required', 'array', 'list', 'min:1'],
            'games.*.first_release_regions.*' => ['required', 'string', 'regex:/\A[A-Z]{2,3}\z/'],
            'games.*.release_dates' => ['present', 'nullable', 'array'],
            'games.*.release_dates.*' => ['nullable', 'string', 'regex:/\A\d{4}(?:-\d{2}(?:-\d{2})?)?\z/'],
            'games.*.description' => ['present', 'nullable', 'string'],
            // Catalog URLs can contain Unicode and Wikipedia fragments that Laravel's URL rule rejects.
            'games.*.source_url' => ['present', 'nullable', 'string', 'max:500', 'regex:/\Ahttps?:\/\/\S+\z/u'],
            // Some catalog entries have no verified developer yet; import the game without a developer link.
            'games.*.developer_ids' => ['present', 'array', 'list'],
            'games.*.publisher_ids' => ['required', 'array', 'list'],
            'games.*.publisher_regions' => ['required', 'array'],
            // An empty list means this publisher is known but its release region is unknown.
            'games.*.publisher_regions.*' => ['present', 'array', 'list'],
            'games.*.publisher_regions.*.*' => ['required', 'string', 'regex:/\A[A-Z]{2,3}\z/'],
            'games.*.data_issues' => ['present', 'array', 'list'],
            'games.*.data_issues.*' => ['string'],
        ];

        foreach (array_keys($games) as $index) {
            $rules["games.{$index}.platform_ids.*"] = ['bail', 'required', 'uuid', 'distinct:ignore_case', Rule::exists('platforms', 'id')->whereNull('deleted_at')];
            $rules["games.{$index}.developer_ids.*"] = ['bail', 'required', 'uuid', 'distinct:ignore_case', Rule::exists('developers', 'id')];
            $rules["games.{$index}.publisher_ids.*"] = ['bail', 'required', 'uuid', 'distinct:ignore_case', Rule::exists('publishers', 'id')];
        }

        $validator = Validator::make(['games' => $games], $rules, [
            'games.*.platform_ids.*.exists' => 'The platform at :attribute is missing or deleted. Run platforms:import first.',
            'games.*.developer_ids.*.exists' => 'The developer at :attribute is missing. Run nes-developers:import first.',
            'games.*.publisher_ids.*.exists' => 'The publisher at :attribute is missing. Run nes-publishers:import first.',
        ]);
        if ($validator->fails()) {
            throw new RuntimeException(implode(PHP_EOL, $validator->errors()->all()));
        }

        $validated = array_map(static function (array $attributes): array {
            $attributes['id'] = strtolower($attributes['id']);
            foreach (['platform_ids', 'developer_ids', 'publisher_ids'] as $field) {
                $attributes[$field] = array_map('strtolower', $attributes[$field]);
            }
            $attributes['publisher_regions'] = array_combine(
                array_map('strtolower', array_keys($attributes['publisher_regions'])),
                array_values($attributes['publisher_regions']),
            );

            return $attributes;
        }, $validator->validated()['games']);

        foreach ($validated as $index => $game) {
            $publisherIds = $game['publisher_ids'];
            $publisherRegionIds = array_keys($game['publisher_regions']);
            sort($publisherIds);
            sort($publisherRegionIds);
            if ($publisherIds !== $publisherRegionIds) {
                throw new RuntimeException("The publisher_regions keys for games.{$index} must exactly match publisher_ids.");
            }
        }

        return $validated;
    }

    /** @param array<int, array<string, mixed>> $games */
    private function importGames(array $games): array
    {
        $statistics = [
            'created' => 0, 'updated' => 0, 'unchanged' => 0,
            'platform_links_added' => 0, 'platform_links_removed' => 0,
            'developer_links_added' => 0, 'developer_links_removed' => 0,
            'publisher_links_added' => 0, 'publisher_links_removed' => 0, 'publisher_links_updated' => 0,
        ];

        DB::beginTransaction();
        try {
            foreach ($games as $attributes) {
                $catalogId = $attributes['id'];
                $platformIds = $attributes['platform_ids'];
                $developerIds = $attributes['developer_ids'];
                $publisherRegions = array_map(static fn (array $regions): array => ['regions' => $regions], $attributes['publisher_regions']);

                // Catalog metadata is validated above. These columns do not exist on nes_games yet.
                unset(
                    $attributes['id'], $attributes['platform_ids'], $attributes['developer_ids'],
                    $attributes['publisher_ids'], $attributes['publisher_regions'],
                    $attributes['initial_release_year'], $attributes['first_release_date'],
                    $attributes['first_release_regions'], $attributes['data_issues'],
                );

                $byId = NesGame::find($catalogId);
                $bySlug = NesGame::where('slug', $attributes['slug'])->first();
                if ($byId !== null && $bySlug !== null && ! $byId->is($bySlug)) {
                    throw new RuntimeException("NES game UUID '{$catalogId}' and slug '{$attributes['slug']}' identify different database rows.");
                }
                $game = $byId ?? $bySlug ?? new NesGame;
                $created = ! $game->exists;
                if ($created) {
                    $game->id = $catalogId;
                }
                $game->fill($attributes);
                $changed = $game->isDirty();
                if ($created || $changed) {
                    $game->save();
                }

                $platformChanges = $game->platforms()->sync($platformIds);
                $developerChanges = $game->developers()->sync($developerIds);
                $publisherChanges = $game->publishers()->sync($publisherRegions);
                $statistics['platform_links_added'] += count($platformChanges['attached']);
                $statistics['platform_links_removed'] += count($platformChanges['detached']);
                $statistics['developer_links_added'] += count($developerChanges['attached']);
                $statistics['developer_links_removed'] += count($developerChanges['detached']);
                $statistics['publisher_links_added'] += count($publisherChanges['attached']);
                $statistics['publisher_links_removed'] += count($publisherChanges['detached']);
                $statistics['publisher_links_updated'] += count($publisherChanges['updated']);

                $linksChanged = $platformChanges['attached'] !== [] || $platformChanges['detached'] !== []
                    || $developerChanges['attached'] !== [] || $developerChanges['detached'] !== []
                    || $publisherChanges['attached'] !== [] || $publisherChanges['detached'] !== [] || $publisherChanges['updated'] !== [];
                $statistics[$created ? 'created' : ($changed || $linksChanged ? 'updated' : 'unchanged')]++;
            }

            $this->option('dry-run') ? DB::rollBack() : DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }

        return $statistics;
    }
}

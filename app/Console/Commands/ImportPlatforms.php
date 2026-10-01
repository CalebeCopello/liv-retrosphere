<?php

namespace App\Console\Commands;

use App\Models\Platform;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class ImportPlatforms extends Command
{
    protected $signature = 'platforms:import
        {path=database/data/platform-catalog.json : Path relative to the project root, or an absolute path}
        {--dry-run : Validate and process the file without keeping changes}';

    protected $description = 'Import platforms from a JSON file';

    public function handle(): int
    {
        try {
            $path = $this->resolvePath(
                (string) $this->argument('path'),
            );

            $platforms = $this->readJson($path);
            $platforms = $this->validatePlatforms($platforms);

            $statistics = $this->importPlatforms($platforms);

            if ($statistics['existing_ids_retained'] > 0) {
                $this->warn(
                    "Kept existing database UUIDs for {$statistics['existing_ids_retained']} platform(s) matched by slug.",
                );
            }

            if ($this->option('dry-run')) {
                $this->warn('Dry run completed. No changes were saved.');
            } else {
                $this->info('Platforms imported successfully.');
            }

            $this->table(
                ['Result', 'Total'],
                [
                    ['Created', $statistics['created']],
                    ['Updated', $statistics['updated']],
                    ['Unchanged', $statistics['unchanged']],
                    ['Manufacturer links added', $statistics['links_added']],
                    ['Manufacturer links removed', $statistics['links_removed']],
                ],
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJson(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException(
                "The JSON file was not found: {$path}",
            );
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(
                "The JSON file could not be read: {$path}",
            );
        }

        $platforms = json_decode(
            $contents,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        if (! str_starts_with(ltrim($contents), '[')
            || ! is_array($platforms)
            || ! array_is_list($platforms)) {
            throw new RuntimeException(
                'The JSON root must be an array of platforms.',
            );
        }

        return $platforms;
    }

    /**
     * @param array<int, array<string, mixed>> $platforms
     *
     * @return array<int, array<string, mixed>>
     */
    private function validatePlatforms(array $platforms): array
    {
        $rules = [
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => [
                'required',
                'array:id,name,slug,image,short_name,type,generation,initial_release_year,description,source_url,manufacturer_ids',
            ],
            'platforms.*.id' => ['required', 'uuid', 'distinct:ignore_case'],
            'platforms.*.name' => ['required', 'string', 'max:250', 'distinct:ignore_case'],
            'platforms.*.slug' => [
                'required',
                'string',
                'max:120',
                'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                'distinct',
            ],
            'platforms.*.image' => [
                'sometimes',
                'nullable',
                'string',
                'max:120',
                'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\.png\z/',
                'distinct',
            ],
            'platforms.*.short_name' => ['present', 'nullable', 'string', 'max:30'],
            'platforms.*.type' => ['required', 'string', 'max:30'],
            'platforms.*.generation' => ['required', 'integer', 'between:1,9'],
            'platforms.*.initial_release_year' => ['required', 'integer', 'between:1970,2100'],
            'platforms.*.description' => ['present', 'nullable', 'string'],
            'platforms.*.source_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'platforms.*.manufacturer_ids' => ['sometimes', 'array', 'list'],
        ];

        foreach (array_keys($platforms) as $index) {
            // Scope distinct to one platform: many platforms can share a manufacturer.
            $rules["platforms.{$index}.manufacturer_ids.*"] = [
                'bail',
                'required',
                'uuid',
                'distinct:ignore_case',
                Rule::exists('manufacturers', 'id'),
            ];
        }

        $validator = Validator::make(
            ['platforms' => $platforms],
            $rules,
            [
                'platforms.*.manufacturer_ids.*.exists' =>
                    'The manufacturer at :attribute is missing or deleted. Run manufacturers:import first.',
            ],
        );

        if ($validator->fails()) {
            throw new RuntimeException(
                implode(PHP_EOL, $validator->errors()->all()),
            );
        }

        return array_map(static function (array $attributes): array {
            $attributes['id'] = strtolower($attributes['id']);

            if (array_key_exists('manufacturer_ids', $attributes)) {
                $attributes['manufacturer_ids'] = array_map('strtolower', $attributes['manufacturer_ids']);
            }

            return $attributes;
        }, $validator->validated()['platforms']);
    }

    /**
     * @param array<int, array<string, mixed>> $platforms
     *
     * @return array{
     *     created: int,
     *     updated: int,
     *     unchanged: int,
     *     links_added: int,
     *     links_removed: int,
     *     existing_ids_retained: int
     * }
     */
    private function importPlatforms(array $platforms): array
    {
        $statistics = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'links_added' => 0,
            'links_removed' => 0,
            'existing_ids_retained' => 0,
        ];

        DB::beginTransaction();

        try {
            foreach ($platforms as $attributes) {
                $catalogId = $attributes['id'];
                $manufacturerIds = $attributes['manufacturer_ids'] ?? null;

                // Relationship data is not a platforms column. Never overwrite an existing PK.
                unset($attributes['id'], $attributes['manufacturer_ids']);

                $byId = Platform::withTrashed()->find($catalogId);
                $bySlug = Platform::withTrashed()->where('slug', $attributes['slug'])->first();

                if ($byId !== null && $bySlug !== null && ! $byId->is($bySlug)) {
                    throw new RuntimeException(
                        "Platform UUID '{$catalogId}' and slug '{$attributes['slug']}' identify different database rows.",
                    );
                }

                $platform = $byId ?? $bySlug ?? new Platform;

                $wasCreated = ! $platform->exists;
                $wasDeleted = $platform->exists && $platform->trashed();

                if ($wasCreated) {
                    $platform->id = $catalogId;
                } elseif (strtolower((string) $platform->getKey()) !== $catalogId) {
                    // Older imports generated UUIDs instead of taking them from the JSON.
                    $statistics['existing_ids_retained']++;
                }

                if ($wasDeleted) {
                    $platform->restore();
                }

                $platform->fill($attributes);

                $wasChanged = $platform->isDirty();

                if ($wasCreated || $wasChanged) {
                    $platform->save();
                }

                $linksChanged = false;

                if ($manufacturerIds !== null) {
                    // A provided list is authoritative; [] removes all links for this platform.
                    $changes = $platform->manufacturers()->sync($manufacturerIds);
                    $statistics['links_added'] += count($changes['attached']);
                    $statistics['links_removed'] += count($changes['detached']);
                    $linksChanged = $changes['attached'] !== [] || $changes['detached'] !== [];
                }

                if ($wasCreated) {
                    $statistics['created']++;
                } elseif ($wasDeleted || $wasChanged || $linksChanged) {
                    $statistics['updated']++;
                } else {
                    $statistics['unchanged']++;
                }
            }

            if ($this->option('dry-run')) {
                DB::rollBack();
            } else {
                DB::commit();
            }

            return $statistics;
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }
    }
}
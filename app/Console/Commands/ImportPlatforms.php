<?php

namespace App\Console\Commands;

use App\Models\Platform;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class ImportPlatforms extends Command
{
    protected $signature = 'platforms:import
        {path=database/data/platform-catalog.json : Path relative to the project root}
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

        if (! is_array($platforms) || ! array_is_list($platforms)) {
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
        $validator = Validator::make(
            ['platforms' => $platforms],
            [
                'platforms' => ['required', 'array', 'min:1'],

                'platforms.*.name' => [
                    'required',
                    'string',
                    'max:250',
                    'distinct',
                ],

                'platforms.*.slug' => [
                    'required',
                    'string',
                    'max:120',
                    'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    'distinct',
                ],

                'platforms.*.image' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:150',
                    'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\.png\z/',
                    'distinct',
                ],

                'platforms.*.short_name' => [
                    'present',
                    'nullable',
                    'string',
                    'max:30',
                ],

                'platforms.*.type' => [
                    'required',
                    'string',
                    'max:30',
                ],

                'platforms.*.generation' => [
                    'required',
                    'integer',
                    'between:1,9',
                ],

                'platforms.*.initial_release_year' => [
                    'required',
                    'integer',
                    'between:1970,2100',
                ],

                'platforms.*.description' => [
                    'present',
                    'nullable',
                    'string',
                ],

                'platforms.*.source_url' => [
                    'sometimes',
                    'nullable',
                    'url',
                    'max:500',
                ],
            ],
        );

        if ($validator->fails()) {
            throw new RuntimeException(
                implode(PHP_EOL, $validator->errors()->all()),
            );
        }

        return $validator->validated()['platforms'];
    }

    /**
     * @param array<int, array<string, mixed>> $platforms
     *
     * @return array{
     *     created: int,
     *     updated: int,
     *     unchanged: int
     * }
     */
    private function importPlatforms(array $platforms): array
    {
        $statistics = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
        ];

        DB::beginTransaction();

        try {
            foreach ($platforms as $attributes) {
                $platform = Platform::query()
                    ->withTrashed()
                    ->firstOrNew([
                        'slug' => $attributes['slug'],
                    ]);

                $wasCreated = ! $platform->exists;
                $wasDeleted = $platform->exists && $platform->trashed();

                if ($wasDeleted) {
                    $platform->restore();
                }

                $platform->fill($attributes);

                $wasChanged = $platform->isDirty();

                if ($wasCreated || $wasChanged) {
                    $platform->save();
                }

                if ($wasCreated) {
                    $statistics['created']++;
                } elseif ($wasDeleted || $wasChanged) {
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

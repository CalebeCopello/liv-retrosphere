<?php

namespace App\Console\Commands;

use App\Models\Manufacturer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class ImportManufacturers extends Command
{
    protected $signature = 'manufacturers:import
        {path=database/data/manufacturer-catalog.json : Path relative to the project root, or an absolute path}
        {--dry-run : Validate and process the file without keeping changes}';

    protected $description = 'Import manufacturers from a JSON file';

    public function handle(): int
    {
        try {
            $path = $this->resolvePath((string) $this->argument('path'));
            $manufacturers = $this->validateManufacturers($this->readJson($path));
            $statistics = $this->importManufacturers($manufacturers);

            if ($this->option('dry-run')) {
                $this->warn('Dry run completed. No changes were saved.');
            } else {
                $this->info('Manufacturers imported successfully.');
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
     * @return array<int, mixed>
     */
    private function readJson(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("The JSON file was not found: {$path}");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("The JSON file could not be read: {$path}");
        }

        $manufacturers = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        // Decoding a numeric-keyed JSON object as an array would otherwise pass array_is_list.
        if (! str_starts_with(ltrim($contents), '[')
            || ! is_array($manufacturers)
            || ! array_is_list($manufacturers)) {
            throw new RuntimeException('The JSON root must be an array of manufacturers.');
        }

        return $manufacturers;
    }

    /**
     * @param array<int, mixed> $manufacturers
     * @return array<int, array{id: string, name: string, slug: string}>
     */
    private function validateManufacturers(array $manufacturers): array
    {
        $validator = Validator::make(
            ['manufacturers' => $manufacturers],
            [
                'manufacturers' => ['required', 'array', 'min:1'],
                'manufacturers.*' => ['required', 'array:id,name,slug'],
                'manufacturers.*.id' => ['required', 'uuid', 'distinct:ignore_case'],
                'manufacturers.*.name' => ['required', 'string', 'max:250', 'distinct:ignore_case'],
                'manufacturers.*.slug' => [
                    'required',
                    'string',
                    'max:120',
                    'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                    'distinct',
                ],
            ],
        );

        if ($validator->fails()) {
            throw new RuntimeException(implode(PHP_EOL, $validator->errors()->all()));
        }

        return array_map(static function (array $attributes): array {
            $attributes['id'] = strtolower($attributes['id']);

            return $attributes;
        }, $validator->validated()['manufacturers']);
    }

    /**
     * @param array<int, array{id: string, name: string, slug: string}> $manufacturers
     * @return array{created: int, updated: int, unchanged: int}
     */
    private function importManufacturers(array $manufacturers): array
    {
        $statistics = ['created' => 0, 'updated' => 0, 'unchanged' => 0];

        DB::beginTransaction();

        try {
            foreach ($manufacturers as $attributes) {
                $manufacturer = Manufacturer::withTrashed()->find($attributes['id']);
                $sameSlug = Manufacturer::withTrashed()
                    ->where('slug', $attributes['slug'])
                    ->first();

                // The platform catalog references these exact manufacturer UUIDs.
                if ($sameSlug !== null
                    && ($manufacturer === null || ! $sameSlug->is($manufacturer))) {
                    throw new RuntimeException(
                        "Manufacturer '{$attributes['slug']}' already exists with UUID "
                        ."'{$sameSlug->getKey()}', but the catalog uses '{$attributes['id']}'. "
                        .'Align its UUID in both catalogs with the existing database record.',
                    );
                }

                $manufacturer ??= new Manufacturer;
                $wasCreated = ! $manufacturer->exists;
                $wasDeleted = $manufacturer->exists && $manufacturer->trashed();

                if ($wasCreated) {
                    $manufacturer->id = $attributes['id'];
                }

                if ($wasDeleted) {
                    $manufacturer->restore();
                }

                $manufacturer->fill([
                    'name' => $attributes['name'],
                    'slug' => $attributes['slug'],
                ]);

                $wasChanged = $manufacturer->isDirty();

                if ($wasCreated || $wasChanged) {
                    $manufacturer->save();
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
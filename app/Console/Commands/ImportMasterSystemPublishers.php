<?php

namespace App\Console\Commands;

use App\Models\Publisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class ImportMasterSystemPublishers extends Command
{
    protected $signature = 'master-system-publishers:import
        {path=database/data/master-system-publisher-catalog.json : Path relative to the project root, or an absolute path}
        {--dry-run : Validate and process the file without keeping changes}';

    protected $description = 'Import Master System game publishers from a JSON file';

    public function handle(): int
    {
        try {
            $publishers = $this->validatePublishers($this->readJson($this->resolvePath((string) $this->argument('path'))));
            $statistics = $this->importPublishers($publishers);

            $this->option('dry-run')
                ? $this->warn('Dry run completed. No changes were saved.')
                : $this->info('Publishers imported successfully.');
            $this->table(['Result', 'Total'], [
                ['Created', $statistics['created']],
                ['Updated', $statistics['updated']],
                ['Unchanged', $statistics['unchanged']],
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
        $publishers = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (! str_starts_with(ltrim($contents), '[') || ! is_array($publishers) || ! array_is_list($publishers)) {
            throw new RuntimeException('The JSON root must be an array of publishers.');
        }

        return $publishers;
    }

    /**
     * @param  array<int, mixed>  $publishers
     * @return array<int, array{id: string, name: string, slug: string, source_url: ?string}>
     */
    private function validatePublishers(array $publishers): array
    {
        $validator = Validator::make(['publishers' => $publishers], [
            'publishers' => ['required', 'array', 'min:1'],
            'publishers.*' => ['required', 'array:id,name,slug,source_url'],
            'publishers.*.id' => ['required', 'uuid', 'distinct:ignore_case'],
            'publishers.*.name' => ['required', 'string', 'max:250', 'distinct:ignore_case'],
            'publishers.*.slug' => ['required', 'string', 'max:180', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', 'distinct'],
            // Catalog URLs can contain Unicode and Wikipedia fragments that Laravel's URL rule rejects.
            'publishers.*.source_url' => ['present', 'nullable', 'string', 'max:500', 'regex:/\Ahttps?:\/\/\S+\z/u'],
        ]);
        if ($validator->fails()) {
            throw new RuntimeException(implode(PHP_EOL, $validator->errors()->all()));
        }

        return array_map(static function (array $attributes): array {
            $attributes['id'] = strtolower($attributes['id']);

            return $attributes;
        }, $validator->validated()['publishers']);
    }

    /** @param array<int, array{id: string, name: string, slug: string, source_url: ?string}> $publishers */
    private function importPublishers(array $publishers): array
    {
        $statistics = ['created' => 0, 'updated' => 0, 'unchanged' => 0];
        DB::beginTransaction();
        try {
            foreach ($publishers as $attributes) {
                $catalogId = $attributes['id'];
                unset($attributes['id']);

                $byId = Publisher::find($catalogId);
                $bySlug = Publisher::where('slug', $attributes['slug'])->first();
                if ($byId !== null && $bySlug !== null && ! $byId->is($bySlug)) {
                    throw new RuntimeException("Publisher UUID '{$catalogId}' and slug '{$attributes['slug']}' identify different database rows.");
                }
                $publisher = $byId ?? $bySlug ?? new Publisher;
                $created = ! $publisher->exists;
                if ($created) {
                    $publisher->id = $catalogId;
                }
                $publisher->fill($attributes);
                $changed = $publisher->isDirty();
                if ($created || $changed) {
                    $publisher->save();
                }
                $statistics[$created ? 'created' : ($changed ? 'updated' : 'unchanged')]++;
            }
            if ($this->option('dry-run')) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }

        return $statistics;
    }
}

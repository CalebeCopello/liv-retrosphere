<?php

namespace App\Console\Commands;

use App\Models\Developer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class ImportMasterSystemDevelopers extends Command
{
    protected $signature = 'master-system-developers:import
        {path=database/data/master-system-developer-catalog.json : Path relative to the project root, or an absolute path}
        {--dry-run : Validate and process the file without keeping changes}';

    protected $description = 'Import Master System game developers from a JSON file';

    public function handle(): int
    {
        try {
            $developers = $this->validateDevelopers($this->readJson($this->resolvePath((string) $this->argument('path'))));
            $statistics = $this->importDevelopers($developers);

            $this->option('dry-run')
                ? $this->warn('Dry run completed. No changes were saved.')
                : $this->info('Developers imported successfully.');

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

        $developers = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (! str_starts_with(ltrim($contents), '[') || ! is_array($developers) || ! array_is_list($developers)) {
            throw new RuntimeException('The JSON root must be an array of developers.');
        }

        return $developers;
    }

    /**
     * @param  array<int, mixed>  $developers
     * @return array<int, array{id: string, name: string, slug: string, source_url: ?string}>
     */
    private function validateDevelopers(array $developers): array
    {
        $validator = Validator::make(['developers' => $developers], [
            'developers' => ['required', 'array', 'min:1'],
            'developers.*' => ['required', 'array:id,name,slug,source_url'],
            'developers.*.id' => ['required', 'uuid', 'distinct:ignore_case'],
            'developers.*.name' => ['required', 'string', 'max:250', 'distinct:ignore_case'],
            'developers.*.slug' => ['required', 'string', 'max:180', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', 'distinct'],
            // Catalog URLs can contain Unicode and Wikipedia fragments that Laravel's URL rule rejects.
            'developers.*.source_url' => ['present', 'nullable', 'string', 'max:500', 'regex:/\Ahttps?:\/\/\S+\z/u'],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException(implode(PHP_EOL, $validator->errors()->all()));
        }

        return array_map(static function (array $attributes): array {
            $attributes['id'] = strtolower($attributes['id']);

            return $attributes;
        }, $validator->validated()['developers']);
    }

    /** @param array<int, array{id: string, name: string, slug: string, source_url: ?string}> $developers */
    private function importDevelopers(array $developers): array
    {
        $statistics = ['created' => 0, 'updated' => 0, 'unchanged' => 0];

        DB::beginTransaction();
        try {
            foreach ($developers as $attributes) {
                $catalogId = $attributes['id'];
                unset($attributes['id']);

                $byId = Developer::find($catalogId);
                $bySlug = Developer::where('slug', $attributes['slug'])->first();
                if ($byId !== null && $bySlug !== null && ! $byId->is($bySlug)) {
                    throw new RuntimeException("Developer UUID '{$catalogId}' and slug '{$attributes['slug']}' identify different database rows.");
                }

                $developer = $byId ?? $bySlug ?? new Developer;
                $created = ! $developer->exists;
                if ($created) {
                    $developer->id = $catalogId;
                }
                $developer->fill($attributes);
                $changed = $developer->isDirty();
                if ($created || $changed) {
                    $developer->save();
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

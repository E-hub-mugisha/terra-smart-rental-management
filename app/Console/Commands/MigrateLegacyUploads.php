<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Support\FileUpload;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Moves files saved by the old Storage-based code (storage/app/public and
 * storage/app/private) into the new project folders and rewrites the paths
 * in the database. Safe to run more than once: rows already pointing at the
 * new folders, and files that no longer exist, are skipped.
 */
class MigrateLegacyUploads extends Command
{
    protected $signature = 'uploads:migrate-legacy {--dry-run : Show what would move without changing anything}';

    protected $description = 'Move files from storage/app into the custom upload folders and update database paths.';

    private int $moved = 0;
    private int $missing = 0;

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        // Public images: storage/app/public/... -> public/image/...
        foreach ([
            'property_images' => FileUpload::PROPERTY_IMAGES,
            'building_images' => FileUpload::BUILDING_IMAGES,
            'floor_images' => FileUpload::FLOOR_IMAGES,
            'unit_images' => FileUpload::UNIT_IMAGES,
        ] as $table => $folder) {
            $this->migrateTable($table, 'image_path', false, fn () => $folder, $dry);
        }
        $this->migrateTable('maintenance_requests', 'attachment_path', false, fn () => FileUpload::MAINTENANCE_IMAGES, $dry);

        // Private files: storage/app/private/... -> uploads/...
        $ownerKeys = collect(Document::OWNER_TYPES)->mapWithKeys(fn ($v, $k) => [$v[0] => $k]);
        $this->migrateTable('documents', 'path', true,
            fn ($row) => Document::directoryFor($row->category, $ownerKeys[$row->documentable_type] ?? 'other'), $dry, ['category', 'documentable_type']);
        $this->migrateTable('inspection_media', 'path', true,
            fn ($row) => FileUpload::INSPECTIONS . '/' . $row->inspection_id, $dry, ['inspection_id']);
        $this->migrateTable('message_attachments', 'path', true,
            fn ($row) => FileUpload::MESSAGES . '/' . Carbon::parse($row->created_at ?? now())->format('Y/m'), $dry, ['created_at']);

        $this->info(($dry ? '[dry run] ' : '') . "Moved {$this->moved} file(s); {$this->missing} referenced file(s) were not found and were left unchanged.");

        return self::SUCCESS;
    }

    private function migrateTable(string $table, string $column, bool $private, callable $folderFor, bool $dry, array $extra = []): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        $newPrefixes = $private
            ? ['documents/', FileUpload::INSPECTIONS . '/', FileUpload::MESSAGES . '/']
            : ['image/'];

        DB::table($table)->whereNotNull($column)->where($column, '!=', '')->orderBy('id')
            ->select(array_merge(['id', $column], $extra))
            ->chunkById(200, function ($rows) use ($table, $column, $private, $folderFor, $dry, $newPrefixes) {
                foreach ($rows as $row) {
                    $old = ltrim(str_replace('\\', '/', $row->{$column}), '/');
                    if (collect($newPrefixes)->contains(fn ($p) => str_starts_with($old, $p)) && FileUpload::exists($old, $private)) {
                        continue; // already in the new layout
                    }

                    $source = collect($private
                        ? [storage_path('app/private/' . $old), storage_path('app/' . $old)]
                        : [storage_path('app/public/' . $old), public_path('storage/' . $old)])
                        ->first(fn ($candidate) => is_file($candidate));

                    if (! $source) {
                        $this->missing++;
                        continue;
                    }

                    // Same naming as new uploads: time()_uniqid().extension
                    $extension = strtolower(pathinfo($old, PATHINFO_EXTENSION)) ?: 'bin';
                    $newPath = $folderFor($row) . '/' . time() . '_' . uniqid() . '.' . $extension;

                    if ($dry) {
                        $this->line("{$table}#{$row->id}: {$old} -> {$newPath}");
                        $this->moved++;
                        continue;
                    }

                    $destination = FileUpload::absolutePath($newPath, $private);
                    if (! is_dir(dirname($destination))) {
                        mkdir(dirname($destination), 0755, true);
                    }
                    if (! @rename($source, $destination)) {
                        if (! @copy($source, $destination)) {
                            $this->warn("Could not move {$source}");
                            continue;
                        }
                        @unlink($source);
                    }

                    DB::table($table)->where('id', $row->id)->update([$column => $newPath]);
                    $this->moved++;
                }
            });
    }
}

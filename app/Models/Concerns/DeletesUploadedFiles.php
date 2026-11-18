<?php

namespace App\Models\Concerns;

use App\Support\FileUpload;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Keeps upload folders clean:
 *  - when a record is permanently deleted, its files are deleted;
 *  - when a file column is replaced, the old file is deleted.
 *
 * Soft-deleted records keep their files (they can be restored and are part
 * of the audit trail). Deletion runs after the database transaction commits,
 * so a rolled-back change never loses a file.
 *
 * Models declare: protected array $uploadColumns = ['image_path' => false];
 * (column => true for private files under uploads/, false for public/).
 */
trait DeletesUploadedFiles
{
    public static function bootDeletesUploadedFiles(): void
    {
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive(static::class), true);

        $onRemoved = function ($model) {
            $files = [];
            foreach ($model->uploadColumns() as $column => $private) {
                $files[] = [$model->getRawOriginal($column) ?? $model->getAttribute($column), $private];
            }
            DB::afterCommit(function () use ($files) {
                foreach ($files as [$path, $private]) {
                    FileUpload::delete($path, $private);
                }
            });
        };

        $usesSoftDeletes ? static::forceDeleted($onRemoved) : static::deleted($onRemoved);

        static::updated(function ($model) {
            foreach ($model->uploadColumns() as $column => $private) {
                $old = $model->getOriginal($column);
                if ($model->wasChanged($column) && $old && $old !== $model->getAttribute($column)) {
                    DB::afterCommit(fn () => FileUpload::delete($old, $private));
                }
            }
        });
    }

    /** @return array<string, bool> */
    public function uploadColumns(): array
    {
        return property_exists($this, 'uploadColumns') ? $this->uploadColumns : [];
    }
}

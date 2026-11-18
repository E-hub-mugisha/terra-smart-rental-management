<?php

namespace App\Models;

use App\Models\Concerns\DeletesUploadedFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FloorImage extends Model
{
    use DeletesUploadedFiles;

    /** Uploaded file columns (stored in public/image/floors); true = private. */
    protected array $uploadColumns = ['image_path' => false];

    protected $fillable = [
        'floor_id',
        'image_path',
        'caption',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }
}
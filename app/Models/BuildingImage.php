<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuildingImage extends Model
{
    protected $fillable = [
        'building_id',
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

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}
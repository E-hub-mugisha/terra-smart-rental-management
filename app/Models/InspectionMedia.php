<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionMedia extends Model
{
    /** Inspection evidence is private and served through InspectionController. */
    public const DISK = 'local';

    protected $table = 'inspection_media';

    protected $fillable = [
        'inspection_id', 'inspection_item_id', 'uploaded_by', 'path',
        'original_name', 'mime_type', 'size', 'caption',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InspectionItem::class, 'inspection_item_id');
    }

    public function isVideo(): bool
    {
        return str_starts_with((string) $this->mime_type, 'video/');
    }
}

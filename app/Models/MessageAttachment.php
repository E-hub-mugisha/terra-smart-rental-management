<?php

namespace App\Models;

use App\Models\Concerns\DeletesUploadedFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    use DeletesUploadedFiles;

    /** Uploaded file columns (stored in uploads/messages/{year}/{month}); true = private. */
    protected array $uploadColumns = ['path' => true];

    /** Attachments live on the private disk and are served through MessageController. */

    protected $fillable = [
        'message_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }

        return max(1, (int) round($bytes / 1024)) . ' KB';
    }
}

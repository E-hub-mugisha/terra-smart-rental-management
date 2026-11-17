<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Append-only record of data changes and security events (brief §24). */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'event', 'auditable_type', 'auditable_id', 'old_values', 'new_values',
        'ip_address', 'user_agent', 'url', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function auditable(): MorphTo { return $this->morphTo(); }

    public function getSubjectLabelAttribute(): string
    {
        return $this->auditable_type ? class_basename($this->auditable_type) . ' #' . $this->auditable_id : '—';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inspection extends Model
{
    public const TYPE_MOVE_IN = 'move_in';
    public const TYPE_MOVE_OUT = 'move_out';

    public const STATUSES = [
        'draft' => 'Draft',
        'awaiting_tenant' => 'Awaiting tenant',
        'disputed' => 'Disputed by tenant',
        'completed' => 'Completed',
    ];

    /** Condition scale, worst last. */
    public const CONDITIONS = [
        'excellent' => 'Excellent',
        'good' => 'Good',
        'fair' => 'Fair',
        'poor' => 'Poor',
        'damaged' => 'Damaged',
        'missing' => 'Missing',
        'na' => 'Not applicable',
    ];

    protected $fillable = [
        'inspection_number', 'lease_id', 'unit_id', 'type', 'status', 'baseline_inspection_id',
        'inspection_date', 'conducted_by', 'electricity_reading', 'water_reading', 'keys_handed',
        'general_notes', 'submitted_at', 'tenant_responded_at', 'tenant_comment', 'completed_at',
        'deposit_held', 'deposit_deductions', 'deposit_refund', 'currency', 'deposit_settled_at',
        'deposit_refund_reference', 'settlement_notes',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'electricity_reading' => 'decimal:2',
            'water_reading' => 'decimal:2',
            'keys_handed' => 'integer',
            'submitted_at' => 'datetime',
            'tenant_responded_at' => 'datetime',
            'completed_at' => 'datetime',
            'deposit_held' => 'decimal:2',
            'deposit_deductions' => 'decimal:2',
            'deposit_refund' => 'decimal:2',
            'deposit_settled_at' => 'datetime',
        ];
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    public function baseline(): BelongsTo
    {
        return $this->belongsTo(self::class, 'baseline_inspection_id');
    }

    public function moveOut(): HasOne
    {
        return $this->hasOne(self::class, 'baseline_inspection_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InspectionItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(InspectionMedia::class)->orderBy('id');
    }

    public function isMoveIn(): bool
    {
        return $this->type === self::TYPE_MOVE_IN;
    }

    public function isMoveOut(): bool
    {
        return $this->type === self::TYPE_MOVE_OUT;
    }

    /** Staff may edit while drafting, or after the tenant disputes it. */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'disputed'], true);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->isMoveIn() ? 'Move-in inspection' : 'Move-out inspection';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public static function conditionRank(?string $condition): ?int
    {
        $rank = array_search($condition, ['excellent', 'good', 'fair', 'poor', 'damaged', 'missing'], true);

        return $rank === false ? null : $rank;
    }
}

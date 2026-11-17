<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionItem extends Model
{
    protected $fillable = [
        'inspection_id', 'baseline_item_id', 'area', 'item', 'condition',
        'quantity', 'notes', 'deduction_amount', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'deduction_amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function baseline(): BelongsTo
    {
        return $this->belongsTo(self::class, 'baseline_item_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(InspectionMedia::class);
    }

    public function getConditionLabelAttribute(): ?string
    {
        return $this->condition ? (Inspection::CONDITIONS[$this->condition] ?? $this->condition) : null;
    }

    /**
     * Compared with the move-in baseline: 'worse', 'same', 'better', or null
     * when either side wasn't rated.
     */
    public function changeFromBaseline(): ?string
    {
        $now = Inspection::conditionRank($this->condition);
        $before = Inspection::conditionRank($this->baseline?->condition);

        if ($now === null || $before === null) {
            return null;
        }

        return $now > $before ? 'worse' : ($now < $before ? 'better' : 'same');
    }
}

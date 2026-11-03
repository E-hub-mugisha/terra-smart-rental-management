<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyExpense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'property_id',
        'recorded_by',
        'expense_number',
        'category',
        'title',
        'description',
        'amount',
        'currency',
        'expense_date',
        'payment_method',
        'vendor_name',
        'reference_number',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
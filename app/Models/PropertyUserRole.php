<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Gives an accountant or agent access to a specific property. */
class PropertyUserRole extends Model
{
    public const ROLES = ['accountant' => 'Accountant / finance officer', 'agent' => 'Real estate agent'];

    protected $fillable = ['property_id', 'user_id', 'role', 'commission_rate', 'assigned_by'];

    protected function casts(): array
    {
        return ['commission_rate' => 'decimal:2'];
    }

    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

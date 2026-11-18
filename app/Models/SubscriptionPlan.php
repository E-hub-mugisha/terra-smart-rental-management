<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A Terra subscription package sold to property owners and managers. */
class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'code', 'billing_cycle', 'price', 'currency', 'max_properties', 'max_units', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function contracts(): HasMany { return $this->hasMany(SubscriptionContract::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'tenant_number',
        'full_name',
        'email',
        'phone',
        'alternative_phone',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'status',
        'notes',
        'created_by',
        'country', 'province', 'district',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(TenantApplication::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentPayment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(RentInvoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }
}

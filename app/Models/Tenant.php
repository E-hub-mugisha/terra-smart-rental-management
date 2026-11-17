<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
        'id_type',
        'id_number',
        'occupants',
        'kyc_status',
        'kyc_verified_at',
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
            'occupants' => 'integer',
            'kyc_verified_at' => 'datetime',
        ];
    }

    public const ID_TYPES = [
        'national_id' => 'Rwandan National ID',
        'passport' => 'Passport',
        'refugee_id' => 'Refugee ID',
    ];

    public const KYC_LABELS = [
        'unverified' => 'Not submitted',
        'pending' => 'Awaiting verification',
        'verified' => 'Verified',
        'rejected' => 'Rejected — resubmit',
    ];

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }

    /** Recompute KYC status from the tenant's identity documents. */
    public function refreshKycStatus(): void
    {
        $ids = $this->documents()->whereIn('category', Document::KYC_ID_CATEGORIES)->get();

        $status = match (true) {
            $ids->contains('status', 'verified') => 'verified',
            $ids->contains('status', 'pending') => 'pending',
            $ids->contains('status', 'rejected') => 'rejected',
            default => 'unverified',
        };

        $this->update([
            'kyc_status' => $status,
            'kyc_verified_at' => $status === 'verified' ? ($this->kyc_verified_at ?? now()) : null,
        ]);
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

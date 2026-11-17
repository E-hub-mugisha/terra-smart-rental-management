<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Reusable lease wording with {{placeholders}} (brief §8 "predefined templates and configurable clauses"). */
class LeaseTemplate extends Model
{
    /** Placeholders available in clause text, with a description for the editor. */
    public const PLACEHOLDERS = [
        'landlord_name' => 'Property owner',
        'manager_name' => 'Property manager',
        'tenant_name' => 'Tenant full name',
        'tenant_id_type' => 'Tenant ID type',
        'tenant_id_number' => 'Tenant ID / passport number',
        'tenant_phone' => 'Tenant phone',
        'occupants' => 'Number of occupants',
        'property_name' => 'Property name',
        'property_address' => 'Property address',
        'unit_number' => 'Unit number',
        'lease_number' => 'Lease number',
        'start_date' => 'Start date',
        'end_date' => 'End date',
        'monthly_rent' => 'Monthly rent with currency',
        'deposit_amount' => 'Deposit with currency',
        'rent_due_day' => 'Rent due day of month',
        'payment_frequency' => 'Payment frequency',
        'notice_period_days' => 'Notice period (days)',
        'special_conditions' => 'Special conditions (entered when generating)',
        'today' => "Today's date",
    ];

    protected $fillable = ['name', 'description', 'clauses', 'is_default', 'property_id', 'created_by'];

    protected function casts(): array
    {
        return [
            'clauses' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}

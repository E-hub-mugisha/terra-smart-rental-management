<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A Terra field agent who registers property owners and managers on the ground. */
class FieldStaff extends Model
{
    protected $table = 'field_staff';

    public const STATUSES = ['active' => 'Active', 'suspended' => 'Suspended'];

    protected $fillable = [
        'user_id', 'staff_number', 'phone', 'region', 'district', 'status', 'notes', 'last_active_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['last_active_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    /** Owners this staff member registered (property_owners.created_by holds the user id). */
    public function registeredOwners(): HasMany
    {
        return $this->hasMany(PropertyOwner::class, 'created_by', 'user_id');
    }

    public function registeredManagers(): HasMany
    {
        return $this->hasMany(PropertyManager::class, 'created_by', 'user_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public static function nextNumber(): string
    {
        $next = (int) static::max('id') + 1;
        do {
            $number = 'FS-' . str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
        } while (static::where('staff_number', $number)->exists());

        return $number;
    }
}

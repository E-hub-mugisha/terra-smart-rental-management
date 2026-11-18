<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PropertyOwner extends Model
{
    protected $fillable = [
        'user_id',
        'owner_number',
        'owner_type',
        'name',
        'email',
        'phone',
        'national_id',
        'company_name',
        'country',
        'province',
        'district',
        'address',
        'verification_status',
        'status',
        'notes',
        'created_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Contracts and identity documents filed on the owner's profile. */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A verified technician / company in the Terra service network (brief §4E, §15). */
class ServiceProvider extends Model
{
    public const CATEGORIES = [
        'plumbing' => ['Plumbing', 'bi-droplet'],
        'electrical' => ['Electrical', 'bi-lightning-charge'],
        'painting' => ['Painting', 'bi-palette'],
        'cleaning' => ['Cleaning', 'bi-stars'],
        'construction' => ['Construction', 'bi-bricks'],
        'internet' => ['Internet', 'bi-wifi'],
        'security' => ['Security', 'bi-shield-lock'],
        'landscaping' => ['Landscaping', 'bi-tree'],
        'appliances' => ['Appliance repair', 'bi-tools'],
        'other' => ['Other property services', 'bi-wrench-adjustable'],
    ];

    protected $fillable = [
        'user_id', 'company_name', 'contact_name', 'phone', 'email', 'categories', 'district',
        'status', 'verified_at', 'verified_by', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function jobs(): HasMany { return $this->hasMany(MaintenanceRequest::class); }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function categoryLabels(): array
    {
        return collect($this->categories ?? [])->map(fn ($c) => self::CATEGORIES[$c][0] ?? $c)->all();
    }
}

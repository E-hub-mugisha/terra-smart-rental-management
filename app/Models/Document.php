<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A stored document attached to a property, unit, tenant or lease (brief §6, MVP #14). */
class Document extends Model
{
    use SoftDeletes;

    public const DISK = 'local';

    public const CATEGORIES = [
        'national_id' => 'National ID',
        'passport' => 'Passport',
        'proof_of_income' => 'Proof of income',
        'employment_letter' => 'Employment letter',
        'reference_letter' => 'Reference letter',
        'lease_contract' => 'Signed lease contract',
        'title_deed' => 'Title deed / ownership',
        'insurance' => 'Insurance',
        'permit' => 'Permit / licence',
        'utility' => 'Utility document',
        'other' => 'Other',
    ];

    /** Categories that count as identity documents for KYC. */
    public const KYC_ID_CATEGORIES = ['national_id', 'passport'];

    /** What each owner type may hold: key => [model class, allowed categories]. */
    public const OWNER_TYPES = [
        'property' => [Property::class, ['title_deed', 'insurance', 'permit', 'utility', 'other']],
        'unit' => [Unit::class, ['utility', 'insurance', 'other']],
        'tenant' => [Tenant::class, ['national_id', 'passport', 'proof_of_income', 'employment_letter', 'reference_letter', 'other']],
        'lease' => [Lease::class, ['lease_contract', 'insurance', 'other']],
    ];

    protected $fillable = [
        'documentable_type', 'documentable_id', 'category', 'title', 'path', 'original_name', 'mime_type', 'size',
        'uploaded_by', 'status', 'verified_by', 'verified_at', 'rejection_reason', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'expires_at' => 'date',
        ];
    }

    public function documentable(): MorphTo { return $this->morphTo(); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', $this->category));
    }

    public function getOwnerKeyAttribute(): ?string
    {
        foreach (self::OWNER_TYPES as $key => [$class]) {
            if ($this->documentable_type === $class) {
                return $key;
            }
        }

        return null;
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;

        return $bytes >= 1048576 ? number_format($bytes / 1048576, 1) . ' MB' : max(1, (int) round($bytes / 1024)) . ' KB';
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}

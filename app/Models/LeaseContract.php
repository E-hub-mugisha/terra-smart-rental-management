<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The generated contract text for one lease. Editable while draft, locked once final. */
class LeaseContract extends Model
{
    protected $fillable = ['lease_id', 'lease_template_id', 'clauses', 'variables', 'status', 'generated_by', 'finalized_at'];

    protected function casts(): array
    {
        return [
            'clauses' => 'array',
            'variables' => 'array',
            'finalized_at' => 'datetime',
        ];
    }

    public function lease(): BelongsTo { return $this->belongsTo(Lease::class); }
    public function template(): BelongsTo { return $this->belongsTo(LeaseTemplate::class, 'lease_template_id'); }
    public function generator(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }

    public function isFinal(): bool
    {
        return $this->status === 'final';
    }
}

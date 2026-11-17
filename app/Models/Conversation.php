<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    public const TYPE_DIRECT = 'direct';
    public const TYPE_ANNOUNCEMENT = 'announcement';

    protected $fillable = [
        'type',
        'subject',
        'property_id',
        'unit_id',
        'created_by',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['can_reply', 'last_read_at', 'archived_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isAnnouncement(): bool
    {
        return $this->type === self::TYPE_ANNOUNCEMENT;
    }

    public function participantFor(User $user): ?ConversationParticipant
    {
        return $this->participants->firstWhere('user_id', $user->id)
            ?? $this->participants()->where('user_id', $user->id)->first();
    }

    /** Conversations the user takes part in. */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('participants', fn ($q) => $q->where('user_id', $user->id));
    }

    /** Conversations with messages from others the user has not read. */
    public function scopeUnreadFor(Builder $query, User $user): Builder
    {
        // Unread = a message from someone else newer than the last one this user read.
        return $query->whereHas('participants', function ($p) use ($user) {
            $p->where('user_id', $user->id)
                ->whereNull('archived_at')
                ->whereExists(function ($m) use ($user) {
                    $m->selectRaw('1')
                        ->from('messages')
                        ->whereColumn('messages.conversation_id', 'conversation_participants.conversation_id')
                        ->where('messages.user_id', '!=', $user->id)
                        ->where(fn ($w) => $w
                            ->whereNull('conversation_participants.last_read_message_id')
                            ->orWhereColumn('messages.id', '>', 'conversation_participants.last_read_message_id'));
                });
        });
    }

    /** Title as seen by a given participant. */
    public function titleFor(User $user): string
    {
        if ($this->isAnnouncement()) {
            return $this->subject;
        }

        $others = $this->users->where('id', '!=', $user->id)->pluck('name');

        return $others->isNotEmpty() ? $others->implode(', ') : $this->subject;
    }
}

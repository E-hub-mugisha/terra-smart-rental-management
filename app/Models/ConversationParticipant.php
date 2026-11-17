<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'can_reply',
        'last_read_message_id',
        'last_read_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'can_reply' => 'boolean',
            'last_read_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /** Record that the participant has read everything up to (and including) a message. */
    public function markReadUpTo(?int $messageId): void
    {
        if ($messageId && $messageId > (int) $this->last_read_message_id) {
            $this->update(['last_read_message_id' => $messageId, 'last_read_at' => now()]);
        }
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

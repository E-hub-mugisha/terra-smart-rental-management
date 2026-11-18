<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A logged call between a Terra admin and a subscriber. */
class SubscriberCall extends Model
{
    /** Outcomes an admin can log for a phone or WhatsApp call. */
    public const OUTCOMES = [
        'answered' => 'Answered',
        'no_answer' => 'No answer',
        'busy' => 'Busy',
        'voicemail' => 'Voicemail',
        'callback' => 'Call back requested',
    ];

    /** Every outcome, including system-recorded ones. */
    public const ALL_OUTCOMES = self::OUTCOMES + ['sent' => 'Sent', 'started' => 'Call started'];

    public const CHANNELS = ['phone' => 'Phone call', 'whatsapp' => 'WhatsApp', 'browser' => 'In-platform call', 'email' => 'Email'];

    public const CHANNEL_ICONS = ['phone' => 'bi-telephone', 'whatsapp' => 'bi-whatsapp', 'browser' => 'bi-camera-video', 'email' => 'bi-envelope'];

    protected $fillable = [
        'user_id', 'called_by', 'direction', 'channel', 'phone', 'room', 'outcome', 'duration_seconds', 'subject', 'notes', 'called_at',
    ];

    public function getOutcomeLabelAttribute(): string
    {
        return self::ALL_OUTCOMES[$this->outcome] ?? ucfirst(str_replace('_', ' ', $this->outcome));
    }

    public function getChannelLabelAttribute(): string
    {
        return self::CHANNELS[$this->channel] ?? ucfirst($this->channel);
    }

    /** Address of the in-browser call room (self-hosted Jitsi or meet.jit.si). */
    public function getRoomUrlAttribute(): ?string
    {
        return $this->room ? rtrim((string) config('services.calls.meet_url'), '/') . '/' . $this->room : null;
    }

    protected function casts(): array
    {
        return ['called_at' => 'datetime'];
    }

    public function subscriber(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function caller(): BelongsTo { return $this->belongsTo(User::class, 'called_by'); }

    public function getDurationLabelAttribute(): string
    {
        $s = (int) $this->duration_seconds;

        return $s ? sprintf('%d:%02d', intdiv($s, 60), $s % 60) : '—';
    }
}

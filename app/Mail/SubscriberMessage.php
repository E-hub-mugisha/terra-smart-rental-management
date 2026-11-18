<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A personal email from a Terra administrator to a subscriber. */
class SubscriberMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $subjectLine,
        public string $body,
        public string $senderName,
        public ?string $senderEmail = null,
        public ?string $actionUrl = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
            replyTo: $this->senderEmail ? [new Address($this->senderEmail, $this->senderName)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.subscriber-message');
    }
}

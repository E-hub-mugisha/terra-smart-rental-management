<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnerAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $ownerName,
        public string $ownerNumber,
        public string $email,
        public string $password,
        public string $loginUrl,
        public bool $isReset = false,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isReset
                ? 'Your Terra account credentials have been reset'
                : 'Your Terra property owner account is ready',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.owner-account-created');
    }
}
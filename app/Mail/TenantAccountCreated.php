<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $tenantName,
        public string $tenantNumber,
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
                : 'Your Terra tenant account is ready',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.tenant-account-created');
    }
}

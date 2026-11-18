<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FieldStaffAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $staffName,
        public string $staffNumber,
        public string $email,
        public string $password,
        public string $loginUrl,
        public bool $isReset = false,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isReset ? 'Your Terra field staff password was reset' : 'Your Terra field staff account is ready',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.field-staff-account-created');
    }
}

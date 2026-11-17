<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sign-in code. Deliberately NOT queued: the user is waiting on the login
 * screen, and a stopped queue worker must not lock people out.
 */
class TwoFactorCode extends Notification
{
    use Queueable;

    public function __construct(private string $code, private int $minutes)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Terra sign-in code: ' . $this->code)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Use this code to finish signing in to Terra:')
            ->line('**' . $this->code . '**')
            ->line('It expires in ' . $this->minutes . ' minutes. If you did not try to sign in, change your password straight away.');
    }
}

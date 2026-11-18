<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One notification type for every Terra alert (brief §12).
 *
 * The in-app (database) copy is written immediately; email and SMS are
 * queued so slow providers never hold up a web request. Run a queue worker
 * (`php artisan queue:work`) for those channels to be delivered.
 */
class TerraAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param string      $category  e.g. rent_due, payment, lease, maintenance, application, message, announcement, vacancy
     * @param string      $title     short headline
     * @param string      $message   one or two sentences
     * @param string|null $url       where the alert should take the user
     * @param array       $options   icon, tone (info|success|warning|danger), mail (bool), sms (bool), context (array), action (button label)
     */
    public function __construct(
        public string $category,
        public string $title,
        public string $message,
        public ?string $url = null,
        public array $options = [],
    ) {
    }

    public function via(object $notifiable): array
    {
        // Anonymous (on-demand) recipients: tenants without a login get email only.
        if ($notifiable instanceof \Illuminate\Notifications\AnonymousNotifiable) {
            return ($this->options['mail'] ?? true) ? ['mail'] : [];
        }

        $channels = ['database'];

        if (($this->options['mail'] ?? true) && ($notifiable->notify_email ?? true) && ! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        if (($this->options['sms'] ?? false) && ($notifiable->notify_sms ?? false) && config('services.sms.enabled')) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    /** The in-app copy is written synchronously so the bell updates at once. */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => $this->category,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->options['icon'] ?? self::iconFor($this->category),
            'tone' => $this->options['tone'] ?? 'info',
            'context' => $this->options['context'] ?? [],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title . ' | ' . config('app.name'))
            ->greeting('Hello' . (! empty($notifiable->name) ? ' ' . $notifiable->name : '') . ',')
            ->line($this->message);

        if ($this->url) {
            $mail->action($this->options['action'] ?? 'Open in Terra', $this->url);
        }

        return $mail->line('You can change which emails you receive from the Notifications page in your Terra account.');
    }

    public function toSms(object $notifiable): string
    {
        return mb_substr($this->title . ': ' . $this->message, 0, 300);
    }

    public static function iconFor(string $category): string
    {
        return [
            'rent_due' => 'bi-calendar-event',
            'rent_overdue' => 'bi-exclamation-triangle',
            'invoice' => 'bi-receipt',
            'payment' => 'bi-cash-coin',
            'lease' => 'bi-file-earmark-text',
            'maintenance' => 'bi-tools',
            'application' => 'bi-file-earmark-person',
            'message' => 'bi-chat-dots',
            'announcement' => 'bi-megaphone',
            'vacancy' => 'bi-door-open',
            'inspection' => 'bi-clipboard-check',
            'document' => 'bi-file-earmark-lock',
            'subscription' => 'bi-patch-check',
            'registration' => 'bi-person-plus',
            'call' => 'bi-camera-video',
        ][$category] ?? 'bi-bell';
    }
}

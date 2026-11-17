<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * SMS delivery hook (brief §12: "channels should be designed for SMS").
 *
 * Disabled unless SMS_ENABLED=true. The only driver shipped is "log", which
 * writes the text to the application log; plug a Rwandan SMS gateway in by
 * adding a driver branch below once a provider contract is in place.
 */
class SmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $phone = method_exists($notifiable, 'routeNotificationForSms')
            ? $notifiable->routeNotificationForSms($notification)
            : null;

        if (! $phone || ! method_exists($notification, 'toSms')) {
            return;
        }

        $text = $notification->toSms($notifiable);

        match (config('services.sms.driver', 'log')) {
            default => Log::channel(config('services.sms.log_channel', 'stack'))
                ->info('[SMS] to ' . $phone . ': ' . $text),
        };
    }
}

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('rent-invoices:generate')
    ->dailyAt('00:15')
    ->withoutOverlapping();

// Rent due / overdue and lease expiry alerts (brief §12).
Schedule::command('notifications:send-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping();

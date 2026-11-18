<?php

namespace App\Console\Commands;

use App\Services\SubscriptionBilling;
use Illuminate\Console\Command;

class RunSubscriptionBilling extends Command
{
    protected $signature = 'subscriptions:run';

    protected $description = 'Expire or renew ended subscription contracts, issue due subscription invoices and send reminders.';

    public function handle(SubscriptionBilling $billing): int
    {
        $stats = $billing->runDaily();

        $this->info(sprintf(
            'Subscriptions: %d expired, %d renewed, %d invoices issued, %d reminders sent.',
            $stats['expired'], $stats['renewed'], $stats['invoices'], $stats['reminders'],
        ));

        return self::SUCCESS;
    }
}

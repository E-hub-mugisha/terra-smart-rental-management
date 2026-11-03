<?php

namespace App\Console\Commands;

use App\Services\RentInvoiceGenerator;
use Illuminate\Console\Command;

class GenerateRentInvoices extends Command
{
    protected $signature = 'rent-invoices:generate';

    protected $description = 'Generate missing monthly rent invoices for active leases';

    public function handle(RentInvoiceGenerator $generator): int
    {
        $this->info('Generating missing rent invoices...');

        $result = $generator->generateDueInvoices();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Leases checked', $result['leases_checked']],
                ['Invoices created', $result['created']],
                ['Existing invoices skipped', $result['skipped']],
                ['Errors', $result['errors']],
            ]
        );

        foreach ($result['messages'] as $message) {
            $this->error($message);
        }

        if ($result['errors'] > 0) {
            return self::FAILURE;
        }

        $this->info('Rent invoice generation completed.');

        return self::SUCCESS;
    }
}

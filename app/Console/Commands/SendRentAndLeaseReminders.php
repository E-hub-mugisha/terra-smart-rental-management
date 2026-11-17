<?php

namespace App\Console\Commands;

use App\Models\Lease;
use App\Models\RentInvoice;
use App\Services\Notifier;
use Illuminate\Console\Command;

/**
 * Daily scheduled alerts (brief §12):
 *  - Tenants: rent due soon (3 days), due today, overdue (1, 3, 7, 14, 30 days)
 *  - Staff:   rent overdue (1 and 7 days)
 *  - Both:    lease expiring (60, 30 and 7 days before end date)
 *
 * Each alert is keyed in the cache, so re-running the command on the same
 * day never sends duplicates.
 */
class SendRentAndLeaseReminders extends Command
{
    protected $signature = 'notifications:send-reminders {--dry-run : List what would be sent without sending}';

    protected $description = 'Send rent due/overdue and lease expiry reminders';

    private const DUE_SOON_DAYS = 3;
    private const OVERDUE_TENANT_DAYS = [1, 3, 7, 14, 30];
    private const OVERDUE_STAFF_DAYS = [1, 7];
    private const LEASE_EXPIRY_DAYS = [60, 30, 7];

    public function handle(Notifier $notifier): int
    {
        $today = now()->startOfDay();
        $dry = (bool) $this->option('dry-run');
        $sent = 0;

        $invoices = RentInvoice::query()
            ->where('balance_due', '>', 0)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->whereDate('due_date', '<=', $today->copy()->addDays(self::DUE_SOON_DAYS))
            ->whereDate('due_date', '>=', $today->copy()->subDays(max(self::OVERDUE_TENANT_DAYS)))
            ->with(['tenant.user', 'unit.building.property'])
            ->get();

        foreach ($invoices as $invoice) {
            $diff = (int) $today->diffInDays($invoice->due_date->copy()->startOfDay(), false); // + = days until due

            $jobs = [];
            if ($diff === self::DUE_SOON_DAYS) {
                $jobs[] = ['soon', $diff, fn () => $notifier->rentDue($invoice, 'soon', $diff)];
            } elseif ($diff === 0) {
                $jobs[] = ['today', 0, fn () => $notifier->rentDue($invoice, 'today')];
            } elseif ($diff < 0) {
                $late = -$diff;
                if (in_array($late, self::OVERDUE_TENANT_DAYS, true)) {
                    $jobs[] = ['overdue', $late, fn () => $notifier->rentDue($invoice, 'overdue', $late)];
                }
                if (in_array($late, self::OVERDUE_STAFF_DAYS, true)) {
                    $jobs[] = ['staff-overdue', $late, fn () => $notifier->rentOverdueForStaff($invoice, $late)];
                }
            }

            foreach ($jobs as [$stage, $days, $send]) {
                $this->line(sprintf('  invoice %s → %s (%d)', $invoice->invoice_number, $stage, $days));
                if (! $dry && $notifier->once("invoice:{$invoice->id}:{$stage}:{$days}", 40, $send)) {
                    $sent++;
                }
            }
        }

        foreach (self::LEASE_EXPIRY_DAYS as $days) {
            $leases = Lease::query()
                ->where('status', 'active')
                ->whereDate('end_date', $today->copy()->addDays($days))
                ->with(['tenant.user', 'unit.building.property'])
                ->get();

            foreach ($leases as $lease) {
                $this->line(sprintf('  lease %s → expiring in %d days', $lease->lease_number, $days));
                if (! $dry && $notifier->once("lease:{$lease->id}:expiring:{$days}", 90, fn () => $notifier->leaseExpiring($lease, $days))) {
                    $sent++;
                }
            }
        }

        $this->info($dry ? 'Dry run complete.' : "Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}

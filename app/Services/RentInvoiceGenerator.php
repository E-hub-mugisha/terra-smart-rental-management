<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\RentInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RentInvoiceGenerator
{
    /**
     * Generate any missing rent invoices from each active lease's
     * starting month through the current month, respecting lease dates.
     */
    public function generateDueInvoices(): array
    {
        $today = Carbon::today();

        $leases = Lease::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $today->toDateString())
            ->orderBy('id')
            ->get(['id']);

        $result = [
            'leases_checked' => 0,
            'created' => 0,
            'skipped' => 0,
            'errors' => 0,
            'messages' => [],
        ];

        foreach ($leases as $leaseReference) {
            $result['leases_checked']++;

            try {
                $leaseResult = $this->generateForLease(
                    $leaseReference->id,
                    $today
                );

                $result['created'] += $leaseResult['created'];
                $result['skipped'] += $leaseResult['skipped'];
            } catch (\Throwable $exception) {
                report($exception);

                $result['errors']++;
                $result['messages'][] = "Lease ID {$leaseReference->id}: invoice generation failed.";
            }
        }

        return $result;
    }

    private function generateForLease(int $leaseId, Carbon $today): array
    {
        return DB::transaction(function () use ($leaseId, $today) {
            // Serialize generation for this lease to prevent duplicate
            // invoices when the admin action and scheduler run together.
            $lease = Lease::query()
                ->whereKey($leaseId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lease->status !== 'active') {
                return ['created' => 0, 'skipped' => 0];
            }

            $leaseStart = Carbon::parse($lease->start_date)->startOfDay();
            $leaseEnd = Carbon::parse($lease->end_date)->startOfDay();

            if ($leaseStart->gt($today) || $leaseEnd->lt($leaseStart)) {
                return ['created' => 0, 'skipped' => 0];
            }

            $lastMonth = $today->copy()->startOfMonth();

            if ($leaseEnd->lt($lastMonth)) {
                $lastMonth = $leaseEnd->copy()->startOfMonth();
            }

            $month = $leaseStart->copy()->startOfMonth();

            $created = 0;
            $skipped = 0;

            while ($month->lte($lastMonth)) {
                $calendarMonthStart = $month->copy()->startOfMonth();
                $calendarMonthEnd = $month->copy()->endOfMonth();

                // Clip the billing period to the actual lease dates.
                $periodStart = $calendarMonthStart->gt($leaseStart)
                    ? $calendarMonthStart->copy()
                    : $leaseStart->copy();

                $periodEnd = $calendarMonthEnd->lt($leaseEnd)
                    ? $calendarMonthEnd->copy()
                    : $leaseEnd->copy();

                if ($periodStart->lte($periodEnd)) {
                    $existing = RentInvoice::query()
                        ->where('lease_id', $lease->id)
                        ->where('invoice_type', 'rent')
                        ->whereDate('billing_period_start', $periodStart->toDateString())
                        ->whereDate('billing_period_end', $periodEnd->toDateString())
                        ->exists();

                    if ($existing) {
                        $skipped++;
                    } else {
                        $dueDay = max(1, min(31, (int) $lease->rent_due_day));

                        $dueDate = $calendarMonthStart->copy()
                            ->day(min($dueDay, $calendarMonthEnd->day));

                        // A first, partial billing period cannot be due
                        // before the tenant's lease begins.
                        if ($dueDate->lt($periodStart)) {
                            $dueDate = $periodStart->copy();
                        }

                        $amount = round((float) $lease->monthly_rent, 2);

                        if ($amount <= 0) {
                            throw new \RuntimeException(
                                "Lease {$lease->lease_number} has a non-positive monthly rent."
                            );
                        }

                        RentInvoice::create([
                            'invoice_number' => $this->generateInvoiceNumber(),
                            'lease_id' => $lease->id,
                            'tenant_id' => $lease->tenant_id,
                            'unit_id' => $lease->unit_id,
                            'invoice_type' => 'rent',
                            'billing_period_start' => $periodStart->toDateString(),
                            'billing_period_end' => $periodEnd->toDateString(),
                            'issue_date' => $periodStart->toDateString(),
                            'due_date' => $dueDate->toDateString(),
                            'subtotal' => $amount,
                            'tax_amount' => 0,
                            'total_amount' => $amount,
                            'amount_paid' => 0,
                            'balance_due' => $amount,
                            'currency' => strtoupper($lease->currency ?: 'RWF'),
                            'status' => 'unpaid',
                            'description' => sprintf(
                                'Rent for lease %s (%s to %s)',
                                $lease->lease_number,
                                $periodStart->format('d M Y'),
                                $periodEnd->format('d M Y')
                            ),
                            'notes' => null,
                        ]);

                        $created++;
                    }
                }

                $month->addMonthNoOverflow()->startOfMonth();
            }

            return compact('created', 'skipped');
        }, 3);
    }

    private function generateInvoiceNumber(): string
    {
        do {
            $number = 'INV-' . strtoupper(Str::random(8));
        } while (RentInvoice::where('invoice_number', $number)->exists());

        return $number;
    }
}

<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\RentInvoice;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\Payments\InvoiceBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The one lease workflow (brief §8), shared by the admin portal, the
 * owner/manager portal and the tenant portal:
 *
 *   draft ──send──▶ pending_tenant_acceptance ──tenant accepts──▶ pending_activation
 *     ▲                     │                                        │
 *     └──────recall─────────┘          required invoices paid + move-in confirmed
 *                                                                    ▼
 *                                   terminated / expired ◀── active (unit occupied)
 *
 * Every transition locks the lease row and re-checks its state, so double
 * clicks and concurrent staff actions can't skip a step.
 */
class LeaseLifecycle
{
    public const DRAFT = 'draft';
    public const AWAITING_TENANT = 'pending_tenant_acceptance';
    public const AWAITING_ACTIVATION = 'pending_activation';
    public const ACTIVE = 'active';

    /** Statuses that hold a unit for their dates. */
    public const LIVE = [self::DRAFT, self::AWAITING_TENANT, self::AWAITING_ACTIVATION, self::ACTIVE];

    public const LABELS = [
        'draft' => 'Draft',
        'pending_tenant_acceptance' => 'Awaiting tenant acceptance',
        'pending_activation' => 'Awaiting activation',
        'active' => 'Active',
        'expired' => 'Expired',
        'terminated' => 'Terminated',
        'cancelled' => 'Cancelled',
    ];

    /** Staff: lock the terms and ask the tenant to accept. Reserves the unit. */
    public function sendToTenant(Lease $lease): Lease
    {
        return DB::transaction(function () use ($lease) {
            $locked = $this->lock($lease);
            $this->expect($locked, self::DRAFT, 'Only a draft lease can be sent to the tenant.');
            if ($locked->tenant_accepted_at) {
                $this->fail('The tenant has already accepted this lease.');
            }

            $unit = Unit::whereKey($locked->unit_id)->lockForUpdate()->firstOrFail();
            if (! in_array($unit->status, ['available', 'reserved'], true)) {
                $this->fail('Unit ' . $unit->unit_number . ' is ' . str_replace('_', ' ', $unit->status) . ', so it cannot be offered.');
            }
            $this->assertNoOverlap($locked);

            $unit->update(['status' => 'reserved']);
            $locked->update(['status' => self::AWAITING_TENANT]);

            return $locked;
        });
    }

    /** Staff: take an unaccepted lease back to draft to change its terms. */
    public function recall(Lease $lease): Lease
    {
        return DB::transaction(function () use ($lease) {
            $locked = $this->lock($lease);
            $this->expect($locked, self::AWAITING_TENANT, 'Only a lease waiting for the tenant can be recalled.');
            if ($locked->tenant_accepted_at) {
                $this->fail('The tenant has already accepted this lease.');
            }

            $locked->update(['status' => self::DRAFT]);
            $this->releaseUnitIfFree($locked);

            return $locked;
        });
    }

    /**
     * Staff: delete a lease that never took effect. Leases that are active,
     * awaiting activation or have received money are kept for the record.
     */
    public function delete(Lease $lease): void
    {
        DB::transaction(function () use ($lease) {
            $locked = $this->lock($lease);

            if (in_array($locked->status, [self::ACTIVE, self::AWAITING_ACTIVATION], true)) {
                $this->fail('An active or accepted lease cannot be deleted. Terminate it instead.');
            }

            if ($locked->payments()->exists() || $locked->invoices()->where('amount_paid', '>', 0)->exists()) {
                $this->fail('This lease has recorded payments, so it cannot be deleted.');
            }

            $locked->invoices()->get()->each->delete();
            $locked->delete();
            $this->releaseUnitIfFree($locked);
        });
    }

    /** Tenant: accept the offered lease. */
    public function accept(Lease $lease, Tenant $tenant): Lease
    {
        return DB::transaction(function () use ($lease, $tenant) {
            $locked = $this->lock($lease);
            if ((int) $locked->tenant_id !== (int) $tenant->id) {
                abort(404);
            }
            if ($locked->tenant_accepted_at) {
                $this->fail('You have already accepted this lease.');
            }
            $this->expect($locked, self::AWAITING_TENANT, 'This lease is not currently awaiting your acceptance.');

            $locked->update(['tenant_accepted_at' => now(), 'status' => self::AWAITING_ACTIVATION]);

            return $locked;
        });
    }

    /** Staff: create the deposit (if any) and first rent invoices. Safe to repeat. */
    public function generateRequiredInvoices(Lease $lease): Lease
    {
        return DB::transaction(function () use ($lease) {
            $locked = $this->lock($lease);
            $this->expectAccepted($locked, 'The tenant must accept the lease before the first invoices can be generated.');

            if ((float) $locked->deposit_amount > 0) {
                $this->createInvoiceIfMissing($locked, 'deposit', (float) $locked->deposit_amount, 'Security deposit - ' . $locked->lease_number);
            }

            $start = $locked->start_date->copy();
            $end = $start->copy()->addMonthNoOverflow()->subDay();
            if ($end->gt($locked->end_date)) {
                $end = $locked->end_date->copy();
            }
            $this->createInvoiceIfMissing($locked, 'rent', (float) $locked->monthly_rent, 'Initial rent - ' . $locked->lease_number, $start, $end);

            return $locked;
        });
    }

    /** Staff: record that the tenant has moved in (keys handed over). */
    public function confirmMoveIn(Lease $lease): Lease
    {
        return DB::transaction(function () use ($lease) {
            $locked = $this->lock($lease);
            $this->expectAccepted($locked, 'Only a tenant-accepted lease awaiting activation can be confirmed for move-in.');
            $locked->update(['move_in_confirmed_at' => $locked->move_in_confirmed_at ?? now()]);

            return $locked;
        });
    }

    /**
     * Staff: make the lease active. Requires tenant acceptance, a start date
     * that has arrived, confirmed move-in, the deposit and first rent invoices
     * fully paid, and no other active lease on the unit for these dates.
     */
    public function activate(Lease $lease): Lease
    {
        return DB::transaction(function () use ($lease) {
            $locked = $this->lock($lease);
            $this->expectAccepted($locked, 'Only a tenant-accepted lease awaiting activation can be activated.');

            if ($locked->start_date->isFuture()) {
                $this->fail('The lease starts on ' . $locked->start_date->format('d M Y') . '; it can be activated from that date.');
            }
            if (! $locked->move_in_confirmed_at) {
                $this->fail('Confirm move-in before activating the lease.');
            }

            $invoices = RentInvoice::where('lease_id', $locked->id)->whereIn('invoice_type', ['deposit', 'rent'])->lockForUpdate()->get();
            if (((float) $locked->deposit_amount > 0 && ! $invoices->contains('invoice_type', 'deposit')) || ! $invoices->contains('invoice_type', 'rent')) {
                $this->fail('Generate the required deposit and first rent invoices first.');
            }
            foreach ($invoices as $invoice) {
                InvoiceBalance::refresh($invoice);
                if ((float) $invoice->fresh()->balance_due > 0) {
                    $this->fail('Invoice ' . $invoice->invoice_number . ' still has ' . $invoice->currency . ' '
                        . number_format((float) $invoice->fresh()->balance_due, 0) . ' outstanding.');
                }
            }

            $conflict = Lease::where('unit_id', $locked->unit_id)->whereKeyNot($locked->id)->where('status', self::ACTIVE)
                ->whereDate('start_date', '<=', $locked->end_date)->whereDate('end_date', '>=', $locked->start_date)->exists();
            if ($conflict) {
                $this->fail('Another active lease overlaps these dates for this unit.');
            }

            $locked->update(['status' => self::ACTIVE, 'activated_at' => now()]);
            Unit::whereKey($locked->unit_id)->lockForUpdate()->first()?->update(['status' => 'occupied']);

            return $locked;
        });
    }

    /** What each party should do next — used by the lease pages. */
    public function nextStep(Lease $lease): ?string
    {
        return match ($lease->status) {
            self::DRAFT => 'Review the terms, then send the lease to the tenant.',
            self::AWAITING_TENANT => 'Waiting for the tenant to accept in their portal.',
            self::AWAITING_ACTIVATION => match (true) {
                ! RentInvoice::where('lease_id', $lease->id)->where('invoice_type', 'rent')->exists() => 'Generate the deposit and first rent invoices.',
                RentInvoice::where('lease_id', $lease->id)->whereIn('invoice_type', ['deposit', 'rent'])->where('balance_due', '>', 0)->exists() => 'Waiting for the deposit and first rent to be paid.',
                ! $lease->move_in_confirmed_at => 'Confirm move-in when the tenant receives the keys.',
                $lease->start_date?->isFuture() => 'Activate on ' . $lease->start_date->format('d M Y') . '.',
                default => 'Everything is ready — activate the lease.',
            },
            default => null,
        };
    }

    public function assertNoOverlap(Lease $lease, ?string $start = null, ?string $end = null): void
    {
        $start ??= $lease->start_date?->toDateString();
        $end ??= $lease->end_date?->toDateString();

        $overlap = Lease::where('unit_id', $lease->unit_id)
            ->whereKeyNot($lease->id)
            ->whereIn('status', self::LIVE)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();

        if ($overlap) {
            $this->fail('Another lease overlaps these dates for this unit.');
        }
    }

    /* ------------------------------------------------------------------ */

    private function lock(Lease $lease): Lease
    {
        return Lease::whereKey($lease->id)->lockForUpdate()->firstOrFail();
    }

    private function expect(Lease $lease, string $status, string $message): void
    {
        if ($lease->status !== $status) {
            $this->fail($message);
        }
    }

    private function expectAccepted(Lease $lease, string $message): void
    {
        if ($lease->status !== self::AWAITING_ACTIVATION || ! $lease->tenant_accepted_at) {
            $this->fail($message);
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['lease' => $message]);
    }

    /** Put the unit back on the market unless another live lease still holds it. */
    private function releaseUnitIfFree(Lease $lease): void
    {
        $unit = Unit::whereKey($lease->unit_id)->lockForUpdate()->first();
        $held = Lease::where('unit_id', $lease->unit_id)->whereKeyNot($lease->id)
            ->whereIn('status', [self::AWAITING_TENANT, self::AWAITING_ACTIVATION, self::ACTIVE])->exists();

        if ($unit && $unit->status === 'reserved' && ! $held) {
            $unit->update(['status' => 'available']);
        }
    }

    private function createInvoiceIfMissing(Lease $lease, string $type, float $amount, string $description, $periodStart = null, $periodEnd = null): void
    {
        $exists = RentInvoice::where('lease_id', $lease->id)
            ->where('invoice_type', $type)
            ->when($periodStart, fn ($q) => $q->whereDate('billing_period_start', $periodStart))
            ->when($periodEnd, fn ($q) => $q->whereDate('billing_period_end', $periodEnd))
            ->exists();

        if ($exists) {
            return;
        }

        RentInvoice::create([
            'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'unit_id' => $lease->unit_id,
            'invoice_type' => $type,
            'billing_period_start' => $periodStart ?? $lease->start_date,
            'billing_period_end' => $periodEnd ?? $lease->start_date,
            'issue_date' => now()->toDateString(),
            'due_date' => $lease->start_date,
            'subtotal' => $amount,
            'tax_amount' => 0,
            'total_amount' => $amount,
            'amount_paid' => 0,
            'balance_due' => $amount,
            'currency' => $lease->currency,
            'status' => 'unpaid',
            'description' => $description,
        ]);
    }
}

<?php

namespace App\Services\Payments;

use App\Models\RentInvoice;
use App\Models\RentPaymentAllocation;

/** Recalculate an invoice from its confirmed allocations (same rule as the manager payment screen). */
class InvoiceBalance
{
    public static function refresh(RentInvoice $invoice): void
    {
        $paid = (float) RentPaymentAllocation::query()
            ->where('rent_invoice_id', $invoice->id)
            ->whereHas('payment', fn ($q) => $q->where('status', 'confirmed'))
            ->sum('amount');

        $total = (float) $invoice->total_amount;
        $paid = round(min($paid, $total), 2);
        $balance = round(max(0, $total - $paid), 2);

        $invoice->update([
            'amount_paid' => $paid,
            'balance_due' => $balance,
            'status' => match (true) {
                $balance <= 0 => 'paid',
                $paid > 0 => 'partially_paid',
                // Money was taken back off the invoice: it is open again.
                in_array($invoice->status, ['paid', 'partially_paid'], true) => 'unpaid',
                default => $invoice->status,
            },
        ]);
    }
}

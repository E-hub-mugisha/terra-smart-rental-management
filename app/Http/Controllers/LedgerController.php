<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Services\PropertyAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Automated rent ledger (brief §10): every invoice is a debit, every
 * confirmed payment a credit, with a running balance both the tenant
 * and the landlord can see.
 */
class LedgerController extends Controller
{
    public function __construct(private PropertyAudience $audience)
    {
    }

    public function show(Request $request, Lease $lease)
    {
        $role = $this->authorizeLease($request, $lease);
        $lease->load(['tenant', 'unit.building.property']);
        $entries = $this->entries($lease);

        if ($request->query('format') === 'csv') {
            return $this->csv($lease, $entries);
        }

        return view('ledger.show', [
            'lease' => $lease,
            'entries' => $entries,
            'role' => $role,
            'totals' => [
                'debits' => $entries->sum('debit'),
                'credits' => $entries->sum('credit'),
                'balance' => $entries->last()['balance'] ?? 0,
            ],
            'payable' => $role === 'tenant'
                ? RentInvoice::where('lease_id', $lease->id)->where('balance_due', '>', 0)->whereNotIn('status', ['cancelled', 'void'])->orderBy('due_date')->get()
                : collect(),
        ]);
    }

    public function entries(Lease $lease): Collection
    {
        $invoices = RentInvoice::where('lease_id', $lease->id)
            ->whereNotIn('status', ['cancelled', 'void'])
            ->get()
            ->map(fn ($i) => [
                'date' => $i->issue_date ?? $i->created_at,
                'sort' => 0,
                'description' => ucfirst($i->invoice_type ?: 'rent') . ' invoice ' . $i->invoice_number
                    . ($i->billing_period_start ? ' · ' . $i->billing_period_start->format('M Y') : ''),
                'reference' => $i->invoice_number,
                'debit' => (float) $i->total_amount,
                'credit' => 0.0,
                'kind' => 'invoice',
                'id' => $i->id,
            ]);

        $payments = RentPayment::where('lease_id', $lease->id)
            ->where('status', 'confirmed')
            ->get()
            ->map(fn ($p) => [
                'date' => $p->payment_date ?? $p->created_at,
                'sort' => 1,
                'description' => 'Payment · ' . ucwords(str_replace('_', ' ', (string) $p->payment_method)) . ($p->provider ? ' (' . $p->provider . ')' : ''),
                'reference' => $p->payment_number,
                'debit' => 0.0,
                'credit' => (float) $p->amount,
                'kind' => 'payment',
                'id' => $p->id,
            ]);

        $balance = 0.0;

        return $invoices->concat($payments)
            ->sortBy(fn ($e) => $e['date']->format('Y-m-d') . '-' . $e['sort'] . '-' . str_pad((string) $e['id'], 10, '0', STR_PAD_LEFT))
            ->values()
            ->map(function ($e) use (&$balance) {
                $balance = round($balance + $e['debit'] - $e['credit'], 2);

                return $e + ['balance' => $balance];
            });
    }

    private function csv(Lease $lease, Collection $entries)
    {
        $filename = 'ledger-' . $lease->lease_number . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($entries, $lease) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Description', 'Reference', 'Debit', 'Credit', 'Balance', 'Currency']);
            foreach ($entries as $e) {
                fputcsv($out, [$e['date']->format('Y-m-d'), $e['description'], $e['reference'], $e['debit'] ?: '', $e['credit'] ?: '', $e['balance'], $lease->currency ?: 'RWF']);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function authorizeLease(Request $request, Lease $lease): string
    {
        $user = $request->user();
        if ($this->audience->canViewFinance($user, $this->audience->propertyIdForLease($lease))) {
            return 'staff';
        }
        abort_unless($this->audience->isTenantOf($user, $lease), 404);

        return 'tenant';
    }
}

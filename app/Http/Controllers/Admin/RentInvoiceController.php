<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentInvoice;
use App\Services\RentInvoiceGenerator;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RentInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $query = RentInvoice::query()
            ->with([
                'tenant',
                'lease',
                'unit',
            ]);

        // Search by invoice, tenant or lease.
        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function (Builder $q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function (Builder $tenantQuery) use ($search) {
                        $tenantQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('tenant_number', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('lease', function (Builder $leaseQuery) use ($search) {
                        $leaseQuery->where('lease_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('invoice_type')) {
            $query->where('invoice_type', $request->input('invoice_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->input('overdue') === 'yes') {
            $query->where('balance_due', '>', 0)
                ->whereDate('due_date', '<', $today);
        } elseif ($request->input('overdue') === 'no') {
            $query->where(function (Builder $q) use ($today) {
                $q->where('balance_due', '<=', 0)
                    ->orWhereDate('due_date', '>=', $today);
            });
        }

        if ($request->filled('currency')) {
            $query->where('currency', strtoupper($request->input('currency')));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('issue_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('issue_date', '<=', $request->input('to_date'));
        }

        $invoices = $query
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Dashboard totals are global and do not change with list filters.
        $base = RentInvoice::query();

        $totals = [
            'invoices' => (clone $base)->count(),
            'invoiced' => (float) (clone $base)->sum('total_amount'),
            'collected' => (float) (clone $base)->sum('amount_paid'),
            'outstanding' => (float) (clone $base)->sum('balance_due'),
            'overdue_count' => (clone $base)
                ->where('balance_due', '>', 0)
                ->whereDate('due_date', '<', $today)
                ->count(),
            'overdue_amount' => (float) (clone $base)
                ->where('balance_due', '>', 0)
                ->whereDate('due_date', '<', $today)
                ->sum('balance_due'),
            'unpaid_count' => (clone $base)
                ->where('balance_due', '>', 0)
                ->count(),
            'paid_count' => (clone $base)
                ->where('balance_due', '<=', 0)
                ->count(),
        ];

        return view('admin.rent-invoices.index', compact('invoices', 'totals'));
    }

    public function show(RentInvoice $rentInvoice)
    {
        $rentInvoice->load([
            'tenant',
            'lease',
            'unit',
            'allocations.payment.tenant',
            'allocations.payment.recorder',
        ]);

        // The authoritative paid amount comes from allocations linked
        // to confirmed payments, not merely from a payment record existing.
        $confirmedPaid = (float) $rentInvoice->allocations()
            ->whereHas('payment', function (Builder $query) {
                $query->where('status', 'confirmed');
            })
            ->sum('amount');

        $total = (float) $rentInvoice->total_amount;
        $confirmedPaid = round(min($confirmedPaid, $total), 2);
        $balance = round(max(0, $total - $confirmedPaid), 2);

        $today = Carbon::today();
        $isOverdue = $balance > 0
            && $rentInvoice->due_date !== null
            && $rentInvoice->due_date->lt($today);

        return view('admin.rent-invoices.show', compact(
            'rentInvoice',
            'confirmedPaid',
            'balance',
            'isOverdue'
        ));
    }


    public function generate(Request $request, RentInvoiceGenerator $generator)
    {
        $result = $generator->generateDueInvoices();

        if ($result['errors'] > 0) {
            return redirect()
                ->route('admin.rent-invoices.index')
                ->with('warning', sprintf(
                    'Generation completed with errors. Created: %d, skipped: %d, errors: %d.',
                    $result['created'],
                    $result['skipped'],
                    $result['errors']
                ));
        }

        return redirect()
            ->route('admin.rent-invoices.index')
            ->with('success', sprintf(
                'Invoice generation completed. Created: %d, skipped existing: %d, leases checked: %d.',
                $result['created'],
                $result['skipped'],
                $result['leases_checked']
            ));
    }
}

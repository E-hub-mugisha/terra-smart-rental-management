<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'currency'    => ['nullable', 'in:RWF,USD'],
        ]);

        $startDate = $filters['start_date']
            ?? Carbon::now()->startOfMonth()->toDateString();

        $endDate = $filters['end_date']
            ?? Carbon::today()->toDateString();

        $propertyId = $filters['property_id'] ?? null;
        $currency = $filters['currency'] ?? null;

        $properties = DB::table('properties')
            ->select('id', 'name', 'property_code')
            ->orderBy('name')
            ->get();

        /*
         * Invoiced rent is based on invoice issue dates.
         * Property filtering is done through the unit's property_id.
         */
        $invoiceQuery = DB::table('rent_invoices as ri')
            ->join('units as u', 'u.id', '=', 'ri.unit_id')
            ->whereNull('ri.deleted_at')
            ->whereBetween('ri.issue_date', [$startDate, $endDate])
            ->when($propertyId, fn ($q) =>
                $q->where('u.property_id', $propertyId)
            )
            ->when($currency, fn ($q) =>
                $q->where('ri.currency', $currency)
            );

        $invoicedByCurrency = (clone $invoiceQuery)
            ->select(
                'ri.currency',
                DB::raw('SUM(ri.total_amount) as total')
            )
            ->groupBy('ri.currency')
            ->get()
            ->keyBy('currency');

        /*
         * Collections come only from confirmed payments and their allocations.
         * This prevents pending payments and unallocated amounts being counted.
         */
        $allocationQuery = DB::table('rent_payment_allocations as rpa')
            ->join('rent_payments as rp', 'rp.id', '=', 'rpa.rent_payment_id')
            ->join('rent_invoices as ri', 'ri.id', '=', 'rpa.rent_invoice_id')
            ->join('units as u', 'u.id', '=', 'ri.unit_id')
            ->where('rp.status', 'confirmed')
            ->whereNull('ri.deleted_at')
            ->whereBetween('rp.payment_date', [$startDate, $endDate])
            ->when($propertyId, fn ($q) =>
                $q->where('u.property_id', $propertyId)
            )
            ->when($currency, fn ($q) =>
                $q->where('rp.currency', $currency)
            );

        $collectedByCurrency = (clone $allocationQuery)
            ->select(
                'rp.currency',
                DB::raw('SUM(rpa.amount) as total')
            )
            ->groupBy('rp.currency')
            ->get()
            ->keyBy('currency');

        /*
         * Outstanding and overdue balances are measured as at the end date.
         * These are current balances, not historical snapshots.
         */
        $balanceQuery = DB::table('rent_invoices as ri')
            ->join('units as u', 'u.id', '=', 'ri.unit_id')
            ->whereNull('ri.deleted_at')
            ->whereDate('ri.issue_date', '<=', $endDate)
            ->where('ri.balance_due', '>', 0)
            ->when($propertyId, fn ($q) =>
                $q->where('u.property_id', $propertyId)
            )
            ->when($currency, fn ($q) =>
                $q->where('ri.currency', $currency)
            );

        $outstandingByCurrency = (clone $balanceQuery)
            ->select(
                'ri.currency',
                DB::raw('SUM(ri.balance_due) as total')
            )
            ->groupBy('ri.currency')
            ->get()
            ->keyBy('currency');

        $overdueByCurrency = (clone $balanceQuery)
            ->whereDate('ri.due_date', '<', $endDate)
            ->select(
                'ri.currency',
                DB::raw('SUM(ri.balance_due) as total')
            )
            ->groupBy('ri.currency')
            ->get()
            ->keyBy('currency');

        /*
         * Expenses use the existing property_expenses.property_id column.
         * This assumes property_id references properties.id.
         */
        $expenseQuery = DB::table('property_expenses as pe')
            ->whereBetween('pe.expense_date', [$startDate, $endDate])
            ->when($propertyId, fn ($q) =>
                $q->where('pe.property_id', $propertyId)
            )
            ->when($currency, fn ($q) =>
                $q->where('pe.currency', $currency)
            );

        $expensesByCurrency = (clone $expenseQuery)
            ->select(
                'pe.currency',
                DB::raw('SUM(pe.amount) as total')
            )
            ->groupBy('pe.currency')
            ->get()
            ->keyBy('currency');

        /*
         * Display separate rows for each currency. Never add RWF and USD
         * together as if they were the same currency.
         */
        $currencies = collect(['RWF', 'USD']);

        if ($currency) {
            $currencies = collect([$currency]);
        }

        $financialSummary = $currencies->map(function ($code) use (
            $invoicedByCurrency,
            $collectedByCurrency,
            $outstandingByCurrency,
            $overdueByCurrency,
            $expensesByCurrency
        ) {
            $invoiced = (float) ($invoicedByCurrency[$code]->total ?? 0);
            $collected = (float) ($collectedByCurrency[$code]->total ?? 0);
            $outstanding = (float) ($outstandingByCurrency[$code]->total ?? 0);
            $overdue = (float) ($overdueByCurrency[$code]->total ?? 0);
            $expenses = (float) ($expensesByCurrency[$code]->total ?? 0);

            return [
                'currency' => $code,
                'invoiced' => $invoiced,
                'collected' => $collected,
                'outstanding' => $outstanding,
                'overdue' => $overdue,
                'expenses' => $expenses,
                'net_income' => $collected - $expenses,
                'collection_rate' => $invoiced > 0
                    ? round(($collected / $invoiced) * 100, 2)
                    : 0,
            ];
        });

        // Monthly collections: confirmed allocated amounts only.
        $monthlyCollections = (clone $allocationQuery)
            ->select(
                DB::raw('DATE_FORMAT(rp.payment_date, "%Y-%m") as month'),
                'rp.currency',
                DB::raw('SUM(rpa.amount) as total')
            )
            ->groupBy(
                DB::raw('DATE_FORMAT(rp.payment_date, "%Y-%m")'),
                'rp.currency'
            )
            ->orderBy('month')
            ->get();

        // Outstanding invoice details for the selected reporting period.
        $outstandingInvoices = (clone $balanceQuery)
            ->leftJoin('tenants as t', 't.id', '=', 'ri.tenant_id')
            ->leftJoin('leases as l', 'l.id', '=', 'ri.lease_id')
            ->select(
                'ri.id',
                'ri.invoice_number',
                'ri.invoice_type',
                'ri.issue_date',
                'ri.due_date',
                'ri.total_amount',
                'ri.amount_paid',
                'ri.balance_due',
                'ri.currency',
                't.full_name as tenant_name',
                'l.lease_number',
                'u.unit_number'
            )
            ->orderBy('ri.due_date')
            ->limit(100)
            ->get();

        // Expense breakdown by category.
        $expenseBreakdown = (clone $expenseQuery)
            ->select(
                'pe.category',
                'pe.currency',
                DB::raw('SUM(pe.amount) as total')
            )
            ->groupBy('pe.category', 'pe.currency')
            ->orderByDesc('total')
            ->get();

        return view('admin.financial-reports.index', compact(
            'properties',
            'startDate',
            'endDate',
            'propertyId',
            'currency',
            'financialSummary',
            'monthlyCollections',
            'outstandingInvoices',
            'expenseBreakdown'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'currency'    => ['nullable', 'in:RWF,USD'],
        ]);

        $startDate = $filters['start_date']
            ?? Carbon::now()->startOfMonth()->toDateString();

        $endDate = $filters['end_date']
            ?? Carbon::today()->toDateString();

        $query = DB::table('rent_invoices as ri')
            ->join('units as u', 'u.id', '=', 'ri.unit_id')
            ->leftJoin('tenants as t', 't.id', '=', 'ri.tenant_id')
            ->leftJoin('leases as l', 'l.id', '=', 'ri.lease_id')
            ->whereNull('ri.deleted_at')
            ->whereBetween('ri.issue_date', [$startDate, $endDate])
            ->when($filters['property_id'] ?? null, fn ($q, $id) =>
                $q->where('u.property_id', $id)
            )
            ->when($filters['currency'] ?? null, fn ($q, $code) =>
                $q->where('ri.currency', $code)
            )
            ->select(
                'ri.invoice_number',
                'ri.invoice_type',
                'ri.issue_date',
                'ri.due_date',
                't.full_name as tenant',
                'l.lease_number',
                'u.unit_number',
                'ri.currency',
                'ri.total_amount',
                'ri.amount_paid',
                'ri.balance_due',
                'ri.status'
            )
            ->orderBy('ri.issue_date')
            ->orderBy('ri.invoice_number');

        $filename = 'propertypro-financial-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $file = fopen('php://output', 'w');

            // Helps spreadsheet applications recognize UTF-8.
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'Invoice Number',
                'Type',
                'Issue Date',
                'Due Date',
                'Tenant',
                'Lease Number',
                'Unit',
                'Currency',
                'Invoiced Amount',
                'Amount Paid',
                'Balance Due',
                'Status',
            ]);

            $query->chunk(500, function ($rows) use ($file) {
                foreach ($rows as $row) {
                    fputcsv($file, [
                        $row->invoice_number,
                        $row->invoice_type,
                        $row->issue_date,
                        $row->due_date,
                        $row->tenant,
                        $row->lease_number,
                        $row->unit_number,
                        $row->currency,
                        $row->total_amount,
                        $row->amount_paid,
                        $row->balance_due,
                        $row->status,
                    ]);
                }
            });

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
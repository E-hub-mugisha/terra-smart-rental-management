<?php

namespace App\Http\Controllers;

use App\Models\PropertyExpense;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\Unit;
use App\Services\PropertyAudience;
use Illuminate\Http\Request;

/**
 * Accountant / finance officer workspace (brief §4F): read-only access to
 * rent ledgers, payments, expenses, receipts and financial reports for the
 * properties they are assigned to (admins and property staff can use it too).
 */
class FinanceController extends Controller
{
    public function __construct(private PropertyAudience $audience)
    {
    }

    public function dashboard(Request $request)
    {
        $unitIds = $this->unitIds($request);
        $ids = $this->audience->financePropertyIds($request->user());
        $month = now()->startOfMonth();

        $invoices = RentInvoice::when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds))->whereNotIn('status', ['cancelled', 'void']);
        $payments = RentPayment::when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds))->where('status', 'confirmed');
        $expenses = PropertyExpense::when($ids !== null, fn ($q) => $q->whereIn('property_id', $ids));

        $billedMonth = (float) (clone $invoices)->where('issue_date', '>=', $month)->sum('total_amount');
        $collectedMonth = (float) (clone $payments)->where('payment_date', '>=', $month)->sum('amount');
        $expensesMonth = (float) (clone $expenses)->where('expense_date', '>=', $month)->sum('amount');

        return view('finance.dashboard', [
            'kpis' => [
                'billed' => $billedMonth,
                'collected' => $collectedMonth,
                'rate' => $billedMonth > 0 ? round(min($collectedMonth, $billedMonth) / $billedMonth * 100, 1) : null,
                'outstanding' => (float) (clone $invoices)->sum('balance_due'),
                'overdue' => (float) (clone $invoices)->where('balance_due', '>', 0)->whereDate('due_date', '<', now())->sum('balance_due'),
                'expenses' => $expensesMonth,
                'net' => $collectedMonth - $expensesMonth,
                'pending_payments' => RentPayment::when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds))->where('status', 'pending')->count(),
            ],
            'recentPayments' => (clone $payments)->with(['tenant', 'unit.building.property'])->latest('payment_date')->latest('id')->limit(8)->get(),
            'overdueInvoices' => (clone $invoices)->where('balance_due', '>', 0)->whereDate('due_date', '<', now())->with(['tenant', 'unit.building.property'])->orderBy('due_date')->limit(8)->get(),
        ]);
    }

    public function payments(Request $request)
    {
        $unitIds = $this->unitIds($request);
        $rows = RentPayment::when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds))
            ->with(['tenant', 'unit.building.property'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('payment_date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('payment_date', '<=', $request->query('to')))
            ->latest('payment_date')->latest('id')->paginate(25)->withQueryString();

        return view('finance.list', ['kind' => 'payments', 'rows' => $rows, 'title' => 'Rent payments']);
    }

    public function invoices(Request $request)
    {
        $unitIds = $this->unitIds($request);
        $rows = RentInvoice::when($unitIds !== null, fn ($q) => $q->whereIn('unit_id', $unitIds))
            ->with(['tenant', 'unit.building.property'])
            ->when($request->query('status') === 'outstanding', fn ($q) => $q->where('balance_due', '>', 0)->whereNotIn('status', ['cancelled', 'void']))
            ->when($request->query('status') === 'overdue', fn ($q) => $q->where('balance_due', '>', 0)->whereDate('due_date', '<', now()))
            ->when($request->query('status') === 'paid', fn ($q) => $q->where('status', 'paid'))
            ->latest('issue_date')->latest('id')->paginate(25)->withQueryString();

        return view('finance.list', ['kind' => 'invoices', 'rows' => $rows, 'title' => 'Rent invoices']);
    }

    public function expenses(Request $request)
    {
        $ids = $this->audience->financePropertyIds($request->user());
        $this->unitIds($request); // access check
        $rows = PropertyExpense::when($ids !== null, fn ($q) => $q->whereIn('property_id', $ids))
            ->with('property:id,name')
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->query('category')))
            ->latest('expense_date')->latest('id')->paginate(25)->withQueryString();

        return view('finance.list', ['kind' => 'expenses', 'rows' => $rows, 'title' => 'Property expenses']);
    }

    /** Unit ids the user may see finances for (null = all); 403 if none. */
    private function unitIds(Request $request)
    {
        $ids = $this->audience->financePropertyIds($request->user());
        abort_unless($ids === null || $ids->isNotEmpty(), 403, 'No properties are assigned to your finance account yet.');

        return $ids === null ? null : Unit::whereHas('building', fn ($b) => $b->whereIn('property_id', $ids))->pluck('id');
    }
}

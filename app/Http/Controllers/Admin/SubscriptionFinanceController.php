<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Subscription invoices (outstanding, overdue, history), transactions and plans. */
class SubscriptionFinanceController extends Controller
{
    public function invoices(Request $request): View
    {
        $filter = $request->query('filter', 'outstanding');

        $invoices = SubscriptionInvoice::query()
            ->with(['subscriber:id,name,email,role', 'contract:id,contract_number'])
            ->when($filter === 'outstanding', fn ($q) => $q->open())
            ->when($filter === 'overdue', fn ($q) => $q->overdue())
            ->when($filter === 'paid', fn ($q) => $q->where('status', 'paid'))
            ->when($filter === 'void', fn ($q) => $q->where('status', 'void'))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . trim((string) $request->query('q')) . '%';
                $q->where(fn ($w) => $w->where('invoice_number', 'like', $term)
                    ->orWhereHas('subscriber', fn ($s) => $s->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            })
            ->orderByRaw("case when status in ('unpaid','partial') then 0 else 1 end")
            ->orderBy($filter === 'all' || $filter === 'paid' ? 'issue_date' : 'due_date', $filter === 'all' || $filter === 'paid' ? 'desc' : 'asc')
            ->paginate(25)
            ->withQueryString();

        $summary = [
            'outstanding' => (float) SubscriptionInvoice::open()->sum(DB::raw('amount - amount_paid')),
            'overdue' => (float) SubscriptionInvoice::overdue()->sum(DB::raw('amount - amount_paid')),
            'overdue_count' => SubscriptionInvoice::overdue()->count(),
            'aging' => [
                '1–30 days' => (float) SubscriptionInvoice::overdue()->whereDate('due_date', '>=', today()->subDays(30))->sum(DB::raw('amount - amount_paid')),
                '31–60 days' => (float) SubscriptionInvoice::overdue()->whereDate('due_date', '<', today()->subDays(30))->whereDate('due_date', '>=', today()->subDays(60))->sum(DB::raw('amount - amount_paid')),
                '61–90 days' => (float) SubscriptionInvoice::overdue()->whereDate('due_date', '<', today()->subDays(60))->whereDate('due_date', '>=', today()->subDays(90))->sum(DB::raw('amount - amount_paid')),
                '90+ days' => (float) SubscriptionInvoice::overdue()->whereDate('due_date', '<', today()->subDays(90))->sum(DB::raw('amount - amount_paid')),
            ],
        ];

        return view('admin.subscriptions.invoices', compact('invoices', 'summary', 'filter'));
    }

    public function payments(Request $request): View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = SubscriptionPayment::query()
            ->with(['subscriber:id,name,email,role', 'invoice:id,invoice_number', 'contract:id,contract_number', 'recorder:id,name'])
            ->when(isset(SubscriptionPayment::METHODS[$request->query('method')]), fn ($q) => $q->where('method', $request->query('method')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('paid_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('paid_at', '<=', $request->date('to')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . trim((string) $request->query('q')) . '%';
                $q->where(fn ($w) => $w->where('reference', 'like', $term)
                    ->orWhereHas('subscriber', fn ($s) => $s->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            })
            ->latest('paid_at');

        if ($request->query('export') === 'csv') {
            return response()->streamDownload(function () use ($query) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Date', 'Subscriber', 'Email', 'Contract', 'Invoice', 'Method', 'Reference', 'Amount', 'Currency', 'Recorded by']);
                $query->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $p) {
                        fputcsv($out, [$p->paid_at->format('Y-m-d H:i'), $p->subscriber?->name, $p->subscriber?->email, $p->contract?->contract_number,
                            $p->invoice?->invoice_number, $p->method_label, $p->reference, $p->amount, $p->currency, $p->recorder?->name]);
                    }
                });
                fclose($out);
            }, 'terra-subscription-transactions-' . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
        }

        $total = (clone $query)->sum('amount');
        $payments = $query->paginate(25)->withQueryString();

        $monthly = SubscriptionPayment::where('paid_at', '>=', now()->startOfMonth()->subMonths(5))
            ->get(['amount', 'paid_at'])
            ->groupBy(fn ($p) => $p->paid_at->format('Y-m'))
            ->map(fn ($g) => (float) $g->sum('amount'));

        return view('admin.subscriptions.payments', compact('payments', 'total', 'monthly'));
    }

    public function plans(): View
    {
        return view('admin.subscriptions.plans', [
            'plans' => SubscriptionPlan::withCount(['contracts as active_contracts_count' => fn ($q) => $q->where('status', 'active')])->orderBy('price')->get(),
        ]);
    }

    public function storePlan(Request $request): RedirectResponse
    {
        SubscriptionPlan::create($this->planData($request));

        return back()->with('success', 'Plan created.');
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $plan->update($this->planData($request, $plan));

        return back()->with('success', 'Plan updated.');
    }

    public function destroyPlan(SubscriptionPlan $plan): RedirectResponse
    {
        if ($plan->contracts()->withTrashed()->exists()) {
            return back()->with('error', 'This plan is used by subscription contracts. Mark it inactive instead.');
        }

        $plan->delete();

        return back()->with('success', 'Plan deleted.');
    }

    private function planData(Request $request, ?SubscriptionPlan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:40', 'alpha_dash', Rule::unique('subscription_plans', 'code')->ignore($plan?->id)],
            'billing_cycle' => ['required', Rule::in(['monthly', 'quarterly', 'annual'])],
            'price' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'currency' => ['required', Rule::in(['RWF', 'USD', 'EUR', 'KES', 'UGX', 'TZS'])],
            'max_properties' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'max_units' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['code'] = $data['code'] ?: Str::slug($data['name']) . '-' . Str::lower(Str::random(4));
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}

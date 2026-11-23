<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionContract;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Notifier;
use App\Services\SubscriptionBilling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/** Subscription agreements with owners and managers: create, track status, bill and collect. */
class SubscriptionContractController extends Controller
{
    public function __construct(private SubscriptionBilling $billing, private Notifier $notifier)
    {
    }

    public function index(Request $request): View
    {
        $contracts = SubscriptionContract::query()
            ->with(['subscriber:id,name,email,role', 'plan:id,name'])
            ->addSelect(['subscription_contracts.*'])
            ->addSelect(['outstanding' => SubscriptionInvoice::selectRaw('coalesce(sum(amount - amount_paid), 0)')
                ->whereColumn('subscription_contract_id', 'subscription_contracts.id')->whereIn('status', ['unpaid', 'partial'])])
            ->when(isset(SubscriptionContract::STATUSES[$request->query('status')]), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->query('status') === 'expiring', fn ($q) => $q->where('status', 'active')->whereNotNull('end_date')
                ->whereBetween('end_date', [today(), today()->addDays(30)]))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . trim((string) $request->query('q')) . '%';
                $q->where(fn ($w) => $w->where('contract_number', 'like', $term)->orWhere('title', 'like', $term)
                    ->orWhereHas('subscriber', fn ($s) => $s->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $counts = SubscriptionContract::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return view('admin.subscriptions.contracts.index', compact('contracts', 'counts'));
    }

    public function create(Request $request): View
    {
        $contract = new SubscriptionContract([
            'start_date' => today(),
            'end_date' => today()->addYear()->subDay(),
            'billing_cycle' => 'monthly',
            'currency' => 'RWF',
            'payment_due_days' => 7,
            'status' => 'pending',
            'title' => 'Terra subscription agreement',
            'terms' => $this->defaultTerms(),
            'user_id' => (int) $request->query('subscriber') ?: null,
        ]);

        return view('admin.subscriptions.contracts.form', $this->formData($contract));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $contract = DB::transaction(fn () => SubscriptionContract::create($data + [
            'contract_number' => SubscriptionContract::nextNumber(),
            'created_by' => $request->user()->id,
            'status' => 'pending',
        ]));

        $this->notifier->subscriptionContractEvent($contract, 'created');

        if ($request->boolean('activate_now')) {
            $this->billing->activate($contract);
        }

        return redirect()->route('admin.subscriptions.contracts.show', $contract)
            ->with('success', "Contract {$contract->contract_number} created" . ($contract->status === 'active' ? ' and activated.' : '. Activate it once it is signed.'));
    }

    public function show(SubscriptionContract $contract): View
    {
        $contract->load(['subscriber.propertyOwner', 'subscriber.propertyManager', 'plan', 'creator:id,name', 'documents.uploader:id,name']);

        return view('admin.subscriptions.contracts.show', [
            'contract' => $contract,
            'invoices' => $contract->invoices()->withCount('payments')->latest('period_start')->latest('id')->get(),
            'payments' => $contract->payments()->with(['invoice:id,invoice_number', 'recorder:id,name'])->latest('paid_at')->get(),
            'outstanding' => $contract->outstanding(),
        ]);
    }

    public function edit(SubscriptionContract $contract): View|RedirectResponse
    {
        if (in_array($contract->status, ['terminated'], true)) {
            return redirect()->route('admin.subscriptions.contracts.show', $contract)->with('error', 'Terminated contracts cannot be edited.');
        }

        return view('admin.subscriptions.contracts.form', $this->formData($contract));
    }

    public function update(Request $request, SubscriptionContract $contract): RedirectResponse
    {
        abort_if($contract->status === 'terminated', 422, 'Terminated contracts cannot be edited.');

        $contract->update($this->validated($request, $contract));

        return redirect()->route('admin.subscriptions.contracts.show', $contract)->with('success', 'Contract updated.');
    }

    /** pending/expired → active, renew, terminate. */
    public function transition(Request $request, SubscriptionContract $contract): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['activate', 'renew', 'terminate'])],
            'reason' => ['nullable', 'required_if:action,terminate', 'string', 'max:255'],
            'months' => ['nullable', 'integer', 'min:1', 'max:60'],
        ], ['reason.required_if' => 'Give a reason for terminating the contract.']);

        try {
            match ($data['action']) {
                'activate' => $this->billing->activate($contract),
                'renew' => $this->billing->renew($contract, isset($data['months']) ? (int) $data['months'] : null),
                'terminate' => $this->billing->terminate($contract, $data['reason']),
            };
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', match ($data['action']) {
            'activate' => 'Contract activated. Invoices that are due have been issued.',
            'renew' => 'Contract renewed until ' . $contract->fresh()->end_date?->format('d M Y') . '.',
            'terminate' => 'Contract terminated. Future unpaid invoices were voided.',
        });
    }

    public function issueInvoice(Request $request, SubscriptionContract $contract): RedirectResponse
    {
        if (! in_array($contract->status, ['active', 'pending'], true)) {
            return back()->with('error', 'Invoices can only be issued for pending or active contracts.');
        }

        $data = $request->validate([
            'period_start' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:1', 'max:1000000000'],
        ]);

        $invoice = $this->billing->issueInvoice($contract, isset($data['period_start']) ? Carbon::parse($data['period_start']) : null, $request->user());
        if (! empty($data['amount'])) {
            $invoice->update(['amount' => $data['amount']]);
        }

        return back()->with('success', "Invoice {$invoice->invoice_number} issued.");
    }

    public function recordPayment(Request $request, SubscriptionContract $contract): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000000'],
            'method' => ['required', Rule::in(array_keys(SubscriptionPayment::METHODS))],
            'reference' => ['nullable', 'string', 'max:60'],
            'paid_at' => ['nullable', 'date', 'before_or_equal:now'],
            'subscription_invoice_id' => ['nullable', 'integer', Rule::exists('subscription_invoices', 'id')->where('subscription_contract_id', $contract->id)],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->billing->recordPayment($contract, $data, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment of ' . $contract->currency . ' ' . number_format((float) $data['amount']) . ' recorded.');
    }

    public function voidInvoice(Request $request, SubscriptionInvoice $invoice): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']]);

        try {
            $this->billing->voidInvoice($invoice, $data['reason']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Invoice {$invoice->invoice_number} voided.");
    }

    /** Delete a contract that has no recorded payments (its unpaid invoices go with it). */
    public function destroy(SubscriptionContract $contract): RedirectResponse
    {
        if ($contract->payments()->exists()) {
            return back()->with('error', 'Payments have been recorded on this contract, so it cannot be deleted. Terminate it instead.');
        }

        DB::transaction(function () use ($contract) {
            $contract->invoices()->delete();
            $contract->delete();
        });

        return redirect()
            ->route('admin.subscriptions.contracts.index')
            ->with('success', "Contract {$contract->contract_number} deleted.");
    }

    /* ------------------------------------------------------------------ */

    private function validated(Request $request, ?SubscriptionContract $contract = null): array
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->whereIn('role', ['owner', 'manager'])],
            'subscription_plan_id' => ['nullable', 'integer', Rule::exists('subscription_plans', 'id')],
            'title' => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'billing_cycle' => ['required', Rule::in(array_keys(SubscriptionContract::CYCLES))],
            'amount' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'currency' => ['required', Rule::in(['RWF', 'USD', 'EUR', 'KES', 'UGX', 'TZS'])],
            'payment_due_days' => ['required', 'integer', 'min:0', 'max:90'],
            'auto_renew' => ['nullable', 'boolean'],
            'signed_at' => ['nullable', 'date', 'before_or_equal:now'],
            'terms' => ['nullable', 'string', 'max:20000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], ['user_id.exists' => 'Choose a property owner or manager.']);

        $data['auto_renew'] = $request->boolean('auto_renew');

        // A started contract keeps its subscriber; history must not move between accounts.
        if ($contract && $contract->invoices()->exists()) {
            $data['user_id'] = $contract->user_id;
        }

        return $data;
    }

    private function formData(SubscriptionContract $contract): array
    {
        return [
            'contract' => $contract,
            'subscribers' => User::whereIn('role', ['owner', 'manager'])->orderBy('name')->get(['id', 'name', 'email', 'role']),
            'plans' => SubscriptionPlan::where('is_active', true)->orWhere('id', $contract->subscription_plan_id)->orderBy('price')->get(),
        ];
    }

    private function defaultTerms(): string
    {
        return implode("\n\n", [
            '1. Service. Terra provides the subscriber with access to the Terra Smart Rental Management platform for the properties and units covered by the selected plan.',
            '2. Fees. The subscriber pays the agreed fee for each billing cycle. Invoices are issued at the start of each cycle and are payable within the stated number of days.',
            '3. Late payment. Terra may restrict access to paid features while an invoice is overdue, after notifying the subscriber.',
            '4. Data. The subscriber owns their property, tenant and financial data. Terra processes it only to provide the service and in line with Rwandan data protection law (Law No. 058/2021).',
            '5. Term and renewal. The agreement runs from the start date to the end date. It renews only if auto-renewal is selected or both parties agree in writing.',
            '6. Termination. Either party may terminate with 30 days\' written notice. Fees for periods already started are not refunded.',
        ]);
    }
}

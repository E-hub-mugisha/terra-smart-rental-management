<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\PaymentIntent;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Tenant mobile experience (brief §22): dashboard, lease, rent payment, receipts, maintenance. */
class TenantApiController extends Controller
{
    public function dashboard(Request $request)
    {
        $tenant = $this->tenant($request);
        $lease = $tenant->leases()->whereIn('status', ['active', 'pending_activation', 'pending_tenant_acceptance'])->with('unit.building.property')->latest('id')->first();
        $open = RentInvoice::where('tenant_id', $tenant->id)->where('balance_due', '>', 0)->whereNotIn('status', ['cancelled', 'void']);

        return ['data' => [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->full_name, 'kyc_status' => $tenant->kyc_status],
            'lease' => $lease ? $this->lease($lease) : null,
            'balance_due' => (float) (clone $open)->sum('balance_due'),
            'next_invoice' => ($next = (clone $open)->orderBy('due_date')->first()) ? $this->invoice($next) : null,
            'open_maintenance' => MaintenanceRequest::where('tenant_id', $tenant->id)->whereNotIn('status', ['closed', 'rejected'])->count(),
        ]];
    }

    public function leases(Request $request)
    {
        return ['data' => $this->tenant($request)->leases()->with('unit.building.property')->latest('id')->get()->map(fn ($l) => $this->lease($l))];
    }

    public function invoices(Request $request)
    {
        $rows = RentInvoice::where('tenant_id', $this->tenant($request)->id)
            ->when($request->query('status') === 'open', fn ($q) => $q->where('balance_due', '>', 0))
            ->latest('due_date')->paginate(20);

        return $rows->through(fn ($i) => $this->invoice($i));
    }

    public function payments(Request $request)
    {
        return RentPayment::where('tenant_id', $this->tenant($request)->id)->latest('payment_date')->latest('id')->paginate(20)
            ->through(fn ($p) => [
                'id' => $p->id, 'number' => $p->payment_number, 'amount' => (float) $p->amount, 'currency' => $p->currency,
                'date' => $p->payment_date?->toDateString(), 'method' => $p->payment_method, 'status' => $p->status,
                'receipt_url' => $p->status === 'confirmed' ? route('receipts.show', $p) : null,
            ]);
    }

    public function pay(Request $request, RentInvoice $invoice, PaymentService $payments)
    {
        abort_unless($invoice->tenant_id === $this->tenant($request)->id, 404);
        $balance = (float) $invoice->balance_due;
        abort_unless($balance > 0, 422, 'This invoice is already paid.');
        $request->merge(['phone' => preg_replace('/[\s\-().]/', '', (string) $request->input('phone'))]);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:' . min((float) config('payments.minimum_amount', 100), $balance), 'max:' . $balance],
            'phone' => ['required', 'regex:/^(\+?250|0)?7[2389]\d{7}$/'],
        ]);

        $intent = $payments->startForInvoice($request->user(), $invoice, (float) $data['amount'], $data['phone']);

        return response()->json(['data' => $this->intent($intent)], 201);
    }

    public function paymentStatus(Request $request, PaymentIntent $intent, PaymentService $payments)
    {
        abort_unless($intent->user_id === $request->user()->id, 404);

        return ['data' => $this->intent($payments->sync($intent))];
    }

    public function maintenance(Request $request)
    {
        return MaintenanceRequest::where('tenant_id', $this->tenant($request)->id)->with('serviceProvider')->latest('id')->paginate(20)
            ->through(fn ($m) => [
                'id' => $m->id, 'number' => $m->request_number, 'title' => $m->title, 'category' => $m->category, 'priority' => $m->priority,
                'status' => $m->status, 'created_at' => $m->created_at?->toIso8601String(), 'scheduled_at' => $m->scheduled_at?->toIso8601String(),
                'technician' => $m->serviceProvider ? ['name' => $m->serviceProvider->company_name, 'phone' => $m->serviceProvider->phone] : null,
            ]);
    }

    public function createMaintenance(Request $request)
    {
        $tenant = $this->tenant($request);
        $data = $request->validate([
            'lease_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'string', 'max:40'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'emergency'])],
        ]);
        $lease = Lease::where('tenant_id', $tenant->id)->whereIn('status', ['active', 'pending_activation'])->findOrFail($data['lease_id']);

        $request = MaintenanceRequest::create([
            'request_number' => 'MR-' . now()->format('ymd') . '-' . strtoupper(Str::random(5)),
            'tenant_id' => $tenant->id,
            'unit_id' => $lease->unit_id,
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $data['category'],
            'priority' => $data['priority'],
            'status' => 'submitted',
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => ['id' => $request->id, 'number' => $request->request_number, 'status' => $request->status]], 201);
    }

    /* ------------------------------------------------------------------ */

    private function tenant(Request $request): Tenant
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 403, 'No tenant profile is linked to this account.');

        return $tenant;
    }

    private function lease(Lease $l): array
    {
        return [
            'id' => $l->id, 'number' => $l->lease_number, 'status' => $l->status,
            'property' => $l->unit?->building?->property?->name, 'unit' => $l->unit?->unit_number,
            'start_date' => $l->start_date?->toDateString(), 'end_date' => $l->end_date?->toDateString(),
            'monthly_rent' => (float) $l->monthly_rent, 'deposit' => (float) $l->deposit_amount, 'currency' => $l->currency,
            'rent_due_day' => $l->rent_due_day, 'ledger_url' => route('ledger.show', $l),
        ];
    }

    private function invoice(RentInvoice $i): array
    {
        return [
            'id' => $i->id, 'number' => $i->invoice_number, 'type' => $i->invoice_type, 'status' => $i->status,
            'period_start' => $i->billing_period_start?->toDateString(), 'period_end' => $i->billing_period_end?->toDateString(),
            'due_date' => $i->due_date?->toDateString(), 'total' => (float) $i->total_amount, 'paid' => (float) $i->amount_paid,
            'balance' => (float) $i->balance_due, 'currency' => $i->currency, 'overdue' => (float) $i->balance_due > 0 && $i->due_date?->isPast(),
        ];
    }

    private function intent(PaymentIntent $p): array
    {
        return [
            'id' => $p->id, 'reference' => $p->reference, 'status' => $p->status, 'amount' => (float) $p->amount, 'currency' => $p->currency,
            'provider' => $p->provider, 'failure_reason' => $p->failure_reason,
            'receipt_url' => $p->rent_payment_id ? route('receipts.show', $p->rent_payment_id) : null,
        ];
    }
}

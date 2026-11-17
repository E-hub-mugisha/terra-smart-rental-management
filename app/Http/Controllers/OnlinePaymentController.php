<?php

namespace App\Http\Controllers;

use App\Models\PaymentIntent;
use App\Models\RentInvoice;
use App\Services\Payments\PaymentService;
use App\Services\Payments\SandboxGateway;
use Illuminate\Http\Request;

/** Tenant pays a rent invoice online (brief §9). */
class OnlinePaymentController extends Controller
{
    public function __construct(private PaymentService $payments)
    {
    }

    public function create(Request $request, RentInvoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);

        if ((float) $invoice->balance_due <= 0) {
            return redirect()->route('tenant-portal.rent-invoices.show', $invoice)->with('success', 'This invoice is already paid.');
        }

        $invoice->load(['unit.building.property', 'lease']);

        return view('payments.checkout', [
            'invoice' => $invoice,
            'provider' => $this->payments->gateway(),
            'minimum' => min((float) config('payments.minimum_amount', 100), (float) $invoice->balance_due),
            'phone' => $request->user()->tenant?->phone,
            'recent' => PaymentIntent::where('rent_invoice_id', $invoice->id)->latest('id')->limit(5)->get(),
        ]);
    }

    public function store(Request $request, RentInvoice $invoice)
    {
        $this->authorizeInvoice($request, $invoice);
        $balance = (float) $invoice->balance_due;
        abort_unless($balance > 0, 422, 'This invoice is already paid.');

        // People type numbers as "0788 123 456" or "0788-123-456".
        $request->merge(['phone' => preg_replace('/[\s\-().]/', '', (string) $request->input('phone'))]);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:' . min((float) config('payments.minimum_amount', 100), $balance), 'max:' . $balance],
            'phone' => ['required', 'string', 'regex:/^(\+?250|0)?7[2389]\d{7}$/'],
        ], [
            'phone.regex' => 'Enter a Rwandan mobile number, e.g. 0788 123 456.',
            'amount.max' => 'You can pay at most the outstanding balance.',
        ]);

        // Don't stack prompts: reuse a payment that's still waiting on the phone.
        $pending = PaymentIntent::where('rent_invoice_id', $invoice->id)->where('status', 'pending')
            ->where('created_at', '>', now()->subMinutes(5))->latest('id')->first();
        if ($pending) {
            return redirect()->route('payments.online.show', $pending)
                ->with('warning', 'A payment for this invoice is already waiting for confirmation on your phone.');
        }

        $intent = $this->payments->startForInvoice($request->user(), $invoice, (float) $data['amount'], preg_replace('/\s+/', '', $data['phone']));

        return redirect()->route('payments.online.show', $intent);
    }

    public function show(Request $request, PaymentIntent $intent)
    {
        $this->authorizeIntent($request, $intent);
        $intent = $this->payments->sync($intent)->load(['invoice', 'payment', 'unit.building.property']);

        return view('payments.status', [
            'intent' => $intent,
            'isSandbox' => $intent->provider === 'sandbox',
        ]);
    }

    /** Polled by the status page every few seconds. */
    public function status(Request $request, PaymentIntent $intent)
    {
        $this->authorizeIntent($request, $intent);
        $intent = $this->payments->sync($intent);

        return response()->json([
            'status' => $intent->status,
            'reason' => $intent->failure_reason,
            'receipt' => $intent->rent_payment_id ? route('receipts.show', $intent->rent_payment_id) : null,
        ]);
    }

    /** Test mode only: stands in for the tenant approving/declining the prompt on their phone. */
    public function sandboxDecision(Request $request, PaymentIntent $intent)
    {
        $this->authorizeIntent($request, $intent);
        abort_unless($intent->provider === 'sandbox' && $intent->status === 'pending', 404);

        $decision = $request->validate(['decision' => ['required', 'in:approve,decline']])['decision'];
        app(SandboxGateway::class)->decide($intent, $decision);
        $this->payments->sync($intent->fresh());

        return redirect()->route('payments.online.show', $intent);
    }

    /** Provider webhook. Only used as a hint — the status is always re-fetched from the provider. */
    public function callback(Request $request, string $provider)
    {
        abort_unless(array_key_exists($provider, config('payments.providers', [])) && $provider !== 'sandbox', 404);

        $reference = $this->payments->gateway($provider)->referenceFromCallback($request);
        $intent = $reference ? PaymentIntent::where('reference', $reference)->where('provider', $provider)->first() : null;

        if ($intent) {
            $this->payments->sync($intent);
        }

        return response()->noContent();
    }

    private function authorizeInvoice(Request $request, RentInvoice $invoice): void
    {
        $invoice->loadMissing('tenant');
        abort_unless($invoice->tenant && $invoice->tenant->user_id === $request->user()->id, 404);
        abort_if(in_array($invoice->status, ['cancelled', 'void'], true), 404);
    }

    private function authorizeIntent(Request $request, PaymentIntent $intent): void
    {
        abort_unless($intent->user_id === $request->user()->id, 404);
    }
}

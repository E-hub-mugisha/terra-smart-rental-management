<?php

namespace App\Http\Controllers;

use App\Models\RentPayment;
use App\Services\PropertyAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/** Digital receipts (brief §11): printable / save-as-PDF, and shareable by signed link. */
class ReceiptController extends Controller
{
    public function __construct(private PropertyAudience $audience)
    {
    }

    public function show(Request $request, RentPayment $payment)
    {
        $user = $request->user();
        $payment->loadMissing(['tenant', 'unit.building']);

        $allowed = ($payment->tenant?->user_id !== null && $payment->tenant->user_id === $user->id)
            || $this->audience->canViewFinance($user, $payment->unit?->building?->property_id);
        abort_unless($allowed, 404);

        return $this->render($payment, URL::temporarySignedRoute('receipts.shared', now()->addDays(30), ['payment' => $payment->id]));
    }

    /** Anyone holding the signed link (e.g. shared on WhatsApp) can view it until it expires. */
    public function shared(Request $request, RentPayment $payment)
    {
        return $this->render($payment, null);
    }

    private function render(RentPayment $payment, ?string $shareUrl)
    {
        abort_unless($payment->status === 'confirmed', 404, 'Receipts are issued for confirmed payments only.');

        $payment->load(['tenant', 'lease', 'unit.building.property.owner', 'unit.building.property.activeManagers.user', 'allocations.invoice', 'recorder']);

        return view('payments.receipt', [
            'payment' => $payment,
            'property' => $payment->unit?->building?->property,
            'manager' => $payment->unit?->building?->property?->activeManagers->first(),
            'shareUrl' => $shareUrl,
        ]);
    }
}

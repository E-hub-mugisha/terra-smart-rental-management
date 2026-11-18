<?php

namespace App\Http\Controllers;

use App\Models\SubscriberCall;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Owners and managers see their own Terra subscription, bills and payments. */
class SubscriptionController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isSubscriber(), 403);

        $contracts = $user->subscriptionContracts()->with(['plan', 'documents'])->latest('start_date')->get();
        $invoices = $user->subscriptionInvoices()->with('contract:id,contract_number')->latest('issue_date')->latest('id')->limit(36)->get();

        return view('subscription.show', [
            'current' => $contracts->firstWhere('status', 'active') ?? $contracts->first(),
            'contracts' => $contracts,
            'invoices' => $invoices,
            'payments' => $user->subscriptionPayments()->with('invoice:id,invoice_number')->latest('paid_at')->limit(36)->get(),
            'outstanding' => $invoices->whereIn('status', ['unpaid', 'partial'])->sum(fn ($i) => $i->balance),
            'overdue' => $invoices->filter(fn ($i) => $i->is_overdue)->sum(fn ($i) => $i->balance),
            'support' => User::where('role', 'admin')->orderBy('id')->first(),
        ]);
    }
}

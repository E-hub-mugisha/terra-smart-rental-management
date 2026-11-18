<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SubscriberMessage;
use App\Models\SubscriberCall;
use App\Models\SubscriptionContract;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Notifications\TerraAlert;
use App\Services\Notifier;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Terra admin view of its subscribers: every property owner and manager,
 * their subscription status, balances, and all contact with them
 * (email, in-platform messages and calls).
 */
class SubscriberController extends Controller
{
    public function __construct(private Notifier $notifier)
    {
    }

    public function index(Request $request): View
    {
        $today = today()->toDateString();

        $query = User::query()
            ->whereIn('role', ['owner', 'manager'])
            ->with(['latestSubscription.plan', 'propertyOwner:id,user_id,owner_number,phone,company_name', 'propertyManager:id,user_id,manager_number,phone'])
            ->addSelect(['users.*'])
            ->addSelect(['outstanding' => SubscriptionInvoice::selectRaw('coalesce(sum(amount - amount_paid), 0)')
                ->whereColumn('user_id', 'users.id')->whereIn('status', ['unpaid', 'partial'])])
            ->addSelect(['overdue' => SubscriptionInvoice::selectRaw('coalesce(sum(amount - amount_paid), 0)')
                ->whereColumn('user_id', 'users.id')->whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', $today)])
            ->addSelect(['last_paid_at' => SubscriptionPayment::selectRaw('max(paid_at)')->whereColumn('user_id', 'users.id')]);

        $query->when($request->filled('q'), function (Builder $q) use ($request) {
            $term = '%' . trim((string) $request->query('q')) . '%';
            $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('phone', 'like', $term));
        });
        $query->when(in_array($request->query('role'), ['owner', 'manager'], true), fn ($q) => $q->where('role', $request->query('role')));

        $status = $request->query('status');
        if ($status === 'none') {
            $query->whereDoesntHave('subscriptionContracts');
        } elseif (isset(SubscriptionContract::STATUSES[$status])) {
            $query->whereHas('latestSubscription', fn ($q) => $q->where('status', $status));
        }

        match ($request->query('billing')) {
            'overdue' => $query->whereHas('subscriptionInvoices', fn ($q) => $q->overdue()),
            'outstanding' => $query->whereHas('subscriptionInvoices', fn ($q) => $q->open()),
            'clear' => $query->whereDoesntHave('subscriptionInvoices', fn ($q) => $q->open()),
            default => null,
        };

        $subscribers = $query->orderByDesc('overdue')->orderBy('name')->paginate(20)->withQueryString();

        $stats = [
            'subscribers' => User::whereIn('role', ['owner', 'manager'])->count(),
            'active' => SubscriptionContract::where('status', 'active')->count(),
            'pending' => SubscriptionContract::where('status', 'pending')->count(),
            'expiring' => SubscriptionContract::where('status', 'active')->whereNotNull('end_date')
                ->whereBetween('end_date', [today(), today()->addDays(30)])->count(),
            'outstanding' => (float) SubscriptionInvoice::open()->sum(DB::raw('amount - amount_paid')),
            'overdue' => (float) SubscriptionInvoice::overdue()->sum(DB::raw('amount - amount_paid')),
            'overdue_count' => SubscriptionInvoice::overdue()->distinct('user_id')->count('user_id'),
            'collected_month' => (float) SubscriptionPayment::where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'without_contract' => User::whereIn('role', ['owner', 'manager'])->whereDoesntHave('subscriptionContracts')->count(),
        ];

        return view('admin.subscribers.index', compact('subscribers', 'stats'));
    }

    public function show(User $subscriber): View
    {
        abort_unless($subscriber->isSubscriber(), 404);

        $subscriber->load(['propertyOwner', 'propertyManager', 'fieldStaff']);

        $contracts = $subscriber->subscriptionContracts()->with(['plan', 'documents'])->latest('start_date')->get();
        $invoices = $subscriber->subscriptionInvoices()->with('contract:id,contract_number')->latest('issue_date')->latest('id')->limit(50)->get();
        $payments = $subscriber->subscriptionPayments()->with(['invoice:id,invoice_number', 'recorder:id,name'])->latest('paid_at')->limit(50)->get();
        $calls = $subscriber->subscriberCalls()->with('caller:id,name')->latest('called_at')->limit(50)->get();

        $ownerDocuments = $subscriber->propertyOwner
            ? $subscriber->propertyOwner->documents()->with('uploader:id,name')->latest()->get()
            : collect();

        $phone = $subscriber->contactPhone();
        $registeredBy = User::find($subscriber->propertyOwner?->created_by ?? $subscriber->propertyManager?->created_by);

        return view('admin.subscribers.show', [
            'subscriber' => $subscriber,
            'contracts' => $contracts,
            'invoices' => $invoices,
            'payments' => $payments,
            'calls' => $calls,
            'ownerDocuments' => $ownerDocuments,
            'phone' => $phone,
            'phoneDisplay' => $phone ? Phone::display($phone) : null,
            'phoneDigits' => Phone::digits($phone),
            'registeredBy' => $registeredBy,
            'totals' => [
                'outstanding' => $invoices->whereIn('status', ['unpaid', 'partial'])->sum(fn ($i) => $i->balance),
                'overdue' => $invoices->filter(fn ($i) => $i->is_overdue)->sum(fn ($i) => $i->balance),
                'paid' => (float) $subscriber->subscriptionPayments()->sum('amount'),
                'invoiced' => (float) $subscriber->subscriptionInvoices()->where('status', '!=', 'void')->sum('amount'),
            ],
            'meetUrl' => config('services.calls.meet_url'),
        ]);
    }

    public function email(Request $request, User $subscriber): RedirectResponse
    {
        abort_unless($subscriber->isSubscriber(), 404);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'copy_in_app' => ['nullable', 'boolean'],
        ]);

        $admin = $request->user();

        try {
            Mail::to($subscriber->email, $subscriber->name)->send(new SubscriberMessage(
                recipientName: $subscriber->name,
                subjectLine: $data['subject'],
                body: $data['body'],
                senderName: $admin->name,
                senderEmail: $admin->email,
                actionUrl: route('login'),
            ));
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'The email could not be sent. Check the mail settings and try again.');
        }

        if ($request->boolean('copy_in_app')) {
            $this->notifier->toUser($subscriber, new TerraAlert('message', $data['subject'], Str::limit($data['body'], 240), null,
                ['icon' => 'bi-envelope', 'tone' => 'info', 'mail' => false]));
        }

        SubscriberCall::create([
            'user_id' => $subscriber->id,
            'called_by' => $admin->id,
            'direction' => 'outbound',
            'channel' => 'email',
            'outcome' => 'sent',
            'subject' => $data['subject'],
            'notes' => $data['body'],
            'called_at' => now(),
        ]);

        return back()->with('success', "Email sent to {$subscriber->email}.");
    }

    /** Records the outcome of a phone or WhatsApp call. */
    public function logCall(Request $request, User $subscriber): RedirectResponse
    {
        abort_unless($subscriber->isSubscriber(), 404);

        $data = $request->validate([
            'channel' => ['required', Rule::in(['phone', 'whatsapp', 'browser'])],
            'direction' => ['required', Rule::in(['outbound', 'inbound'])],
            'outcome' => ['required', Rule::in(array_keys(SubscriberCall::OUTCOMES))],
            'minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'seconds' => ['nullable', 'integer', 'min:0', 'max:59'],
            'subject' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'called_at' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $duration = ((int) ($data['minutes'] ?? 0)) * 60 + (int) ($data['seconds'] ?? 0);

        SubscriberCall::create([
            'user_id' => $subscriber->id,
            'called_by' => $request->user()->id,
            'direction' => $data['direction'],
            'channel' => $data['channel'],
            'phone' => $subscriber->contactPhone(),
            'outcome' => $data['outcome'],
            'duration_seconds' => $duration ?: null,
            'subject' => $data['subject'] ?? null,
            'notes' => $data['notes'] ?? null,
            'called_at' => $data['called_at'] ?? now(),
        ]);

        return back()->with('success', 'Call logged.');
    }

    /**
     * Starts an in-browser audio/video call: creates a private room, invites
     * the subscriber with an in-app alert, and opens the call for the admin.
     */
    public function startCall(Request $request, User $subscriber): RedirectResponse
    {
        abort_unless($subscriber->isSubscriber(), 404);

        $call = SubscriberCall::create([
            'user_id' => $subscriber->id,
            'called_by' => $request->user()->id,
            'direction' => 'outbound',
            'channel' => 'browser',
            'room' => 'Terra-' . Str::upper(Str::random(6)) . '-' . Str::random(16),
            'outcome' => 'started',
            'subject' => $request->string('subject')->limit(150)->toString() ?: 'Call from Terra',
            'called_at' => now(),
        ]);

        $this->notifier->toUser($subscriber, new TerraAlert('call',
            'Incoming call from Terra',
            $request->user()->name . ' from Terra is calling you. Tap to join the call in your browser.',
            route('calls.join', $call),
            ['icon' => 'bi-camera-video', 'tone' => 'warning', 'action' => 'Join call', 'mail' => false, 'sms' => true]));

        return redirect()->route('calls.join', $call);
    }
}

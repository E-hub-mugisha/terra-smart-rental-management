<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Message;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\Tenant;
use App\Models\TenantApplication;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\TerraAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

/**
 * Central notification engine (brief §12). Every alert the platform sends
 * goes through here, so wording, links and recipients stay consistent.
 */
class Notifier
{
    public function __construct(private PropertyAudience $audience)
    {
    }

    /* =====================================================================
     |  Rent & payments
     * ===================================================================== */

    public function invoiceIssued(RentInvoice $invoice): void
    {
        $invoice->loadMissing(['tenant.user', 'unit.building.property']);

        $this->toTenant($invoice->tenant, fn ($u) => new TerraAlert(
            'invoice',
            'New rent invoice ' . $invoice->invoice_number,
            sprintf('%s is due on %s for %s.', $this->money($invoice->total_amount, $invoice->currency),
                $invoice->due_date?->format('d M Y'), $this->place($invoice->unit)),
            $this->url($u, 'invoice', $invoice),
            ['action' => 'View invoice']
        ));
    }

    /** Tenant reminder. $stage: soon | today | overdue */
    public function rentDue(RentInvoice $invoice, string $stage, int $days = 0): void
    {
        $invoice->loadMissing(['tenant.user', 'unit.building.property']);
        $amount = $this->money($invoice->balance_due, $invoice->currency);
        $place = $this->place($invoice->unit);

        [$category, $title, $message, $tone] = match ($stage) {
            'soon' => ['rent_due', 'Rent due in ' . $days . ' ' . str('day')->plural($days),
                "{$amount} for {$place} is due on " . $invoice->due_date?->format('d M Y') . '.', 'info'],
            'today' => ['rent_due', 'Rent is due today', "{$amount} for {$place} is due today.", 'warning'],
            default => ['rent_overdue', 'Rent overdue', "{$amount} for {$place} is {$days} " . str('day')->plural($days) . ' overdue. Please pay as soon as possible.', 'danger'],
        };

        $this->toTenant($invoice->tenant, fn ($u) => new TerraAlert($category, $title, $message,
            $this->url($u, 'invoice', $invoice), ['tone' => $tone, 'sms' => true, 'action' => 'View invoice']));
    }

    public function rentOverdueForStaff(RentInvoice $invoice, int $days): void
    {
        $invoice->loadMissing(['tenant', 'unit.building.property']);

        $this->toStaff($invoice->unit, fn ($u) => new TerraAlert(
            'rent_overdue',
            'Rent overdue: ' . ($invoice->tenant?->full_name ?? 'tenant'),
            sprintf('%s for %s is %d %s overdue (invoice %s).', $this->money($invoice->balance_due, $invoice->currency),
                $this->place($invoice->unit), $days, str('day')->plural($days), $invoice->invoice_number),
            $this->url($u, 'invoice', $invoice),
            ['tone' => 'danger']
        ));
    }

    public function paymentConfirmed(RentPayment $payment): void
    {
        $payment->loadMissing(['tenant.user', 'unit.building.property']);
        $amount = $this->money($payment->amount, $payment->currency);
        $place = $this->place($payment->unit);

        $this->toTenant($payment->tenant, fn ($u) => new TerraAlert(
            'payment',
            'Payment successful',
            "We received {$amount} for {$place}. Reference {$payment->payment_number}. Your receipt is ready.",
            $this->url($u, 'payment', $payment),
            ['tone' => 'success', 'sms' => true, 'action' => 'View receipt']
        ));

        $this->toStaff($payment->unit, fn ($u) => new TerraAlert(
            'payment',
            'Rent received',
            ($payment->tenant?->full_name ?? 'A tenant') . " paid {$amount} for {$place}.",
            $this->url($u, 'payment', $payment),
            ['tone' => 'success']
        ));
    }

    /* =====================================================================
     |  Leases
     * ===================================================================== */

    /** $event: sent | accepted | activated | terminated */
    public function leaseEvent(Lease $lease, string $event): void
    {
        $lease->loadMissing(['tenant.user', 'unit.building.property']);
        $place = $this->place($lease->unit);
        $tenantName = $lease->tenant?->full_name ?? 'The tenant';

        match ($event) {
            'sent' => $this->toTenant($lease->tenant, fn ($u) => new TerraAlert('lease', 'Your lease is ready to review',
                "Lease {$lease->lease_number} for {$place} is waiting for your review and acceptance.",
                $this->url($u, 'lease', $lease), ['action' => 'Review lease'])),
            'accepted' => $this->toStaff($lease->unit, fn ($u) => new TerraAlert('lease', 'Lease accepted by tenant',
                "{$tenantName} accepted lease {$lease->lease_number} for {$place}. It is ready for activation.",
                $this->url($u, 'lease', $lease), ['tone' => 'success'])),
            'activated' => $this->toTenant($lease->tenant, fn ($u) => new TerraAlert('lease', 'Your lease is now active',
                "Welcome home! Lease {$lease->lease_number} for {$place} is active from " . $lease->start_date?->format('d M Y') . '.',
                $this->url($u, 'lease', $lease), ['tone' => 'success'])),
            'terminated' => $this->toTenant($lease->tenant, fn ($u) => new TerraAlert('lease', 'Lease terminated',
                "Lease {$lease->lease_number} for {$place} has been terminated.",
                $this->url($u, 'lease', $lease), ['tone' => 'warning'])),
            default => null,
        };
    }

    public function leaseExpiring(Lease $lease, int $days): void
    {
        $lease->loadMissing(['tenant.user', 'unit.building.property']);
        $place = $this->place($lease->unit);
        $end = $lease->end_date?->format('d M Y');

        $this->toTenant($lease->tenant, fn ($u) => new TerraAlert('lease', "Your lease ends in {$days} days",
            "Your lease for {$place} ends on {$end}. Contact your property manager about renewal or move-out.",
            $this->url($u, 'lease', $lease), ['tone' => 'warning']));

        $this->toStaff($lease->unit, fn ($u) => new TerraAlert('lease', "Lease expiring in {$days} days",
            ($lease->tenant?->full_name ?? 'Tenant') . "'s lease for {$place} ends on {$end}.",
            $this->url($u, 'lease', $lease), ['tone' => 'warning']));
    }

    /* =====================================================================
     |  Maintenance
     * ===================================================================== */

    public function maintenanceSubmitted(MaintenanceRequest $request): void
    {
        $request->loadMissing(['tenant', 'unit.building.property']);

        $this->toStaff($request->unit, fn ($u) => new TerraAlert('maintenance',
            'New maintenance request: ' . $request->title,
            ucfirst((string) $request->priority) . ' priority request from ' . ($request->tenant?->full_name ?? 'a tenant')
                . ' for ' . $this->place($request->unit) . '.',
            $this->url($u, 'maintenance', $request),
            ['tone' => in_array($request->priority, ['high', 'urgent', 'emergency'], true) ? 'danger' : 'info']));
    }

    public function maintenanceUpdated(MaintenanceRequest $request, ?string $from, string $to): void
    {
        $request->loadMissing(['tenant.user', 'unit.building.property']);
        $label = ucwords(str_replace('_', ' ', $to));

        if ($request->tenant?->user_id && $request->tenant->user_id === auth()->id()) {
            return; // the tenant made this change themselves (e.g. confirming the fix)
        }

        $this->toTenant($request->tenant, fn ($u) => new TerraAlert('maintenance',
            'Maintenance update: ' . $label,
            "Your request \"{$request->title}\" ({$request->request_number}) is now {$label}."
                . ($request->scheduled_at && $to === 'scheduled' ? ' Visit scheduled for ' . $request->scheduled_at->format('d M Y, H:i') . '.' : ''),
            $this->url($u, 'maintenance', $request),
            ['tone' => in_array($to, ['resolved', 'closed'], true) ? 'success' : ($to === 'rejected' ? 'warning' : 'info')]));
    }

    public function maintenanceAssigned(MaintenanceRequest $request): void
    {
        $request->loadMissing(['tenant.user', 'unit.building.property', 'serviceProvider.user']);
        $provider = $request->serviceProvider;

        if ($provider?->user) {
            $this->safely(fn () => $provider->user->notify(new TerraAlert('maintenance',
                'New job: ' . $request->title,
                ucfirst((string) $request->priority) . ' priority · ' . $this->place($request->unit) . '. Open the job for details and the tenant contact.',
                route('service-provider.maintenance.show', $request),
                ['tone' => in_array($request->priority, ['high', 'emergency'], true) ? 'danger' : 'info', 'sms' => true])));
        }

        $this->toTenant($request->tenant, fn ($u) => new TerraAlert('maintenance',
            'Technician assigned',
            ($provider?->company_name ?? 'A technician') . ' will handle "' . $request->title . '".'
                . ($provider?->phone ? ' Contact: ' . $provider->phone . '.' : ''),
            $this->url($u, 'maintenance', $request)));
    }

    public function maintenanceTenantResponse(MaintenanceRequest $request, bool $fixed): void
    {
        $request->loadMissing(['tenant', 'unit.building.property', 'serviceProvider.user']);
        $title = $fixed ? 'Repair confirmed by tenant' : 'Tenant says it is not fixed';
        $msg = ($request->tenant?->full_name ?? 'The tenant') . ($fixed ? ' confirmed' : ' reopened') . ' "' . $request->title . '"'
            . ($request->tenant_rating ? ' · rated ' . $request->tenant_rating . '/5' : '')
            . ($request->tenant_feedback ? ': "' . str($request->tenant_feedback)->limit(100) . '"' : '.');

        $this->toStaff($request->unit, fn ($u) => new TerraAlert('maintenance', $title, $msg,
            $this->url($u, 'maintenance', $request), ['tone' => $fixed ? 'success' : 'danger']));

        if (! $fixed && $request->serviceProvider?->user) {
            $this->safely(fn () => $request->serviceProvider->user->notify(new TerraAlert('maintenance', $title, $msg,
                route('service-provider.maintenance.show', $request), ['tone' => 'danger'])));
        }
    }

    /* =====================================================================
     |  Applications & vacancies
     * ===================================================================== */

    public function applicationReceived(TenantApplication $application): void
    {
        $application->loadMissing(['tenant', 'unit.building.property']);

        $this->toStaff($application->unit, fn ($u) => new TerraAlert('application',
            'New rental application',
            ($application->tenant?->full_name ?? 'Someone') . ' applied for ' . $this->place($application->unit) . '.',
            $this->url($u, 'application', $application)));
    }

    public function applicationDecided(TenantApplication $application): void
    {
        $application->loadMissing(['tenant.user', 'unit.building.property']);
        $approved = $application->status === 'approved';

        $this->toTenant($application->tenant, fn ($u) => new TerraAlert('application',
            $approved ? 'Application approved' : 'Application update',
            $approved
                ? 'Good news! Your application for ' . $this->place($application->unit) . ' was approved. Your lease will follow shortly.'
                : 'Your application for ' . $this->place($application->unit) . ' was not successful this time.',
            $this->url($u, 'application', $application),
            ['tone' => $approved ? 'success' : 'warning']));
    }

    public function unitVacant(Unit $unit): void
    {
        $unit->loadMissing('building.property');

        $this->toStaff($unit, fn ($u) => new TerraAlert('vacancy',
            'Unit now vacant',
            $this->place($unit) . ' is now available. Share the listing to find a new tenant.',
            Route::has('listings.show') && $unit->building?->property ? route('listings.show', $unit->building->property) : null,
            ['tone' => 'warning', 'mail' => false]));
    }

    /* =====================================================================
     |  Move-in / move-out inspections
     * ===================================================================== */

    public function inspectionSubmitted(\App\Models\Inspection $inspection): void
    {
        $inspection->loadMissing(['lease.tenant.user', 'unit.building.property']);

        $this->toTenant($inspection->lease?->tenant, fn ($u) => new TerraAlert('inspection',
            $inspection->type_label . ' ready for your review',
            'Please check the ' . strtolower($inspection->type_label) . ' report for ' . $this->place($inspection->unit)
                . ($inspection->isMoveOut() ? ', including the deposit settlement,' : '') . ' and confirm it or raise any disagreement.',
            $u ? route('inspections.show', $inspection) : null,
            ['tone' => 'warning', 'action' => 'Review inspection']));
    }

    public function inspectionResponded(\App\Models\Inspection $inspection): void
    {
        $inspection->loadMissing(['lease.tenant', 'unit.building.property']);
        $disputed = $inspection->status === 'disputed';
        $tenant = $inspection->lease?->tenant?->full_name ?? 'The tenant';

        $this->toStaff($inspection->unit, fn ($u) => new TerraAlert('inspection',
            $disputed ? 'Inspection disputed' : 'Inspection confirmed',
            $disputed
                ? "{$tenant} disagreed with the " . strtolower($inspection->type_label) . ' for ' . $this->place($inspection->unit) . ': "'
                    . str($inspection->tenant_comment)->limit(120) . '"'
                : "{$tenant} confirmed the " . strtolower($inspection->type_label) . ' for ' . $this->place($inspection->unit) . '.',
            route('inspections.show', $inspection),
            ['tone' => $disputed ? 'danger' : 'success']));
    }

    public function depositSettled(\App\Models\Inspection $inspection): void
    {
        $inspection->loadMissing(['lease.tenant.user', 'unit.building.property']);

        $this->toTenant($inspection->lease?->tenant, fn ($u) => new TerraAlert('inspection',
            'Deposit settled',
            'Your deposit for ' . $this->place($inspection->unit) . ' has been settled: '
                . $this->money($inspection->deposit_refund, $inspection->currency) . ' refunded after '
                . $this->money($inspection->deposit_deductions, $inspection->currency) . ' in deductions.',
            $u ? route('inspections.show', $inspection) : null,
            ['tone' => 'success']));
    }

    /* =====================================================================
     |  Documents & KYC
     * ===================================================================== */

    public function documentUploaded(\App\Models\Document $document): void
    {
        $tenant = $document->documentable;
        if (! $tenant instanceof Tenant) {
            return;
        }

        $propertyIds = Lease::where('tenant_id', $tenant->id)->with('unit.building')->get()->pluck('unit.building.property_id')
            ->concat(TenantApplication::where('tenant_id', $tenant->id)->with('unit.building')->get()->pluck('unit.building.property_id'))
            ->filter()->unique();

        $staff = $propertyIds->flatMap(fn ($id) => $this->audience->staffUsers($id))->unique('id');
        foreach ($staff as $user) {
            $this->safely(fn () => $user->notify(new TerraAlert('document',
                'Document to verify: ' . $tenant->full_name,
                $tenant->full_name . ' uploaded a ' . strtolower($document->category_label) . ' for verification.',
                route('documents.index', ['owner' => 'tenant:' . $tenant->id]),
                ['tone' => 'warning', 'mail' => false])));
        }
    }

    public function documentReviewed(\App\Models\Document $document): void
    {
        $tenant = $document->documentable;
        if (! $tenant instanceof Tenant) {
            return;
        }
        $ok = $document->status === 'verified';

        $this->toTenant($tenant, fn ($u) => new TerraAlert('document',
            $ok ? 'Document verified' : 'Document rejected',
            $ok ? 'Your ' . strtolower($document->category_label) . ' has been verified.'
                : 'Your ' . strtolower($document->category_label) . ' was rejected: ' . $document->rejection_reason . '. Please upload a new copy.',
            $u ? route('documents.index') : null,
            ['tone' => $ok ? 'success' : 'danger']));
    }

    /* =====================================================================
     |  Lease renewal & move-out notice
     * ===================================================================== */

    public function leaseRequestEvent(\App\Models\LeaseRequest $request, string $event): void
    {
        $request->loadMissing(['lease.tenant.user', 'lease.unit.building.property']);
        $lease = $request->lease;
        $place = $this->place($lease?->unit);
        $tenantName = $lease?->tenant?->full_name ?? 'The tenant';
        $money = fn ($v) => $this->money($v, $lease?->currency);
        $toStaff = fn (string $title, string $msg, string $tone = 'info') => $this->toStaff($lease?->unit, fn ($u) => new TerraAlert('lease', $title, $msg, $this->url($u, 'lease', $lease), ['tone' => $tone]));
        $toTenant = fn (string $title, string $msg, string $tone = 'info') => $this->toTenant($lease?->tenant, fn ($u) => new TerraAlert('lease', $title, $msg, $this->url($u, 'lease', $lease), ['tone' => $tone]));

        match ([$request->type, $event]) {
            ['renewal', 'requested'] => $toStaff('Renewal requested', "{$tenantName} would like to renew the lease for {$place} for {$request->requested_months} months."),
            ['renewal', 'offered'] => $toTenant('Lease renewal offer', "New terms for {$place}: until " . $request->offered_end_date?->format('d M Y') . ' at ' . $money($request->offered_rent) . ' per month. Review and accept in your lease.', 'warning'),
            ['renewal', 'accepted'] => $toStaff('Renewal accepted', "{$tenantName} accepted the renewal for {$place} until " . $request->offered_end_date?->format('d M Y') . '.', 'success'),
            ['renewal', 'declined'] => $request->responded_by && $request->responded_by === auth()->id()
                ? $toTenant('Renewal request declined', "Your renewal request for {$place} was declined." . ($request->response ? ' ' . $request->response : ''), 'warning')
                : $toStaff('Renewal offer declined', "{$tenantName} declined the renewal offer for {$place}.", 'warning'),
            ['move_out', 'requested'] => $toStaff('Move-out notice', "{$tenantName} plans to move out of {$place} on " . $request->move_out_date?->format('d M Y') . '. Plan the move-out inspection.', 'warning'),
            ['move_out', 'acknowledged'] => $toTenant('Move-out notice acknowledged', "Your notice to leave {$place} on " . $request->move_out_date?->format('d M Y') . ' was received. We will contact you about the move-out inspection.'),
            ['renewal', 'withdrawn'], ['move_out', 'withdrawn'] => $toStaff(ucfirst($request->type_label) . ' withdrawn', "{$tenantName} withdrew their " . strtolower($request->type_label) . " for {$place}."),
            default => null,
        };
    }

    /* =====================================================================
     |  Messaging
     * ===================================================================== */

    /**
     * One unread "new message" alert per conversation per user: if the user
     * already has an unread alert for this thread, don't stack another.
     */
    public function newMessage(Message $message, Collection $recipients): void
    {
        $message->loadMissing(['sender', 'conversation']);
        $conversation = $message->conversation;

        foreach ($recipients as $user) {
            $already = $user->unreadNotifications()
                ->where('data', 'like', '%"conversation_id":' . $conversation->id . '}%')
                ->exists();
            if ($already) {
                continue;
            }

            $this->safely(fn () => $user->notify(new TerraAlert(
                $conversation->isAnnouncement() ? 'announcement' : 'message',
                $conversation->isAnnouncement()
                    ? 'Announcement: ' . $conversation->subject
                    : 'New message from ' . ($message->sender?->name ?? 'Terra'),
                str($message->body ?: 'Sent an attachment')->squish()->limit(140)->toString(),
                route('messages.show', $conversation),
                ['tone' => 'info', 'action' => 'Read message', 'context' => ['conversation_id' => $conversation->id]]
            )));
        }
    }

    /* =====================================================================
     |  Subscriptions, contracts & field staff
     * ===================================================================== */

    /** Tell the person a contract PDF was filed on their profile. */
    public function contractDocumentFiled(\App\Models\Document $document): void
    {
        $owner = $document->documentable;
        $user = match (true) {
            $owner instanceof Tenant, $owner instanceof \App\Models\PropertyOwner, $owner instanceof \App\Models\SubscriptionContract => $owner->user,
            default => null,
        };
        if ($owner instanceof \App\Models\SubscriptionContract) {
            $user = $owner->subscriber;
        }
        if (! $user || $user->id === auth()->id()) {
            return;
        }

        $this->toUser($user, new TerraAlert('document',
            'New contract on your profile',
            $document->title . ' has been added to your documents. You can view or download it at any time.',
            route('documents.index'),
            ['icon' => 'bi-file-earmark-pdf', 'tone' => 'info', 'action' => 'View documents']));
    }

    public function subscriptionContractEvent(\App\Models\SubscriptionContract $contract, string $event): void
    {
        [$title, $tone] = match ($event) {
            'created' => ['Your Terra subscription agreement is ready', 'info'],
            'activated' => ['Your Terra subscription is active', 'success'],
            'renewed' => ['Your Terra subscription was renewed', 'success'],
            'expiring' => ['Your Terra subscription expires soon', 'warning'],
            'expired' => ['Your Terra subscription has expired', 'warning'],
            'terminated' => ['Your Terra subscription was terminated', 'danger'],
            default => ['Subscription update', 'info'],
        };

        $period = $contract->start_date?->format('d M Y') . ($contract->end_date ? ' to ' . $contract->end_date->format('d M Y') : '');
        $this->toUser($contract->subscriber, new TerraAlert('subscription', $title,
            'Agreement ' . $contract->contract_number . ' (' . $period . '), ' . $this->money($contract->amount, $contract->currency) . ' per ' . match ($contract->billing_cycle) { 'annual' => 'year', 'quarterly' => 'quarter', default => 'month' } . '.',
            Route::has('subscription.show') ? route('subscription.show') : null,
            ['icon' => 'bi-patch-check', 'tone' => $tone, 'action' => 'View subscription']));
    }

    public function subscriptionInvoiceIssued(\App\Models\SubscriptionInvoice $invoice): void
    {
        $this->toUser($invoice->subscriber, new TerraAlert('subscription',
            'Subscription invoice ' . $invoice->invoice_number,
            $this->money($invoice->amount, $invoice->currency) . ' is due on ' . $invoice->due_date->format('d M Y') . '.',
            Route::has('subscription.show') ? route('subscription.show') : null,
            ['icon' => 'bi-receipt', 'tone' => 'info', 'action' => 'View invoice']));
    }

    public function subscriptionInvoiceOverdue(\App\Models\SubscriptionInvoice $invoice): void
    {
        $this->toUser($invoice->subscriber, new TerraAlert('subscription',
            'Subscription payment overdue',
            'Invoice ' . $invoice->invoice_number . ' (' . $this->money($invoice->balance, $invoice->currency) . ') was due on ' . $invoice->due_date->format('d M Y') . '. Please pay to keep your account active.',
            Route::has('subscription.show') ? route('subscription.show') : null,
            ['icon' => 'bi-exclamation-triangle', 'tone' => 'danger', 'action' => 'Pay now', 'sms' => true]));
    }

    public function subscriptionPaymentReceived(\App\Models\SubscriptionPayment $payment): void
    {
        $this->toUser($payment->subscriber, new TerraAlert('payment',
            'Subscription payment received',
            'We received ' . $this->money($payment->amount, $payment->currency) . ' by ' . strtolower($payment->method_label) . '. Thank you.',
            Route::has('subscription.show') ? route('subscription.show') : null,
            ['icon' => 'bi-check2-circle', 'tone' => 'success']));
    }

    /** Admins hear about new owners/managers signed up by field staff. */
    public function fieldRegistration(User $staff, string $kind, string $name, ?string $url): void
    {
        foreach (User::where('role', 'admin')->get() as $admin) {
            $this->toUser($admin, new TerraAlert('registration',
                'New ' . $kind . ' registered in the field',
                $staff->name . ' registered ' . $name . ' as a property ' . $kind . '.',
                $url,
                ['icon' => 'bi-person-plus', 'tone' => 'info', 'mail' => false]));
        }
    }

    /** Send one alert to one user, never breaking the caller. */
    public function toUser(?User $user, TerraAlert $alert): void
    {
        if ($user) {
            $this->safely(fn () => $user->notify($alert));
        }
    }

    /* =====================================================================
     |  Delivery helpers
     * ===================================================================== */

    /** Run a callback only once per key within $days (stops duplicate reminders). */
    public function once(string $key, int $days, callable $callback): bool
    {
        if (! Cache::add('terra-notify:' . $key, true, now()->addDays($days))) {
            return false;
        }

        $callback();

        return true;
    }

    private function toTenant(?Tenant $tenant, callable $make): void
    {
        if (! $tenant) {
            return;
        }

        if ($tenant->user) {
            $this->safely(fn () => $tenant->user->notify($make($tenant->user)));
        } elseif ($tenant->email) {
            // Tenants registered by staff without a login still get the email.
            $this->safely(fn () => Notification::route('mail', $tenant->email)->notify($make(null)));
        }
    }

    private function toStaff(?Unit $unit, callable $make): void
    {
        $propertyId = $this->audience->propertyIdForUnit($unit);

        foreach ($this->audience->staffUsers($propertyId) as $user) {
            if ($user->id === auth()->id()) {
                continue; // don't tell people about things they just did
            }
            $this->safely(fn () => $user->notify($make($user)));
        }
    }

    /** A notification failure must never break the business action that triggered it. */
    private function safely(callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            Log::warning('Terra notification failed: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    /** Link to the record in the recipient's own portal. */
    public function url(?User $user, string $kind, $model): ?string
    {
        if (! $user) {
            return Route::has('login') ? route('login') : url('/');
        }

        $map = match (true) {
            $user->isTenant() => [
                'invoice' => 'tenant-portal.rent-invoices.show',
                'payment' => 'tenant-portal.payments.show',
                'lease' => 'tenant-portal.leases.show',
                'maintenance' => 'tenant-portal.maintenance-requests.show',
                'application' => 'tenant-portal.applications.show',
            ],
            $user->isAdmin() => [
                'invoice' => 'admin.rent-invoices.show',
                'payment' => 'admin.payments.show',
                'lease' => 'admin.leases.show',
                'application' => 'admin.tenant-applications.show',
            ],
            default => [
                'invoice' => 'property-manager.invoices.show',
                'payment' => 'property-manager.payments.show',
                'lease' => 'property-manager.leases.show',
                'maintenance' => 'property-manager.maintenance-requests.show',
                'application' => 'property-manager.applications.show',
            ],
        };

        $name = $map[$kind] ?? null;

        return $name && Route::has($name) ? route($name, $model) : null;
    }

    private function money($amount, ?string $currency): string
    {
        $currency = $currency ?: 'RWF';

        return $currency . ' ' . number_format((float) $amount, $currency === 'RWF' ? 0 : 2);
    }

    private function place(?Unit $unit): string
    {
        if (! $unit) {
            return 'your unit';
        }

        $property = $unit->building?->property?->name;

        return 'Unit ' . $unit->unit_number . ($property ? ', ' . $property : '');
    }
}

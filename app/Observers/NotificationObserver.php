<?php

namespace App\Observers;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\TenantApplication;
use App\Models\Unit;
use App\Services\Notifier;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

/**
 * Turns domain changes into alerts. Registered for several models in
 * AppServiceProvider; runs after the surrounding DB transaction commits,
 * so a rolled-back action never produces a notification.
 */
class NotificationObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private Notifier $notifier)
    {
    }

    public function created(Model $model): void
    {
        match (true) {
            $model instanceof RentPayment => $model->status === 'confirmed' && $this->notifier->paymentConfirmed($model),
            $model instanceof RentInvoice => $model->status !== 'cancelled' && $this->notifier->invoiceIssued($model),
            $model instanceof MaintenanceRequest => $this->notifier->maintenanceSubmitted($model),
            $model instanceof TenantApplication => $this->notifier->applicationReceived($model),
            $model instanceof Lease => $this->leaseStatus($model),
            default => null,
        };
    }

    public function updated(Model $model): void
    {
        if (! $model->wasChanged('status')) {
            return;
        }

        $from = $model->getOriginal('status');
        $to = $model->status;

        match (true) {
            $model instanceof RentPayment => $to === 'confirmed' && $this->notifier->paymentConfirmed($model),
            $model instanceof MaintenanceRequest => $this->notifier->maintenanceUpdated($model, $from, $to),
            $model instanceof TenantApplication => in_array($to, ['approved', 'rejected'], true) && $this->notifier->applicationDecided($model),
            $model instanceof Lease => $this->leaseStatus($model),
            $model instanceof Unit => $to === 'available' && in_array($from, ['occupied', 'reserved'], true) && $this->notifier->unitVacant($model),
            default => null,
        };
    }

    private function leaseStatus(Lease $lease): void
    {
        $event = [
            'pending_tenant_acceptance' => 'sent',
            'pending_activation' => 'accepted',
            'active' => 'activated',
            'terminated' => 'terminated',
        ][$lease->status] ?? null;

        if ($event) {
            $this->notifier->leaseEvent($lease, $event);
        }
    }
}

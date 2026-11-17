{{--
    Lease workflow checklist + actions (draft → awaiting tenant → awaiting activation → active).
    Requires $lease and $routes = ['send', 'recall', 'invoices', 'moveIn', 'activate', 'edit'?] route names,
    plus $method (HTTP verb the portal uses for state changes: 'PATCH' or 'POST').
--}}
@php
    $lifecycle = app(\App\Services\LeaseLifecycle::class);
    $verb = $method ?? 'POST';
    $status = $lease->status;
    $cur = $lease->currency ?: 'RWF';
    $required = $lease->invoices()->whereIn('invoice_type', ['deposit', 'rent'])->get();
    $depositInv = $required->firstWhere('invoice_type', 'deposit');
    $rentInv = $required->firstWhere('invoice_type', 'rent');
    $needsDeposit = (float) $lease->deposit_amount > 0;
    $invoicesReady = $rentInv && (! $needsDeposit || $depositInv);
    $invoicesPaid = $invoicesReady && $required->sum(fn ($i) => (float) $i->balance_due) <= 0;
    $started = ! $lease->start_date?->isFuture();
    $unitStatus = $lease->unit?->status;
    $check = fn ($ok) => $ok ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-circle text-secondary"></i>';
@endphp

@if(in_array($status, ['draft', 'pending_tenant_acceptance', 'pending_activation'], true))
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0" style="color:#19265d;"><i class="bi bi-signpost-split me-1"></i> Lease workflow</h6>
            <span class="badge rounded-pill {{ ['draft' => 'text-bg-light border', 'pending_tenant_acceptance' => 'text-bg-warning', 'pending_activation' => 'text-bg-info'][$status] }}">
                {{ \App\Services\LeaseLifecycle::LABELS[$status] }}
            </span>
        </div>
        <p class="small text-secondary mb-3"><strong>Next:</strong> {{ $lifecycle->nextStep($lease) }}</p>

        @error('lease')<div class="alert alert-danger small py-2">{{ $message }}</div>@enderror

        <ol class="list-unstyled small mb-3 d-grid gap-1">
            <li>{!! $check($status !== 'draft') !!} Sent to the tenant
                @if($status !== 'draft' && $unitStatus)<span class="text-secondary">· unit {{ str_replace('_', ' ', $unitStatus) }}</span>@endif</li>
            <li>{!! $check((bool) $lease->tenant_accepted_at) !!} Tenant accepted
                @if($lease->tenant_accepted_at)<span class="text-secondary">· {{ $lease->tenant_accepted_at->format('d M Y') }}</span>@endif</li>
            <li>{!! $check($invoicesReady) !!} Deposit{{ $needsDeposit ? '' : ' (none)' }} &amp; first rent invoiced</li>
            <li>{!! $check($invoicesPaid) !!} Deposit &amp; first rent paid
                @if($invoicesReady && ! $invoicesPaid)<span class="text-danger">· {{ $cur }} {{ number_format($required->sum(fn ($i) => (float) $i->balance_due)) }} outstanding</span>@endif</li>
            <li>{!! $check((bool) $lease->move_in_confirmed_at) !!} Move-in confirmed</li>
            <li>{!! $check($started) !!} Start date reached <span class="text-secondary">· {{ $lease->start_date?->format('d M Y') }}</span></li>
        </ol>

        @if($status === 'draft')
            <form method="POST" action="{{ route($routes['send'], $lease) }}" onsubmit="return confirm('Send this lease to the tenant? The terms are locked and the unit is reserved until they respond.')">
                @csrf @if($verb !== 'POST') @method($verb) @endif
                <button class="btn btn-sm w-100 rounded-pill text-white" style="background:#D05208;"><i class="bi bi-send me-1"></i> Send to tenant</button>
            </form>
            @if(! empty($routes['edit']))<a href="{{ route($routes['edit'], $lease) }}" class="btn btn-sm btn-light w-100 rounded-pill mt-2">Edit terms</a>@endif
        @elseif($status === 'pending_tenant_acceptance')
            <form method="POST" action="{{ route($routes['recall'], $lease) }}" onsubmit="return confirm('Recall the lease to draft? The tenant will no longer be able to accept it until you send it again.')">
                @csrf @if($verb !== 'POST') @method($verb) @endif
                <button class="btn btn-sm btn-outline-secondary w-100 rounded-pill"><i class="bi bi-arrow-counterclockwise me-1"></i> Recall to edit terms</button>
            </form>
        @elseif($status === 'pending_activation')
            <div class="d-grid gap-2">
                @unless($invoicesReady)
                    <form method="POST" action="{{ route($routes['invoices'], $lease) }}">@csrf
                        <button class="btn btn-sm btn-dark w-100 rounded-pill"><i class="bi bi-receipt me-1"></i> Generate deposit &amp; first rent invoices</button></form>
                @endunless
                @unless($lease->move_in_confirmed_at)
                    <form method="POST" action="{{ route($routes['moveIn'], $lease) }}" onsubmit="return confirm('Confirm the tenant has moved in (keys handed over)?')">
                        @csrf @if($verb !== 'POST') @method($verb) @endif
                        <button class="btn btn-sm btn-outline-dark w-100 rounded-pill"><i class="bi bi-key me-1"></i> Confirm move-in</button></form>
                @endunless
                <form method="POST" action="{{ route($routes['activate'], $lease) }}" onsubmit="return confirm('Activate this lease? The unit will be marked occupied.')">
                    @csrf @if($verb !== 'POST') @method($verb) @endif
                    <button class="btn btn-sm btn-success w-100 rounded-pill" @disabled(! ($invoicesPaid && $lease->move_in_confirmed_at && $started))>
                        <i class="bi bi-check-circle me-1"></i> Activate lease</button>
                </form>
                @if($invoicesReady && ! $invoicesPaid)
                    <p class="small text-secondary mb-0">The tenant can pay online from their invoices, or record payments under Rent Payments.</p>
                @endif
            </div>
        @endif
    </div>
</div>
@endif

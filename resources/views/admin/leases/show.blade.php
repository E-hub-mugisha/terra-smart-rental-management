@extends('layouts.app')

@section('title', 'Manage ' . $lease->lease_number)

@push('styles')
<style>
/* Lease details: compact type scale, scoped under .ls so nothing leaks into the layout.
   Tip: if your top navbar is fixed, set --ls-sticky on .ls to its height. */
.ls {
    --o: #D05208; --o-dark: #b94706; --o-soft: #fff2e9; --o-line: #f4d3bf;
    --navy: #19265d; --ink: #27334a; --mute: #6b778c;
    --line: #e6e9f0; --line-soft: #eff1f6; --wash: #f6f7fb;
    --ok: #147847; --ok-bg: #e8f7ee; --ok-line: #cfeedb;
    --warn: #8f5a08; --warn-bg: #fff3dc; --warn-line: #f3dfb0;
    --bad: #b42318; --bad-bg: #fff1f0; --bad-line: #f3cdc9;
    --info: #116a9c; --info-bg: #e7f4fd;
    --ls-sticky: 0px;
    color: var(--ink); font-size: .8rem; line-height: 1.5; padding-bottom: 36px;
}
.ls *, .ls *::before, .ls *::after { box-sizing: border-box; }
.ls :focus-visible { outline: 2px solid var(--o); outline-offset: 2px; border-radius: 6px; }
.ls .min-w-0 { min-width: 0; }
.ls .is-mute { color: var(--mute); }

.ls-back { display: inline-flex; align-items: center; gap: 6px; margin-bottom: 10px; color: var(--mute); font-size: .76rem; font-weight: 600; text-decoration: none; }
.ls-back:hover { color: var(--o); }
.ls-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 14px; margin-bottom: 18px; }
.ls-title { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin: 0 0 4px; color: var(--navy); font-size: clamp(1.25rem, 2.2vw, 1.55rem); font-weight: 800; letter-spacing: -.03em; }
.ls-sub { margin: 0; color: var(--mute); }

.ls-pill { display: inline-flex; align-items: center; gap: 5px; width: fit-content; padding: 3px 9px; border-radius: 999px; background: var(--wash); color: var(--mute); font-size: .7rem; font-weight: 700; white-space: nowrap; letter-spacing: 0; }
.ls-pill::before { content: ""; width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.ls-pill.is-success { background: var(--ok-bg); color: var(--ok); }
.ls-pill.is-info { background: var(--info-bg); color: var(--info); }
.ls-pill.is-warning { background: var(--warn-bg); color: var(--warn); }
.ls-pill.is-danger { background: var(--bad-bg); color: var(--bad); }
.ls-pill.is-dark { background: #e9ebf0; color: #363f52; }

.ls-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 32px; padding: 5px 12px;
    border: 1px solid var(--line); border-radius: 8px; background: #fff; color: var(--navy);
    font-size: .76rem; font-weight: 700; line-height: 1.2; text-decoration: none; white-space: nowrap; cursor: pointer;
    transition: background .15s, border-color .15s, color .15s; }
.ls-btn:hover { background: var(--o-soft); border-color: var(--o-line); color: var(--o); }
.ls-btn.is-block { width: 100%; min-height: 36px; }
.ls-btn.is-ok { background: var(--ok); border-color: var(--ok); color: #fff; }
.ls-btn.is-ok:hover { background: #0f6339; border-color: #0f6339; color: #fff; }
.ls-btn.is-ghost-ok { color: var(--ok); border-color: var(--ok-line); }
.ls-btn.is-ghost-ok:hover { background: var(--ok-bg); color: var(--ok); border-color: #b7e3c8; }
.ls-btn.is-xs { min-height: 26px; padding: 3px 9px; font-size: .72rem; border-radius: 7px; }
.ls-btn:disabled { opacity: .6; pointer-events: none; }

.ls-alert { display: flex; gap: 9px; align-items: flex-start; margin-bottom: 14px; padding: 10px 13px; border-radius: 10px; font-weight: 600; }
.ls-alert.is-ok { background: var(--ok-bg); color: var(--ok); border: 1px solid var(--ok-line); }
.ls-alert.is-bad { background: var(--bad-bg); color: var(--bad); border: 1px solid var(--bad-line); }
.ls-alert.is-warn { background: var(--warn-bg); color: var(--warn); border: 1px solid var(--warn-line); }
.ls-alert.is-plain { background: var(--wash); color: var(--mute); border: 1px solid var(--line); }
.ls-alert ul { margin: 3px 0 0; padding-left: 16px; font-weight: 500; }
.ls-alert .btn-close { margin-left: auto; font-size: .6rem; }

.ls-panel { min-width: 0; background: #fff; border: 1px solid var(--line); border-radius: 14px; }
.ls-panel + .ls-panel { margin-top: 16px; }
.ls-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; padding: 13px 16px; border-bottom: 1px solid var(--line); }
.ls-panel-title { margin: 0; color: var(--navy); font-size: .88rem; font-weight: 800; letter-spacing: -.01em; }
.ls-panel-note { margin: 2px 0 0; color: var(--mute); font-size: .72rem; }
.ls-panel-body { padding: 16px; }

/* Key figures */
.ls-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 16px; background: #fff; border: 1px solid var(--line); border-radius: 14px; }
.ls-strip > div { min-width: 0; padding: 13px 16px; border-left: 1px solid var(--line); }
.ls-strip > div:first-child { border-left: 0; }
.ls-strip dt { color: var(--mute); font-size: .72rem; font-weight: 700; }
.ls-strip dd { margin: 5px 0 0; color: var(--navy); font-size: 1.05rem; font-weight: 800; letter-spacing: -.02em; overflow-wrap: anywhere; }
.ls-strip dd.is-bad { color: var(--bad); }
.ls-strip dd.is-ok { color: var(--ok); }
.ls-strip dd small { display: block; margin-top: 2px; color: var(--mute); font-size: .7rem; font-weight: 600; letter-spacing: 0; }

/* Progress stepper */
.ls-steps { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0; margin: 0 0 16px; padding: 14px 16px; list-style: none; background: #fff; border: 1px solid var(--line); border-radius: 14px; }
.ls-step { position: relative; display: flex; align-items: center; gap: 9px; min-width: 0; padding-right: 10px; }
.ls-step::after { content: ""; position: absolute; left: 34px; right: 10px; top: 50%; height: 2px; background: var(--line); z-index: 0; }
.ls-step:last-child::after { display: none; }
.ls-step.is-done::after { background: var(--ok); }
.ls-dot { position: relative; z-index: 1; display: grid; place-items: center; flex: 0 0 26px; width: 26px; height: 26px; border-radius: 50%; background: var(--wash); border: 2px solid var(--line); color: var(--mute); font-size: .7rem; font-weight: 800; }
.ls-step.is-done .ls-dot { background: var(--ok); border-color: var(--ok); color: #fff; }
.ls-step.is-current .ls-dot { background: #fff; border-color: var(--o); color: var(--o); }
.ls-step-text { position: relative; z-index: 1; min-width: 0; padding-right: 8px; background: #fff; line-height: 1.3; }
.ls-step-text b { display: block; color: var(--navy); font-size: .74rem; font-weight: 800; }
.ls-step-text span { color: var(--mute); font-size: .68rem; }
.ls-step.is-current .ls-step-text span { color: var(--o); font-weight: 700; }

/* Detail grid */
.ls-dl { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin: 0; border: 1px solid var(--line-soft); border-radius: 10px; overflow: hidden; }
.ls-dl > div { margin: -.5px; padding: 10px 13px; border-right: 1px solid var(--line-soft); border-bottom: 1px solid var(--line-soft); }
.ls-dl dt { margin-bottom: 2px; color: var(--mute); font-size: .7rem; font-weight: 700; }
.ls-dl dd { margin: 0; color: var(--ink); font-weight: 650; overflow-wrap: anywhere; }
.ls-dl dd small { display: block; color: var(--mute); font-weight: 500; font-size: .72rem; }
.ls-dl dd.is-empty { color: var(--mute); font-weight: 500; }
.ls-terms { margin: 0; max-width: 80ch; line-height: 1.7; white-space: pre-line; overflow-wrap: anywhere; }

/* Invoices */
.ls-table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.ls-table { width: 100%; min-width: 760px; margin: 0; border-collapse: collapse; font-size: .78rem; }
.ls-table th { padding: 8px 14px; background: var(--wash); border-bottom: 1px solid var(--line); color: var(--mute); font-size: .7rem; font-weight: 700; text-align: left; white-space: nowrap; }
.ls-table td { padding: 10px 14px; border-bottom: 1px solid var(--line-soft); vertical-align: middle; }
.ls-table .t-end { text-align: right; }
.ls-table tbody tr.ls-inv:hover td { background: #fcfcfe; }
.ls-table tr.ls-alloc > td { padding: 0; background: var(--wash); }
.ls-alloc-inner { padding: 10px 14px 12px; }
.ls-alloc-inner table { width: 100%; font-size: .74rem; border-collapse: collapse; }
.ls-alloc-inner th { padding: 4px 10px 4px 0; background: none; border-bottom: 1px solid var(--line); text-transform: none; }
.ls-alloc-inner td { padding: 5px 10px 5px 0; border-bottom: 1px dashed var(--line); background: none; }
.ls-alloc-inner tr:last-child td { border-bottom: 0; }
.ls-strong { color: var(--navy); font-weight: 800; }
.ls-note { color: var(--mute); font-size: .72rem; }
.ls-note.is-bad { color: var(--bad); font-weight: 700; }
.ls-bar { width: 64px; height: 4px; margin-top: 4px; overflow: hidden; border-radius: 9px; background: #edf0f5; }
.ls-bar i { display: block; height: 100%; background: var(--ok); border-radius: 9px; }
.ls-tog i { transition: transform .2s; }
.ls-tog[aria-expanded="true"] i { transform: rotate(180deg); }

/* Sidebar */
.ls-side { position: sticky; top: calc(var(--ls-sticky) + 16px); }
.ls-check { display: flex; gap: 10px; padding: 11px 0; border-bottom: 1px solid var(--line-soft); }
.ls-check:first-child { padding-top: 0; }
.ls-check-icon { display: grid; place-items: center; flex: 0 0 22px; width: 22px; height: 22px; margin-top: 1px; border-radius: 50%; background: var(--warn-bg); color: var(--warn); font-size: .72rem; }
.ls-check.is-done .ls-check-icon { background: var(--ok-bg); color: var(--ok); }
.ls-check b { display: block; color: var(--navy); font-size: .78rem; font-weight: 800; }
.ls-check p { margin: 2px 0 0; color: var(--mute); font-size: .73rem; }
.ls-actions { display: grid; gap: 8px; margin-top: 16px; }
.ls-hint { margin: 12px 0 0; color: var(--mute); font-size: .72rem; line-height: 1.6; }

@media (max-width: 1199.98px) { .ls-side { position: static; } }
@media (max-width: 991.98px) {
    .ls-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ls-strip > div:nth-child(3) { border-left: 0; }
    .ls-strip > div:nth-child(n+3) { border-top: 1px solid var(--line); }
    .ls-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 0; }
    .ls-step:nth-child(2)::after { display: none; }
}
@media (max-width: 575.98px) {
    .ls-panel-head, .ls-panel-body { padding: 13px; }
    .ls-strip > div { padding: 11px 12px; }
    .ls-steps { grid-template-columns: 1fr; }
    .ls-step::after { display: none; }
}
@media (prefers-reduced-motion: reduce) { .ls *, .ls *::before, .ls *::after { transition-duration: .01ms !important; } }
</style>
@endpush

@section('content')
@php
    use Illuminate\Support\Str;

    $status = $lease->status;

    $pillFor = fn ($s) => match ($s) {
        'active' => 'is-success',
        'pending_activation' => 'is-info',
        'pending_tenant_acceptance' => 'is-warning',
        'rejected', 'cancelled' => 'is-danger',
        'terminated' => 'is-dark',
        default => '',
    };
    $statusLabel = fn ($s) => $s === 'pending_tenant_acceptance' ? 'Awaiting acceptance' : Str::headline((string) $s);

    $money = fn ($cur, $amount) => ($cur ?: 'RWF') . ' ' . number_format((float) $amount, ($cur ?: 'RWF') === 'RWF' ? 0 : 2);

    // Payments that count are confirmed ones only
    $paidOf    = fn ($inv) => (float) $inv->allocations
        ->filter(fn ($a) => $a->payment && $a->payment->status === 'confirmed')
        ->sum('amount');
    $balanceOf = fn ($inv) => max(0, (float) $inv->total_amount - $paidOf($inv));

    /* ---- Activation readiness (computed once, used by stepper and sidebar) ---- */
    $requiredInvoices   = $invoices->whereIn('invoice_type', ['deposit', 'rent']);
    $hasRequiredRent    = $requiredInvoices->contains(fn ($i) => $i->invoice_type === 'rent');
    $hasRequiredDeposit = (float) $lease->deposit_amount <= 0 || $requiredInvoices->contains(fn ($i) => $i->invoice_type === 'deposit');
    $invoicesCreated    = $hasRequiredRent && $hasRequiredDeposit;
    $allRequiredPaid    = $invoicesCreated && $requiredInvoices->every(fn ($i) => $balanceOf($i) <= 0);

    $accepted  = (bool) $lease->tenant_accepted_at;
    $movedIn   = (bool) $lease->move_in_confirmed_at;
    $isActive  = $status === 'active';
    $ready     = $accepted && $allRequiredPaid && $movedIn;

    $showStepper = in_array($status, ['draft', 'pending_tenant_acceptance', 'pending_activation', 'active'], true);
    $steps = [
        ['Tenant accepts',   $accepted || $isActive,           $accepted ? $lease->tenant_accepted_at->format('d M Y') : 'Waiting on tenant'],
        ['Invoices paid',    $allRequiredPaid || $isActive,    $allRequiredPaid ? 'Fully paid' : ($invoicesCreated ? 'Awaiting payment' : 'Not generated')],
        ['Move-in confirmed', $movedIn || $isActive,           $movedIn ? $lease->move_in_confirmed_at->format('d M Y') : 'Not confirmed'],
        ['Lease active',     $isActive,                        $isActive && $lease->activated_at ? $lease->activated_at->format('d M Y') : 'Not activated'],
    ];
    $currentStep = collect($steps)->search(fn ($s) => !$s[1]); // first step not done

    $outstanding = (float) $invoiceSummary['balance'];
    $end         = $lease->end_date;
    $daysLeft    = ($isActive && $end) ? (int) now()->startOfDay()->diffInDays($end->copy()->startOfDay(), false) : null;
@endphp

<div class="ls container-fluid py-4">

    <a href="{{ route('admin.leases.index') }}" class="ls-back"><i class="bi bi-arrow-left"></i> Back to leases</a>

    <div class="ls-head">
        <div>
            <h1 class="ls-title">
                {{ $lease->lease_number }}
                <span class="ls-pill {{ $pillFor($status) }}">{{ $statusLabel($status) }}</span>
            </h1>
            <p class="ls-sub">
                {{ collect([
                    $lease->tenant?->full_name ?? 'Tenant unavailable',
                    $lease->unit?->building?->name,
                    $lease->unit ? 'Unit ' . $lease->unit->unit_number : null,
                ])->filter()->implode(', ') }}
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-dismissible fade show p-0 border-0 bg-transparent mb-0" role="status">
            <div class="ls-alert is-ok w-100">
                <i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-dismissible fade show p-0 border-0 bg-transparent mb-0" role="alert">
            <div class="ls-alert is-bad w-100">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                    That action couldn't be completed:
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
            </div>
        </div>
    @endif

    {{-- Key figures --}}
    <dl class="ls-strip">
        <div>
            <dt>Monthly rent</dt>
            <dd>{{ $money($lease->currency, $lease->monthly_rent) }}<small>{{ Str::headline($lease->payment_frequency ?? 'monthly') }} payments</small></dd>
        </div>
        <div>
            <dt>Required deposit</dt>
            <dd>{{ $money($lease->currency, $lease->deposit_amount) }}</dd>
        </div>
        <div>
            <dt>Total invoiced</dt>
            <dd>{{ $money($lease->currency, $invoiceSummary['total']) }}<small>{{ $invoices->count() }} {{ Str::plural('invoice', $invoices->count()) }}</small></dd>
        </div>
        <div>
            <dt>Outstanding balance</dt>
            <dd class="{{ $outstanding > 0 ? 'is-bad' : 'is-ok' }}">{{ $money($lease->currency, $outstanding) }}<small>{{ $outstanding > 0 ? 'Still to be paid' : 'Nothing owed' }}</small></dd>
        </div>
    </dl>

    {{-- Progress --}}
    @if($showStepper)
        <ol class="ls-steps" aria-label="Lease progress">
            @foreach($steps as $i => [$stepName, $done, $stepNote])
                <li class="ls-step {{ $done ? 'is-done' : ($i === $currentStep ? 'is-current' : '') }}" @if($i === $currentStep) aria-current="step" @endif>
                    <span class="ls-dot">@if($done)<i class="bi bi-check-lg"></i>@else{{ $i + 1 }}@endif</span>
                    <span class="ls-step-text"><b>{{ $stepName }}</b><span>{{ $stepNote }}</span></span>
                </li>
            @endforeach
        </ol>
    @else
        <div class="ls-alert is-plain">
            <i class="bi bi-info-circle"></i>
            <span>This lease is {{ Str::lower($statusLabel($status)) }}, so it is no longer moving through activation.</span>
        </div>
    @endif

    <div class="row g-4">

        {{-- ================= MAIN ================= --}}
        <div class="col-xl-8">

            <section class="ls-panel">
                <div class="ls-panel-head">
                    <div>
                        <h2 class="ls-panel-title">Lease information</h2>
                        <p class="ls-panel-note">Parties, dates and milestones</p>
                    </div>
                </div>

                <div class="ls-panel-body">
                    <dl class="ls-dl mb-0">
                        <div>
                            <dt>Tenant</dt>
                            <dd class="{{ $lease->tenant?->full_name ? '' : 'is-empty' }}">
                                {{ $lease->tenant?->full_name ?? 'Not available' }}
                                @if($lease->tenant?->email)<small>{{ $lease->tenant->email }}</small>@endif
                            </dd>
                        </div>
                        <div>
                            <dt>Property</dt>
                            <dd class="{{ $lease->unit?->building?->name ? '' : 'is-empty' }}">
                                {{ $lease->unit?->building?->name ?? 'Not available' }}
                                <small>Unit {{ $lease->unit?->unit_number ?? 'N/A' }}</small>
                            </dd>
                        </div>
                        <div>
                            <dt>Start date</dt>
                            <dd class="{{ $lease->start_date ? '' : 'is-empty' }}">{{ $lease->start_date?->format('d M Y') ?? 'Not set' }}</dd>
                        </div>
                        <div>
                            <dt>End date</dt>
                            <dd class="{{ $end ? '' : 'is-empty' }}">
                                {{ $end?->format('d M Y') ?? 'Open-ended' }}
                                @if($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 60)
                                    <small style="color:var(--warn);font-weight:700;">Ends in {{ $daysLeft }} {{ Str::plural('day', $daysLeft) }}</small>
                                @elseif($daysLeft !== null && $daysLeft < 0)
                                    <small style="color:var(--bad);font-weight:700;">Ended {{ abs($daysLeft) }} {{ Str::plural('day', abs($daysLeft)) }} ago</small>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Tenant acceptance</dt>
                            <dd>
                                @if($accepted)
                                    <span class="ls-pill is-success">Accepted</span><small class="mt-1">{{ $lease->tenant_accepted_at->format('d M Y, H:i') }}</small>
                                @else
                                    <span class="ls-pill is-warning">Awaiting acceptance</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Move-in confirmation</dt>
                            <dd>
                                @if($movedIn)
                                    <span class="ls-pill is-success">Confirmed</span><small class="mt-1">{{ $lease->move_in_confirmed_at->format('d M Y, H:i') }}</small>
                                @else
                                    <span class="ls-pill">Not confirmed</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Lease activated</dt>
                            <dd>
                                @if($lease->activated_at)
                                    <span class="ls-pill is-success">Active</span><small class="mt-1">{{ $lease->activated_at->format('d M Y, H:i') }}</small>
                                @else
                                    <span class="ls-pill">Not activated</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Payment frequency</dt>
                            <dd>{{ Str::headline($lease->payment_frequency ?? 'monthly') }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            @if($lease->terms)
                <section class="ls-panel">
                    <div class="ls-panel-head">
                        <div>
                            <h2 class="ls-panel-title">Lease terms</h2>
                            <p class="ls-panel-note">As agreed with the tenant</p>
                        </div>
                    </div>
                    <div class="ls-panel-body"><p class="ls-terms">{{ $lease->terms }}</p></div>
                </section>
            @endif

            {{-- Invoices --}}
            <section class="ls-panel">
                <div class="ls-panel-head">
                    <div>
                        <h2 class="ls-panel-title">Invoices and payments</h2>
                        <p class="ls-panel-note">Only confirmed payments count towards a balance</p>
                    </div>

                    @if($status === 'pending_activation' && $accepted)
                        <form method="POST" action="{{ route('admin.leases.generate-required-invoices', $lease) }}" data-busy="Generating…">
                            @csrf
                            <button type="submit" class="ls-btn is-xs"><i class="bi bi-receipt"></i> Generate required invoices</button>
                        </form>
                    @endif
                </div>

                <div class="ls-table-wrap">
                    <table class="ls-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Type</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th class="t-end">Payments</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                @php
                                    $paid    = $paidOf($invoice);
                                    $balance = $balanceOf($invoice);
                                    $total   = (float) $invoice->total_amount;
                                    $percent = $total > 0 ? min(100, (int) round($paid / $total * 100)) : 0;
                                    $overdue = $balance > 0 && $invoice->due_date && $invoice->due_date->isPast();
                                    $allocCount = $invoice->allocations->count();
                                @endphp

                                <tr class="ls-inv">
                                    <td>
                                        <div class="ls-strong">{{ $invoice->invoice_number }}</div>
                                        <div class="ls-note {{ $overdue ? 'is-bad' : '' }}">
                                            Due {{ $invoice->due_date?->format('d M Y') ?? 'N/A' }}{{ $overdue ? ', overdue' : '' }}
                                        </div>
                                    </td>
                                    <td>{{ Str::headline($invoice->invoice_type) }}</td>
                                    <td>{{ $money($invoice->currency, $total) }}</td>
                                    <td style="color:var(--ok);">
                                        {{ $money($invoice->currency, $paid) }}
                                        <div class="ls-bar" aria-hidden="true"><i style="width: {{ $percent }}%"></i></div>
                                    </td>
                                    <td style="color: {{ $balance > 0 ? 'var(--bad)' : 'var(--ok)' }}; font-weight:700;">{{ $money($invoice->currency, $balance) }}</td>
                                    <td>
                                        <span class="ls-pill {{ $balance <= 0 ? 'is-success' : ($paid > 0 ? 'is-warning' : '') }}">
                                            {{ $balance <= 0 ? 'Paid' : ($paid > 0 ? 'Partially paid' : 'Unpaid') }}
                                        </span>
                                    </td>
                                    <td class="t-end">
                                        @if($allocCount)
                                            <button type="button" class="ls-btn is-xs ls-tog" data-alloc="alloc-{{ $invoice->id }}" aria-expanded="false" aria-controls="alloc-{{ $invoice->id }}">
                                                {{ $allocCount }} {{ Str::plural('payment', $allocCount) }} <i class="bi bi-chevron-down"></i>
                                            </button>
                                        @else
                                            <span class="ls-note">None yet</span>
                                        @endif
                                    </td>
                                </tr>

                                @if($allocCount)
                                    <tr class="ls-alloc" id="alloc-{{ $invoice->id }}" hidden>
                                        <td colspan="7">
                                            <div class="ls-alloc-inner">
                                                <table>
                                                    <thead>
                                                        <tr><th>Payment reference</th><th>Date</th><th>Method</th><th>Payment status</th><th>Allocated</th></tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($invoice->allocations as $allocation)
                                                            @php $pStatus = $allocation->payment?->status; @endphp
                                                            <tr>
                                                                <td>{{ $allocation->payment?->transaction_reference ?: $allocation->payment?->payment_number ?: 'N/A' }}</td>
                                                                <td>{{ $allocation->allocated_at?->format('d M Y') ?? 'N/A' }}</td>
                                                                <td>{{ Str::headline($allocation->payment?->payment_method ?? 'N/A') }}</td>
                                                                <td><span class="ls-pill {{ $pStatus === 'confirmed' ? 'is-success' : ($pStatus ? 'is-warning' : '') }}">{{ ucfirst($pStatus ?? 'Unknown') }}</span></td>
                                                                <td>{{ $money($invoice->currency, $allocation->amount) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center is-mute" style="padding:30px 14px;">
                                        No invoices yet.
                                        @if($status === 'pending_activation' && $accepted) Use <strong>Generate required invoices</strong> to create the deposit and first rent invoices.
                                        @else Invoices are created once the tenant has accepted the lease. @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        {{-- ================= ACTIONS ================= --}}
        <div class="col-xl-4">
            <div class="ls-side">
                <section class="ls-panel">
                    <div class="ls-panel-head">
                        <div>
                            <h2 class="ls-panel-title">Lease activation</h2>
                            <p class="ls-panel-note">{{ $status === 'pending_activation' ? 'Finish the checklist, then activate' : 'Current stage of this lease' }}</p>
                        </div>
                        <span class="ls-pill {{ $pillFor($status) }}">{{ $statusLabel($status) }}</span>
                    </div>

                    <div class="ls-panel-body">

                        @if($status === 'draft')
                            <div class="ls-alert is-plain mb-0">
                                <i class="bi bi-pencil-square"></i>
                                <span style="font-weight:500;">This lease is still a draft. Submit it for tenant acceptance through your usual lease workflow.</span>
                            </div>

                        @elseif($status === 'pending_tenant_acceptance')
                            <div class="ls-alert is-warn mb-0">
                                <i class="bi bi-hourglass-split"></i>
                                <span style="font-weight:500;">The tenant hasn't accepted this lease yet. Activation unlocks once they do.</span>
                            </div>

                        @elseif($status === 'pending_activation')
                            <div class="ls-check {{ $accepted ? 'is-done' : '' }}">
                                <span class="ls-check-icon"><i class="bi {{ $accepted ? 'bi-check-lg' : 'bi-clock' }}"></i></span>
                                <div>
                                    <b>Tenant acceptance</b>
                                    <p>{{ $accepted ? 'Accepted on ' . $lease->tenant_accepted_at->format('d M Y') : 'Waiting for the tenant to accept.' }}</p>
                                </div>
                            </div>

                            <div class="ls-check {{ $allRequiredPaid ? 'is-done' : '' }}">
                                <span class="ls-check-icon"><i class="bi {{ $allRequiredPaid ? 'bi-check-lg' : 'bi-clock' }}"></i></span>
                                <div>
                                    <b>Deposit and first rent paid</b>
                                    <p>
                                        @if($allRequiredPaid) All required invoices are fully paid.
                                        @elseif(!$invoicesCreated) The required invoices haven't been generated yet.
                                        @else Record and confirm payments in your finance workflow. @endif
                                    </p>

                                    @if(!$invoicesCreated && $accepted)
                                        <form method="POST" action="{{ route('admin.leases.generate-required-invoices', $lease) }}" class="mt-2" data-busy="Generating…">
                                            @csrf
                                            <button type="submit" class="ls-btn is-xs"><i class="bi bi-receipt"></i> Generate invoices</button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="ls-check {{ $movedIn ? 'is-done' : '' }}">
                                <span class="ls-check-icon"><i class="bi {{ $movedIn ? 'bi-check-lg' : 'bi-clock' }}"></i></span>
                                <div>
                                    <b>Move-in confirmed</b>
                                    <p>{{ $movedIn ? 'Confirmed on ' . $lease->move_in_confirmed_at->format('d M Y') : 'Confirm once the tenant is ready to move in.' }}</p>
                                </div>
                            </div>

                            <div class="ls-actions">
                                @if(!$movedIn)
                                    <form method="POST" action="{{ route('admin.leases.confirm-move-in', $lease) }}"
                                          data-confirm="Confirm that the tenant is ready to move in?" data-busy="Confirming…">
                                        @csrf
                                        <button type="submit" class="ls-btn is-ghost-ok is-block"><i class="bi bi-box-arrow-in-right"></i> Confirm move-in</button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.leases.activate', $lease) }}"
                                      data-confirm="Activate this lease? The system will verify all required conditions." data-busy="Activating…">
                                    @csrf
                                    <button type="submit" class="ls-btn is-block {{ $ready ? 'is-ok' : '' }}">
                                        <i class="bi bi-check-circle"></i> Activate lease
                                    </button>
                                </form>
                            </div>

                            <p class="ls-hint">
                                @if($ready)
                                    Everything looks ready. The server will still recheck payments, move-in, acceptance, the start date and conflicting active leases.
                                @else
                                    Some steps are still open, so activation will probably be refused. The server rechecks payments, move-in, acceptance, the start date and conflicting active leases.
                                @endif
                            </p>

                        @elseif($status === 'active')
                            <div class="ls-alert is-ok mb-0">
                                <i class="bi bi-check-circle-fill"></i>
                                <div>
                                    This lease is active.
                                    @if($lease->activated_at)<div style="font-weight:500;">Activated {{ $lease->activated_at->format('d M Y, H:i') }}</div>@endif
                                </div>
                            </div>

                        @else
                            <div class="ls-alert is-plain mb-0">
                                <i class="bi bi-info-circle"></i>
                                <span style="font-weight:500;">No activation actions are available for a {{ Str::lower($statusLabel($status)) }} lease.</span>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ---- Show or hide a payment history row ---- */
    document.querySelectorAll('[data-alloc]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = document.getElementById(btn.dataset.alloc);
            if (!row) return;
            var open = row.hidden;
            row.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    /* ---- Confirm if asked, then lock the button so a double click can't submit twice ---- */
    document.querySelectorAll('.ls form[data-busy]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
                event.preventDefault();
                return;
            }
            var submit = form.querySelector('[type="submit"]');
            if (submit) {
                // Disable after the browser has collected the form data
                setTimeout(function () {
                    submit.disabled = true;
                    submit.textContent = form.dataset.busy;
                }, 0);
            }
        });
    });
});
</script>
@endsection
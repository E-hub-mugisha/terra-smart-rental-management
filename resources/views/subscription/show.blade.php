@extends('layouts.app')

@section('title', 'My subscription')
@section('page_heading', 'My Terra subscription')
@section('page_subtitle', 'Your agreement with Terra, bills and payments.')

@use('App\Support\Money')
@use('App\Models\SubscriptionContract')
@include('partials.sx-styles')

@section('content')
@if($overdue > 0)
    <div class="alert alert-danger d-flex align-items-center gap-2"><i class="bi bi-exclamation-triangle"></i>
        <div>You have {{ Money::format($overdue, $current?->currency) }} overdue. Please pay as soon as possible to keep full access, or <a href="{{ $support ? route('messages.create', ['to' => $support->id]) : route('messages.index') }}">message Terra</a> if you need help.</div></div>
@endif

@if($current)
    <div class="sx-card mb-3">
        <div class="sx-card-body d-flex flex-wrap justify-content-between gap-3 align-items-center">
            <div>
                <div class="sx-muted small">{{ $current->contract_number }}</div>
                <h2 class="h5 mb-1" style="color:var(--terra-navy,#19265d)">{{ $current->plan?->name ? $current->plan->name . ' plan' : $current->title }}</h2>
                <div class="small">
                    <span class="sx-pill {{ $current->status_tone }}">{{ $current->status_label }}</span>
                    <span class="sx-muted ms-1">{{ $current->start_date->format('d M Y') }} – {{ $current->end_date?->format('d M Y') ?? 'open-ended' }}</span>
                    @if($current->auto_renew)<span class="sx-muted"> · renews automatically</span>@endif
                </div>
            </div>
            <div class="text-end">
                <div class="sx-value" style="font-size:1.4rem;font-weight:700;color:var(--terra-navy,#19265d)">{{ Money::format($current->amount, $current->currency) }}</div>
                <div class="sx-muted small">per {{ ['annual' => 'year', 'quarterly' => 'quarter'][$current->billing_cycle] ?? 'month' }}</div>
            </div>
        </div>
    </div>
@else
    <div class="sx-card mb-3"><div class="sx-empty"><i class="bi bi-patch-question"></i>You don't have a subscription agreement yet. Terra will share one with you.</div></div>
@endif

<div class="sx-stats">
    <div class="sx-stat sx-warn"><div class="sx-label">Balance due</div><div class="sx-value">{{ Money::format($outstanding, $current?->currency) }}</div></div>
    <div class="sx-stat {{ $overdue > 0 ? 'sx-danger' : 'sx-ok' }}"><div class="sx-label">Overdue</div><div class="sx-value">{{ Money::format($overdue, $current?->currency) }}</div></div>
    <div class="sx-stat"><div class="sx-label">Paid to date</div><div class="sx-value">{{ Money::format($payments->sum('amount'), $current?->currency) }}</div></div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="sx-card mb-3">
            <div class="sx-card-head"><h2>Invoices</h2></div>
            <div class="table-responsive">
                <table class="sx-table">
                    <thead><tr><th>Invoice</th><th>Period</th><th>Due</th><th class="sx-num">Amount</th><th class="sx-num">Balance</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($invoices as $i)
                        <tr>
                            <td><strong>{{ $i->invoice_number }}</strong></td>
                            <td class="small">{{ $i->period_start?->format('d M') }} – {{ $i->period_end?->format('d M Y') }}</td>
                            <td class="small">{{ $i->due_date->format('d M Y') }}</td>
                            <td class="sx-num">{{ Money::format($i->amount, $i->currency) }}</td>
                            <td class="sx-num"><strong>{{ Money::format($i->balance, $i->currency) }}</strong></td>
                            <td><span class="sx-pill {{ $i->status_tone }}">{{ $i->display_status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="sx-empty"><i class="bi bi-receipt"></i>No invoices yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="sx-card">
            <div class="sx-card-head"><h2>Payment history</h2></div>
            <div class="table-responsive">
                <table class="sx-table">
                    <thead><tr><th>Date</th><th>Invoice</th><th>Method</th><th>Reference</th><th class="sx-num">Amount</th></tr></thead>
                    <tbody>
                    @forelse($payments as $p)
                        <tr>
                            <td class="small">{{ $p->paid_at->format('d M Y') }}</td>
                            <td class="small">{{ $p->invoice?->invoice_number ?? 'Credit' }}</td>
                            <td class="small">{{ $p->method_label }}</td>
                            <td class="small">{{ $p->reference ?: '—' }}</td>
                            <td class="sx-num"><strong>{{ Money::format($p->amount, $p->currency) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="sx-empty"><i class="bi bi-cash"></i>No payments yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="sx-card mb-3">
            <div class="sx-card-head"><h2>Agreements</h2></div>
            <ul class="list-unstyled m-0 p-3">
                @forelse($contracts as $c)
                    <li class="py-2 border-bottom">
                        <div class="d-flex justify-content-between"><strong class="small">{{ $c->contract_number }}</strong><span class="sx-pill {{ $c->status_tone }}">{{ $c->status_label }}</span></div>
                        <div class="sx-muted small">{{ $c->start_date->format('d M Y') }} – {{ $c->end_date?->format('d M Y') ?? 'open' }}</div>
                        @foreach($c->documents as $d)
                            <a href="{{ route('documents.download', $d) }}" target="_blank" rel="noopener" class="small d-inline-flex align-items-center gap-1 mt-1"><i class="bi bi-file-earmark-pdf text-danger"></i> {{ $d->title }}</a>
                        @endforeach
                        @if($c->terms)
                            <details class="mt-1"><summary class="small" style="cursor:pointer">Read terms</summary><div class="small mt-1" style="white-space:pre-line">{{ $c->terms }}</div></details>
                        @endif
                    </li>
                @empty
                    <li class="sx-muted small">No agreements yet.</li>
                @endforelse
            </ul>
        </div>
        <div class="sx-card">
            <div class="sx-card-body small">
                <strong class="d-block mb-1">How to pay</strong>
                Pay by MTN MoMo, Airtel Money or bank transfer using your invoice number as the reference. Terra records your payment and you get a confirmation here and by email.
                <div class="mt-2"><a href="{{ $support ? route('messages.create', ['to' => $support->id]) : route('messages.index') }}" class="sx-btn sx-btn-outline"><i class="bi bi-chat-dots"></i> Message Terra</a></div>
            </div>
        </div>
    </div>
</div>
@endsection

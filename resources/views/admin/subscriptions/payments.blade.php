@extends('layouts.app')

@section('title', 'Subscription transactions')
@section('page_heading', 'Subscription transactions')
@section('page_subtitle', 'Every subscription payment received from owners and managers.')

@use('App\Support\Money')
@use('App\Models\SubscriptionPayment')
@include('partials.sx-styles')

@section('content')
@php
    $months = collect(range(5, 0))->mapWithKeys(fn ($i) => [now()->startOfMonth()->subMonths($i)->format('Y-m') => (float) ($monthly[now()->startOfMonth()->subMonths($i)->format('Y-m')] ?? 0)]);
    $max = max(1, $months->max());
@endphp
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="sx-stat h-100"><div class="sx-label">Total for this view</div><div class="sx-value">{{ Money::format($total) }}</div><div class="sx-sub">{{ $payments->total() }} transactions</div></div>
    </div>
    <div class="col-md-8">
        <div class="sx-card h-100"><div class="sx-card-body py-2">
            <div class="sx-muted small mb-1">Collected per month</div>
            <div class="sx-bar" role="img" aria-label="Subscription income for the last six months">
                @foreach($months as $m => $v)
                    <div><strong>{{ $v >= 1000000 ? round($v / 1000000, 1) . 'M' : ($v >= 1000 ? round($v / 1000) . 'k' : (int) $v) }}</strong><span class="b" style="height:{{ max(2, round($v / $max * 70)) }}px"></span>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $m)->format('M') }}</div>
                @endforeach
            </div>
        </div></div>
    </div>
</div>

<div class="sx-card">
    <div class="sx-card-head">
        <form method="GET" class="sx-filters w-100">
            <div class="flex-grow-1" style="min-width:180px;"><label class="form-label small mb-1" for="pq">Search</label>
                <input type="search" id="pq" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Reference or subscriber"></div>
            <div><label class="form-label small mb-1" for="pm">Method</label>
                <select id="pm" name="method" class="form-select form-select-sm"><option value="">Any</option>@foreach(SubscriptionPayment::METHODS as $k => $l)<option value="{{ $k }}" @selected(request('method') === $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label class="form-label small mb-1" for="pf">From</label><input type="date" id="pf" name="from" value="{{ request('from') }}" class="form-control form-control-sm"></div>
            <div><label class="form-label small mb-1" for="pt">To</label><input type="date" id="pt" name="to" value="{{ request('to') }}" class="form-control form-control-sm"></div>
            <div class="d-flex gap-2">
                <button class="sx-btn sx-btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                <a href="{{ route('admin.subscriptions.payments', request()->query() + ['export' => 'csv']) }}" class="sx-btn sx-btn-outline"><i class="bi bi-download"></i> CSV</a>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="sx-table">
            <thead><tr><th>Date</th><th>Subscriber</th><th>Contract</th><th>Invoice</th><th>Method</th><th>Reference</th><th class="sx-num">Amount</th><th>Recorded by</th></tr></thead>
            <tbody>
            @forelse($payments as $p)
                <tr>
                    <td class="small">{{ $p->paid_at->format('d M Y H:i') }}</td>
                    <td>@if($p->subscriber)<a href="{{ route('admin.subscribers.show', $p->subscriber) }}">{{ $p->subscriber->name }}</a>@endif</td>
                    <td class="small"><a href="{{ route('admin.subscriptions.contracts.show', $p->subscription_contract_id) }}">{{ $p->contract?->contract_number }}</a></td>
                    <td class="small">{{ $p->invoice?->invoice_number ?? 'Credit' }}</td>
                    <td class="small">{{ $p->method_label }}</td>
                    <td class="small">{{ $p->reference ?: '—' }}</td>
                    <td class="sx-num"><strong>{{ Money::format($p->amount, $p->currency) }}</strong></td>
                    <td class="small">{{ $p->recorder?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="sx-empty"><i class="bi bi-cash"></i>No transactions match these filters.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($payments->hasPages())<div class="p-3">{{ $payments->links() }}</div>@endif
</div>
@endsection

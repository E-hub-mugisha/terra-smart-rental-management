@extends('layouts.app')

@section('title', 'Subscription invoices')
@section('page_heading', 'Outstanding & overdue')
@section('page_subtitle', 'Subscription invoices across all subscribers, with ageing of overdue balances.')

@use('App\Support\Money')
@include('partials.sx-styles')

@section('content')
<div class="sx-stats">
    <div class="sx-stat sx-warn"><div class="sx-label">Outstanding</div><div class="sx-value">{{ Money::format($summary['outstanding']) }}</div></div>
    <div class="sx-stat sx-danger"><div class="sx-label">Overdue</div><div class="sx-value">{{ Money::format($summary['overdue']) }}</div><div class="sx-sub">{{ $summary['overdue_count'] }} invoices</div></div>
    @foreach($summary['aging'] as $bucket => $amount)
        <div class="sx-stat"><div class="sx-label">Overdue {{ $bucket }}</div><div class="sx-value" style="font-size:1.1rem">{{ Money::format($amount) }}</div></div>
    @endforeach
</div>

<div class="sx-chipbar">
    @foreach(['outstanding' => 'Outstanding', 'overdue' => 'Overdue', 'paid' => 'Paid', 'void' => 'Void', 'all' => 'Full history'] as $key => $label)
        <a href="{{ route('admin.subscriptions.invoices', ['filter' => $key] + request()->only('q')) }}" class="sx-chip {{ $filter === $key ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="sx-card">
    <div class="sx-card-head">
        <form method="GET" class="sx-filters w-100">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <div class="flex-grow-1" style="max-width:420px;"><label class="visually-hidden" for="iq">Search</label>
                <input type="search" id="iq" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Invoice number or subscriber"></div>
            <button class="sx-btn sx-btn-outline"><i class="bi bi-search"></i> Search</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="sx-table">
            <thead><tr><th>Invoice</th><th>Subscriber</th><th>Contract</th><th>Period</th><th>Due</th><th class="sx-num">Amount</th><th class="sx-num">Balance</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($invoices as $i)
                <tr>
                    <td><strong>{{ $i->invoice_number }}</strong><div class="sx-muted small">{{ $i->issue_date->format('d M Y') }}</div></td>
                    <td>@if($i->subscriber)<a href="{{ route('admin.subscribers.show', $i->subscriber) }}">{{ $i->subscriber->name }}</a>@endif</td>
                    <td class="small"><a href="{{ route('admin.subscriptions.contracts.show', $i->subscription_contract_id) }}">{{ $i->contract?->contract_number }}</a></td>
                    <td class="small">{{ $i->period_start?->format('d M') }} – {{ $i->period_end?->format('d M Y') }}</td>
                    <td class="small">{{ $i->due_date->format('d M Y') }}@if($i->is_overdue)<div class="text-danger">{{ (int) $i->due_date->diffInDays(today()) }} days late</div>@endif</td>
                    <td class="sx-num">{{ Money::format($i->amount, $i->currency) }}</td>
                    <td class="sx-num"><strong>{{ Money::format($i->balance, $i->currency) }}</strong></td>
                    <td><span class="sx-pill {{ $i->status_tone }}">{{ $i->display_status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="sx-empty"><i class="bi bi-check2-circle"></i>Nothing here. {{ $filter === 'overdue' ? 'No subscriber is behind on payments.' : '' }}</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())<div class="p-3">{{ $invoices->links() }}</div>@endif
</div>
@endsection

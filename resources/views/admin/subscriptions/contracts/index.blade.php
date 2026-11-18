@extends('layouts.app')

@section('title', 'Subscription contracts')
@section('page_heading', 'Subscription contracts')
@section('page_subtitle', 'Agreements with property owners and managers: pending, active, expired and terminated.')

@use('App\Support\Money')
@use('App\Models\SubscriptionContract')
@include('partials.sx-styles')

@section('content')
@php $current = request('status'); @endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="sx-chipbar m-0">
        <a href="{{ route('admin.subscriptions.contracts.index', request()->except('status', 'page')) }}" class="sx-chip {{ ! $current ? 'active' : '' }}">All ({{ $counts->sum() }})</a>
        @foreach(SubscriptionContract::STATUSES as $key => $label)
            <a href="{{ route('admin.subscriptions.contracts.index', ['status' => $key] + request()->except('status', 'page')) }}" class="sx-chip {{ $current === $key ? 'active' : '' }}">{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
        @endforeach
        <a href="{{ route('admin.subscriptions.contracts.index', ['status' => 'expiring']) }}" class="sx-chip {{ $current === 'expiring' ? 'active' : '' }}">Expiring in 30 days</a>
    </div>
    <a href="{{ route('admin.subscriptions.contracts.create') }}" class="sx-btn sx-btn-primary"><i class="bi bi-file-earmark-plus"></i> New contract</a>
</div>

<div class="sx-card">
    <div class="sx-card-head">
        <form method="GET" class="sx-filters w-100">
            @if($current)<input type="hidden" name="status" value="{{ $current }}">@endif
            <div class="flex-grow-1" style="max-width:420px;">
                <label class="visually-hidden" for="cq">Search</label>
                <input type="search" id="cq" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Contract number, title or subscriber">
            </div>
            <button class="sx-btn sx-btn-outline"><i class="bi bi-search"></i> Search</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="sx-table">
            <thead><tr><th>Contract</th><th>Subscriber</th><th>Plan</th><th>Start</th><th>End</th><th class="sx-num">Fee / cycle</th><th class="sx-num">Outstanding</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($contracts as $c)
                <tr>
                    <td><a href="{{ route('admin.subscriptions.contracts.show', $c) }}"><strong>{{ $c->contract_number }}</strong></a><div class="sx-muted small">{{ \Illuminate\Support\Str::limit($c->title, 40) }}</div></td>
                    <td>
                        @if($c->subscriber)
                            <a href="{{ route('admin.subscribers.show', $c->subscriber) }}">{{ $c->subscriber->name }}</a>
                            <div class="sx-muted small">{{ $c->subscriber->role === 'owner' ? 'Owner' : 'Manager' }}</div>
                        @else — @endif
                    </td>
                    <td class="small">{{ $c->plan?->name ?? 'Custom' }}</td>
                    <td class="small">{{ $c->start_date->format('d M Y') }}</td>
                    <td class="small">
                        {{ $c->end_date?->format('d M Y') ?? 'Open-ended' }}
                        @if($c->isExpiringWithin(30))<div><span class="sx-pill warning">{{ (int) today()->diffInDays($c->end_date) }} days left</span></div>@endif
                    </td>
                    <td class="sx-num">{{ Money::format($c->amount, $c->currency) }}<div class="sx-muted small">{{ SubscriptionContract::CYCLE_LABELS[$c->billing_cycle] ?? '' }}</div></td>
                    <td class="sx-num">{{ $c->outstanding > 0 ? Money::format($c->outstanding, $c->currency) : '—' }}</td>
                    <td><span class="sx-pill {{ $c->status_tone }}">{{ $c->status_label }}</span></td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="sx-empty"><i class="bi bi-file-earmark-text"></i>No contracts found.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($contracts->hasPages())<div class="p-3">{{ $contracts->links() }}</div>@endif
</div>
@endsection

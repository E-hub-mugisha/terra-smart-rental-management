@extends('layouts.app')

@section('title', 'Subscribers')
@section('page_heading', 'Subscribers')
@section('page_subtitle', 'Every property owner and manager on Terra, with their subscription, balance and contact options.')

@use('App\Support\Money')
@use('App\Support\Phone')
@include('partials.sx-styles')

@section('content')
<div class="sx-stats">
    <div class="sx-stat"><div class="sx-label"><i class="bi bi-people"></i> Subscribers</div><div class="sx-value">{{ number_format($stats['subscribers']) }}</div><div class="sx-sub">{{ $stats['without_contract'] }} without a contract</div></div>
    <a class="sx-stat sx-ok" href="{{ route('admin.subscriptions.contracts.index', ['status' => 'active']) }}"><div class="sx-label"><i class="bi bi-patch-check"></i> Active contracts</div><div class="sx-value">{{ number_format($stats['active']) }}</div><div class="sx-sub">{{ $stats['pending'] }} pending signature</div></a>
    <a class="sx-stat sx-warn" href="{{ route('admin.subscriptions.contracts.index', ['status' => 'expiring']) }}"><div class="sx-label"><i class="bi bi-calendar-event"></i> Expiring in 30 days</div><div class="sx-value">{{ number_format($stats['expiring']) }}</div><div class="sx-sub">Renew or follow up</div></a>
    <a class="sx-stat" href="{{ route('admin.subscriptions.invoices') }}"><div class="sx-label"><i class="bi bi-hourglass-split"></i> Outstanding</div><div class="sx-value">{{ Money::format($stats['outstanding']) }}</div><div class="sx-sub">All open invoices</div></a>
    <a class="sx-stat sx-danger" href="{{ route('admin.subscriptions.invoices', ['filter' => 'overdue']) }}"><div class="sx-label"><i class="bi bi-exclamation-triangle"></i> Overdue</div><div class="sx-value">{{ Money::format($stats['overdue']) }}</div><div class="sx-sub">{{ $stats['overdue_count'] }} {{ Str::plural('subscriber', $stats['overdue_count']) }} behind</div></a>
    <a class="sx-stat sx-ok" href="{{ route('admin.subscriptions.payments') }}"><div class="sx-label"><i class="bi bi-cash-coin"></i> Collected this month</div><div class="sx-value">{{ Money::format($stats['collected_month']) }}</div><div class="sx-sub">{{ now()->format('F Y') }}</div></a>
</div>

<div class="sx-card">
    <div class="sx-card-head">
        <form method="GET" class="sx-filters w-100">
            <div class="flex-grow-1" style="min-width:200px;">
                <label class="form-label small mb-1" for="sq">Search</label>
                <input type="search" id="sq" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Name, email or phone">
            </div>
            <div>
                <label class="form-label small mb-1" for="srole">Type</label>
                <select id="srole" name="role" class="form-select form-select-sm">
                    <option value="">Owners & managers</option>
                    <option value="owner" @selected(request('role') === 'owner')>Owners</option>
                    <option value="manager" @selected(request('role') === 'manager')>Managers</option>
                </select>
            </div>
            <div>
                <label class="form-label small mb-1" for="sstatus">Subscription</label>
                <select id="sstatus" name="status" class="form-select form-select-sm">
                    <option value="">Any status</option>
                    @foreach(\App\Models\SubscriptionContract::STATUSES as $k => $label)
                        <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
                    @endforeach
                    <option value="none" @selected(request('status') === 'none')>No contract</option>
                </select>
            </div>
            <div>
                <label class="form-label small mb-1" for="sbilling">Billing</label>
                <select id="sbilling" name="billing" class="form-select form-select-sm">
                    <option value="">Any</option>
                    <option value="overdue" @selected(request('billing') === 'overdue')>Overdue</option>
                    <option value="outstanding" @selected(request('billing') === 'outstanding')>Has balance</option>
                    <option value="clear" @selected(request('billing') === 'clear')>Paid up</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button class="sx-btn sx-btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                @if(request()->hasAny(['q', 'role', 'status', 'billing']))
                    <a href="{{ route('admin.subscribers.index') }}" class="sx-btn sx-btn-outline">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="sx-table">
            <thead>
                <tr>
                    <th>Subscriber</th>
                    <th>Phone</th>
                    <th>Subscription</th>
                    <th>Renews / ends</th>
                    <th class="sx-num">Outstanding</th>
                    <th class="sx-num">Overdue</th>
                    <th>Last payment</th>
                    <th class="text-end">Contact</th>
                </tr>
            </thead>
            <tbody>
            @forelse($subscribers as $s)
                @php
                    $c = $s->latestSubscription;
                    $phone = $s->phone ?? $s->propertyOwner?->phone ?? $s->propertyManager?->phone;
                    $digits = Phone::digits($phone);
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.subscribers.show', $s) }}" class="d-flex align-items-center gap-2 text-decoration-none">
                            <span class="sx-avatar">{{ collect(explode(' ', $s->name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') }}</span>
                            <span>
                                <strong class="d-block" style="color:var(--terra-navy,#19265d)">{{ $s->name }}</strong>
                                <span class="sx-muted small">{{ $s->role === 'owner' ? 'Owner' : 'Manager' }} · {{ $s->email }}</span>
                            </span>
                        </a>
                    </td>
                    <td class="small">{{ $phone ? Phone::display($phone) : '—' }}</td>
                    <td>
                        @if($c)
                            <span class="sx-pill {{ $c->status_tone }}">{{ $c->status_label }}</span>
                            <div class="sx-muted small mt-1">{{ $c->plan?->name ?? $c->title }}</div>
                        @else
                            <span class="sx-pill secondary">No contract</span>
                        @endif
                    </td>
                    <td class="small">
                        @if($c?->status === 'terminated')
                            Ended {{ $c->terminated_at?->format('d M Y') }}
                        @else
                            {{ $c?->end_date?->format('d M Y') ?? '—' }}
                            @if($c?->auto_renew && $c->status === 'active')<div class="sx-muted">auto-renews</div>@endif
                        @endif
                    </td>
                    <td class="sx-num">{{ $s->outstanding > 0 ? Money::format($s->outstanding) : '—' }}</td>
                    <td class="sx-num">@if($s->overdue > 0)<span class="sx-pill danger">{{ Money::format($s->overdue) }}</span>@else — @endif</td>
                    <td class="small">{{ $s->last_paid_at ? \Illuminate\Support\Carbon::parse($s->last_paid_at)->format('d M Y') : '—' }}</td>
                    <td class="text-end text-nowrap">
                        @if($digits)
                            <a href="tel:+{{ $digits }}" class="btn btn-sm btn-light" title="Call {{ $s->name }}" aria-label="Call {{ $s->name }}"><i class="bi bi-telephone"></i></a>
                            <a href="https://wa.me/{{ $digits }}" target="_blank" rel="noopener" class="btn btn-sm btn-light" title="WhatsApp" aria-label="WhatsApp {{ $s->name }}"><i class="bi bi-whatsapp"></i></a>
                        @endif
                        <a href="{{ route('messages.create', ['to' => $s->id]) }}" class="btn btn-sm btn-light" title="Message in Terra" aria-label="Message {{ $s->name }}"><i class="bi bi-chat-dots"></i></a>
                        <a href="{{ route('admin.subscribers.show', $s) }}#contact" class="btn btn-sm btn-light" title="Email" aria-label="Email {{ $s->name }}"><i class="bi bi-envelope"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="sx-empty"><i class="bi bi-person-x"></i>No subscribers match these filters.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($subscribers->hasPages())
        <div class="p-3">{{ $subscribers->links() }}</div>
    @endif
</div>
@endsection

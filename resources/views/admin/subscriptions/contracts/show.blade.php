@extends('layouts.app')

@section('title', $contract->contract_number)
@section('page_heading', 'Contract ' . $contract->contract_number)
@section('page_subtitle', $contract->title)

@use('App\Support\Money')
@use('App\Models\SubscriptionContract')
@use('App\Models\SubscriptionPayment')
@include('partials.sx-styles')

@section('content')
@php
    $sub = $contract->subscriber;
    $openInvoices = $invoices->whereIn('status', ['unpaid', 'partial']);
    $steps = ['pending' => 'Pending', 'active' => 'Active', 'end' => $contract->status === 'terminated' ? 'Terminated' : 'Expired'];
    $reached = match ($contract->status) { 'pending' => 0, 'active' => 1, default => 2 };
@endphp

<a href="{{ route('admin.subscriptions.contracts.index') }}" class="sx-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3"><i class="bi bi-arrow-left"></i> All contracts</a>

<div class="sx-card mb-3">
    <div class="sx-card-body d-flex flex-wrap gap-3 justify-content-between align-items-center">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="sx-pill {{ $contract->status_tone }}" style="font-size:.8rem">{{ $contract->status_label }}</span>
                @if($contract->auto_renew)<span class="sx-pill info"><i class="bi bi-arrow-repeat"></i> Auto-renew</span>@endif
                @if($contract->isExpiringWithin(30))<span class="sx-pill warning">Expires in {{ (int) today()->diffInDays($contract->end_date) }} days</span>@endif
            </div>
            <div class="small sx-muted">
                @if($sub)<a href="{{ route('admin.subscribers.show', $sub) }}"><strong>{{ $sub->name }}</strong></a> · {{ $sub->role === 'owner' ? 'Property owner' : 'Property manager' }} · @endif
                {{ $contract->start_date->format('d M Y') }} – {{ $contract->end_date?->format('d M Y') ?? 'open-ended' }}
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if(in_array($contract->status, ['pending', 'expired']))
                <form method="POST" action="{{ route('admin.subscriptions.contracts.transition', $contract) }}" class="m-0">@csrf<input type="hidden" name="action" value="activate">
                    <button class="sx-btn sx-btn-success"><i class="bi bi-play-circle"></i> Activate</button></form>
            @endif
            @if(in_array($contract->status, ['active', 'expired']))
                <button class="sx-btn sx-btn-outline" data-bs-toggle="modal" data-bs-target="#renewModal"><i class="bi bi-arrow-repeat"></i> Renew</button>
            @endif
            @if($contract->status !== 'terminated')
                <a href="{{ route('admin.subscriptions.contracts.edit', $contract) }}" class="sx-btn sx-btn-outline"><i class="bi bi-pencil"></i> Edit</a>
                <button class="sx-btn sx-btn-danger" data-bs-toggle="modal" data-bs-target="#terminateModal"><i class="bi bi-x-octagon"></i> Terminate</button>
            @endif
        </div>
    </div>
    <div class="px-3 pb-3">
        <div class="d-flex gap-2" aria-label="Contract status">
            @foreach($steps as $i => $label)
                @php $idx = array_search($i, array_keys($steps)); @endphp
                <div class="flex-fill text-center small">
                    <div style="height:6px;border-radius:4px;background:{{ $idx <= $reached ? ($idx === 2 && $contract->status === 'terminated' ? '#b42318' : 'var(--terra-orange,#D05208)') : 'var(--terra-border,#e7eaf0)' }}"></div>
                    <div class="mt-1 {{ $idx === $reached ? 'fw-bold' : 'sx-muted' }}">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="sx-stats">
    <div class="sx-stat"><div class="sx-label">Fee</div><div class="sx-value">{{ Money::format($contract->amount, $contract->currency) }}</div><div class="sx-sub">{{ SubscriptionContract::CYCLE_LABELS[$contract->billing_cycle] ?? '' }} · due in {{ $contract->payment_due_days }} days</div></div>
    <div class="sx-stat"><div class="sx-label">Invoiced</div><div class="sx-value">{{ Money::format($invoices->where('status', '!=', 'void')->sum('amount'), $contract->currency) }}</div><div class="sx-sub">{{ $invoices->count() }} invoices</div></div>
    <div class="sx-stat sx-ok"><div class="sx-label">Paid</div><div class="sx-value">{{ Money::format($payments->sum('amount'), $contract->currency) }}</div></div>
    <div class="sx-stat {{ $outstanding > 0 ? 'sx-danger' : '' }}"><div class="sx-label">Outstanding</div><div class="sx-value">{{ Money::format($outstanding, $contract->currency) }}</div></div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="sx-card mb-3">
            <div class="sx-card-head">
                <h2>Invoices</h2>
                @if(in_array($contract->status, ['active', 'pending']))
                    <form method="POST" action="{{ route('admin.subscriptions.contracts.invoices.store', $contract) }}" class="m-0">@csrf
                        <button class="sx-btn sx-btn-outline"><i class="bi bi-receipt"></i> Issue next invoice</button></form>
                @endif
            </div>
            <div class="table-responsive">
                <table class="sx-table">
                    <thead><tr><th>Invoice</th><th>Period</th><th>Due</th><th class="sx-num">Amount</th><th class="sx-num">Balance</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($invoices as $i)
                        <tr>
                            <td><strong>{{ $i->invoice_number }}</strong><div class="sx-muted small">Issued {{ $i->issue_date->format('d M Y') }}</div></td>
                            <td class="small">{{ $i->period_start?->format('d M Y') }} – {{ $i->period_end?->format('d M Y') }}</td>
                            <td class="small">{{ $i->due_date->format('d M Y') }}</td>
                            <td class="sx-num">{{ Money::format($i->amount, $i->currency) }}</td>
                            <td class="sx-num"><strong>{{ Money::format($i->balance, $i->currency) }}</strong></td>
                            <td><span class="sx-pill {{ $i->status_tone }}">{{ $i->display_status }}</span>@if($i->notes && $i->status === 'void')<div class="sx-muted small">{{ $i->notes }}</div>@endif</td>
                            <td class="text-end">
                                @if($i->status === 'unpaid' && $i->payments_count === 0)
                                    <form method="POST" action="{{ route('admin.subscriptions.invoices.void', $i) }}" class="m-0" onsubmit="var r = prompt('Reason for voiding {{ $i->invoice_number }}?'); if (!r) return false; this.reason.value = r;">
                                        @csrf<input type="hidden" name="reason">
                                        <button class="btn btn-sm btn-light" title="Void invoice">Void</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="sx-empty"><i class="bi bi-receipt"></i>{{ $contract->status === 'pending' ? 'Invoices are issued automatically once the contract is activated.' : 'No invoices yet.' }}</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sx-card mb-3">
            <div class="sx-card-head"><h2>Payments</h2></div>
            <div class="table-responsive">
                <table class="sx-table">
                    <thead><tr><th>Date</th><th>Invoice</th><th>Method</th><th>Reference</th><th class="sx-num">Amount</th><th>Recorded by</th></tr></thead>
                    <tbody>
                    @forelse($payments as $p)
                        <tr>
                            <td class="small">{{ $p->paid_at->format('d M Y H:i') }}</td>
                            <td class="small">{{ $p->invoice?->invoice_number ?? 'Credit (unallocated)' }}</td>
                            <td class="small">{{ $p->method_label }}</td>
                            <td class="small">{{ $p->reference ?: '—' }}</td>
                            <td class="sx-num"><strong>{{ Money::format($p->amount, $p->currency) }}</strong></td>
                            <td class="small">{{ $p->recorder?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="sx-empty"><i class="bi bi-cash"></i>No payments yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sx-card mb-3">
            <div class="sx-card-head"><h2>Terms</h2></div>
            <div class="sx-card-body small" style="white-space:pre-line;line-height:1.7">{{ $contract->terms ?: 'No written terms recorded.' }}</div>
        </div>
    </div>

    <div class="col-xl-4">
        @if($contract->status !== 'terminated')
            <div class="sx-card mb-3">
                <div class="sx-card-head"><h2>Record a payment</h2></div>
                <form method="POST" action="{{ route('admin.subscriptions.contracts.payments.store', $contract) }}" class="sx-card-body row g-2">
                    @csrf
                    <div class="col-12">
                        <label class="form-label small mb-1" for="pInvoice">Apply to</label>
                        <select id="pInvoice" name="subscription_invoice_id" class="form-select form-select-sm">
                            <option value="">Oldest open invoices first</option>
                            @foreach($openInvoices as $i)<option value="{{ $i->id }}">{{ $i->invoice_number }} · {{ Money::format($i->balance, $i->currency) }} due</option>@endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1" for="pAmount">Amount ({{ $contract->currency }})</label>
                        <input id="pAmount" type="number" step="0.01" min="1" name="amount" required class="form-control form-control-sm @error('amount') is-invalid @enderror" value="{{ old('amount', $openInvoices->first()?->balance) }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1" for="pMethod">Method</label>
                        <select id="pMethod" name="method" class="form-select form-select-sm">@foreach(SubscriptionPayment::METHODS as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1" for="pRef">Reference</label>
                        <input id="pRef" name="reference" maxlength="60" class="form-control form-control-sm" placeholder="MoMo / bank ref">
                    </div>
                    <div class="col-6">
                        <label class="form-label small mb-1" for="pDate">Paid on</label>
                        <input id="pDate" type="datetime-local" name="paid_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}" max="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-1" for="pNotes">Notes</label>
                        <input id="pNotes" name="notes" maxlength="255" class="form-control form-control-sm">
                    </div>
                    @error('amount')<div class="col-12 text-danger small">{{ $message }}</div>@enderror
                    <div class="col-12 d-grid mt-2"><button class="sx-btn sx-btn-primary justify-content-center"><i class="bi bi-cash-coin"></i> Record payment</button></div>
                </form>
            </div>
        @endif

        @include('documents._profile-panel', [
            'owner' => $contract,
            'ownerKey' => 'subscription',
            'documents' => $contract->documents,
            'canUpload' => true,
            'heading' => 'Signed agreement',
        ])

        <div class="sx-card">
            <div class="sx-card-head"><h2>Details</h2></div>
            <div class="sx-card-body">
                <dl class="sx-dl">
                    <dt>Plan</dt><dd>{{ $contract->plan?->name ?? 'Custom' }}</dd>
                    <dt>Signed</dt><dd>{{ $contract->signed_at?->format('d M Y') ?? '—' }}</dd>
                    <dt>Activated</dt><dd>{{ $contract->activated_at?->format('d M Y') ?? '—' }}</dd>
                    @if($contract->terminated_at)
                        <dt>Terminated</dt><dd>{{ $contract->terminated_at->format('d M Y') }}</dd>
                        <dt>Reason</dt><dd>{{ $contract->termination_reason }}</dd>
                    @endif
                    <dt>Created by</dt><dd>{{ $contract->creator?->name ?? '—' }} · {{ $contract->created_at->format('d M Y') }}</dd>
                    @if($contract->notes)<dt>Notes</dt><dd style="white-space:pre-line">{{ $contract->notes }}</dd>@endif
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="renewModal" tabindex="-1" aria-labelledby="renewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.subscriptions.contracts.transition', $contract) }}" class="modal-content">
            @csrf<input type="hidden" name="action" value="renew">
            <div class="modal-header"><h5 class="modal-title" id="renewLabel">Renew {{ $contract->contract_number }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <label class="form-label" for="renewMonths">Extend by (months)</label>
                <input type="number" id="renewMonths" name="months" min="1" max="60" value="12" class="form-control">
                <div class="form-text">Current end date: {{ $contract->end_date?->format('d M Y') ?? 'open-ended' }}.</div>
            </div>
            <div class="modal-footer"><button type="button" class="sx-btn sx-btn-outline" data-bs-dismiss="modal">Cancel</button><button class="sx-btn sx-btn-primary">Renew</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="terminateModal" tabindex="-1" aria-labelledby="termLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.subscriptions.contracts.transition', $contract) }}" class="modal-content">
            @csrf<input type="hidden" name="action" value="terminate">
            <div class="modal-header"><h5 class="modal-title" id="termLabel">Terminate {{ $contract->contract_number }}?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <p class="small">The subscription ends today. Unpaid invoices for future periods are voided; past invoices stay payable. The subscriber is notified.</p>
                <label class="form-label" for="termReason">Reason</label>
                <input id="termReason" name="reason" required maxlength="255" class="form-control">
            </div>
            <div class="modal-footer"><button type="button" class="sx-btn sx-btn-outline" data-bs-dismiss="modal">Cancel</button><button class="sx-btn sx-btn-danger">Terminate contract</button></div>
        </form>
    </div>
</div>
@endsection

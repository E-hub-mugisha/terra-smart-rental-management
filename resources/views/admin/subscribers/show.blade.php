@extends('layouts.app')

@section('title', $subscriber->name . ' · Subscriber')
@section('page_heading', $subscriber->name)
@section('page_subtitle', ($subscriber->role === 'owner' ? 'Property owner' : 'Property manager') . ' · subscription, billing and contact history')

@use('App\Support\Money')
@use('App\Models\SubscriberCall')
@include('partials.sx-styles')

@section('content')
@php
    $profile = $subscriber->propertyOwner ?? $subscriber->propertyManager;
    $number = $subscriber->propertyOwner?->owner_number ?? $subscriber->propertyManager?->manager_number;
    $active = $contracts->firstWhere('status', 'active');
    $initials = collect(explode(' ', $subscriber->name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp

<a href="{{ route('admin.subscribers.index') }}" class="sx-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3"><i class="bi bi-arrow-left"></i> All subscribers</a>

<div class="sx-card mb-3">
    <div class="sx-card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <span class="sx-avatar lg">{{ $initials }}</span>
            <div>
                <h2 class="h5 mb-1" style="color:var(--terra-navy,#19265d)">{{ $subscriber->name }}</h2>
                <div class="sx-muted small">
                    {{ $subscriber->role === 'owner' ? 'Property owner' : 'Property manager' }}@if($number) · {{ $number }}@endif
                    · <a href="mailto:{{ $subscriber->email }}">{{ $subscriber->email }}</a>
                    @if($phoneDisplay) · {{ $phoneDisplay }}@endif
                </div>
                <div class="mt-1">
                    @if($active)
                        <span class="sx-pill success"><i class="bi bi-patch-check"></i> Active until {{ $active->end_date?->format('d M Y') ?? 'open-ended' }}</span>
                    @elseif($contracts->isNotEmpty())
                        <span class="sx-pill {{ $contracts->first()->status_tone }}">{{ $contracts->first()->status_label }}</span>
                    @else
                        <span class="sx-pill secondary">No subscription contract</span>
                    @endif
                    @if($registeredBy)
                        <span class="sx-muted small ms-2">Registered by {{ $registeredBy->name }}{{ $registeredBy->role === 'field_staff' ? ' (field staff)' : '' }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2" id="contact">
            @if($phoneDigits)
                <a href="tel:+{{ $phoneDigits }}" class="sx-btn sx-btn-outline"><i class="bi bi-telephone"></i> Call</a>
                <a href="https://wa.me/{{ $phoneDigits }}" target="_blank" rel="noopener" class="sx-btn sx-btn-outline"><i class="bi bi-whatsapp"></i> WhatsApp</a>
            @endif
            <form method="POST" action="{{ route('admin.subscribers.calls.start', $subscriber) }}" class="m-0">
                @csrf
                <button class="sx-btn sx-btn-success" title="Starts a private audio/video call in the browser and alerts {{ $subscriber->name }} to join"><i class="bi bi-camera-video"></i> In-platform call</button>
            </form>
            <a href="{{ route('messages.create', ['to' => $subscriber->id]) }}" class="sx-btn sx-btn-outline"><i class="bi bi-chat-dots"></i> Message</a>
            <button type="button" class="sx-btn sx-btn-outline" data-bs-toggle="modal" data-bs-target="#emailModal"><i class="bi bi-envelope"></i> Email</button>
            <button type="button" class="sx-btn sx-btn-outline" data-bs-toggle="modal" data-bs-target="#callModal"><i class="bi bi-journal-plus"></i> Log call</button>
            <a href="{{ route('admin.subscriptions.contracts.create', ['subscriber' => $subscriber->id]) }}" class="sx-btn sx-btn-primary"><i class="bi bi-file-earmark-plus"></i> New contract</a>
        </div>
    </div>
</div>

<div class="sx-stats">
    <div class="sx-stat"><div class="sx-label">Invoiced</div><div class="sx-value">{{ Money::format($totals['invoiced']) }}</div></div>
    <div class="sx-stat sx-ok"><div class="sx-label">Paid</div><div class="sx-value">{{ Money::format($totals['paid']) }}</div></div>
    <div class="sx-stat sx-warn"><div class="sx-label">Outstanding</div><div class="sx-value">{{ Money::format($totals['outstanding']) }}</div></div>
    <div class="sx-stat {{ $totals['overdue'] > 0 ? 'sx-danger' : '' }}"><div class="sx-label">Overdue</div><div class="sx-value">{{ Money::format($totals['overdue']) }}</div></div>
</div>

<div class="sx-tabs" role="tablist">
    <button type="button" class="active" data-sx-tab="contracts">Contracts ({{ $contracts->count() }})</button>
    <button type="button" data-sx-tab="invoices">Invoices ({{ $invoices->count() }})</button>
    <button type="button" data-sx-tab="payments">Payments ({{ $payments->count() }})</button>
    <button type="button" data-sx-tab="history">Contact history ({{ $calls->count() }})</button>
    <button type="button" data-sx-tab="documents">Documents</button>
    <button type="button" data-sx-tab="profile">Profile</button>
</div>

<div data-sx-pane="contracts">
    <div class="sx-card">
        <div class="table-responsive">
            <table class="sx-table">
                <thead><tr><th>Contract</th><th>Plan</th><th>Period</th><th>Billing</th><th class="sx-num">Fee</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($contracts as $c)
                    <tr>
                        <td><a href="{{ route('admin.subscriptions.contracts.show', $c) }}"><strong>{{ $c->contract_number }}</strong></a><div class="sx-muted small">{{ $c->title }}</div></td>
                        <td class="small">{{ $c->plan?->name ?? 'Custom' }}</td>
                        <td class="small">{{ $c->start_date->format('d M Y') }} – {{ $c->end_date?->format('d M Y') ?? 'open' }}</td>
                        <td class="small">{{ \App\Models\SubscriptionContract::CYCLE_LABELS[$c->billing_cycle] ?? $c->billing_cycle }}@if($c->auto_renew) · auto-renew @endif</td>
                        <td class="sx-num">{{ Money::format($c->amount, $c->currency) }}</td>
                        <td><span class="sx-pill {{ $c->status_tone }}">{{ $c->status_label }}</span></td>
                        <td class="text-end"><a href="{{ route('admin.subscriptions.contracts.show', $c) }}" class="btn btn-sm btn-light">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="sx-empty"><i class="bi bi-file-earmark"></i>No subscription contract yet.
                        <div class="mt-2"><a href="{{ route('admin.subscriptions.contracts.create', ['subscriber' => $subscriber->id]) }}" class="sx-btn sx-btn-primary">Create contract</a></div></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div data-sx-pane="invoices" hidden>
    <div class="sx-card">
        <div class="table-responsive">
            <table class="sx-table">
                <thead><tr><th>Invoice</th><th>Contract</th><th>Period</th><th>Due</th><th class="sx-num">Amount</th><th class="sx-num">Paid</th><th class="sx-num">Balance</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($invoices as $i)
                    <tr>
                        <td><strong>{{ $i->invoice_number }}</strong><div class="sx-muted small">Issued {{ $i->issue_date->format('d M Y') }}</div></td>
                        <td class="small"><a href="{{ route('admin.subscriptions.contracts.show', $i->subscription_contract_id) }}">{{ $i->contract?->contract_number }}</a></td>
                        <td class="small">{{ $i->period_start?->format('d M') }} – {{ $i->period_end?->format('d M Y') }}</td>
                        <td class="small">{{ $i->due_date->format('d M Y') }}</td>
                        <td class="sx-num">{{ Money::format($i->amount, $i->currency) }}</td>
                        <td class="sx-num">{{ Money::format($i->amount_paid, $i->currency) }}</td>
                        <td class="sx-num"><strong>{{ Money::format($i->balance, $i->currency) }}</strong></td>
                        <td><span class="sx-pill {{ $i->status_tone }}">{{ $i->display_status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="sx-empty"><i class="bi bi-receipt"></i>No invoices yet.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div data-sx-pane="payments" hidden>
    <div class="sx-card">
        <div class="table-responsive">
            <table class="sx-table">
                <thead><tr><th>Date</th><th>Invoice</th><th>Method</th><th>Reference</th><th class="sx-num">Amount</th><th>Recorded by</th></tr></thead>
                <tbody>
                @forelse($payments as $p)
                    <tr>
                        <td class="small">{{ $p->paid_at->format('d M Y H:i') }}</td>
                        <td class="small">{{ $p->invoice?->invoice_number ?? 'Credit' }}</td>
                        <td class="small">{{ $p->method_label }}</td>
                        <td class="small">{{ $p->reference ?: '—' }}</td>
                        <td class="sx-num"><strong>{{ Money::format($p->amount, $p->currency) }}</strong></td>
                        <td class="small">{{ $p->recorder?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="sx-empty"><i class="bi bi-cash"></i>No payments recorded.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div data-sx-pane="history" hidden>
    <div class="sx-card">
        <div class="sx-card-body">
            <ul class="sx-timeline">
                @forelse($calls as $call)
                    <li>
                        <span class="sx-ico"><i class="bi {{ SubscriberCall::CHANNEL_ICONS[$call->channel] ?? 'bi-telephone' }}"></i></span>
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <strong style="color:var(--terra-navy,#19265d)">{{ $call->subject ?: $call->channel_label }}</strong>
                                <span class="sx-muted small">{{ $call->called_at->format('d M Y H:i') }}</span>
                            </div>
                            <div class="small sx-muted">
                                {{ $call->channel_label }} · {{ $call->direction === 'inbound' ? 'Inbound' : 'Outbound' }} ·
                                <span class="sx-pill {{ in_array($call->outcome, ['answered', 'sent']) ? 'success' : (in_array($call->outcome, ['started', 'callback']) ? 'info' : 'warning') }}">{{ $call->outcome_label }}</span>
                                @if($call->duration_seconds) · {{ $call->duration_label }} @endif
                                · by {{ $call->caller?->name ?? 'system' }}
                            </div>
                            @if($call->notes)<div class="small mt-1" style="white-space:pre-line">{{ \Illuminate\Support\Str::limit($call->notes, 600) }}</div>@endif
                            @if($call->channel === 'browser' && $call->outcome === 'started' && $call->called_at->gt(now()->subHours(12)))
                                <a href="{{ route('calls.join', $call) }}" class="small">Rejoin call</a>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="sx-empty d-block"><i class="bi bi-telephone"></i>No calls or emails recorded yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

<div data-sx-pane="documents" hidden>
    @if($subscriber->propertyOwner)
        @include('documents._profile-panel', ['owner' => $subscriber->propertyOwner, 'ownerKey' => 'owner', 'documents' => $ownerDocuments, 'canUpload' => true, 'heading' => 'Owner documents & contracts'])
    @endif
    @foreach($contracts as $c)
        @if($c->documents->isNotEmpty())
            <div class="sx-card mt-3">
                <div class="sx-card-head"><h3>Signed agreement · {{ $c->contract_number }}</h3></div>
                <ul class="list-unstyled m-0 p-3">
                    @foreach($c->documents as $d)
                        <li class="d-flex justify-content-between align-items-center py-1"><span><i class="bi bi-file-earmark-pdf text-danger"></i> {{ $d->title }}</span>
                            <a href="{{ route('documents.download', $d) }}" target="_blank" class="btn btn-sm btn-light">View</a></li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endforeach
    @if(! $subscriber->propertyOwner && $contracts->every(fn ($c) => $c->documents->isEmpty()))
        <div class="sx-card"><div class="sx-empty"><i class="bi bi-folder2-open"></i>Signed subscription agreements appear here once uploaded on a contract.</div></div>
    @endif
</div>

<div data-sx-pane="profile" hidden>
    <div class="sx-card">
        <div class="sx-card-body">
            <dl class="sx-dl">
                <dt>Account email</dt><dd>{{ $subscriber->email }}</dd>
                <dt>Phone</dt><dd>{{ $phoneDisplay ?? '—' }}</dd>
                <dt>Joined</dt><dd>{{ $subscriber->created_at?->format('d M Y') }}</dd>
                @if($subscriber->propertyOwner)
                    <dt>Owner type</dt><dd>{{ ucfirst($subscriber->propertyOwner->owner_type) }}{{ $subscriber->propertyOwner->company_name ? ' · ' . $subscriber->propertyOwner->company_name : '' }}</dd>
                    <dt>Verification</dt><dd>{{ ucfirst($subscriber->propertyOwner->verification_status) }}</dd>
                    <dt>Location</dt><dd>{{ collect([$subscriber->propertyOwner->district, $subscriber->propertyOwner->province, $subscriber->propertyOwner->country])->filter()->implode(', ') ?: '—' }}</dd>
                    <dt>Profile</dt><dd><a href="{{ route('admin.owners.show', $subscriber->propertyOwner) }}">Open owner record</a></dd>
                @elseif($subscriber->propertyManager)
                    <dt>Job title</dt><dd>{{ $subscriber->propertyManager->job_title ?: '—' }}</dd>
                    <dt>Status</dt><dd>{{ ucfirst($subscriber->propertyManager->status) }}</dd>
                    <dt>Location</dt><dd>{{ collect([$subscriber->propertyManager->district, $subscriber->propertyManager->province, $subscriber->propertyManager->country])->filter()->implode(', ') ?: '—' }}</dd>
                    <dt>Profile</dt><dd><a href="{{ route('admin.managers.show', $subscriber->propertyManager) }}">Open manager record</a></dd>
                @endif
            </dl>
        </div>
    </div>
</div>

{{-- Email modal --}}
<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('admin.subscribers.email', $subscriber) }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title" id="emailModalLabel">Email {{ $subscriber->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div class="mb-2 small sx-muted">To: {{ $subscriber->email }} · replies come back to {{ auth()->user()->email }}</div>
                <div class="mb-3">
                    <label class="form-label" for="emSubject">Subject</label>
                    <input id="emSubject" name="subject" required maxlength="150" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}">
                    @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-2">
                    <label class="form-label" for="emBody">Message</label>
                    <textarea id="emBody" name="body" rows="8" required maxlength="5000" class="form-control @error('body') is-invalid @enderror">{{ old('body') }}</textarea>
                    @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-check">
                    <input type="hidden" name="copy_in_app" value="0">
                    <input class="form-check-input" type="checkbox" name="copy_in_app" value="1" id="emCopy" checked>
                    <label class="form-check-label small" for="emCopy">Also show it as an in-app notification</label>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="sx-btn sx-btn-outline" data-bs-dismiss="modal">Cancel</button><button class="sx-btn sx-btn-primary"><i class="bi bi-send"></i> Send email</button></div>
        </form>
    </div>
</div>

{{-- Log call modal --}}
<div class="modal fade" id="callModal" tabindex="-1" aria-labelledby="callModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.subscribers.calls.store', $subscriber) }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title" id="callModalLabel">Log a call</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body row g-3">
                <div class="col-6">
                    <label class="form-label" for="clChannel">Channel</label>
                    <select id="clChannel" name="channel" class="form-select"><option value="phone">Phone</option><option value="whatsapp">WhatsApp</option><option value="browser">In-platform</option></select>
                </div>
                <div class="col-6">
                    <label class="form-label" for="clDir">Direction</label>
                    <select id="clDir" name="direction" class="form-select"><option value="outbound">We called them</option><option value="inbound">They called us</option></select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="clOutcome">Outcome</label>
                    <select id="clOutcome" name="outcome" class="form-select">@foreach(SubscriberCall::OUTCOMES as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select>
                </div>
                <div class="col-6"><label class="form-label" for="clMin">Minutes</label><input id="clMin" type="number" name="minutes" min="0" max="600" class="form-control"></div>
                <div class="col-6"><label class="form-label" for="clSec">Seconds</label><input id="clSec" type="number" name="seconds" min="0" max="59" class="form-control"></div>
                <div class="col-12"><label class="form-label" for="clSubject">Topic</label><input id="clSubject" name="subject" maxlength="150" class="form-control" placeholder="e.g. Overdue invoice follow-up"></div>
                <div class="col-12"><label class="form-label" for="clNotes">Notes</label><textarea id="clNotes" name="notes" rows="3" maxlength="3000" class="form-control"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="sx-btn sx-btn-outline" data-bs-dismiss="modal">Cancel</button><button class="sx-btn sx-btn-primary">Save</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var tabs = document.querySelectorAll('[data-sx-tab]');
    function show(name) {
        tabs.forEach(function (t) { t.classList.toggle('active', t.dataset.sxTab === name); });
        document.querySelectorAll('[data-sx-pane]').forEach(function (p) { p.hidden = p.dataset.sxPane !== name; });
    }
    tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.sxTab); history.replaceState(null, '', '#' + t.dataset.sxTab); }); });
    var h = location.hash.replace('#', '');
    if (h && document.querySelector('[data-sx-pane="' + h + '"]')) show(h);
    @if($errors->has('subject') || $errors->has('body'))
        document.addEventListener('DOMContentLoaded', function () { if (window.bootstrap) new bootstrap.Modal(document.getElementById('emailModal')).show(); });
    @endif
})();
</script>
@endpush

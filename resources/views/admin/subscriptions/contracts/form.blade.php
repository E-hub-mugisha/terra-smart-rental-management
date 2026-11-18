@extends('layouts.app')

@php $editing = $contract->exists; @endphp
@section('title', $editing ? 'Edit ' . $contract->contract_number : 'New subscription contract')
@section('page_heading', $editing ? 'Edit contract ' . $contract->contract_number : 'New subscription contract')
@section('page_subtitle', 'The agreement between Terra and a property owner or manager.')

@use('App\Models\SubscriptionContract')
@include('partials.sx-styles')

@section('content')
@php
    $val = fn ($field, $default = null) => old($field, $contract->{$field} ?? $default);
    $dateVal = fn ($field) => old($field, $contract->{$field} instanceof \Carbon\CarbonInterface ? $contract->{$field}->format('Y-m-d') : $contract->{$field});
    $planJson = $plans->mapWithKeys(fn ($p) => [$p->id => ['price' => (float) $p->price, 'cycle' => $p->billing_cycle, 'currency' => $p->currency, 'name' => $p->name]]);
@endphp

<a href="{{ $editing ? route('admin.subscriptions.contracts.show', $contract) : route('admin.subscriptions.contracts.index') }}" class="sx-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3"><i class="bi bi-arrow-left"></i> Back</a>

<form method="POST" action="{{ $editing ? route('admin.subscriptions.contracts.update', $contract) : route('admin.subscriptions.contracts.store') }}">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="sx-card mb-3">
                <div class="sx-card-head"><h2>Parties & plan</h2></div>
                <div class="sx-card-body row g-3">
                    <div class="col-md-7">
                        <label class="form-label" for="user_id">Subscriber <span class="text-danger">*</span></label>
                        <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror" required @disabled($editing && $contract->invoices()->exists())>
                            <option value="">Choose an owner or manager…</option>
                            @foreach($subscribers->groupBy('role') as $role => $group)
                                <optgroup label="{{ $role === 'owner' ? 'Property owners' : 'Property managers' }}">
                                    @foreach($group as $s)
                                        <option value="{{ $s->id }}" @selected((int) $val('user_id') === $s->id)>{{ $s->name }} · {{ $s->email }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @if($editing && $contract->invoices()->exists())
                            <input type="hidden" name="user_id" value="{{ $contract->user_id }}">
                            <div class="form-text">The subscriber cannot change once invoices exist.</div>
                        @endif
                        @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="subscription_plan_id">Plan</label>
                        <select id="subscription_plan_id" name="subscription_plan_id" class="form-select">
                            <option value="">Custom (no plan)</option>
                            @foreach($plans as $p)
                                <option value="{{ $p->id }}" @selected((int) $val('subscription_plan_id') === $p->id)>{{ $p->name }} · {{ \App\Support\Money::format($p->price, $p->currency) }}/{{ $p->billing_cycle === 'annual' ? 'yr' : ($p->billing_cycle === 'quarterly' ? 'qtr' : 'mo') }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Choosing a plan fills in the fee and billing cycle.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="title">Contract title <span class="text-danger">*</span></label>
                        <input id="title" name="title" maxlength="150" required class="form-control @error('title') is-invalid @enderror" value="{{ $val('title') }}">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="sx-card mb-3">
                <div class="sx-card-head"><h2>Term & billing</h2></div>
                <div class="sx-card-body row g-3">
                    <div class="col-sm-6 col-md-4">
                        <label class="form-label" for="start_date">Start date <span class="text-danger">*</span></label>
                        <input type="date" id="start_date" name="start_date" required class="form-control @error('start_date') is-invalid @enderror" value="{{ $dateVal('start_date') }}">
                        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <label class="form-label" for="end_date">End date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ $dateVal('end_date') }}">
                        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Leave empty for an open-ended agreement.</div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <label class="form-label" for="signed_at">Signed on</label>
                        <input type="date" id="signed_at" name="signed_at" class="form-control @error('signed_at') is-invalid @enderror" value="{{ $dateVal('signed_at') }}" max="{{ today()->toDateString() }}">
                        @error('signed_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <label class="form-label" for="billing_cycle">Billing cycle <span class="text-danger">*</span></label>
                        <select id="billing_cycle" name="billing_cycle" class="form-select">
                            @foreach(SubscriptionContract::CYCLE_LABELS as $k => $label)<option value="{{ $k }}" @selected($val('billing_cycle') === $k)>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <label class="form-label" for="amount">Fee per cycle <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select name="currency" id="currency" class="form-select" style="max-width:90px" aria-label="Currency">
                                @foreach(['RWF', 'USD', 'EUR', 'KES', 'UGX', 'TZS'] as $cur)<option @selected($val('currency') === $cur)>{{ $cur }}</option>@endforeach
                            </select>
                            <input type="number" step="0.01" min="0" id="amount" name="amount" required class="form-control @error('amount') is-invalid @enderror" value="{{ $val('amount') }}">
                        </div>
                        @error('amount')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <label class="form-label" for="payment_due_days">Payment due (days)</label>
                        <input type="number" min="0" max="90" id="payment_due_days" name="payment_due_days" required class="form-control" value="{{ $val('payment_due_days', 7) }}">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="auto_renew" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="auto_renew" name="auto_renew" value="1" @checked($val('auto_renew'))>
                            <label class="form-check-label" for="auto_renew">Renew automatically at the end date</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sx-card mb-3">
                <div class="sx-card-head"><h2>Terms</h2></div>
                <div class="sx-card-body">
                    <label class="visually-hidden" for="terms">Terms</label>
                    <textarea id="terms" name="terms" rows="12" maxlength="20000" class="form-control" style="font-size:.88rem">{{ $val('terms') }}</textarea>
                    <label class="form-label mt-3" for="notes">Internal notes</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="5000" class="form-control">{{ $val('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="sx-card" style="position:sticky;top:90px;">
                <div class="sx-card-head"><h2>Summary</h2></div>
                <div class="sx-card-body">
                    <dl class="sx-dl mb-3">
                        <dt>Status</dt><dd>{{ $editing ? $contract->status_label : 'Pending (until activated)' }}</dd>
                        <dt>Fee</dt><dd id="sumFee">—</dd>
                        <dt>Term</dt><dd id="sumTerm">—</dd>
                        <dt>Contract value</dt><dd id="sumValue">—</dd>
                    </dl>
                    @unless($editing)
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="activate_now" value="1" id="activate_now">
                            <label class="form-check-label small" for="activate_now">The contract is already signed: activate it now and issue the first invoice</label>
                        </div>
                    @endunless
                    <button class="sx-btn sx-btn-primary w-100 justify-content-center"><i class="bi bi-check2-circle"></i> {{ $editing ? 'Save changes' : 'Create contract' }}</button>
                    <p class="small sx-muted mt-3 mb-0">After saving you can upload the signed PDF on the contract page. The subscriber is notified in-app and by email.</p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var plans = {!! json_encode($planJson) !!};
    var $ = function (id) { return document.getElementById(id); };
    var months = { monthly: 1, quarterly: 3, annual: 12 };
    $('subscription_plan_id').addEventListener('change', function () {
        var p = plans[this.value];
        if (!p) return;
        $('amount').value = p.price; $('billing_cycle').value = p.cycle; $('currency').value = p.currency;
        if (!$('title').value || $('title').value === 'Terra subscription agreement') $('title').value = 'Terra ' + p.name + ' subscription agreement';
        summary();
    });
    function summary() {
        var amt = parseFloat($('amount').value || 0), cur = $('currency').value, cyc = $('billing_cycle').value;
        var fmt = function (n) { return cur + ' ' + n.toLocaleString(undefined, { maximumFractionDigits: cur === 'RWF' ? 0 : 2 }); };
        $('sumFee').textContent = fmt(amt) + ' / ' + (cyc === 'annual' ? 'year' : cyc === 'quarterly' ? 'quarter' : 'month');
        var s = $('start_date').value, e = $('end_date').value;
        if (s && e) {
            var a = new Date(s), b = new Date(e); b.setDate(b.getDate() + 1);
            var m = (b.getFullYear() - a.getFullYear()) * 12 + (b.getMonth() - a.getMonth());
            $('sumTerm').textContent = m + ' months';
            $('sumValue').textContent = fmt(Math.ceil(m / months[cyc]) * amt);
        } else { $('sumTerm').textContent = s ? 'Open-ended' : '—'; $('sumValue').textContent = '—'; }
    }
    ['amount', 'currency', 'billing_cycle', 'start_date', 'end_date'].forEach(function (id) { $(id).addEventListener('input', summary); $(id).addEventListener('change', summary); });
    summary();
})();
</script>
@endpush

@extends('layouts.app')

@section('title', 'Review ' . $tenantApplication->application_number)

@push('styles')
<style>
/* Tenant application review: scoped under .ta so nothing leaks into the layout.
   Tip: if your top navbar is fixed, set --ta-sticky on .ta to its height. */
.ta {
    --o: #D05208; --o-dark: #b94706; --o-soft: #fff2e9; --o-line: #f4d3bf;
    --navy: #19265d; --ink: #27334a; --mute: #6b778c;
    --line: #e6e9f0; --line-soft: #eff1f6; --wash: #f6f7fb;
    --ok: #147847; --ok-bg: #e8f7ee; --ok-line: #cfeedb;
    --warn: #8f5a08; --warn-bg: #fff3dc; --warn-line: #f3dfb0;
    --bad: #b42318; --bad-bg: #fff1f0; --bad-line: #f3cdc9;
    --ta-sticky: 0px;
    color: var(--ink); font-size: .9rem; padding-bottom: 40px;
}
.ta *, .ta *::before, .ta *::after { box-sizing: border-box; }
.ta :focus-visible { outline: 2px solid var(--o); outline-offset: 2px; border-radius: 6px; }
.ta .min-w-0 { min-width: 0; }
.ta .is-mute { color: var(--mute); }

.ta-back { display: inline-flex; align-items: center; gap: 7px; margin-bottom: 14px; color: var(--mute); font-size: .8rem; font-weight: 600; text-decoration: none; }
.ta-back:hover { color: var(--o); }
.ta-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
.ta-title { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin: 0 0 6px; color: var(--navy); font-size: clamp(1.5rem, 2.6vw, 2rem); font-weight: 800; letter-spacing: -.035em; }
.ta-sub { margin: 0; color: var(--mute); line-height: 1.6; }

.ta-pill { display: inline-flex; align-items: center; gap: 6px; width: fit-content; padding: 4px 11px; border-radius: 999px; background: var(--wash); color: var(--mute); font-size: .76rem; font-weight: 700; white-space: nowrap; letter-spacing: 0; }
.ta-pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.ta-pill.is-warning { background: var(--warn-bg); color: var(--warn); }
.ta-pill.is-success { background: var(--ok-bg); color: var(--ok); }
.ta-pill.is-danger { background: var(--bad-bg); color: var(--bad); }

.ta-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 42px; padding: 9px 16px;
    border: 1px solid var(--line); border-radius: 10px; background: #fff; color: var(--navy);
    font-size: .85rem; font-weight: 700; line-height: 1.2; text-decoration: none; cursor: pointer;
    transition: background .15s, border-color .15s, color .15s; }
.ta-btn:hover { background: var(--o-soft); border-color: var(--o-line); color: var(--o); }
.ta-btn.is-block { width: 100%; }
.ta-btn.is-ok { background: var(--ok); border-color: var(--ok); color: #fff; }
.ta-btn.is-ok:hover { background: #0f6339; border-color: #0f6339; color: #fff; }
.ta-btn.is-bad { color: var(--bad); border-color: var(--bad-line); background: #fff; }
.ta-btn.is-bad:hover { background: var(--bad-bg); color: var(--bad); border-color: #eab7b2; }
.ta-btn:disabled { opacity: .6; pointer-events: none; }

.ta-alert { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 16px; padding: 12px 14px; border-radius: 12px; font-weight: 600; }
.ta-alert.is-ok { background: var(--ok-bg); color: var(--ok); border: 1px solid var(--ok-line); }
.ta-alert.is-bad { background: var(--bad-bg); color: var(--bad); border: 1px solid var(--bad-line); }
.ta-alert.is-warn { background: var(--warn-bg); color: var(--warn); border: 1px solid var(--warn-line); }
.ta-alert ul { margin: 4px 0 0; padding-left: 18px; font-weight: 500; }
.ta-alert .btn-close { margin-left: auto; font-size: .7rem; }

.ta-panel { min-width: 0; background: #fff; border: 1px solid var(--line); border-radius: 16px; }
.ta-panel + .ta-panel { margin-top: 20px; }
.ta-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 17px 20px; border-bottom: 1px solid var(--line); }
.ta-panel-title { margin: 0; color: var(--navy); font-size: 1rem; font-weight: 800; letter-spacing: -.015em; }
.ta-panel-note { margin: 3px 0 0; color: var(--mute); font-size: .79rem; }
.ta-panel-body { padding: 20px; }

/* Key figures strip */
.ta-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 20px; background: #fff; border: 1px solid var(--line); border-radius: 16px; }
.ta-strip > div { min-width: 0; padding: 16px 20px; border-left: 1px solid var(--line); }
.ta-strip > div:first-child { border-left: 0; }
.ta-strip dt { color: var(--mute); font-size: .76rem; font-weight: 700; }
.ta-strip dd { margin: 6px 0 0; color: var(--navy); font-size: 1.15rem; font-weight: 800; letter-spacing: -.02em; overflow-wrap: anywhere; }
.ta-strip dd small { display: block; margin-top: 3px; color: var(--mute); font-size: .75rem; font-weight: 600; letter-spacing: 0; }

/* Detail grid */
.ta-dl { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); margin: 0; border: 1px solid var(--line-soft); border-radius: 12px; overflow: hidden; }
.ta-dl > div { margin: -.5px; padding: 12px 14px; border-right: 1px solid var(--line-soft); border-bottom: 1px solid var(--line-soft); }
.ta-dl dt { margin-bottom: 3px; color: var(--mute); font-size: .74rem; font-weight: 700; }
.ta-dl dd { margin: 0; color: var(--ink); font-weight: 650; overflow-wrap: anywhere; }
.ta-dl dd.is-empty { color: var(--mute); font-weight: 500; }

/* Offered vs listed rent */
.ta-compare { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 18px; margin-top: 14px; padding: 12px 14px; border-radius: 12px; background: var(--wash); font-size: .82rem; }
.ta-compare strong { color: var(--navy); }
.ta-delta { padding: 2px 9px; border-radius: 99px; font-size: .74rem; font-weight: 800; }
.ta-delta.is-up { background: var(--ok-bg); color: var(--ok); }
.ta-delta.is-down { background: var(--bad-bg); color: var(--bad); }
.ta-delta.is-same { background: #edf0fb; color: var(--navy); }

.ta-notes { margin: 0; line-height: 1.7; white-space: pre-line; overflow-wrap: anywhere; }
.ta-reviewer { display: flex; align-items: center; gap: 10px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--line-soft); color: var(--mute); font-size: .8rem; }
.ta-avatar { display: grid; place-items: center; flex: 0 0 34px; width: 34px; height: 34px; border-radius: 10px; background: #edf0fb; color: var(--navy); font-size: .75rem; font-weight: 800; }

/* Decision sidebar */
.ta-side { position: sticky; top: calc(var(--ta-sticky) + 16px); }
.ta-seg { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; padding: 4px; margin-bottom: 18px; background: var(--wash); border-radius: 11px; }
.ta-seg button { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 8px 10px; border: 0; border-radius: 8px; background: transparent; color: var(--mute); font-size: .82rem; font-weight: 700; }
.ta-seg button:hover { color: var(--navy); }
.ta-seg button.is-active { background: #fff; box-shadow: 0 1px 3px rgba(25,38,93,.12); }
.ta-seg button.is-active[data-pane="approve"] { color: var(--ok); }
.ta-seg button.is-active[data-pane="reject"] { color: var(--bad); }
.ta-pane[hidden] { display: none; }
.ta-group { margin: 18px 0 12px; padding-top: 14px; border-top: 1px solid var(--line-soft); color: var(--navy); font-size: .8rem; font-weight: 800; }
.ta-group:first-of-type { margin-top: 4px; padding-top: 0; border-top: 0; }
.ta .form-label { margin-bottom: 6px; color: #34415a; font-size: .8rem; font-weight: 700; }
.ta .form-control, .ta .form-select { min-height: 42px; border-color: #dde2ec; border-radius: 9px; font-size: .86rem; box-shadow: none; }
.ta textarea.form-control { min-height: 80px; }
.ta .form-control:focus, .ta .form-select:focus { border-color: var(--o); box-shadow: 0 0 0 3px rgba(208,82,8,.1); }
.ta .form-text { color: var(--mute); font-size: .76rem; }
.ta-hint { margin-top: 6px; font-size: .76rem; color: var(--mute); }
.ta-lease-row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line-soft); font-size: .83rem; }
.ta-lease-row:last-of-type { border-bottom: 0; }
.ta-lease-row span:first-child { color: var(--mute); }
.ta-lease-row span:last-child { color: var(--ink); font-weight: 700; text-align: right; }

@media (max-width: 991.98px) {
    .ta-side { position: static; }
    .ta-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ta-strip > div:nth-child(3) { border-left: 0; }
    .ta-strip > div:nth-child(n+3) { border-top: 1px solid var(--line); }
}
@media (max-width: 575.98px) {
    .ta-panel-head, .ta-panel-body { padding: 16px; }
    .ta-strip > div { padding: 14px; }
}
@media (prefers-reduced-motion: reduce) {
    .ta *, .ta *::before, .ta *::after { transition-duration: .01ms !important; }
}
</style>
@endpush

@section('content')
@php
    use Illuminate\Support\Str;

    $app    = $tenantApplication;
    $unit   = $app->unit;
    $tenant = $app->tenant;
    $lease  = $app->lease;
    $status = $app->status;

    $pill = ['pending' => 'is-warning', 'approved' => 'is-success', 'rejected' => 'is-danger', 'withdrawn' => ''][$status] ?? '';

    $date       = $app->application_date ?? $app->created_at;
    $currency   = $app->currency ?? 'RWF';
    $decimals   = $currency === 'RWF' ? 0 : 2;
    $money      = fn ($cur, $amount, $d = null) => ($cur ?: 'RWF') . ' ' . number_format((float) $amount, $d ?? (($cur ?: 'RWF') === 'RWF' ? 0 : 2));
    $headline   = fn ($v) => $v ? Str::headline((string) $v) : null;
    $ordinal    = fn (int $d) => $d . (in_array($d % 100, [11, 12, 13]) ? 'th' : match ($d % 10) { 1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th' });

    // Offered rent compared with the listed rent, to help the decision
    $offered = (float) $app->offered_rent;
    $listed  = $unit ? (float) $unit->rent_amount : null;
    $diffPct = ($listed && $listed > 0) ? (int) round(($offered - $listed) / $listed * 100) : null;

    $canDecide      = !$lease && $status === 'pending' && $unit;
    $unitUnavailable = $unit && !in_array($unit->status, ['available', 'reserved'], true);

    // Restore old input only into the form that was actually submitted
    $submitted = old('_form');
    $oldFor    = fn (string $form, string $field, $default = null) => $submitted === $form ? old($field, $default) : $default;
    $startPane = $submitted === 'reject' ? 'reject' : 'approve';

    $reviewerName = $app->reviewer?->name;
    $reviewerInitials = $reviewerName
        ? collect(explode(' ', trim($reviewerName)))->filter()->take(2)->map(fn ($p) => strtoupper(substr($p, 0, 1)))->implode('')
        : null;
@endphp

<div class="ta container-fluid py-4">

    <a href="{{ route('admin.tenant-applications.index') }}" class="ta-back">
        <i class="bi bi-arrow-left"></i> Back to applications
    </a>

    {{-- Heading --}}
    <div class="ta-head">
        <div>
            <h1 class="ta-title">
                {{ $app->application_number }}
                <span class="ta-pill {{ $pill }}">{{ ucfirst($status) }}</span>
            </h1>
            <p class="ta-sub">
                Application from <strong>{{ $tenant?->full_name ?? 'an unknown tenant' }}</strong>
                @if($unit) for Unit {{ $unit->unit_number }} @endif
                @if($date), submitted {{ $date->format('d M Y') }} @endif
            </p>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-dismissible fade show p-0 border-0 bg-transparent mb-0" role="status">
            <div class="ta-alert is-ok w-100">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="ta-alert is-bad" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                Please fix the following and try again:
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        </div>
    @endif

    {{-- Key figures --}}
    <dl class="ta-strip">
        <div>
            <dt>Offered rent</dt>
            <dd>{{ $money($currency, $offered) }}<small>per month</small></dd>
        </div>
        <div>
            <dt>Requested move-in</dt>
            <dd class="{{ $app->desired_move_in_date ? '' : 'is-mute' }}">
                {{ $app->desired_move_in_date?->format('d M Y') ?? 'Not specified' }}
                @if($app->desired_move_in_date && $status === 'pending' && $app->desired_move_in_date->isPast())
                    <small style="color:var(--bad);">Date has passed</small>
                @endif
            </dd>
        </div>
        <div>
            <dt>Occupants</dt>
            <dd>{{ $app->number_of_occupants ?? 'Not specified' }}</dd>
        </div>
        <div>
            <dt>Employment</dt>
            <dd>{{ $headline($app->employment_status) ?? 'Not specified' }}</dd>
        </div>
    </dl>

    <div class="row g-4">

        {{-- ================= MAIN ================= --}}
        <div class="col-lg-8">

            <section class="ta-panel">
                <div class="ta-panel-head">
                    <div>
                        <h2 class="ta-panel-title">Applicant</h2>
                        <p class="ta-panel-note">Contact details and application dates</p>
                    </div>
                </div>

                <div class="ta-panel-body">
                    <dl class="ta-dl mb-0">
                        <div>
                            <dt>Full name</dt>
                            <dd class="{{ $tenant?->full_name ? '' : 'is-empty' }}">{{ $tenant?->full_name ?? 'Not available' }}</dd>
                        </div>
                        <div>
                            <dt>Email</dt>
                            <dd class="{{ $tenant?->email ? '' : 'is-empty' }}">
                                @if($tenant?->email)<a href="mailto:{{ $tenant->email }}">{{ $tenant->email }}</a>@else Not available @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Phone</dt>
                            <dd class="{{ $tenant?->phone ? '' : 'is-empty' }}">
                                @if($tenant?->phone)<a href="tel:{{ preg_replace('/\s+/', '', $tenant->phone) }}">{{ $tenant->phone }}</a>@else Not available @endif
                            </dd>
                        </div>
                        <div>
                            <dt>Application date</dt>
                            <dd>{{ $date?->format('d M Y') ?? 'Not available' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="ta-panel">
                <div class="ta-panel-head">
                    <div>
                        <h2 class="ta-panel-title">Requested unit</h2>
                        <p class="ta-panel-note">What the applicant wants to rent</p>
                    </div>
                    @if($unit)
                        <span class="ta-pill {{ $unit->status === 'available' ? 'is-success' : ($unit->status === 'maintenance' ? 'is-warning' : '') }}">
                            {{ $headline($unit->status ?? 'unknown') }}
                        </span>
                    @endif
                </div>

                <div class="ta-panel-body">
                    @if($unit)
                        @if($unitUnavailable && $canDecide)
                            <div class="ta-alert is-warn">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>This unit is currently {{ Str::lower($headline($unit->status)) }}. Check it is free before approving.</span>
                            </div>
                        @endif

                        <dl class="ta-dl mb-0">
                            <div>
                                <dt>Unit number</dt>
                                <dd>{{ $unit->unit_number }}</dd>
                            </div>
                            <div>
                                <dt>Unit type</dt>
                                <dd class="{{ $unit->unit_type ? '' : 'is-empty' }}">{{ $headline($unit->unit_type) ?? 'Not specified' }}</dd>
                            </div>
                            <div>
                                <dt>Listed rent</dt>
                                <dd>{{ $money($unit->currency, $unit->rent_amount) }} <span class="is-mute fw-normal">/ month</span></dd>
                            </div>
                            <div>
                                <dt>Security deposit</dt>
                                <dd>{{ $money($unit->currency, $unit->deposit_amount ?? 0) }}</dd>
                            </div>
                        </dl>

                        @if($diffPct !== null)
                            <div class="ta-compare">
                                <span>Offered <strong>{{ $money($currency, $offered) }}</strong></span>
                                <span>Listed <strong>{{ $money($unit->currency, $listed) }}</strong></span>
                                <span class="ta-delta {{ $diffPct > 0 ? 'is-up' : ($diffPct < 0 ? 'is-down' : 'is-same') }}">
                                    {{ $diffPct === 0 ? 'Matches the listed rent' : (abs($diffPct) . '% ' . ($diffPct > 0 ? 'above' : 'below') . ' listed rent') }}
                                </span>
                            </div>
                        @endif
                    @else
                        <div class="ta-alert is-warn mb-0">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span>The requested unit could not be found, so approval is unavailable.</span>
                        </div>
                    @endif
                </div>
            </section>

            @if($app->decision_notes)
                <section class="ta-panel">
                    <div class="ta-panel-head">
                        <div>
                            <h2 class="ta-panel-title">Decision notes</h2>
                            <p class="ta-panel-note">Recorded when this application was reviewed</p>
                        </div>
                    </div>

                    <div class="ta-panel-body">
                        <p class="ta-notes">{{ $app->decision_notes }}</p>

                        @if($reviewerName)
                            <div class="ta-reviewer">
                                <span class="ta-avatar">{{ $reviewerInitials ?: 'R' }}</span>
                                <span>
                                    Reviewed by <strong style="color:var(--ink);">{{ $reviewerName }}</strong>
                                    @if($app->reviewed_at) on {{ $app->reviewed_at->format('d M Y, H:i') }} @endif
                                </span>
                            </div>
                        @endif
                    </div>
                </section>
            @endif
        </div>

        {{-- ================= DECISION ================= --}}
        <div class="col-lg-4">
            <div class="ta-side">

                @if($lease)
                    <section class="ta-panel">
                        <div class="ta-panel-head">
                            <div>
                                <h2 class="ta-panel-title">Lease created</h2>
                                <p class="ta-panel-note">This application led to a lease</p>
                            </div>
                            <span class="ta-pill is-success">{{ ucfirst($lease->status) }}</span>
                        </div>

                        <div class="ta-panel-body">
                            <div class="ta-lease-row"><span>Lease number</span><span>{{ $lease->lease_number }}</span></div>
                            <div class="ta-lease-row"><span>Start date</span><span>{{ $lease->start_date?->format('d M Y') ?? 'Not set' }}</span></div>
                            <div class="ta-lease-row"><span>Monthly rent</span><span>{{ $money($lease->currency, $lease->monthly_rent) }}</span></div>
                        </div>
                    </section>

                @elseif($canDecide)
                    <section class="ta-panel">
                        <div class="ta-panel-head">
                            <div>
                                <h2 class="ta-panel-title">Make a decision</h2>
                                <p class="ta-panel-note">Your decision is recorded under your name</p>
                            </div>
                        </div>

                        <div class="ta-panel-body">
                            <div class="ta-seg" role="tablist" aria-label="Decision">
                                <button type="button" role="tab" data-pane="approve" class="{{ $startPane === 'approve' ? 'is-active' : '' }}" aria-selected="{{ $startPane === 'approve' ? 'true' : 'false' }}">
                                    <i class="bi bi-check-circle"></i> Approve
                                </button>
                                <button type="button" role="tab" data-pane="reject" class="{{ $startPane === 'reject' ? 'is-active' : '' }}" aria-selected="{{ $startPane === 'reject' ? 'true' : 'false' }}">
                                    <i class="bi bi-x-circle"></i> Reject
                                </button>
                            </div>

                            {{-- Approve --}}
                            <div class="ta-pane" id="pane-approve" role="tabpanel" @if($startPane !== 'approve') hidden @endif>
                                <p class="ta-hint mt-0 mb-3">Approving creates a lease for Unit {{ $unit->unit_number }}. Confirm the agreed terms first.</p>

                                <form method="POST" action="{{ route('admin.tenant-applications.approve', $app) }}" data-confirm="Approve this application and create its lease?" data-busy="Creating lease…">
                                    @csrf
                                    <input type="hidden" name="_form" value="approve">

                                    <div class="ta-group">Lease term</div>

                                    <div class="mb-3">
                                        <label class="form-label" for="start_date">Start date *</label>
                                        <input id="start_date" type="date" name="start_date" class="form-control" required
                                               value="{{ $oldFor('approve', 'start_date', $app->desired_move_in_date?->format('Y-m-d') ?? now()->toDateString()) }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="end_date">End date</label>
                                        <input id="end_date" type="date" name="end_date" class="form-control"
                                               value="{{ $oldFor('approve', 'end_date') }}">
                                        <div class="form-text">Leave blank for an open-ended lease.</div>
                                    </div>

                                    <div class="ta-group">Rent and payment</div>

                                    <div class="mb-3">
                                        <label class="form-label" for="monthly_rent">Agreed monthly rent ({{ $currency }}) *</label>
                                        <input id="monthly_rent" type="number" name="monthly_rent" class="form-control" min="0.01" step="0.01" required
                                               value="{{ $oldFor('approve', 'monthly_rent', $app->offered_rent ?? $unit->rent_amount) }}">
                                        @if($listed !== null)
                                            <div class="ta-hint">Listed rent is {{ $money($unit->currency, $listed) }}.</div>
                                        @endif
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="deposit_amount">Security deposit *</label>
                                        <input id="deposit_amount" type="number" name="deposit_amount" class="form-control" min="0" step="0.01" required
                                               value="{{ $oldFor('approve', 'deposit_amount', $unit->deposit_amount ?? 0) }}">
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-6">
                                            <label class="form-label" for="rent_due_day">Rent due day *</label>
                                            <select id="rent_due_day" name="rent_due_day" class="form-select" required>
                                                @for($day = 1; $day <= 28; $day++)
                                                    <option value="{{ $day }}" @selected((int) $oldFor('approve', 'rent_due_day', 1) === $day)>{{ $ordinal($day) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label" for="payment_frequency">Frequency *</label>
                                            <select id="payment_frequency" name="payment_frequency" class="form-select" required>
                                                @foreach(['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'annually' => 'Annually'] as $value => $text)
                                                    <option value="{{ $value }}" @selected($oldFor('approve', 'payment_frequency', 'monthly') === $value)>{{ $text }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="ta-group">Notes</div>

                                    <div class="mb-3">
                                        <label class="form-label" for="approve_notes">Decision notes</label>
                                        <textarea id="approve_notes" name="decision_notes" class="form-control" rows="2">{{ $oldFor('approve', 'decision_notes') }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="terms">Lease terms</label>
                                        <textarea id="terms" name="terms" class="form-control" rows="3">{{ $oldFor('approve', 'terms') }}</textarea>
                                    </div>

                                    <button type="submit" class="ta-btn is-ok is-block">
                                        <i class="bi bi-check-circle"></i> Approve and create lease
                                    </button>
                                </form>
                            </div>

                            {{-- Reject --}}
                            <div class="ta-pane" id="pane-reject" role="tabpanel" @if($startPane !== 'reject') hidden @endif>
                                <p class="ta-hint mt-0 mb-3">The reason is saved with the application so the team can see why it was declined.</p>

                                <form method="POST" action="{{ route('admin.tenant-applications.reject', $app) }}" data-confirm="Reject this application?" data-busy="Rejecting…">
                                    @csrf
                                    <input type="hidden" name="_form" value="reject">

                                    <div class="mb-3">
                                        <label class="form-label" for="reject_notes">Reason for rejection *</label>
                                        <textarea id="reject_notes" name="decision_notes" class="form-control" rows="4" required>{{ $oldFor('reject', 'decision_notes') }}</textarea>
                                    </div>

                                    <button type="submit" class="ta-btn is-bad is-block">
                                        <i class="bi bi-x-circle"></i> Reject application
                                    </button>
                                </form>
                            </div>
                        </div>
                    </section>

                @else
                    <section class="ta-panel">
                        <div class="ta-panel-head">
                            <div>
                                <h2 class="ta-panel-title">Decision</h2>
                                <p class="ta-panel-note">No further action needed</p>
                            </div>
                            <span class="ta-pill {{ $pill }}">{{ ucfirst($status) }}</span>
                        </div>

                        <div class="ta-panel-body">
                            <p class="is-mute mb-0" style="line-height:1.65;">
                                @if($status === 'pending' && !$unit)
                                    This application is still pending, but its unit no longer exists, so it can't be approved from here.
                                @else
                                    This application is {{ $status }}, so it can't be changed from this screen.
                                @endif
                            </p>
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ---- Approve / Reject switch ---- */
    var buttons = document.querySelectorAll('.ta-seg [data-pane]');
    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            buttons.forEach(function (b) {
                var on = b === btn;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-selected', on ? 'true' : 'false');
                var pane = document.getElementById('pane-' + b.dataset.pane);
                if (pane) pane.hidden = !on;
            });
        });
    });

    /* ---- Confirm, then lock the button so a double click can't submit twice ---- */
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
                return;
            }
            var submit = form.querySelector('[type="submit"]');
            if (submit) {
                // Disable after the browser has collected the form data
                setTimeout(function () {
                    submit.disabled = true;
                    submit.textContent = form.dataset.busy || 'Saving…';
                }, 0);
            }
        });
    });
});
</script>
@endsection
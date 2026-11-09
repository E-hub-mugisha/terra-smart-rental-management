@extends('layouts.app')

@section('title', 'Lease Details')

@section('content')
@php
    $status = $lease->status;

    $statusLabels = [
        'draft' => 'Draft',
        'pending_tenant_acceptance' => 'Awaiting acceptance',
        'pending_activation' => 'Awaiting activation',
        'active' => 'Active',
        'terminated' => 'Terminated',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
    ];
    $statusTone = [
        'draft' => 'mute',
        'pending_tenant_acceptance' => 'warn',
        'pending_activation' => 'info',
        'active' => 'ok',
        'terminated' => 'mute',
        'rejected' => 'bad',
        'cancelled' => 'bad',
        'expired' => 'mute',
    ];
    $tone = $statusTone[$status] ?? 'mute';
    $statusLabel = $statusLabels[$status] ?? ucwords(str_replace('_', ' ', $status));

    $cur = $lease->currency ?: 'RWF';
    $dec = $cur === 'RWF' ? 0 : 2;
    $num = fn ($amount) => number_format((float) $amount, $dec);

    $start = $lease->start_date;
    $end = $lease->end_date;
    $months = ($start && $end && $end->gt($start)) ? max(1, (int) round($start->floatDiffInMonths($end))) : null;

    $accepted = !is_null($lease->tenant_accepted_at);
    $moveInConfirmed = !is_null($lease->move_in_confirmed_at);
    $activated = !is_null($lease->activated_at) || $status === 'active';
    $canAccept = $status === 'pending_tenant_acceptance' && !$accepted;

    $steps = [
        [
            'title' => 'Tenant acceptance',
            'done' => $accepted,
            'text' => $accepted ? 'Lease accepted' : 'Awaiting your acceptance',
            'date' => $lease->tenant_accepted_at?->format('d M Y, H:i'),
        ],
        [
            'title' => 'Move-in confirmation',
            'done' => $moveInConfirmed,
            'text' => $moveInConfirmed ? 'Confirmed by management' : 'Awaiting management confirmation',
            'date' => $lease->move_in_confirmed_at?->format('d M Y'),
        ],
        [
            'title' => 'Lease activation',
            'done' => $activated,
            'text' => $activated ? 'Lease activated' : 'Payment and activation checks pending',
            'date' => $lease->activated_at?->format('d M Y'),
        ],
    ];
    $currentIdx = collect($steps)->search(fn ($s) => !$s['done']);
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="ld">
    <div class="container py-4">

        {{-- Header --}}
        <header class="ld-head">
            <div>
                <a href="{{ route('tenant-portal.leases.index') }}" class="ld-back">
                    <i class="bi bi-arrow-left"></i> Back to my leases
                </a>
                <h2>Lease {{ $lease->lease_number }}</h2>
                <p>
                    {{ $lease->unit?->building?->name ?? 'Property' }}
                    @if ($lease->unit?->unit_number) · Unit {{ $lease->unit->unit_number }} @endif
                </p>
            </div>
            <span class="ld-pill ld-pill--{{ $tone }}"><i></i>{{ $statusLabel }}</span>
        </header>

        @if (session('success'))
            <div class="ld-flash" role="status">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="ld-flash-x" data-bs-dismiss="alert" aria-label="Dismiss"
                        onclick="this.closest('.ld-flash').remove()"><i class="bi bi-x-lg"></i></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="ld-errors" role="alert">
                <i class="bi bi-exclamation-octagon-fill"></i>
                <div>
                    <strong>Please review the following</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Status notice --}}
        @if ($status === 'pending_tenant_acceptance')
            <div class="ld-notice ld-notice--warn" role="status">
                <i class="bi bi-pencil-square"></i>
                <p>
                    <strong>Draft lease awaiting your acceptance</strong>
                    <span>Please review the rental amount, dates, deposit and terms below before accepting this agreement.</span>
                </p>
                @if ($canAccept)
                    <a href="#accept" class="ld-btn ld-btn--solid">Go to acceptance</a>
                @endif
            </div>
        @elseif ($status === 'pending_activation')
            <div class="ld-notice ld-notice--info" role="status">
                <i class="bi bi-hourglass-split"></i>
                <p>
                    <strong>Lease accepted</strong>
                    <span>
                        Your acceptance was recorded
                        @if ($lease->tenant_accepted_at)
                            on {{ $lease->tenant_accepted_at->format('d M Y, H:i') }}.
                        @endif
                        The property manager must complete the required payment and move-in checks before the lease becomes active.
                    </span>
                </p>
            </div>
        @elseif ($status === 'active')
            <div class="ld-notice ld-notice--ok" role="status">
                <i class="bi bi-check-circle-fill"></i>
                <p>
                    <strong>Lease is active</strong>
                    <span>This rental agreement has been activated.</span>
                </p>
            </div>
        @elseif ($status === 'draft')
            <div class="ld-notice ld-notice--mute" role="status">
                <i class="bi bi-hourglass"></i>
                <p>
                    <strong>Still being prepared</strong>
                    <span>The property manager is preparing this lease. You can accept it once it is submitted for your review.</span>
                </p>
            </div>
        @endif

        <div class="ld-grid">

            {{-- ========== MAIN ========== --}}
            <div class="ld-main">

                {{-- Agreement --}}
                <article class="ld-sheet">
                    <div class="ld-money">
                        <div>
                            <p class="ld-muted">Monthly rent</p>
                            <p class="ld-big"><small>{{ $cur }}</small>{{ $num($lease->monthly_rent) }}</p>
                        </div>
                        <div>
                            <p class="ld-muted">Security deposit</p>
                            <p class="ld-big ld-big--sm"><small>{{ $cur }}</small>{{ $num($lease->deposit_amount) }}</p>
                        </div>
                    </div>

                    <section class="ld-period" aria-label="Lease period">
                        <div>
                            <p class="ld-muted">Starts</p>
                            <p class="ld-date">{{ $start?->format('d M Y') ?? 'N/A' }}</p>
                        </div>
                        <div class="ld-period-line" aria-hidden="true">
                            <span>{{ $months ? $months . ' ' . \Illuminate\Support\Str::plural('month', $months) : '' }}</span>
                        </div>
                        <div class="ld-period-end">
                            <p class="ld-muted">Ends</p>
                            <p class="ld-date">{{ $end?->format('d M Y') ?? 'N/A' }}</p>
                        </div>
                    </section>

                    <h3 class="ld-h">Agreement details</h3>
                    <dl class="ld-facts">
                        <div><dt>Lease number</dt><dd>{{ $lease->lease_number }}</dd></div>
                        <div><dt>Application number</dt><dd>{{ $lease->application?->application_number ?? 'N/A' }}</dd></div>
                        <div><dt>Property / building</dt><dd>{{ $lease->unit?->building?->name ?? 'N/A' }}</dd></div>
                        <div><dt>Unit</dt><dd>{{ $lease->unit?->unit_number ?? 'N/A' }}</dd></div>
                        <div><dt>Payment frequency</dt><dd>{{ ucwords(str_replace('_', ' ', $lease->payment_frequency)) }}</dd></div>
                        <div><dt>Rent due day</dt><dd>Day {{ $lease->rent_due_day }} of the payment month</dd></div>
                    </dl>
                </article>

                {{-- Terms --}}
                <section class="ld-sheet ld-terms" aria-labelledby="ldTermsTitle">
                    <h3 class="ld-h" id="ldTermsTitle">Terms and conditions</h3>
                    @if ($lease->terms)
                        <div class="ld-terms-body">{{ $lease->terms }}</div>
                    @else
                        <p class="ld-muted">
                            No additional lease terms have been provided. Contact the property manager if you need clarification before accepting.
                        </p>
                    @endif
                </section>

                {{-- Acceptance --}}
                @if ($canAccept)
                    <section class="ld-accept" id="accept" aria-labelledby="ldAcceptTitle">
                        <h3 id="ldAcceptTitle">Review and accept lease</h3>
                        <p>By accepting, you confirm that you have reviewed the lease details and agree to the terms presented.</p>

                        <form method="POST"
                              action="{{ route('tenant-portal.leases.accept', $lease) }}"
                              onsubmit="return confirm('Have you reviewed the lease and agreed to its terms?');">
                            @csrf

                            <label class="ld-check" for="acceptLeaseTerms">
                                <input type="checkbox" name="accept_terms" value="1" id="acceptLeaseTerms" required>
                                <span>I have reviewed this lease agreement and agree to the terms and conditions stated above.</span>
                            </label>

                            <button type="submit" class="ld-btn ld-btn--solid ld-btn--lg">
                                <i class="bi bi-check-circle"></i> Accept lease
                            </button>
                        </form>
                    </section>
                @elseif ($accepted)
                    <section class="ld-accepted">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>
                            <h3>Tenant acceptance recorded</h3>
                            <p>
                                Accepted on {{ $lease->tenant_accepted_at->format('d M Y, H:i') }}.
                                The lease must still satisfy the activation requirements before it becomes active.
                            </p>
                        </div>
                    </section>
                @endif
            </div>

            {{-- ========== SIDEBAR ========== --}}
            <aside class="ld-side">

                <section class="ld-card" aria-labelledby="ldProgTitle">
                    <h3 class="ld-h" id="ldProgTitle">Lease progress</h3>
                    <ol class="ld-steps">
                        @foreach ($steps as $i => $s)
                            <li class="{{ $s['done'] ? 'done' : ($i === $currentIdx ? 'current' : 'todo') }}">
                                <span class="dot"><i class="bi {{ $s['done'] ? 'bi-check-lg' : '' }}"></i></span>
                                <div>
                                    <p class="t">{{ $s['title'] }}</p>
                                    <p class="s">{{ $s['text'] }}</p>
                                    @if ($s['done'] && $s['date'])<p class="s d">{{ $s['date'] }}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>

                @if ($lease->application)
                    <section class="ld-card ld-related">
                        <h3 class="ld-h">Related application</h3>
                        <p class="ld-muted">{{ $lease->application->application_number }}</p>
                        <a href="{{ route('tenant-portal.applications.show', $lease->application) }}" class="ld-btn ld-btn--ghost">
                            View application
                        </a>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</div>

<style>
    .ld {
        --ld-ink: var(--terra-navy, #19265d);
        --ld-accent: var(--terra-orange, #D05208);
        --ld-text: #232a45;
        --ld-muted: #6b7388;
        --ld-line: #e3e6ee;
        --ld-soft: #f5f6fa;
        --ld-ok: #1a7f46;
        font-family: 'Instrument Sans', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        color: var(--ld-text);
        font-variant-numeric: tabular-nums;
        line-height: 1.5;
    }
    .ld *, .ld *::before, .ld *::after { box-sizing: border-box; }
    .ld h2, .ld h3, .ld p, .ld dl, .ld dd, .ld ol, .ld ul { margin: 0; padding: 0; }
    .ld-muted { color: var(--ld-muted); font-size: .875rem; }
    .ld-h { font-size: 1rem; font-weight: 700; color: var(--ld-ink); margin-bottom: 12px; letter-spacing: -.01em; }

    /* Header */
    .ld-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 24px; }
    .ld-back { display: inline-flex; align-items: center; gap: 6px; font-size: .875rem; font-weight: 600; color: var(--ld-muted); text-decoration: none; }
    .ld-back:hover { color: var(--ld-ink); }
    .ld-head h2 { font-size: 1.75rem; font-weight: 700; letter-spacing: -.025em; color: var(--ld-ink); margin-top: 8px; overflow-wrap: anywhere; }
    .ld-head p { color: var(--ld-muted); margin-top: 2px; }

    /* Pill */
    .ld-pill { display: inline-flex; align-items: center; gap: 7px; padding: 5px 14px; border-radius: 999px; font-size: .85rem; font-weight: 600; background: #eef0f6; color: #4a5272; }
    .ld-pill i { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
    .ld-pill--ok   { background: #e7f6ed; color: #1a7f46; }
    .ld-pill--warn { background: #fff3d6; color: #9a6300; }
    .ld-pill--info { background: #e6f1fb; color: #1b64a8; }
    .ld-pill--bad  { background: #fdecea; color: #b42318; }

    /* Buttons */
    .ld-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; font: inherit; font-weight: 600; font-size: .875rem; padding: 9px 16px; border-radius: 10px; text-decoration: none; cursor: pointer; border: 1px solid transparent; white-space: nowrap; transition: background .15s, color .15s, border-color .15s; }
    .ld-btn--ghost { background: transparent; color: var(--ld-ink); border-color: var(--ld-line); }
    .ld-btn--ghost:hover { background: var(--ld-soft); color: var(--ld-ink); border-color: #cfd4e1; }
    .ld-btn--solid { background: var(--ld-accent); color: #fff; }
    .ld-btn--solid:hover { background: #b94707; color: #fff; }
    .ld-btn--lg { padding: 12px 22px; font-size: .95rem; }
    .ld-btn:focus-visible, .ld-flash-x:focus-visible, .ld-check input:focus-visible { outline: 3px solid color-mix(in srgb, var(--ld-accent) 55%, transparent); outline-offset: 2px; }

    /* Messages */
    .ld-flash, .ld-errors, .ld-notice { display: flex; gap: 14px; padding: 14px 18px; margin-bottom: 16px; border-radius: 12px; border: 1px solid transparent; }
    .ld-flash { align-items: center; background: #e7f6ed; color: #146c3a; border-color: #bfe5cf; }
    .ld-flash span { flex: 1; }
    .ld-flash-x { border: 0; background: transparent; color: inherit; padding: 4px; border-radius: 6px; cursor: pointer; opacity: .7; }
    .ld-flash-x:hover { opacity: 1; }
    .ld-errors { background: #fdecea; color: #8e1d14; border-color: #f4c6c1; }
    .ld-errors > i, .ld-notice > i { font-size: 1.25rem; line-height: 1.4; }
    .ld-errors strong { display: block; }
    .ld-errors ul { padding-left: 1.1rem; margin-top: 4px; font-size: .9rem; }
    .ld-notice { flex-wrap: wrap; align-items: center; margin-bottom: 20px; }
    .ld-notice p { flex: 1; min-width: 220px; }
    .ld-notice strong { display: block; }
    .ld-notice span { font-size: .875rem; }
    .ld-notice--warn { background: #fff7e6; color: #7a5200; border-color: #f1dba5; }
    .ld-notice--warn strong { color: #5f3f00; }
    .ld-notice--info { background: #eaf3fc; color: #1b4f82; border-color: #c4dcf3; }
    .ld-notice--ok   { background: #e7f6ed; color: #146c3a; border-color: #bfe5cf; }
    .ld-notice--mute { background: var(--ld-soft); color: #4a5272; border-color: var(--ld-line); }

    /* Grid */
    .ld-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 24px; align-items: start; }
    @media (min-width: 992px) { .ld-grid { grid-template-columns: minmax(0, 1fr) 340px; } .ld-side { position: sticky; top: 84px; } }
    .ld-main { display: grid; gap: 20px; }
    .ld-side { display: grid; gap: 16px; }

    /* Sheet */
    .ld-sheet, .ld-card { background: #fff; border: 1px solid var(--ld-line); border-radius: 14px; }
    .ld-sheet { padding: 28px; }
    .ld-card { padding: 20px; }

    .ld-money { display: flex; flex-wrap: wrap; gap: 20px 56px; padding-bottom: 24px; border-bottom: 1px solid var(--ld-line); }
    .ld-big { font-size: clamp(1.9rem, 4vw, 2.6rem); font-weight: 700; letter-spacing: -.035em; line-height: 1.1; color: var(--ld-ink); overflow-wrap: anywhere; }
    .ld-big--sm { font-size: clamp(1.4rem, 3vw, 1.9rem); margin-top: 6px; }
    .ld-big small { font-size: .42em; font-weight: 600; letter-spacing: 0; color: var(--ld-muted); margin-right: .45em; }

    /* Period */
    .ld-period { display: flex; align-items: center; gap: 16px; padding: 22px 0; border-bottom: 1px solid var(--ld-line); margin-bottom: 24px; }
    .ld-date { font-weight: 700; font-size: 1.05rem; color: var(--ld-ink); }
    .ld-period-end { text-align: right; }
    .ld-period-line { flex: 1; position: relative; height: 2px; background: var(--ld-line); border-radius: 2px; min-width: 40px; }
    .ld-period-line::before, .ld-period-line::after { content: ""; position: absolute; top: -4px; width: 10px; height: 10px; border-radius: 50%; background: var(--ld-ink); }
    .ld-period-line::before { left: 0; } .ld-period-line::after { right: 0; }
    .ld-period-line span { position: absolute; left: 50%; top: -11px; transform: translateX(-50%); background: #fff; padding: 0 10px; font-size: .8rem; font-weight: 600; color: var(--ld-muted); white-space: nowrap; }

    /* Facts */
    .ld-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); border-top: 1px solid var(--ld-line); }
    .ld-facts > div { padding: 13px 12px 13px 0; border-bottom: 1px solid var(--ld-line); }
    .ld-facts dt { font-size: .8rem; color: var(--ld-muted); }
    .ld-facts dd { font-weight: 600; color: var(--ld-ink); overflow-wrap: anywhere; }
    @media (max-width: 575.98px) { .ld-facts { grid-template-columns: 1fr; } .ld-sheet { padding: 20px 18px; } }

    /* Terms */
    .ld-terms-body { white-space: pre-line; overflow-wrap: anywhere; max-width: 72ch; font-size: .95rem; line-height: 1.7; }

    /* Acceptance */
    .ld-accept { background: #fff; border: 2px solid var(--ld-accent); border-radius: 14px; padding: 24px 28px; scroll-margin-top: 90px; }
    .ld-accept h3 { font-size: 1.15rem; font-weight: 700; color: var(--ld-ink); }
    .ld-accept > p { color: var(--ld-muted); margin: 4px 0 18px; max-width: 60ch; }
    .ld-check { display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px; margin-bottom: 18px; border: 1px solid var(--ld-line); border-radius: 12px; background: var(--ld-soft); cursor: pointer; font-size: .92rem; }
    .ld-check input { width: 20px; height: 20px; margin-top: 2px; accent-color: var(--ld-accent); flex-shrink: 0; cursor: pointer; }
    .ld-accepted { display: flex; gap: 14px; align-items: flex-start; padding: 20px 24px; background: #fff; border: 1px solid var(--ld-line); border-radius: 14px; }
    .ld-accepted > i { font-size: 1.6rem; color: var(--ld-ok); line-height: 1.2; }
    .ld-accepted h3 { font-size: 1rem; font-weight: 700; color: var(--ld-ink); }
    .ld-accepted p { color: var(--ld-muted); font-size: .9rem; margin-top: 2px; }
    @media (max-width: 575.98px) { .ld-accept { padding: 20px 18px; } .ld-accept .ld-btn { width: 100%; } }

    /* Progress */
    .ld-steps { list-style: none; }
    .ld-steps li { display: grid; grid-template-columns: 24px minmax(0, 1fr); gap: 12px; position: relative; padding-bottom: 20px; }
    .ld-steps li:last-child { padding-bottom: 0; }
    .ld-steps li::before { content: ""; position: absolute; left: 11px; top: 24px; bottom: 0; width: 2px; background: var(--ld-line); }
    .ld-steps li:last-child::before { display: none; }
    .ld-steps li.done::before { background: var(--ld-ok); }
    .ld-steps .dot { width: 24px; height: 24px; border-radius: 50%; border: 2px solid var(--ld-line); background: #fff; display: grid; place-items: center; font-size: .7rem; color: #fff; position: relative; z-index: 1; }
    .ld-steps li.done .dot { background: var(--ld-ok); border-color: var(--ld-ok); }
    .ld-steps li.current .dot { border-color: #d19a00; box-shadow: 0 0 0 4px rgba(209,154,0,.16); }
    .ld-steps .t { font-weight: 600; color: var(--ld-ink); font-size: .92rem; }
    .ld-steps li.todo .t { color: var(--ld-muted); }
    .ld-steps .s { font-size: .82rem; color: var(--ld-muted); }
    .ld-steps .s.d { color: var(--ld-ok); font-weight: 600; }

    .ld-related .ld-muted { margin-bottom: 12px; }
    .ld-related .ld-btn { width: 100%; }
</style>
@endsection
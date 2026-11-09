@extends('layouts.app')

@section('title', 'My Leases')

@section('content')
@php
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

    // Visual tone per status: ok | warn | info | bad | mute
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

    $awaiting = $leases->getCollection()->where('status', 'pending_tenant_acceptance');
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="ls">
    <div class="container py-4">

        <header class="ls-head">
            <div>
                <h2>My Leases</h2>
                <p>Review your rental agreements and their current status.</p>
            </div>
            <a href="{{ route('tenant-portal.applications.index') }}" class="ls-btn ls-btn--ghost">
                <i class="bi bi-arrow-left"></i> My applications
            </a>
        </header>

        @if (session('success'))
            <div class="ls-flash" role="status">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="ls-flash-x" data-bs-dismiss="alert" aria-label="Dismiss"
                        onclick="this.closest('.ls-flash').remove()"><i class="bi bi-x-lg"></i></button>
            </div>
        @endif

        @if ($leases->count())

            @if ($awaiting->count())
                <div class="ls-attn" role="status">
                    <i class="bi bi-pencil-square"></i>
                    <p>
                        <strong>
                            {{ $awaiting->count() === 1 ? '1 lease needs' : $awaiting->count() . ' leases need' }} your response
                        </strong>
                        <span>Review the terms and accept to move your lease towards activation.</span>
                    </p>
                    <a href="{{ route('tenant-portal.leases.show', $awaiting->first()) }}" class="ls-btn ls-btn--solid">
                        {{ $awaiting->count() === 1 ? 'Review lease' : 'Review first lease' }}
                    </a>
                </div>
            @endif

            <section class="ls-list" aria-label="Your leases">

                <div class="ls-cols" aria-hidden="true">
                    <span>Lease</span>
                    <span>Property</span>
                    <span>Monthly rent</span>
                    <span>Lease period</span>
                    <span></span>
                </div>

                @foreach ($leases as $lease)
                    @php
                        $status = $lease->status;
                        $tone = $statusTone[$status] ?? 'mute';
                        $label = $statusLabels[$status] ?? ucwords(str_replace('_', ' ', $status));

                        $cur = $lease->currency ?: 'RWF';
                        $rent = number_format((float) $lease->monthly_rent, $cur === 'RWF' ? 0 : 2);

                        $start = $lease->start_date;
                        $end = $lease->end_date;

                        // Time-left meter, only for active leases with a valid range
                        $pct = null; $leftText = null;
                        if ($status === 'active' && $start && $end && $end->gt($start)) {
                            $span = max(1, (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()));
                            $gone = (int) $start->copy()->startOfDay()->diffInDays(now()->startOfDay(), false);
                            $pct = max(0, min(100, round(($gone / $span) * 100)));
                            $left = (int) now()->startOfDay()->diffInDays($end->copy()->startOfDay(), false);
                            $leftText = $left < 0
                                ? 'Past end date'
                                : ($left === 0
                                    ? 'Ends today'
                                    : ($left >= 60
                                        ? floor($left / 30) . ' months left'
                                        : $left . ' ' . \Illuminate\Support\Str::plural('day', $left) . ' left'));
                        }
                    @endphp

                    <article class="ls-row {{ $status === 'pending_tenant_acceptance' ? 'is-action' : '' }}" role="group">

                        <div class="ls-cell ls-lease">
                            <h3>{{ $lease->lease_number }}</h3>
                            <span class="ls-pill ls-pill--{{ $tone }}"><i></i>{{ $label }}</span>
                        </div>

                        <div class="ls-cell" data-label="Property">
                            <p class="ls-strong">{{ $lease->unit?->building?->name ?? 'Property' }}</p>
                            <p class="ls-sub">Unit {{ $lease->unit?->unit_number ?? 'N/A' }}</p>
                        </div>

                        <div class="ls-cell" data-label="Monthly rent">
                            <p class="ls-strong ls-money"><small>{{ $cur }}</small>{{ $rent }}</p>
                        </div>

                        <div class="ls-cell" data-label="Lease period">
                            <p class="ls-strong">
                                {{ $start?->format('d M Y') ?? 'N/A' }}
                                <span class="ls-to">to</span>
                                {{ $end?->format('d M Y') ?? 'N/A' }}
                            </p>
                            @if ($pct !== null)
                                <div class="ls-meter" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Lease time elapsed">
                                    <i data-w="{{ $pct }}"></i>
                                </div>
                                <p class="ls-sub">{{ $leftText }}</p>
                            @endif
                        </div>

                        <div class="ls-cell ls-act">
                            <a href="{{ route('tenant-portal.leases.show', $lease) }}"
                               class="ls-btn {{ $status === 'pending_tenant_acceptance' ? 'ls-btn--solid' : 'ls-btn--ghost' }}">
                                @if ($status === 'pending_tenant_acceptance')
                                    Review &amp; accept
                                @else
                                    View lease
                                @endif
                            </a>
                        </div>
                    </article>
                @endforeach
            </section>

            <div class="ls-pager">
                {{ $leases->links() }}
            </div>

        @else
            <section class="ls-empty">
                <i class="bi bi-file-earmark-text"></i>
                <h3>No leases yet</h3>
                <p>When a lease is prepared for one of your rental applications, it will appear here.</p>
                <a href="{{ route('tenant-portal.applications.index') }}" class="ls-btn ls-btn--solid">View my applications</a>
            </section>
        @endif

    </div>
</div>

<style>
    .ls {
        --ls-ink: var(--terra-navy, #19265d);
        --ls-accent: var(--terra-orange, #D05208);
        --ls-text: #232a45;
        --ls-muted: #6b7388;
        --ls-line: #e3e6ee;
        --ls-soft: #f5f6fa;
        font-family: 'Instrument Sans', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        color: var(--ls-text);
        font-variant-numeric: tabular-nums;
        line-height: 1.5;
    }
    .ls *, .ls *::before, .ls *::after { box-sizing: border-box; }
    .ls h2, .ls h3, .ls p { margin: 0; }

    /* Header */
    .ls-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
    .ls-head h2 { font-size: 1.75rem; font-weight: 700; letter-spacing: -.025em; color: var(--ls-ink); }
    .ls-head p { color: var(--ls-muted); margin-top: 2px; }

    /* Buttons */
    .ls-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; font: inherit; font-weight: 600; font-size: .875rem; padding: 9px 16px; border-radius: 10px; text-decoration: none; cursor: pointer; border: 1px solid transparent; white-space: nowrap; transition: background .15s, color .15s, border-color .15s; }
    .ls-btn--ghost { background: transparent; color: var(--ls-ink); border-color: var(--ls-line); }
    .ls-btn--ghost:hover { background: var(--ls-soft); color: var(--ls-ink); border-color: #cfd4e1; }
    .ls-btn--solid { background: var(--ls-accent); color: #fff; }
    .ls-btn--solid:hover { background: #b94707; color: #fff; }
    .ls-btn:focus-visible, .ls-flash-x:focus-visible { outline: 3px solid color-mix(in srgb, var(--ls-accent) 55%, transparent); outline-offset: 2px; }

    /* Flash */
    .ls-flash { display: flex; align-items: center; gap: 12px; padding: 12px 16px; margin-bottom: 16px; border-radius: 12px; background: #e7f6ed; color: #146c3a; border: 1px solid #bfe5cf; }
    .ls-flash span { flex: 1; }
    .ls-flash-x { border: 0; background: transparent; color: inherit; padding: 4px; border-radius: 6px; cursor: pointer; opacity: .7; }
    .ls-flash-x:hover { opacity: 1; }

    /* Attention strip */
    .ls-attn { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; padding: 16px 20px; margin-bottom: 20px; border-radius: 14px; background: #fff7e6; border: 1px solid #f1dba5; color: #7a5200; }
    .ls-attn > i { font-size: 1.3rem; }
    .ls-attn p { flex: 1; min-width: 220px; }
    .ls-attn strong { display: block; color: #5f3f00; }
    .ls-attn span { font-size: .875rem; }

    /* List */
    .ls-list { background: #fff; border: 1px solid var(--ls-line); border-radius: 14px; overflow: hidden; }
    .ls-cols, .ls-row { display: grid; gap: 16px; align-items: center; grid-template-columns: minmax(0, 1.5fr) minmax(0, 1.3fr) minmax(0, 1fr) minmax(0, 1.6fr) auto; padding: 0 24px; }
    .ls-cols { padding-top: 12px; padding-bottom: 12px; background: var(--ls-soft); border-bottom: 1px solid var(--ls-line); font-size: .8rem; font-weight: 600; color: var(--ls-muted); }
    .ls-cols span:last-child { min-width: 112px; }
    .ls-row { padding-top: 18px; padding-bottom: 18px; border-bottom: 1px solid var(--ls-line); position: relative; transition: background .15s; }
    .ls-row:last-child { border-bottom: 0; }
    .ls-row:hover { background: #fafbfd; }
    .ls-row.is-action::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #d19a00; }

    .ls-lease h3 { font-size: 1rem; font-weight: 700; color: var(--ls-ink); overflow-wrap: anywhere; margin-bottom: 6px; }
    .ls-strong { font-weight: 600; color: var(--ls-ink); }
    .ls-sub { font-size: .8rem; color: var(--ls-muted); }
    .ls-to { font-weight: 400; color: var(--ls-muted); margin: 0 2px; }
    .ls-money small { font-size: .78em; font-weight: 500; color: var(--ls-muted); margin-right: .4em; }
    .ls-act { text-align: right; min-width: 112px; }

    /* Status pill */
    .ls-pill { display: inline-flex; align-items: center; gap: 7px; padding: 3px 11px; border-radius: 999px; font-size: .78rem; font-weight: 600; background: #eef0f6; color: #4a5272; }
    .ls-pill i { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .ls-pill--ok   { background: #e7f6ed; color: #1a7f46; }
    .ls-pill--warn { background: #fff3d6; color: #9a6300; }
    .ls-pill--info { background: #e6f1fb; color: #1b64a8; }
    .ls-pill--bad  { background: #fdecea; color: #b42318; }

    /* Time-left meter */
    .ls-meter { height: 5px; border-radius: 999px; background: var(--ls-line); overflow: hidden; margin: 8px 0 4px; max-width: 220px; }
    .ls-meter > i { display: block; height: 100%; width: 0; border-radius: 999px; background: #1a7f46; transition: width 1s cubic-bezier(.22,1,.36,1); }

    .ls-pager { margin-top: 20px; }

    /* Empty */
    .ls-empty { text-align: center; padding: 56px 24px; background: #fff; border: 1px dashed #d3d8e4; border-radius: 14px; }
    .ls-empty > i { font-size: 2.4rem; color: #b8bfd0; }
    .ls-empty h3 { font-size: 1.15rem; font-weight: 700; color: var(--ls-ink); margin: 10px 0 4px; }
    .ls-empty p { color: var(--ls-muted); max-width: 42ch; margin: 0 auto 20px; }

    /* Tablet and mobile: rows become stacked cards */
    @media (max-width: 991.98px) {
        .ls-cols { display: none; }
        .ls-row { grid-template-columns: 1fr 1fr; gap: 14px 20px; padding: 18px 18px 18px 20px; }
        .ls-lease { grid-column: 1 / -1; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; }
        .ls-lease h3 { margin: 0; }
        .ls-cell[data-label]::before { content: attr(data-label); display: block; font-size: .78rem; color: var(--ls-muted); margin-bottom: 2px; }
        .ls-cell[data-label="Lease period"] { grid-column: 1 / -1; }
        .ls-act { grid-column: 1 / -1; text-align: left; }
        .ls-act .ls-btn { width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) { .ls-meter > i { transition: none; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    requestAnimationFrame(function () {
        document.querySelectorAll('.ls-meter > i[data-w]').forEach(function (el) { el.style.width = el.dataset.w + '%'; });
    });
});
</script>
@endsection
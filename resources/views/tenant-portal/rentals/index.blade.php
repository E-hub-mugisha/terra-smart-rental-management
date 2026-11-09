@extends('layouts.app')

@section('title', 'My Rentals')

@section('content')
@php
    $statusOf = fn ($lease) => strtolower($lease->status ?? 'draft');

    $pendingKeys = ['draft', 'pending_tenant_acceptance', 'pending_activation'];
    $pastKeys = ['expired', 'terminated', 'cancelled', 'rejected'];

    $activeCount = $leases->filter(fn ($l) => $statusOf($l) === 'active')->count();
    $pendingCount = $leases->filter(fn ($l) => in_array($statusOf($l), $pendingKeys))->count();
    $pastCount = $leases->filter(fn ($l) => in_array($statusOf($l), $pastKeys))->count();
    $totalCount = $leases->count();

    $money = fn ($amount, $code = 'RWF') => ($code ?: 'RWF') . ' ' . number_format((float) $amount, 0);

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
        'draft' => 'warn',
        'pending_tenant_acceptance' => 'warn',
        'pending_activation' => 'info',
        'active' => 'ok',
        'terminated' => 'bad',
        'rejected' => 'bad',
        'cancelled' => 'mute',
        'expired' => 'mute',
    ];
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<div class="rn">
    <div class="container-fluid py-4">

        {{-- Header --}}
        <header class="rn-head">
            <div>
                <h1>My Rentals</h1>
                <p>View your rented properties, lease information and rental payments.</p>
            </div>
            <a href="{{ route('tenant-portal.maintenance-requests.index') }}" class="rn-btn rn-btn--ghost">
                <i class="bi bi-tools"></i> Maintenance requests
            </a>
        </header>

        @if (session('success'))
            <div class="rn-flash" role="status">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="rn-flash-x" data-bs-dismiss="alert" aria-label="Dismiss"
                        onclick="this.closest('.rn-flash').remove()"><i class="bi bi-x-lg"></i></button>
            </div>
        @endif

        {{-- Summary strip --}}
        <dl class="rn-stats" aria-label="Rental summary">
            <div><dt>Total leases</dt><dd>{{ $totalCount }}</dd></div>
            <div class="is-ok"><dt>Active rentals</dt><dd>{{ $activeCount }}</dd></div>
            <div class="{{ $pendingCount ? 'is-warn' : '' }}"><dt>Pending leases</dt><dd>{{ $pendingCount }}</dd></div>
            <div><dt>Past rentals</dt><dd>{{ $pastCount }}</dd></div>
        </dl>

        {{-- List heading --}}
        <div class="rn-listhead">
            <div>
                <h2>Your rental properties</h2>
                <p>Your leases and accommodation details.</p>
            </div>
            <span>{{ $totalCount }} {{ \Illuminate\Support\Str::plural('record', $totalCount) }}</span>
        </div>

        @forelse ($leases as $lease)
            @php
                $unit = $lease->unit;
                $building = $unit?->building;
                $property = $building?->property;
                $status = $statusOf($lease);
                $tone = $statusTone[$status] ?? 'mute';
                $label = $statusLabels[$status] ?? ucwords(str_replace('_', ' ', $status));
                $cur = $lease->currency ?: 'RWF';

                $startDate = $lease->start_date ? \Illuminate\Support\Carbon::parse($lease->start_date) : null;
                $endDate = $lease->end_date ? \Illuminate\Support\Carbon::parse($lease->end_date) : null;

                $daysRemaining = $endDate
                    ? (int) now()->startOfDay()->diffInDays($endDate->copy()->startOfDay(), false)
                    : null;

                // Time-left text and meter (active leases only)
                $leftText = null; $pct = null;
                if ($status === 'active') {
                    if ($daysRemaining === null) {
                        $leftText = 'Ongoing';
                    } elseif ($daysRemaining < 0) {
                        $leftText = 'Past end date';
                    } elseif ($daysRemaining === 0) {
                        $leftText = 'Ends today';
                    } elseif ($daysRemaining >= 60) {
                        $leftText = floor($daysRemaining / 30) . ' months left';
                    } else {
                        $leftText = $daysRemaining . ' ' . \Illuminate\Support\Str::plural('day', $daysRemaining) . ' left';
                    }

                    if ($startDate && $endDate && $endDate->gt($startDate)) {
                        $span = max(1, (int) $startDate->copy()->startOfDay()->diffInDays($endDate->copy()->startOfDay()));
                        $gone = (int) $startDate->copy()->startOfDay()->diffInDays(now()->startOfDay(), false);
                        $pct = max(0, min(100, round(($gone / $span) * 100)));
                    }
                }

                $imagePath = $unit?->images?->sortBy('sort_order')->first()?->path;
                $imageUrl = $imagePath
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath)
                    : null;

                $location = collect([
                    $property?->village,
                    $property?->cell,
                    $property?->sector,
                    $property?->district,
                    $property?->province,
                ])->filter()->implode(', ') ?: 'Location not provided';
            @endphp

            <article class="rn-card">

                <div class="rn-photo">
                    @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $property?->name ?? 'Rental property' }}" loading="lazy">
                    @else
                        <div class="rn-photo-empty">
                            <i class="bi bi-house-door"></i>
                            <span>No photo available</span>
                        </div>
                    @endif
                    <span class="rn-pill rn-pill--{{ $tone }}"><i></i>{{ $label }}</span>
                </div>

                <div class="rn-body">

                    <div class="rn-top">
                        <div class="rn-title">
                            <p class="rn-code">{{ $property?->code ?? $lease->lease_number ?? 'Rental property' }}</p>
                            <h3>{{ $property?->name ?? 'Rental property' }}</h3>
                            <p class="rn-loc"><i class="bi bi-geo-alt"></i>{{ $location }}</p>
                        </div>
                        <div class="rn-rent">
                            <p class="rn-code">Monthly rent</p>
                            <p class="rn-rent-amt"><small>{{ $cur }}</small>{{ number_format((float) $lease->monthly_rent, 0) }}</p>
                        </div>
                    </div>

                    <dl class="rn-specs">
                        <div><dt>Building</dt><dd>{{ $building?->name ?? '—' }}</dd></div>
                        <div><dt>Unit</dt><dd>{{ $unit?->unit_number ?? '—' }}</dd></div>
                        <div><dt>Bedrooms</dt><dd>{{ $unit?->bedrooms ?? '—' }}</dd></div>
                        <div><dt>Bathrooms</dt><dd>{{ $unit?->bathrooms ?? '—' }}</dd></div>
                    </dl>

                    <div class="rn-period">
                        <div>
                            <p class="rn-code">Lease starts</p>
                            <p class="rn-date">{{ $startDate?->format('d M Y') ?? '—' }}</p>
                        </div>

                        @if ($status === 'active')
                            <div class="rn-timeline">
                                @if ($pct !== null)
                                    <div class="rn-meter" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Lease time elapsed">
                                        <i data-w="{{ $pct }}"></i>
                                    </div>
                                @endif
                                <p>{{ $leftText }}</p>
                            </div>
                        @else
                            <div class="rn-timeline rn-timeline--plain" aria-hidden="true"><div class="rn-rule"></div></div>
                        @endif

                        <div class="rn-period-end">
                            <p class="rn-code">Lease ends</p>
                            <p class="rn-date">{{ $endDate?->format('d M Y') ?? 'Ongoing' }}</p>
                        </div>
                    </div>

                    <footer class="rn-foot">
                        <p class="rn-ref">
                            <i class="bi bi-file-earmark-text"></i>
                            Lease {{ $lease->lease_number ?? 'not assigned' }}
                            @if ($lease->deposit_amount !== null)
                                <span class="rn-sep"></span>
                                Deposit {{ $money($lease->deposit_amount, $lease->currency ?? 'RWF') }}
                            @endif
                        </p>

                        <div class="rn-actions">
                            @if (\Illuminate\Support\Facades\Route::has('tenant-portal.leases.show'))
                                <a href="{{ route('tenant-portal.leases.show', $lease) }}" class="rn-btn rn-btn--ghost">
                                    <i class="bi bi-file-text"></i> View lease
                                </a>
                            @endif
                            <a href="{{ route('tenant-portal.maintenance-requests.create') }}" class="rn-btn rn-btn--solid">
                                <i class="bi bi-tools"></i> Request maintenance
                            </a>
                        </div>
                    </footer>
                </div>
            </article>
        @empty
            <section class="rn-empty">
                <i class="bi bi-house-door"></i>
                <h3>No rentals to display</h3>
                <p>Your rental properties will appear here when you have a lease associated with your tenant account.</p>
                @if (\Illuminate\Support\Facades\Route::has('tenant-portal.applications.index'))
                    <a href="{{ route('tenant-portal.applications.index') }}" class="rn-btn rn-btn--solid">View my applications</a>
                @endif
            </section>
        @endforelse

        @if (method_exists($leases, 'links'))
            <div class="rn-pager">
                {{ $leases->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .rn {
        --rn-ink: var(--terra-navy, #19265d);
        --rn-accent: var(--terra-orange, #D05208);
        --rn-text: #232a45;
        --rn-muted: #6b7388;
        --rn-line: #e3e6ee;
        --rn-soft: #f5f6fa;
        font-family: 'Instrument Sans', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        color: var(--rn-text);
        font-variant-numeric: tabular-nums;
        line-height: 1.5;
    }
    .rn *, .rn *::before, .rn *::after { box-sizing: border-box; }
    .rn h1, .rn h2, .rn h3, .rn p, .rn dl, .rn dd { margin: 0; padding: 0; }

    /* Header */
    .rn-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
    .rn-head h1 { font-size: 1.75rem; font-weight: 700; letter-spacing: -.025em; color: var(--rn-ink); }
    .rn-head p { color: var(--rn-muted); margin-top: 2px; }

    /* Buttons */
    .rn-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; font: inherit; font-weight: 600; font-size: .875rem; padding: 9px 16px; border-radius: 10px; text-decoration: none; cursor: pointer; border: 1px solid transparent; white-space: nowrap; transition: background .15s, color .15s, border-color .15s; }
    .rn-btn--ghost { background: transparent; color: var(--rn-ink); border-color: var(--rn-line); }
    .rn-btn--ghost:hover { background: var(--rn-soft); color: var(--rn-ink); border-color: #cfd4e1; }
    .rn-btn--solid { background: var(--rn-accent); color: #fff; }
    .rn-btn--solid:hover { background: #b94707; color: #fff; }
    .rn-btn:focus-visible, .rn-flash-x:focus-visible { outline: 3px solid color-mix(in srgb, var(--rn-accent) 55%, transparent); outline-offset: 2px; }

    /* Flash */
    .rn-flash { display: flex; align-items: center; gap: 12px; padding: 12px 16px; margin-bottom: 16px; border-radius: 12px; background: #e7f6ed; color: #146c3a; border: 1px solid #bfe5cf; }
    .rn-flash span { flex: 1; }
    .rn-flash-x { border: 0; background: transparent; color: inherit; padding: 4px; border-radius: 6px; cursor: pointer; opacity: .7; }
    .rn-flash-x:hover { opacity: 1; }

    /* Summary strip */
    .rn-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); background: #fff; border: 1px solid var(--rn-line); border-radius: 14px; margin-bottom: 32px; overflow: hidden; }
    @media (min-width: 768px) { .rn-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .rn-stats > div { padding: 18px 22px; border-right: 1px solid var(--rn-line); border-bottom: 1px solid var(--rn-line); }
    .rn-stats > div:nth-child(2n) { border-right: 0; }
    .rn-stats > div:nth-last-child(-n+2) { border-bottom: 0; }
    @media (min-width: 768px) {
        .rn-stats > div { border-bottom: 0; }
        .rn-stats > div:nth-child(2n) { border-right: 1px solid var(--rn-line); }
        .rn-stats > div:last-child { border-right: 0; }
    }
    .rn-stats dt { font-size: .85rem; color: var(--rn-muted); font-weight: 400; }
    .rn-stats dd { font-size: 1.9rem; font-weight: 700; letter-spacing: -.03em; color: var(--rn-ink); line-height: 1.2; }
    .rn-stats .is-ok dd { color: #1a7f46; }
    .rn-stats .is-warn dd { color: #9a6300; }

    /* List heading */
    .rn-listhead { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 8px; margin-bottom: 16px; }
    .rn-listhead h2 { font-size: 1.15rem; font-weight: 700; color: var(--rn-ink); letter-spacing: -.01em; }
    .rn-listhead p, .rn-listhead span { font-size: .875rem; color: var(--rn-muted); }

    /* Card */
    .rn-card { display: grid; grid-template-columns: minmax(0, 1fr); background: #fff; border: 1px solid var(--rn-line); border-radius: 14px; overflow: hidden; margin-bottom: 20px; }
    @media (min-width: 992px) { .rn-card { grid-template-columns: 340px minmax(0, 1fr); } }

    .rn-photo { position: relative; min-height: 220px; background: var(--rn-soft); }
    .rn-photo img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .rn-photo-empty { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; color: #98a0b2; font-size: .85rem; }
    .rn-photo-empty i { font-size: 2.6rem; }
    .rn-photo .rn-pill { position: absolute; top: 14px; left: 14px; box-shadow: 0 1px 4px rgba(25,38,93,.18); }

    .rn-pill { display: inline-flex; align-items: center; gap: 7px; padding: 4px 12px; border-radius: 999px; font-size: .8rem; font-weight: 600; background: #eef0f6; color: #4a5272; }
    .rn-pill i { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .rn-pill--ok   { background: #e7f6ed; color: #1a7f46; }
    .rn-pill--warn { background: #fff3d6; color: #9a6300; }
    .rn-pill--info { background: #e6f1fb; color: #1b64a8; }
    .rn-pill--bad  { background: #fdecea; color: #b42318; }

    .rn-body { padding: 24px 28px; display: flex; flex-direction: column; gap: 20px; min-width: 0; }
    .rn-top { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 16px; }
    .rn-title { min-width: 0; }
    .rn-code { font-size: .8rem; color: var(--rn-muted); }
    .rn-title h3 { font-size: 1.3rem; font-weight: 700; letter-spacing: -.02em; color: var(--rn-ink); margin: 2px 0 4px; overflow-wrap: anywhere; }
    .rn-loc { font-size: .875rem; color: var(--rn-muted); }
    .rn-loc i { margin-right: 6px; }
    .rn-rent { text-align: right; }
    .rn-rent-amt { font-size: 1.6rem; font-weight: 700; letter-spacing: -.03em; color: var(--rn-ink); line-height: 1.2; }
    .rn-rent-amt small { font-size: .5em; font-weight: 600; letter-spacing: 0; color: var(--rn-muted); margin-right: .4em; }

    /* Specs */
    .rn-specs { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); border-top: 1px solid var(--rn-line); border-bottom: 1px solid var(--rn-line); }
    .rn-specs > div { padding: 12px 12px 12px 0; min-width: 0; }
    .rn-specs dt { font-size: .78rem; color: var(--rn-muted); font-weight: 400; }
    .rn-specs dd { font-weight: 600; color: var(--rn-ink); overflow-wrap: anywhere; }

    /* Period */
    .rn-period { display: flex; align-items: center; gap: 18px; }
    .rn-date { font-weight: 700; color: var(--rn-ink); }
    .rn-period-end { text-align: right; }
    .rn-timeline { flex: 1; min-width: 60px; text-align: center; }
    .rn-timeline p { font-size: .8rem; font-weight: 600; color: var(--rn-muted); margin-top: 6px; }
    .rn-meter { height: 6px; border-radius: 999px; background: var(--rn-line); overflow: hidden; }
    .rn-meter > i { display: block; height: 100%; width: 0; border-radius: 999px; background: #1a7f46; transition: width 1s cubic-bezier(.22,1,.36,1); }
    .rn-timeline--plain { align-self: center; }
    .rn-rule { height: 2px; background: var(--rn-line); border-radius: 2px; }

    /* Footer */
    .rn-foot { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; margin-top: auto; padding-top: 16px; border-top: 1px solid var(--rn-line); }
    .rn-ref { font-size: .85rem; color: var(--rn-muted); overflow-wrap: anywhere; }
    .rn-ref i { margin-right: 6px; }
    .rn-sep { display: inline-block; width: 1px; height: 12px; background: var(--rn-line); margin: 0 10px; vertical-align: middle; }
    .rn-actions { display: flex; flex-wrap: wrap; gap: 8px; }

    /* Empty */
    .rn-empty { text-align: center; padding: 56px 24px; background: #fff; border: 1px dashed #d3d8e4; border-radius: 14px; }
    .rn-empty > i { font-size: 2.4rem; color: #b8bfd0; }
    .rn-empty h3 { font-size: 1.15rem; font-weight: 700; color: var(--rn-ink); margin: 10px 0 4px; }
    .rn-empty p { color: var(--rn-muted); max-width: 46ch; margin: 0 auto 20px; }

    .rn-pager { margin-top: 24px; }

    @media (max-width: 575.98px) {
        .rn-body { padding: 18px; }
        .rn-rent { text-align: left; }
        .rn-specs { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .rn-specs > div:nth-child(n+3) { border-top: 1px solid var(--rn-line); }
        .rn-actions, .rn-actions .rn-btn { width: 100%; }
        .rn-stats > div { padding: 14px 16px; }
    }
    @media (prefers-reduced-motion: reduce) { .rn-meter > i { transition: none; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    requestAnimationFrame(function () {
        document.querySelectorAll('.rn-meter > i[data-w]').forEach(function (el) { el.style.width = el.dataset.w + '%'; });
    });
});
</script>
@endsection
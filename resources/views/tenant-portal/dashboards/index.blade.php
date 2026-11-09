@extends('layouts.app')

@section('title', 'Tenant Dashboard')

@section('content')
@php
    use Illuminate\Support\Carbon;

    $user = auth()->user();
    $tenantName = $tenant->full_name ?? $user->name ?? 'Tenant';
    $firstName = explode(' ', trim($tenantName))[0] ?? 'Tenant';
    $initial = strtoupper(mb_substr($firstName, 0, 1));

    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    $currency = function ($amount, $code = 'RWF') {
        return ($code ?: 'RWF') . ' ' . number_format((float) $amount, 0);
    };

    $statusClass = function ($status) {
        return match (strtolower((string) $status)) {
            'active', 'resolved', 'completed', 'paid' => 'status-success',
            'draft', 'pending', 'submitted', 'acknowledged' => 'status-pending',
            'scheduled', 'in_progress', 'in progress' => 'status-info',
            'rejected', 'overdue', 'cancelled', 'canceled' => 'status-danger',
            default => 'status-neutral',
        };
    };
    $statusLabel = fn ($status) => ucfirst(str_replace('_', ' ', $status ?: 'Unknown'));

    $rentalUrl = Route::has('tenant-portal.rentals.index') ? route('tenant-portal.rentals.index') : null;

    // Next lease end date among active leases
    $nextEnd = $leases->filter(fn ($l) => $l->status === 'active' && $l->end_date && Carbon::parse($l->end_date)->isFuture())
        ->map(fn ($l) => Carbon::parse($l->end_date))
        ->sort()->first();
    $nextEndDays = $nextEnd ? (int) now()->startOfDay()->diffInDays($nextEnd->copy()->startOfDay()) : null;
@endphp

<style>
    .tenant-dashboard {
        --td-primary: #D05208; --td-navy: #19265d; --td-text: #20283b;
        --td-muted: #748094; --td-border: #e8ebf1; --td-soft: #f4f6fb;
        color: var(--td-text); font-family: "DM Sans", sans-serif;
    }
    .tenant-dashboard .muted { color: var(--td-muted); }
    .tenant-dashboard .td-in { animation: tdIn .5s both; }
    @keyframes tdIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }

    /* Greeting */
    .td-greet { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; margin-bottom: 22px; }
    .td-greet small { color: var(--td-muted); font-weight: 600; font-size: .82rem; }
    .td-greet h1 { font-size: clamp(1.5rem, 3vw, 2.1rem); font-weight: 800; letter-spacing: -.04em; color: var(--td-navy); margin: 2px 0 0; }
    .td-greet h1 span { color: var(--td-primary); }

    .td-btn { background: var(--td-primary); border: 1px solid var(--td-primary); color: #fff; font-weight: 650; border-radius: 12px; padding: 10px 16px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all .2s; }
    .td-btn:hover { background: #b94706; border-color: #b94706; color: #fff; transform: translateY(-1px); box-shadow: 0 8px 18px rgba(208,82,8,.28); }
    .td-btn-ghost { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.35); color: #fff; }
    .td-btn-ghost:hover { background: rgba(255,255,255,.2); border-color: rgba(255,255,255,.6); box-shadow: none; }
    .td-btn-line { background: #fff; border: 1.5px solid var(--td-border); color: var(--td-navy); }
    .td-btn-line:hover { background: var(--td-navy); border-color: var(--td-navy); color: #fff; box-shadow: none; }

    /* Rent hero card */
    .td-rent { position: relative; overflow: hidden; border-radius: 22px; padding: 28px; color: #fff; background: linear-gradient(120deg, #19265d 0%, #2b3f94 100%); }
    .td-rent::before { content: ""; position: absolute; width: 360px; height: 360px; right: -90px; top: -170px; border-radius: 50%; background: radial-gradient(circle, rgba(208,82,8,.55), transparent 68%); animation: tdFloat 9s ease-in-out infinite; pointer-events: none; }
    .td-rent::after { content: ""; position: absolute; width: 220px; height: 220px; left: -80px; bottom: -130px; border-radius: 50%; background: radial-gradient(circle, rgba(255,255,255,.12), transparent 70%); pointer-events: none; }
    @keyframes tdFloat { 0%,100% { transform: translate(0,0); } 50% { transform: translate(-20px, 18px); } }
    .td-rent > * { position: relative; z-index: 1; }
    .td-rent .eyebrow { font-size: .72rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #ffd2b6; }
    .td-rent .amount { font-size: clamp(1.9rem, 4.5vw, 3rem); font-weight: 800; letter-spacing: -.05em; line-height: 1.1; margin: 8px 0 4px; overflow-wrap: anywhere; }
    .td-rent .sub { color: rgba(255,255,255,.75); font-size: .9rem; }
    .td-rent .chips { display: flex; flex-wrap: wrap; gap: 10px; margin: 18px 0 22px; }
    .td-rent .chip { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18); border-radius: 12px; padding: 8px 14px; font-size: .82rem; }
    .td-rent .chip strong { display: block; font-size: 1.05rem; letter-spacing: -.02em; }

    /* Stats */
    .td-stat { display: flex; align-items: center; gap: 14px; background: #fff; border: 1px solid var(--td-border); border-radius: 16px; padding: 16px 18px; height: 100%; transition: transform .3s, box-shadow .3s; }
    .td-stat:hover { transform: translateY(-3px); box-shadow: 0 12px 26px rgba(25,38,93,.1); }
    .td-stat .ico { width: 46px; height: 46px; border-radius: 13px; display: grid; place-items: center; font-size: 1.2rem; flex-shrink: 0; }
    .ico.o { background: #fff1e8; color: var(--td-primary); } .ico.b { background: #e9edfb; color: #3a4fb5; }
    .ico.g { background: #e8f7ee; color: #1a8a4a; } .ico.a { background: #fff3da; color: #c98500; }
    .td-stat strong { display: block; font-size: 1.45rem; font-weight: 800; letter-spacing: -.03em; color: var(--td-navy); line-height: 1.1; }
    .td-stat span { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--td-muted); }

    /* Cards */
    .td-card { background: #fff; border: 1px solid var(--td-border); border-radius: 18px; padding: 22px; box-shadow: 0 4px 18px rgba(25,38,93,.04); }
    .td-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 16px; }
    .td-head h2 { font-size: 1.05rem; font-weight: 800; color: var(--td-navy); margin: 0; letter-spacing: -.02em; }
    .td-head p { margin: 2px 0 0; font-size: .82rem; color: var(--td-muted); }
    .td-link { color: var(--td-primary); font-size: .86rem; font-weight: 700; text-decoration: none; }
    .td-link:hover { text-decoration: underline; }
    .td-link i { transition: transform .2s; display: inline-block; }
    .td-link:hover i { transform: translateX(4px); }

    .td-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
    .td-tab { border: 1.5px solid var(--td-border); background: #fff; color: var(--td-text); border-radius: 999px; padding: 5px 14px; font-size: .8rem; font-weight: 650; transition: all .2s; }
    .td-tab:hover { border-color: var(--td-primary); color: var(--td-primary); }
    .td-tab.active { background: var(--td-navy); border-color: var(--td-navy); color: #fff; }
    .td-tab .n { opacity: .7; margin-left: 4px; }

    /* Rental item */
    .td-rental { border: 1px solid var(--td-border); border-radius: 16px; padding: 18px; transition: border-color .25s, box-shadow .25s, transform .25s; }
    .td-rental + .td-rental { margin-top: 12px; }
    .td-rental:hover { border-color: rgba(208,82,8,.35); box-shadow: 0 10px 26px rgba(25,38,93,.08); transform: translateY(-2px); }
    .td-rental-ico { width: 48px; height: 48px; flex: 0 0 48px; border-radius: 13px; background: linear-gradient(135deg, #19265d, #2b3f94); color: #fff; display: grid; place-items: center; font-size: 1.25rem; }
    .td-rname { color: var(--td-navy); font-weight: 800; text-decoration: none; letter-spacing: -.01em; }
    .td-rname:hover { color: var(--td-primary); }
    .td-meta { font-size: .82rem; color: var(--td-muted); }
    .td-meta i { margin-right: 5px; color: #a3abc2; }
    .td-kv { display: grid; grid-template-columns: 1fr; gap: 12px; margin-top: 14px; }
    @media (min-width: 576px) { .td-kv { grid-template-columns: 1fr 1.4fr; } }
    .td-kv .k { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--td-muted); }
    .td-kv .v { font-weight: 800; color: var(--td-navy); font-size: 1.02rem; }
    .td-kv .v.sm { font-size: .88rem; font-weight: 650; }
    .td-bar { height: 7px; border-radius: 999px; background: #edf0f6; overflow: hidden; margin-top: 8px; }
    .td-bar > i { display: block; height: 100%; width: 0; border-radius: 999px; background: linear-gradient(90deg, var(--td-primary), #ff9a5c); transition: width 1.1s cubic-bezier(.22,1,.36,1); }
    .td-bar.warn > i { background: linear-gradient(90deg, #c0392b, #ff7a6b); }
    .td-left { font-size: .75rem; color: var(--td-muted); margin-top: 5px; font-weight: 600; }

    /* Status pills */
    .status-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 30px; font-size: .72rem; font-weight: 800; white-space: nowrap; }
    .status-pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .status-success { color: #167347; background: #e5f7ee; } .status-pending { color: #936000; background: #fff4d7; }
    .status-info { color: #2057a7; background: #e8f1ff; } .status-danger { color: #b42318; background: #ffebe9; }
    .status-neutral { color: #5d6678; background: #edf0f4; }

    /* Maintenance timeline */
    .td-tl { position: relative; padding-left: 26px; }
    .td-tl::before { content: ""; position: absolute; left: 7px; top: 6px; bottom: 6px; width: 2px; background: #e8ebf1; }
    .td-tl-item { position: relative; padding: 0 0 18px; }
    .td-tl-item:last-child { padding-bottom: 0; }
    .td-tl-item::before { content: ""; position: absolute; left: -26px; top: 4px; width: 16px; height: 16px; border-radius: 50%; background: #fff; border: 3px solid var(--dot, #9aa1b8); }
    .td-tl-item.status-success { --dot: #1a8a4a; } .td-tl-item.status-pending { --dot: #e0a100; }
    .td-tl-item.status-info { --dot: #2f74d0; } .td-tl-item.status-danger { --dot: #c0392b; }
    .td-tl-box { border: 1px solid var(--td-border); border-radius: 14px; padding: 14px 16px; transition: border-color .2s, background .2s; }
    .td-tl-box:hover { border-color: rgba(208,82,8,.35); background: #fffaf6; }
    .td-tl-title { font-weight: 750; color: var(--td-navy); }

    /* Quick actions */
    .td-qa { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .td-qa a { display: flex; flex-direction: column; gap: 10px; padding: 16px; border: 1px solid var(--td-border); border-radius: 14px; text-decoration: none; color: inherit; transition: all .25s; }
    .td-qa a:hover { border-color: rgba(208,82,8,.4); background: #fffaf6; transform: translateY(-3px); box-shadow: 0 10px 22px rgba(25,38,93,.08); }
    .td-qa .qi { width: 40px; height: 40px; border-radius: 11px; background: #fff1e8; color: var(--td-primary); display: grid; place-items: center; transition: all .25s; }
    .td-qa a:hover .qi { background: var(--td-primary); color: #fff; }
    .td-qa strong { display: block; font-size: .88rem; color: var(--td-navy); font-weight: 800; }
    .td-qa small { color: var(--td-muted); font-size: .74rem; line-height: 1.3; display: block; }

    /* Profile */
    .td-avatar { width: 54px; height: 54px; border-radius: 50%; background: linear-gradient(135deg, var(--td-primary), #ff9a5c); color: #fff; font-weight: 800; font-size: 1.2rem; display: grid; place-items: center; flex-shrink: 0; }
    .td-info { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid #f0f1f5; font-size: .85rem; }
    .td-info:last-of-type { border-bottom: 0; }
    .td-info b { color: var(--td-navy); font-weight: 650; text-align: right; overflow-wrap: anywhere; }
    .td-copy { border: 0; background: var(--td-soft); color: var(--td-navy); width: 28px; height: 28px; border-radius: 8px; margin-left: 6px; transition: all .2s; }
    .td-copy:hover { background: #ffe3d0; color: var(--td-primary); }

    .td-empty { text-align: center; padding: 30px 16px; border: 1.5px dashed #dce1eb; border-radius: 14px; background: #fafbfe; }
    .td-empty i { font-size: 1.9rem; color: var(--td-primary); display: inline-block; animation: tdBob 3s ease-in-out infinite; }
    @keyframes tdBob { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    .td-empty h3 { font-size: .95rem; font-weight: 800; color: var(--td-navy); margin-top: 10px; }

    @media (max-width: 575.98px) { .td-rent { padding: 22px; } .td-card { padding: 16px; } }
    @media (prefers-reduced-motion: reduce) { .td-in, .td-rent::before, .td-empty i { animation: none; } .td-bar > i { transition: none; } }
</style>

<div class="container-fluid py-4 tenant-dashboard">

    {{-- Greeting --}}
    <div class="td-greet td-in">
        <div>
            <small><i class="bi bi-calendar3 me-1"></i>{{ now()->format('l, d F Y') }} · Tenant portal</small>
            <h1>{{ $greeting }}, <span>{{ $firstName }}</span></h1>
        </div>
        @if(Route::has('tenant-portal.profile.index'))
            <a href="{{ route('tenant-portal.profile.index') }}" class="td-btn td-btn-line">
                <i class="bi bi-person-circle"></i> My profile
            </a>
        @endif
    </div>

    {{-- Rent hero + stats --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-5 td-in" style="animation-delay:.05s">
            <section class="td-rent h-100">
                <div class="eyebrow">Monthly rent · combined active leases</div>
                <div class="amount" data-count="{{ (float) $monthlyRentRwf }}" data-prefix="RWF ">{{ $currency($monthlyRentRwf, 'RWF') }}</div>
                @if($monthlyRentUsd > 0)
                    <div class="sub">+ {{ $currency($monthlyRentUsd, 'USD') }}</div>
                @else
                    <div class="sub">Across your active rental agreements</div>
                @endif

                <div class="chips">
                    <div class="chip"><strong>{{ $activeLeaseCount }}</strong>Active {{ \Illuminate\Support\Str::plural('rental', $activeLeaseCount) }}</div>
                    @if($nextEnd)
                        <div class="chip"><strong>{{ $nextEndDays }} {{ \Illuminate\Support\Str::plural('day', $nextEndDays) }}</strong>until next lease ends</div>
                    @endif
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @if($rentalUrl)
                        <a href="{{ $rentalUrl }}" class="td-btn"><i class="bi bi-house-door"></i> My rentals</a>
                    @endif
                    @if(Route::has('tenant-portal.maintenance-requests.create'))
                        <a href="{{ route('tenant-portal.maintenance-requests.create') }}" class="td-btn td-btn-ghost"><i class="bi bi-tools"></i> Report a problem</a>
                    @endif
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-7">
            <div class="row g-3 h-100">
                <div class="col-6 td-in" style="animation-delay:.1s">
                    <div class="td-stat">
                        <div class="ico g"><i class="bi bi-house-check"></i></div>
                        <div><strong data-count="{{ $activeLeaseCount }}">{{ $activeLeaseCount }}</strong><span>Active rentals</span></div>
                    </div>
                </div>
                <div class="col-6 td-in" style="animation-delay:.15s">
                    <div class="td-stat">
                        <div class="ico a"><i class="bi bi-tools"></i></div>
                        <div><strong data-count="{{ $openMaintenanceCount }}">{{ $openMaintenanceCount }}</strong><span>Open requests</span></div>
                    </div>
                </div>
                <div class="col-6 td-in" style="animation-delay:.2s">
                    <div class="td-stat">
                        <div class="ico b"><i class="bi bi-file-earmark-text"></i></div>
                        <div><strong data-count="{{ $pendingLeaseCount }}">{{ $pendingLeaseCount }}</strong><span>Pending leases</span></div>
                    </div>
                </div>
                <div class="col-6 td-in" style="animation-delay:.25s">
                    <div class="td-stat">
                        <div class="ico o"><i class="bi bi-hourglass-split"></i></div>
                        <div>
                            <strong>{{ $nextEnd ? $nextEnd->format('d M Y') : '—' }}</strong>
                            <span>Next lease end</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Main column --}}
        <div class="col-12 col-xl-8">

            {{-- Rentals --}}
            <section class="td-card td-in" style="animation-delay:.3s">
                <div class="td-head">
                    <div>
                        <h2>My rentals</h2>
                        <p>Your latest rental agreements and units.</p>
                    </div>
                    @if($rentalUrl)
                        <a href="{{ $rentalUrl }}" class="td-link">View all <i class="bi bi-arrow-right ms-1"></i></a>
                    @endif
                </div>

                @php
                    $cActive = $recentLeases->where('status', 'active')->count();
                    $cDraft  = $recentLeases->where('status', 'draft')->count();
                @endphp

                @if($recentLeases->count() > 1)
                    <div class="td-tabs" id="tdRentalTabs" role="tablist">
                        <button type="button" class="td-tab active" data-filter="all">All<span class="n">{{ $recentLeases->count() }}</span></button>
                        @if($cActive)<button type="button" class="td-tab" data-filter="active">Active<span class="n">{{ $cActive }}</span></button>@endif
                        @if($cDraft)<button type="button" class="td-tab" data-filter="draft">Pending<span class="n">{{ $cDraft }}</span></button>@endif
                    </div>
                @endif

                <div id="tdRentalList">
                @forelse($recentLeases as $lease)
                    @php
                        $unit = $lease->unit;
                        $building = $unit?->building;
                        $property = $building?->property;
                        $unitLabel = $unit?->unit_number ?? $unit?->name ?? ('Unit #' . ($unit?->id ?? '—'));
                        $propertyName = $property?->name ?? $building?->name ?? 'Rental property';
                        $locationParts = array_filter([$property?->sector, $property?->district, $property?->province]);
                        $leaseCurrency = $lease->currency ?: 'RWF';

                        $start = $lease->start_date ? Carbon::parse($lease->start_date) : null;
                        $end = $lease->end_date ? Carbon::parse($lease->end_date) : null;
                        $pct = null; $daysLeft = null;
                        if ($start && $end && $end->gt($start)) {
                            $total = max(1, $start->diffInDays($end));
                            $elapsed = max(0, min($total, $start->diffInDays(now(), false)));
                            $pct = (int) round(($elapsed / $total) * 100);
                            $daysLeft = (int) now()->startOfDay()->diffInDays($end->copy()->startOfDay(), false);
                        }
                        $leaseUrl = Route::has('tenant-portal.leases.show')
                            ? route('tenant-portal.leases.show', $lease->id)
                            : ($rentalUrl ?: '#');
                    @endphp

                    <article class="td-rental" data-status="{{ $lease->status }}">
                        <div class="d-flex align-items-start gap-3">
                            <div class="td-rental-ico"><i class="bi bi-building"></i></div>
                            <div class="flex-grow-1" style="min-width:0">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                    <div>
                                        <a href="{{ $leaseUrl }}" class="td-rname">{{ $propertyName }}</a>
                                        <div class="td-meta mt-1">
                                            <i class="bi bi-door-open"></i>{{ $unitLabel }}
                                            @if($building && $property)<span class="mx-1">·</span>{{ $building->name }}@endif
                                        </div>
                                    </div>
                                    <span class="status-pill {{ $statusClass($lease->status) }}">{{ $statusLabel($lease->status) }}</span>
                                </div>

                                @if(count($locationParts))
                                    <div class="td-meta mt-2"><i class="bi bi-geo-alt"></i>{{ implode(', ', $locationParts) }}</div>
                                @endif

                                <div class="td-kv">
                                    <div>
                                        <div class="k">Monthly rent</div>
                                        <div class="v">{{ $currency($lease->monthly_rent, $leaseCurrency) }}</div>
                                    </div>
                                    <div>
                                        <div class="k">Lease period</div>
                                        <div class="v sm">
                                            {{ $start ? $start->format('d M Y') : '—' }}
                                            <span class="muted mx-1">to</span>
                                            {{ $end ? $end->format('d M Y') : 'Ongoing' }}
                                        </div>
                                    </div>
                                </div>

                                @if($pct !== null && $lease->status === 'active')
                                    <div class="td-bar {{ $daysLeft !== null && $daysLeft <= 60 ? 'warn' : '' }}" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Lease progress">
                                        <i data-w="{{ $pct }}"></i>
                                    </div>
                                    <div class="td-left">
                                        {{ $pct }}% of lease elapsed
                                        @if($daysLeft !== null && $daysLeft >= 0) · {{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }} left @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="td-empty">
                        <i class="bi bi-house"></i>
                        <h3>No rental agreements yet</h3>
                        <p class="muted small mb-3">Your rental agreements will appear here once they are available on your account.</p>
                        @if(Route::has('tenant-portal.applications.index'))
                            <a href="{{ route('tenant-portal.applications.index') }}" class="td-btn" style="padding:8px 14px;font-size:.85rem">View my applications</a>
                        @endif
                    </div>
                @endforelse
                </div>
            </section>

            {{-- Maintenance --}}
            <section class="td-card mt-4 td-in" style="animation-delay:.35s">
                <div class="td-head">
                    <div>
                        <h2>Recent maintenance</h2>
                        <p>Track repair and maintenance requests.</p>
                    </div>
                    @if(Route::has('tenant-portal.maintenance-requests.index'))
                        <a href="{{ route('tenant-portal.maintenance-requests.index') }}" class="td-link">View all <i class="bi bi-arrow-right ms-1"></i></a>
                    @endif
                </div>

                @if($recentMaintenanceRequests->count())
                    <div class="td-tl">
                        @foreach($recentMaintenanceRequests as $maintenanceRequest)
                            @php
                                $requestUnit = $maintenanceRequest->unit;
                                $requestBuilding = $requestUnit?->building;
                            @endphp
                            <div class="td-tl-item {{ $statusClass($maintenanceRequest->status) }}">
                                <div class="td-tl-box">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div style="min-width:0">
                                            <div class="td-tl-title">{{ $maintenanceRequest->title }}</div>
                                            <div class="td-meta mt-1">
                                                <i class="bi bi-door-open"></i>{{ $requestUnit?->unit_number ?? $requestUnit?->name ?? 'Rental unit' }}
                                                @if($requestBuilding)<span class="mx-1">·</span>{{ $requestBuilding->name }}@endif
                                            </div>
                                            <div class="td-meta mt-1">
                                                <i class="bi bi-clock"></i>Submitted {{ $maintenanceRequest->created_at?->format('d M Y') ?? '—' }}
                                                @if($maintenanceRequest->created_at)<span class="mx-1">·</span>{{ $maintenanceRequest->created_at->diffForHumans() }}@endif
                                            </div>
                                        </div>
                                        <span class="status-pill {{ $statusClass($maintenanceRequest->status) }}">{{ $statusLabel($maintenanceRequest->status) }}</span>
                                    </div>
                                    @if(Route::has('tenant-portal.maintenance-requests.show'))
                                        <div class="mt-2">
                                            <a href="{{ route('tenant-portal.maintenance-requests.show', $maintenanceRequest->id) }}" class="td-link">
                                                View request <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="td-empty">
                        <i class="bi bi-wrench-adjustable"></i>
                        <h3>No maintenance requests</h3>
                        <p class="muted small mb-3">Requests you submit will appear here so you can follow their progress.</p>
                        @if(Route::has('tenant-portal.maintenance-requests.create'))
                            <a href="{{ route('tenant-portal.maintenance-requests.create') }}" class="td-btn" style="padding:8px 14px;font-size:.85rem">Submit a request</a>
                        @endif
                    </div>
                @endif
            </section>
        </div>

        {{-- Sidebar --}}
        <div class="col-12 col-xl-4">
            <section class="td-card mb-4 td-in" style="animation-delay:.4s">
                <div class="td-head"><h2>Quick actions</h2></div>
                <div class="td-qa">
                    @if($rentalUrl)
                        <a href="{{ $rentalUrl }}"><span class="qi"><i class="bi bi-house-door"></i></span><span><strong>My rentals</strong><small>Units and lease details</small></span></a>
                    @endif
                    @if(Route::has('tenant-portal.applications.index'))
                        <a href="{{ route('tenant-portal.applications.index') }}"><span class="qi"><i class="bi bi-file-earmark-text"></i></span><span><strong>Applications</strong><small>Check your applications</small></span></a>
                    @endif
                    @if(Route::has('tenant-portal.maintenance-requests.index'))
                        <a href="{{ route('tenant-portal.maintenance-requests.index') }}"><span class="qi"><i class="bi bi-tools"></i></span><span><strong>Maintenance</strong><small>Report and track issues</small></span></a>
                    @endif
                    @if(Route::has('tenant-portal.profile.index'))
                        <a href="{{ route('tenant-portal.profile.index') }}"><span class="qi"><i class="bi bi-person-gear"></i></span><span><strong>My profile</strong><small>Your personal details</small></span></a>
                    @endif
                </div>
            </section>

            <section class="td-card td-in" style="animation-delay:.45s">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="td-avatar">{{ $initial }}</div>
                    <div style="min-width:0">
                        <div class="fw-bold text-truncate" style="color:var(--td-navy)">{{ $tenantName }}</div>
                        <div class="muted small">Tenant account</div>
                    </div>
                </div>

                <div class="td-info">
                    <span class="muted">Tenant number</span>
                    <span class="d-inline-flex align-items-center">
                        <b id="tdTenantNo">{{ $tenant->tenant_number ?: 'Not assigned' }}</b>
                        @if($tenant->tenant_number)
                            <button type="button" class="td-copy" id="tdCopyNo" aria-label="Copy tenant number" title="Copy"><i class="bi bi-clipboard"></i></button>
                        @endif
                    </span>
                </div>
                <div class="td-info"><span class="muted">Phone</span><b>{{ $tenant->phone ?: 'Not provided' }}</b></div>
                <div class="td-info"><span class="muted">Email</span><b>{{ $tenant->user?->email ?? auth()->user()->email ?? 'Not provided' }}</b></div>
                <div class="td-info">
                    <span class="muted">Account status</span>
                    <span class="status-pill {{ $statusClass($tenant->status) }}">{{ $statusLabel($tenant->status) }}</span>
                </div>

                @if(Route::has('tenant-portal.profile.index'))
                    <a href="{{ route('tenant-portal.profile.index') }}" class="td-btn td-btn-line w-100 justify-content-center mt-3">
                        View profile <i class="bi bi-arrow-right"></i>
                    </a>
                @endif
            </section>
        </div>
    </div>

    <div class="text-center muted small mt-4">
        Terra Smart Rental Management <span class="mx-1">·</span>
        Need help with your rental? Submit a maintenance request from your dashboard.
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Lease progress bars */
    requestAnimationFrame(function () {
        document.querySelectorAll('.td-bar > i[data-w]').forEach(function (el) {
            el.style.width = el.dataset.w + '%';
        });
    });

    /* Count-up numbers */
    if (!reduce) {
        document.querySelectorAll('[data-count]').forEach(function (el) {
            var target = parseFloat(el.dataset.count) || 0;
            if (target <= 0) return;
            var prefix = el.dataset.prefix || '';
            var start = performance.now(), dur = 1000;
            (function tick(now) {
                var p = Math.min((now - start) / dur, 1);
                var eased = 1 - Math.pow(1 - p, 3);
                el.textContent = prefix + Math.round(target * eased).toLocaleString('en-US');
                if (p < 1) requestAnimationFrame(tick);
            })(start);
        });
    }

    /* Rental filter tabs */
    var tabs = document.getElementById('tdRentalTabs');
    if (tabs) {
        tabs.addEventListener('click', function (e) {
            var btn = e.target.closest('.td-tab');
            if (!btn) return;
            tabs.querySelectorAll('.td-tab').forEach(function (b) { b.classList.toggle('active', b === btn); });
            var f = btn.dataset.filter;
            document.querySelectorAll('#tdRentalList .td-rental').forEach(function (card) {
                card.style.display = (f === 'all' || card.dataset.status === f) ? '' : 'none';
            });
        });
    }

    /* Copy tenant number */
    var copy = document.getElementById('tdCopyNo');
    if (copy && navigator.clipboard) {
        copy.addEventListener('click', function () {
            navigator.clipboard.writeText(document.getElementById('tdTenantNo').textContent.trim()).then(function () {
                var i = copy.querySelector('i');
                i.className = 'bi bi-check2';
                setTimeout(function () { i.className = 'bi bi-clipboard'; }, 1600);
            });
        });
    }
});
</script>
@endsection
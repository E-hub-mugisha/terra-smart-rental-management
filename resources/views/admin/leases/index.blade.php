@extends('layouts.app')

@section('title', 'Lease Management')

@push('styles')
<style>
/* Leases: compact type scale, scoped under .ls so nothing leaks into the layout */
.ls {
    --o: #D05208; --o-dark: #b94706; --o-soft: #fff2e9; --o-line: #f4d3bf;
    --navy: #19265d; --ink: #27334a; --mute: #6b778c;
    --line: #e6e9f0; --line-soft: #eff1f6; --wash: #f6f7fb;
    --ok: #147847; --ok-bg: #e8f7ee; --ok-line: #cfeedb;
    --warn: #8f5a08; --warn-bg: #fff3dc; --warn-line: #f3dfb0;
    --bad: #b42318; --bad-bg: #fff1f0; --bad-line: #f3cdc9;
    --info: #116a9c; --info-bg: #e7f4fd;
    color: var(--ink); font-size: .8rem; line-height: 1.5; padding-bottom: 36px;
}
.ls *, .ls *::before, .ls *::after { box-sizing: border-box; }
.ls :focus-visible { outline: 2px solid var(--o); outline-offset: 2px; border-radius: 6px; }
.ls .min-w-0 { min-width: 0; }

.ls-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 14px; margin-bottom: 16px; }
.ls-title { margin: 0 0 4px; color: var(--navy); font-size: clamp(1.25rem, 2.2vw, 1.55rem); font-weight: 800; letter-spacing: -.03em; }
.ls-sub { margin: 0; max-width: 600px; color: var(--mute); }

.ls-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 32px; padding: 5px 12px;
    border: 1px solid var(--line); border-radius: 8px; background: #fff; color: var(--navy);
    font-size: .76rem; font-weight: 700; line-height: 1.2; text-decoration: none; white-space: nowrap; cursor: pointer;
    transition: background .15s, border-color .15s, color .15s; }
.ls-btn:hover { background: var(--o-soft); border-color: var(--o-line); color: var(--o); }
.ls-btn.is-primary { background: var(--o); border-color: var(--o); color: #fff; }
.ls-btn.is-primary:hover { background: var(--o-dark); border-color: var(--o-dark); color: #fff; }

.ls-alert { display: flex; gap: 9px; align-items: flex-start; margin-bottom: 14px; padding: 10px 13px; border-radius: 10px; font-weight: 600; }
.ls-alert.is-ok { background: var(--ok-bg); color: var(--ok); border: 1px solid var(--ok-line); }
.ls-alert.is-bad { background: var(--bad-bg); color: var(--bad); border: 1px solid var(--bad-line); }
.ls-alert ul { margin: 0; padding-left: 16px; font-weight: 500; }
.ls-alert .btn-close { margin-left: auto; font-size: .6rem; }

/* Attention strip: what needs doing right now */
.ls-attn { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
.ls-attn a { display: inline-flex; align-items: center; gap: 7px; padding: 7px 12px; border-radius: 10px; font-weight: 700; text-decoration: none; }
.ls-attn .is-info { background: var(--info-bg); color: var(--info); }
.ls-attn .is-warn { background: var(--warn-bg); color: var(--warn); }
.ls-attn a:hover { filter: brightness(.96); }

.ls-panel { background: #fff; border: 1px solid var(--line); border-radius: 14px; }

.ls-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; padding: 12px 14px; border-bottom: 1px solid var(--line); }
.ls-search { position: relative; flex: 1 1 240px; max-width: 380px; }
.ls-search > i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #8f9ab0; pointer-events: none; }
.ls-input { width: 100%; min-height: 34px; padding: 6px 32px 6px 32px; border: 1px solid #dde2ec; border-radius: 9px; background: #fff; color: var(--ink); font-size: .8rem; }
.ls-input:focus { border-color: var(--o); box-shadow: 0 0 0 3px rgba(208,82,8,.1); outline: none; }
.ls-clear { position: absolute; right: 5px; top: 50%; transform: translateY(-50%); display: grid; place-items: center; width: 24px; height: 24px; border-radius: 7px; color: var(--mute); text-decoration: none; }
.ls-clear:hover { background: var(--wash); color: var(--o); }
.ls-count { margin-left: auto; color: var(--mute); font-size: .74rem; }

.ls-tabs { display: flex; gap: 3px; padding: 8px 14px; overflow-x: auto; border-bottom: 1px solid var(--line); scrollbar-width: thin; }
.ls-tab { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 8px; color: var(--mute); font-size: .76rem; font-weight: 700; text-decoration: none; }
.ls-tab:hover { color: var(--navy); background: var(--wash); }
.ls-tab.is-active { background: var(--o-soft); color: var(--o); }
.ls-tab small { font-size: .7rem; font-weight: 700; opacity: .8; }

.ls-headrow, .ls-row { display: grid; align-items: center; gap: 0 14px;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr) minmax(0, 1.2fr) minmax(0, 1fr) minmax(0, 1.2fr) 64px 124px 84px; }
.ls-headrow { padding: 8px 16px; background: var(--wash); border-bottom: 1px solid var(--line); color: var(--mute); font-size: .7rem; font-weight: 700; }
.ls-headrow .t-end { text-align: right; }
.ls-row { padding: 11px 16px; border-bottom: 1px solid var(--line-soft); transition: background .15s; }
.ls-row:last-child { border-bottom: 0; }
.ls-row:hover { background: #fcfcfe; }
.ls-cell { min-width: 0; }
.ls-label { display: none; margin-bottom: 1px; color: var(--mute); font-size: .66rem; font-weight: 700; }
.ls-strong { color: var(--navy); font-weight: 800; overflow-wrap: anywhere; }
.ls-name { color: #2f3b52; font-weight: 700; overflow-wrap: anywhere; }
.ls-note { margin-top: 1px; color: var(--mute); font-size: .72rem; overflow-wrap: anywhere; }
.ls-note.is-warn { color: var(--warn); font-weight: 700; }
.ls-note.is-bad { color: var(--bad); font-weight: 700; }
.ls-rent { color: var(--navy); font-weight: 800; white-space: nowrap; }
.ls-act { display: flex; justify-content: flex-end; gap: 6px; }

.ls-pill { display: inline-flex; align-items: center; gap: 5px; width: fit-content; padding: 3px 9px; border-radius: 999px; background: var(--wash); color: var(--mute); font-size: .7rem; font-weight: 700; white-space: nowrap; }
.ls-pill::before { content: ""; width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.ls-pill.is-success { background: var(--ok-bg); color: var(--ok); }
.ls-pill.is-info { background: var(--info-bg); color: var(--info); }
.ls-pill.is-warning { background: var(--warn-bg); color: var(--warn); }
.ls-pill.is-danger { background: var(--bad-bg); color: var(--bad); }
.ls-pill.is-dark { background: #e9ebf0; color: #363f52; }

.ls-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 11px 16px; border-top: 1px solid var(--line); }
.ls-foot .pagination { margin: 0; gap: 3px; }
.ls-foot .page-link { border-radius: 7px; border-color: var(--line); color: var(--navy); font-size: .74rem; padding: 4px 10px; box-shadow: none; }
.ls-foot .page-item.active .page-link { background: var(--o); border-color: var(--o); color: #fff; }

.ls-empty { padding: 48px 20px; text-align: center; }
.ls-empty-icon { display: grid; place-items: center; width: 52px; height: 52px; margin: 0 auto 14px; border-radius: 16px; background: var(--o-soft); color: var(--o); font-size: 1.4rem; }
.ls-empty h3 { margin: 0 0 5px; color: var(--navy); font-size: .95rem; font-weight: 800; }
.ls-empty p { max-width: 380px; margin: 0 auto 14px; color: var(--mute); }

@media (min-width: 992px) { .ls-grid { display: contents; } }
@media (max-width: 991.98px) {
    .ls-headrow { display: none; }
    .ls-row { grid-template-columns: minmax(0, 1fr) auto; gap: 10px 12px; align-items: start; }
    .ls-cell.is-status { grid-column: 2; grid-row: 1; justify-self: end; }
    .ls-grid { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px 12px; }
    .ls-label { display: block; }
    .ls-act { grid-column: 1 / -1; }
    .ls-act .ls-btn { width: 100%; }
}
@media (max-width: 575.98px) {
    .ls-toolbar { padding: 10px; }
    .ls-search { max-width: none; flex-basis: 100%; }
    .ls-count { margin-left: 0; }
    .ls-tabs { padding: 8px 10px; }
}
@media (prefers-reduced-motion: reduce) { .ls *, .ls *::before, .ls *::after { transition-duration: .01ms !important; } }
</style>
@endpush

@section('content')
@php
    use Illuminate\Support\Str;

    // Tab key => [label, count key in $counts (null = not provided)]
    $tabs = [
        ''                          => ['All',                'all'],
        'draft'                     => ['Draft',              'draft'],
        'pending_tenant_acceptance' => ['Awaiting acceptance', 'awaiting_acceptance'],
        'pending_activation'        => ['Pending activation', 'pending_activation'],
        'active'                    => ['Active',             'active'],
        'terminated'                => ['Terminated',         null],
        'expired'                   => ['Expired',            null],
        'rejected'                  => ['Rejected',           null],
        'cancelled'                 => ['Cancelled',          null],
    ];

    $pillFor = fn ($s) => match ($s) {
        'active' => 'is-success',
        'pending_activation' => 'is-info',
        'pending_tenant_acceptance' => 'is-warning',
        'rejected', 'cancelled' => 'is-danger',
        'terminated' => 'is-dark',
        default => '',
    };
    $statusLabel = fn ($s) => $s === 'pending_tenant_acceptance' ? 'Awaiting acceptance' : Str::headline((string) $s);

    $search        = trim((string) request('search'));
    $currentStatus = (string) request('status');
    $hasFilters    = $search !== '' || $currentStatus !== '';

    $tabUrl = fn (string $status) => route('admin.leases.index', array_filter([
        'search' => $search ?: null,
        'status' => $status ?: null,
    ]));

    $pendingActivation = $counts['pending_activation'] ?? 0;
    $awaiting          = $counts['awaiting_acceptance'] ?? 0;
@endphp

<div class="ls container-fluid py-4">

    <div class="ls-head">
        <div>
            <h1 class="ls-title">Lease management</h1>
            <p class="ls-sub">Lease agreements, rental invoices, payments and move-ins in one place.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-dismissible fade show p-0 border-0 bg-transparent mb-0" role="status">
            <div class="ls-alert is-ok w-100">
                <i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-dismissible fade show p-0 border-0 bg-transparent mb-0" role="alert">
            <div class="ls-alert is-bad w-100">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
            </div>
        </div>
    @endif

    {{-- What needs attention --}}
    @if(($pendingActivation || $awaiting) && !in_array($currentStatus, ['pending_activation', 'pending_tenant_acceptance'], true))
        <div class="ls-attn">
            @if($pendingActivation)
                <a href="{{ $tabUrl('pending_activation') }}" class="is-info">
                    <i class="bi bi-key"></i> {{ number_format($pendingActivation) }} ready for activation steps
                </a>
            @endif
            @if($awaiting)
                <a href="{{ $tabUrl('pending_tenant_acceptance') }}" class="is-warn">
                    <i class="bi bi-hourglass-split"></i> {{ number_format($awaiting) }} waiting for the tenant to accept
                </a>
            @endif
        </div>
    @endif

    <section class="ls-panel">

        <form method="GET" action="{{ route('admin.leases.index') }}" class="ls-toolbar" role="search">
            @if($currentStatus !== '')
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif

            <div class="ls-search">
                <i class="bi bi-search"></i>
                <input type="search" name="search" class="ls-input" value="{{ $search }}"
                       placeholder="Lease number, tenant, email or unit" aria-label="Search leases">
                @if($search !== '')
                    <a href="{{ route('admin.leases.index', array_filter(['status' => $currentStatus])) }}"
                       class="ls-clear" title="Clear search" aria-label="Clear search"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>

            <button type="submit" class="ls-btn is-primary"><i class="bi bi-search"></i> Search</button>

            @if($hasFilters)
                <a href="{{ route('admin.leases.index') }}" class="ls-btn"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            @endif

            <span class="ls-count">{{ number_format($leases->total()) }} {{ Str::plural('lease', $leases->total()) }}</span>
        </form>

        <nav class="ls-tabs" aria-label="Filter by status">
            @foreach($tabs as $key => [$text, $countKey])
                @php $active = (string) $key === $currentStatus; @endphp
                <a href="{{ $tabUrl((string) $key) }}" class="ls-tab {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif>
                    {{ $text }}
                    @if($countKey && isset($counts[$countKey])) <small>{{ number_format($counts[$countKey]) }}</small> @endif
                </a>
            @endforeach
        </nav>

        @if($leases->count())
            <div role="table" aria-label="Leases">
                <div class="ls-headrow" aria-hidden="true">
                    <span>Lease</span><span>Tenant</span><span>Property</span><span>Monthly rent</span><span>Period</span><span>Invoices</span><span>Status</span><span class="t-end">Action</span>
                </div>

                @foreach($leases as $lease)
                    @php
                        $end      = $lease->end_date;
                        $daysLeft = ($lease->status === 'active' && $end) ? (int) now()->startOfDay()->diffInDays($end->copy()->startOfDay(), false) : null;
                        $expiring = $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 60;
                        $overdueEnd = $daysLeft !== null && $daysLeft < 0;
                    @endphp

                    <article class="ls-row" role="row">
                        <div class="ls-cell">
                            <a href="{{ route('admin.leases.show', $lease) }}" class="ls-strong text-decoration-none">{{ $lease->lease_number }}</a>
                        </div>

                        <div class="ls-grid">
                            <div class="ls-cell">
                                <div class="ls-label">Tenant</div>
                                <div class="ls-name">{{ $lease->tenant?->full_name ?? 'Tenant unavailable' }}</div>
                                <div class="ls-note">{{ $lease->tenant?->email ?: 'No email on file' }}</div>
                            </div>

                            <div class="ls-cell">
                                <div class="ls-label">Property</div>
                                <div class="ls-name">{{ $lease->unit?->building?->name ?? 'Property' }}</div>
                                <div class="ls-note">Unit {{ $lease->unit?->unit_number ?? 'N/A' }}</div>
                            </div>

                            <div class="ls-cell">
                                <div class="ls-label">Monthly rent</div>
                                <div class="ls-rent">{{ $lease->currency }} {{ number_format((float) $lease->monthly_rent, $lease->currency === 'RWF' ? 0 : 2) }}</div>
                            </div>

                            <div class="ls-cell">
                                <div class="ls-label">Period</div>
                                <div class="ls-name">{{ $lease->start_date?->format('d M Y') ?? 'Not set' }}</div>
                                <div class="ls-note {{ $expiring ? 'is-warn' : ($overdueEnd ? 'is-bad' : '') }}">
                                    @if($end)
                                        to {{ $end->format('d M Y') }}
                                        @if($expiring) <br>Ends in {{ $daysLeft }} {{ Str::plural('day', $daysLeft) }} @endif
                                        @if($overdueEnd) <br>Ended {{ abs($daysLeft) }} {{ Str::plural('day', abs($daysLeft)) }} ago @endif
                                    @else
                                        Open-ended
                                    @endif
                                </div>
                            </div>

                            <div class="ls-cell">
                                <div class="ls-label">Invoices</div>
                                <div class="ls-name">{{ $lease->invoices_count ?? 0 }}</div>
                            </div>
                        </div>

                        <div class="ls-cell is-status">
                            <span class="ls-pill {{ $pillFor($lease->status) }}">{{ $statusLabel($lease->status) }}</span>
                        </div>

                        <div class="ls-act">
                            <a href="{{ route('admin.leases.show', $lease) }}" class="ls-btn {{ $lease->status === 'pending_activation' ? 'is-primary' : '' }}"
                               aria-label="Manage lease {{ $lease->lease_number }}">Manage</a>
                            @unless(in_array($lease->status, ['active', 'pending_activation'], true))
                                <form method="POST" action="{{ route('admin.leases.destroy', $lease) }}" class="d-inline m-0"
                                      onsubmit="return confirm('Delete this lease? Its unpaid invoices will be deleted too.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ls-btn text-danger" title="Delete lease"
                                            aria-label="Delete lease {{ $lease->lease_number }}"><i class="bi bi-trash3"></i></button>
                                </form>
                            @endunless
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="ls-foot">
                <div class="ls-count" style="margin-left:0;">
                    Showing <strong>{{ $leases->firstItem() }}</strong>–<strong>{{ $leases->lastItem() }}</strong> of <strong>{{ number_format($leases->total()) }}</strong>
                </div>
                @if($leases->hasPages())
                    {{ $leases->withQueryString()->links('pagination::bootstrap-5') }}
                @endif
            </div>
        @else
            <div class="ls-empty">
                <div class="ls-empty-icon"><i class="bi {{ $hasFilters ? 'bi-search' : 'bi-file-earmark-text' }}"></i></div>
                <h3>{{ $hasFilters ? 'No leases match your filters' : 'No leases yet' }}</h3>
                <p>
                    {{ $hasFilters
                        ? 'Try a different keyword or status, or reset the filters to see every lease.'
                        : 'Leases appear here once a tenant application is approved.' }}
                </p>
                @if($hasFilters)
                    <a href="{{ route('admin.leases.index') }}" class="ls-btn"><i class="bi bi-arrow-counterclockwise"></i> Reset filters</a>
                @else
                    <a href="{{ route('admin.tenant-applications.index') }}" class="ls-btn is-primary">Go to applications</a>
                @endif
            </div>
        @endif
    </section>
</div>
@endsection
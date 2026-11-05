@extends('layouts.app')

@section('title', 'Tenant Applications')

@push('styles')
<style>
/* Tenant applications: scoped under .ta so nothing leaks into the layout */
.ta {
    --o: #D05208; --o-dark: #b94706; --o-soft: #fff2e9; --o-line: #f4d3bf;
    --navy: #19265d; --ink: #27334a; --mute: #6b778c;
    --line: #e6e9f0; --line-soft: #eff1f6; --wash: #f6f7fb;
    --ok: #147847; --ok-bg: #e8f7ee; --warn: #8f5a08; --warn-bg: #fff3dc;
    --bad: #b42318; --bad-bg: #fff1f0;
    color: var(--ink); font-size: .9rem; padding-bottom: 40px;
}
.ta *, .ta *::before, .ta *::after { box-sizing: border-box; }
.ta :focus-visible { outline: 2px solid var(--o); outline-offset: 2px; border-radius: 6px; }
.ta .min-w-0 { min-width: 0; }

.ta-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
.ta-title { margin: 0 0 6px; color: var(--navy); font-size: clamp(1.5rem, 2.6vw, 2rem); font-weight: 800; letter-spacing: -.035em; }
.ta-sub { margin: 0; max-width: 620px; color: var(--mute); line-height: 1.6; }

.ta-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 38px; padding: 8px 14px;
    border: 1px solid var(--line); border-radius: 10px; background: #fff; color: var(--navy);
    font-size: .82rem; font-weight: 700; line-height: 1.2; text-decoration: none; white-space: nowrap; cursor: pointer;
    transition: background .15s, border-color .15s, color .15s; }
.ta-btn:hover { background: var(--o-soft); border-color: var(--o-line); color: var(--o); }
.ta-btn.is-primary { background: var(--o); border-color: var(--o); color: #fff; }
.ta-btn.is-primary:hover { background: var(--o-dark); border-color: var(--o-dark); color: #fff; }
.ta-btn.is-sm { min-height: 32px; padding: 5px 11px; font-size: .78rem; border-radius: 8px; }

/* Heads-up banner for work waiting on a decision */
.ta-queue { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-bottom: 18px; padding: 13px 16px;
    background: var(--warn-bg); border: 1px solid #f3dfb0; border-radius: 13px; color: var(--warn); font-weight: 600; }
.ta-queue i { font-size: 1.1rem; }
.ta-queue a { margin-left: auto; color: inherit; font-weight: 800; }

.ta-alert { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 16px; padding: 12px 14px; border-radius: 12px; font-weight: 600; }
.ta-alert.is-ok { background: var(--ok-bg); color: var(--ok); border: 1px solid #cfeedb; }
.ta-alert.is-bad { background: var(--bad-bg); color: var(--bad); border: 1px solid #f3cdc9; }
.ta-alert ul { margin: 0; padding-left: 18px; font-weight: 500; }
.ta-alert .btn-close { margin-left: auto; font-size: .7rem; }

.ta-panel { background: #fff; border: 1px solid var(--line); border-radius: 16px; }

/* Toolbar */
.ta-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 16px; border-bottom: 1px solid var(--line); }
.ta-search { position: relative; flex: 1 1 280px; max-width: 420px; }
.ta-search > i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #8f9ab0; pointer-events: none; }
.ta-input { width: 100%; min-height: 40px; padding: 8px 36px 8px 38px; border: 1px solid #dde2ec; border-radius: 10px; background: #fff; color: var(--ink); font-size: .85rem; }
.ta-input:focus { border-color: var(--o); box-shadow: 0 0 0 3px rgba(208,82,8,.1); outline: none; }
.ta-clear { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); display: grid; place-items: center; width: 28px; height: 28px; border-radius: 8px; color: var(--mute); text-decoration: none; }
.ta-clear:hover { background: var(--wash); color: var(--o); }
.ta-tabs { display: inline-flex; flex-wrap: wrap; gap: 4px; padding: 4px; background: var(--wash); border-radius: 11px; }
.ta-tab { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; color: var(--mute); font-size: .79rem; font-weight: 700; text-decoration: none; }
.ta-tab:hover { color: var(--navy); }
.ta-tab.is-active { background: #fff; color: var(--navy); box-shadow: 0 1px 3px rgba(25,38,93,.12); }
.ta-tab small { font-size: .72rem; font-weight: 700; color: var(--mute); }
.ta-tab.is-attn small { padding: 1px 6px; border-radius: 99px; background: var(--warn-bg); color: var(--warn); }
.ta-count { margin-left: auto; color: var(--mute); font-size: .78rem; }

/* Rows (table on desktop, stacked cards on small screens) */
.ta-head-row, .ta-row { display: grid; align-items: center; gap: 0 16px;
    grid-template-columns: minmax(0, 1.4fr) minmax(0, 1.6fr) minmax(0, .8fr) minmax(0, 1fr) minmax(0, 1.1fr) 120px 96px; }
.ta-head-row { padding: 10px 18px; background: var(--wash); border-bottom: 1px solid var(--line); color: var(--mute); font-size: .74rem; font-weight: 700; }
.ta-head-row .t-end { text-align: right; }
.ta-row { padding: 14px 18px; border-bottom: 1px solid var(--line-soft); transition: background .15s; }
.ta-row:last-child { border-bottom: 0; }
.ta-row:hover { background: #fcfcfe; }
.ta-cell { min-width: 0; font-size: .82rem; }
.ta-label { display: none; margin-bottom: 2px; color: var(--mute); font-size: .7rem; font-weight: 700; }
.ta-strong { color: var(--navy); font-weight: 800; overflow-wrap: anywhere; }
.ta-name { color: #2f3b52; font-weight: 700; overflow-wrap: anywhere; }
.ta-note { margin-top: 3px; color: var(--mute); font-size: .76rem; line-height: 1.5; overflow-wrap: anywhere; }
.ta-note.is-warn { color: var(--warn); font-weight: 600; }
.ta-note.is-bad { color: var(--bad); font-weight: 600; }
.ta-rent { color: var(--navy); font-weight: 800; white-space: nowrap; }
.ta-act { display: flex; justify-content: flex-end; }

.ta-pill { display: inline-flex; align-items: center; gap: 6px; width: fit-content; padding: 4px 10px; border-radius: 999px; background: var(--wash); color: var(--mute); font-size: .73rem; font-weight: 700; white-space: nowrap; }
.ta-pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.ta-pill.is-warning { background: var(--warn-bg); color: var(--warn); }
.ta-pill.is-success { background: var(--ok-bg); color: var(--ok); }
.ta-pill.is-danger { background: var(--bad-bg); color: var(--bad); }
.ta-tag { display: inline-flex; align-items: center; gap: 5px; margin-top: 5px; padding: 2px 8px; border-radius: 6px; background: #edf0fb; color: var(--navy); font-size: .7rem; font-weight: 700; }

.ta-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 18px; border-top: 1px solid var(--line); }
.ta-foot .pagination { margin: 0; gap: 4px; }
.ta-foot .page-link { border-radius: 8px; border-color: var(--line); color: var(--navy); font-size: .8rem; box-shadow: none; }
.ta-foot .page-item.active .page-link { background: var(--o); border-color: var(--o); color: #fff; }

.ta-empty { padding: 56px 20px; text-align: center; }
.ta-empty-icon { display: grid; place-items: center; width: 60px; height: 60px; margin: 0 auto 16px; border-radius: 18px; background: var(--o-soft); color: var(--o); font-size: 1.6rem; }
.ta-empty h3 { margin: 0 0 6px; color: var(--navy); font-size: 1.02rem; font-weight: 800; }
.ta-empty p { max-width: 400px; margin: 0 auto 18px; color: var(--mute); line-height: 1.6; }

@media (max-width: 991.98px) {
    .ta-head-row { display: none; }
    .ta-row { grid-template-columns: minmax(0, 1fr) auto; gap: 12px 14px; align-items: start; }
    .ta-cell.is-main { grid-column: 1; }
    .ta-cell.is-status { grid-column: 2; grid-row: 1; justify-self: end; }
    .ta-grid { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px 14px; }
    .ta-label { display: block; }
    .ta-act { grid-column: 1 / -1; justify-content: stretch; }
    .ta-act .ta-btn { width: 100%; }
}
@media (max-width: 575.98px) {
    .ta-toolbar { padding: 12px; }
    .ta-search { max-width: none; flex-basis: 100%; }
    .ta-count { margin-left: 0; }
}
@media (min-width: 992px) {
    .ta-grid { display: contents; }
}
@media (prefers-reduced-motion: reduce) {
    .ta *, .ta *::before, .ta *::after { transition-duration: .01ms !important; }
}
</style>
@endpush

@section('content')
@php
    use Illuminate\Support\Str;

    $tabs = [
        'all'       => 'All',
        'pending'   => 'Pending',
        'approved'  => 'Approved',
        'rejected'  => 'Rejected',
        'withdrawn' => 'Withdrawn',
    ];

    $statusPill = [
        'pending'   => 'is-warning',
        'approved'  => 'is-success',
        'rejected'  => 'is-danger',
        'withdrawn' => '',
    ];

    $search        = trim((string) request('search'));
    $currentStatus = request('status');
    $hasFilters    = $search !== '' || filled($currentStatus);

    // $counts comes from the controller. Missing keys are simply not shown.
    $count = fn (string $key) => $counts[$key] ?? null;

    $tabUrl = fn (string $key) => route('admin.tenant-applications.index', array_filter([
        'search' => $search ?: null,
        'status' => $key === 'all' ? null : $key,
    ]));

    $pendingCount = $count('pending');
@endphp

<div class="ta container-fluid py-4">

    {{-- Heading --}}
    <div class="ta-head">
        <div>
            <h1 class="ta-title">Tenant applications</h1>
            <p class="ta-sub">Review rental applications and record approval decisions.</p>
        </div>

        <a href="{{ route('admin.tenant-applications.create') }}" class="ta-btn is-primary">
            <i class="bi bi-plus-lg"></i> Add application
        </a>
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
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Work waiting on a decision --}}
    @if($pendingCount && $currentStatus !== 'pending')
        <div class="ta-queue">
            <i class="bi bi-hourglass-split"></i>
            <span>{{ number_format($pendingCount) }} {{ Str::plural('application', $pendingCount) }} waiting for a decision</span>
            <a href="{{ $tabUrl('pending') }}">Review pending</a>
        </div>
    @endif

    <section class="ta-panel">

        {{-- Search and status tabs --}}
        <form method="GET" action="{{ route('admin.tenant-applications.index') }}" class="ta-toolbar" role="search">
            @if($currentStatus)
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif

            <div class="ta-search">
                <i class="bi bi-search"></i>
                <input type="search" name="search" class="ta-input" value="{{ $search }}"
                       placeholder="Search number, tenant or unit" aria-label="Search applications">
                @if($search !== '')
                    <a href="{{ route('admin.tenant-applications.index', array_filter(['status' => $currentStatus])) }}"
                       class="ta-clear" title="Clear search" aria-label="Clear search">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>

            <nav class="ta-tabs" aria-label="Filter by status">
                @foreach($tabs as $key => $text)
                    @php $active = $key === 'all' ? !$currentStatus : $currentStatus === $key; @endphp
                    <a href="{{ $tabUrl($key) }}"
                       class="ta-tab {{ $active ? 'is-active' : '' }} {{ $key === 'pending' && $count('pending') ? 'is-attn' : '' }}"
                       @if($active) aria-current="page" @endif>
                        {{ $text }}
                        @if($count($key) !== null) <small>{{ number_format($count($key)) }}</small> @endif
                    </a>
                @endforeach
            </nav>

            <span class="ta-count">
                {{ number_format($applications->total()) }} {{ Str::plural('result', $applications->total()) }}
            </span>
        </form>

        @if($applications->count())
            <div role="table" aria-label="Tenant applications">
                <div class="ta-head-row" role="row" aria-hidden="true">
                    <span>Application</span>
                    <span>Applicant</span>
                    <span>Unit</span>
                    <span>Offered rent</span>
                    <span>Move-in</span>
                    <span>Status</span>
                    <span class="t-end">Action</span>
                </div>

                @foreach($applications as $application)
                    @php
                        $status   = $application->status;
                        $date     = $application->application_date ?? $application->created_at;
                        $moveIn   = $application->desired_move_in_date;
                        $isOpen   = $status === 'pending';
                        $waitDays = $isOpen && $date ? (int) $date->diffInDays(now()) : null;
                        $moveInPast = $isOpen && $moveIn && $moveIn->isPast();
                        $hasLease = $application->relationLoaded('lease') && $application->lease;
                    @endphp

                    <article class="ta-row" role="row">
                        <div class="ta-cell is-main">
                            <div class="ta-strong">{{ $application->application_number }}</div>
                            <div class="ta-note {{ $waitDays !== null && $waitDays >= 7 ? 'is-warn' : '' }}">
                                {{ $date?->format('d M Y') }}
                                @if($waitDays !== null && $waitDays >= 7)
                                    <br>Waiting {{ $waitDays }} days
                                @endif
                            </div>
                        </div>

                        <div class="ta-grid">
                            <div class="ta-cell">
                                <div class="ta-label">Applicant</div>
                                <div class="ta-name">{{ $application->tenant?->full_name ?? 'Tenant unavailable' }}</div>
                                <div class="ta-note">{{ $application->tenant?->email ?? 'No email on file' }}</div>
                            </div>

                            <div class="ta-cell">
                                <div class="ta-label">Unit</div>
                                <div class="ta-name">{{ $application->unit?->unit_number ? 'Unit ' . $application->unit->unit_number : 'Unit unavailable' }}</div>
                            </div>

                            <div class="ta-cell">
                                <div class="ta-label">Offered rent</div>
                                <div class="ta-rent">{{ $application->currency ?? 'RWF' }} {{ number_format((float) $application->offered_rent, 0) }}</div>
                                <div class="ta-note">per month</div>
                            </div>

                            <div class="ta-cell">
                                <div class="ta-label">Move-in</div>
                                @if($moveIn)
                                    <div class="ta-name">{{ $moveIn->format('d M Y') }}</div>
                                    @if($moveInPast)
                                        <div class="ta-note is-bad">Date has passed</div>
                                    @endif
                                @else
                                    <div class="ta-note">Not specified</div>
                                @endif
                            </div>
                        </div>

                        <div class="ta-cell is-status">
                            <span class="ta-pill {{ $statusPill[$status] ?? '' }}">{{ ucfirst($status) }}</span>
                            @if($hasLease)
                                <div><span class="ta-tag"><i class="bi bi-file-earmark-check"></i> Lease created</span></div>
                            @endif
                        </div>

                        <div class="ta-act">
                            <a href="{{ route('admin.tenant-applications.show', $application) }}"
                               class="ta-btn is-sm {{ $isOpen ? 'is-primary' : '' }}"
                               aria-label="{{ $isOpen ? 'Review' : 'View' }} application {{ $application->application_number }}">
                                {{ $isOpen ? 'Review' : 'View' }}
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="ta-foot">
                <div class="ta-count" style="margin-left:0;">
                    Showing <strong>{{ $applications->firstItem() }}</strong>–<strong>{{ $applications->lastItem() }}</strong>
                    of <strong>{{ number_format($applications->total()) }}</strong>
                </div>

                @if($applications->hasPages())
                    {{ $applications->withQueryString()->links('pagination::bootstrap-5') }}
                @endif
            </div>
        @else
            <div class="ta-empty">
                <div class="ta-empty-icon"><i class="bi {{ $hasFilters ? 'bi-search' : 'bi-file-earmark-text' }}"></i></div>
                <h3>{{ $hasFilters ? 'No applications match your filters' : 'No applications yet' }}</h3>
                <p>
                    {{ $hasFilters
                        ? 'Try a different keyword, or clear the filters to see every application.'
                        : 'Applications will appear here once a tenant applies for a unit, or you can add one yourself.' }}
                </p>

                @if($hasFilters)
                    <a href="{{ route('admin.tenant-applications.index') }}" class="ta-btn">
                        <i class="bi bi-arrow-counterclockwise"></i> Clear filters
                    </a>
                @else
                    <a href="{{ route('admin.tenant-applications.create') }}" class="ta-btn is-primary">
                        <i class="bi bi-plus-lg"></i> Add application
                    </a>
                @endif
            </div>
        @endif
    </section>
</div>
@endsection
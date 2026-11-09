@extends('layouts.app')

@section('title', 'Assigned Properties')
@section('page_heading', 'Assigned Properties')
@section('page_subtitle', 'View and monitor properties assigned to you.')

@push('styles')
<style>
    .pm-wrap { --pm-navy: var(--terra-navy, #111b48); --pm-orange: var(--terra-orange, #d05208); --pm-line: #e4e8f2; --pm-soft: #f4f6fb; }

    /* Toolbar */
    .pm-bar { position: sticky; top: 72px; z-index: 20; background: #fff; border: 1px solid var(--pm-line); border-radius: 16px; padding: 10px; box-shadow: 0 10px 28px rgba(17,27,72,.08); }
    .pm-bar-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .pm-search { position: relative; flex: 1 1 280px; min-width: 0; }
    .pm-search > i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--pm-orange); pointer-events: none; }
    .pm-search input { width: 100%; border: 0; background: var(--pm-soft); border-radius: 12px; padding: 12px 40px 12px 42px; font-size: .93rem; font-weight: 500; transition: box-shadow .2s, background .2s; }
    .pm-search input:focus { outline: 0; background: #fff; box-shadow: 0 0 0 3px rgba(208,82,8,.18); }
    .pm-x { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 28px; height: 28px; border: 0; border-radius: 50%; background: transparent; color: #8b93ac; display: none; }
    .pm-search.has-value .pm-x { display: block; }
    .pm-x:hover { background: #ffeadc; color: var(--pm-orange); }
    .pm-pills { display: flex; flex-wrap: wrap; gap: 6px; }
    .pm-pills input { position: absolute; opacity: 0; pointer-events: none; }
    .pm-pills label { border: 1.5px solid var(--pm-line); border-radius: 999px; padding: 6px 14px; font-size: .82rem; font-weight: 600; cursor: pointer; user-select: none; background: #fff; transition: all .2s; text-transform: capitalize; }
    .pm-pills label:hover { border-color: var(--pm-orange); color: var(--pm-orange); }
    .pm-pills input:checked + label { background: var(--pm-navy); border-color: var(--pm-navy); color: #fff; }
    .pm-pills input:focus-visible + label { outline: 3px solid rgba(208,82,8,.35); outline-offset: 2px; }

    /* Summary strip */
    .pm-summary { display: flex; flex-wrap: wrap; gap: 10px 28px; align-items: center; }
    .pm-sum { display: flex; align-items: center; gap: 10px; }
    .pm-sum .ico { width: 36px; height: 36px; border-radius: 10px; background: #fff1e8; color: var(--pm-orange); display: grid; place-items: center; }
    .pm-sum strong { display: block; font-size: 1.1rem; font-weight: 800; color: var(--pm-navy); line-height: 1.1; letter-spacing: -.02em; }
    .pm-sum span { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #7b839c; }

    /* Results */
    #pmResults { transition: opacity .2s; min-height: 200px; }
    #pmResults.is-loading { opacity: .4; pointer-events: none; }
    .pm-progress { position: fixed; left: 0; top: 0; height: 3px; width: 0; background: var(--pm-orange); z-index: 2000; opacity: 0; transition: width .4s, opacity .3s; }
    .pm-progress.on { width: 70%; opacity: 1; }
    .pm-progress.done { width: 100%; opacity: 0; }
    .pm-chip { display: inline-flex; align-items: center; gap: 8px; background: #fff1e8; border: 1px solid #ffd9c2; color: #a8410a; border-radius: 999px; padding: 4px 10px 4px 13px; font-size: .78rem; font-weight: 600; text-decoration: none; transition: all .2s; }
    .pm-chip:hover { background: var(--pm-orange); border-color: var(--pm-orange); color: #fff; }

    /* Table */
    .pm-panel { background: #fff; border: 1px solid var(--pm-line); border-radius: 18px; overflow: hidden; box-shadow: 0 4px 16px rgba(17,27,72,.05); }
    .pm-table { margin: 0; --bs-table-bg: transparent; }
    .pm-table thead th { background: var(--pm-soft); color: #66708f; font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; padding: 14px 18px; border-bottom: 1px solid var(--pm-line); white-space: nowrap; }
    .pm-table tbody td { padding: 16px 18px; vertical-align: middle; border-bottom: 1px solid #eef0f7; }
    .pm-table tbody tr:last-child td { border-bottom: 0; }
    .pm-row { position: relative; cursor: pointer; animation: pmIn .4s both; transition: background .2s; }
    .pm-row:hover { background: #fffaf6; }
    .pm-row td:first-child { box-shadow: inset 0 0 0 0 var(--pm-orange); transition: box-shadow .25s; }
    .pm-row:hover td:first-child { box-shadow: inset 4px 0 0 0 var(--pm-orange); }
    @keyframes pmIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

    .pm-prop { display: flex; align-items: center; gap: 14px; min-width: 220px; }
    .pm-avatar { width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, var(--pm-navy), #26378a); color: #fff; font-weight: 800; font-size: .9rem; display: grid; place-items: center; flex-shrink: 0; }
    .pm-name { font-weight: 700; color: var(--pm-navy); text-decoration: none; line-height: 1.25; display: block; }
    .pm-name:hover { color: var(--pm-orange); }
    .pm-code { display: inline-block; margin-top: 3px; font-size: .72rem; font-weight: 700; letter-spacing: .03em; color: #7b839c; background: var(--pm-soft); border-radius: 6px; padding: 1px 8px; }
    .pm-loc { font-size: .88rem; color: var(--pm-navy); font-weight: 600; }
    .pm-loc small { display: block; color: #7b839c; font-weight: 500; font-size: .78rem; }
    .pm-loc i { color: var(--pm-orange); margin-right: 5px; }
    .pm-owner { font-size: .88rem; color: #4a5272; max-width: 220px; }
    .pm-count { display: inline-flex; align-items: center; gap: 6px; background: var(--pm-soft); border-radius: 8px; padding: 4px 10px; font-size: .84rem; font-weight: 700; color: var(--pm-navy); font-variant-numeric: tabular-nums; }
    .pm-count i { color: var(--pm-orange); font-size: .8rem; }
    .pm-go { display: inline-flex; align-items: center; gap: 6px; font-size: .82rem; font-weight: 700; border: 1.5px solid var(--pm-line); background: #fff; color: var(--pm-navy); border-radius: 10px; padding: 6px 12px; text-decoration: none; position: relative; z-index: 2; transition: all .2s; }
    .pm-go i { transition: transform .2s; }
    .pm-row:hover .pm-go { background: var(--pm-orange); border-color: var(--pm-orange); color: #fff; }
    .pm-row:hover .pm-go i { transform: translateX(4px); }

    /* Mobile: rows become stacked cards */
    @media (max-width: 767.98px) {
        .pm-table thead { display: none; }
        .pm-table, .pm-table tbody, .pm-table tr, .pm-table td { display: block; width: 100%; }
        .pm-table tbody tr { padding: 14px 16px; border-bottom: 1px solid #eef0f7; }
        .pm-table tbody td { border: 0; padding: 6px 0; display: flex; justify-content: space-between; gap: 12px; align-items: center; }
        .pm-table tbody td[data-label]::before { content: attr(data-label); font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #7b839c; }
        .pm-table tbody td.pm-first { display: block; padding-bottom: 10px; }
        .pm-table tbody td.pm-last { justify-content: flex-end; }
        .pm-bar { top: 60px; }
    }

    .pm-empty { padding: 56px 24px; text-align: center; }
    .pm-empty i { font-size: 2.6rem; color: var(--pm-orange); display: inline-block; animation: pmFloat 3s ease-in-out infinite; }
    @keyframes pmFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-7px); } }
    @media (prefers-reduced-motion: reduce) { .pm-row, .pm-empty i { animation: none; } }
</style>
@endpush

@section('content')
@php
    $chips = [];
    if (request()->filled('search')) { $chips['search'] = '“' . request('search') . '”'; }
    if (request()->filled('status')) { $chips['status'] = ucwords(str_replace('_', ' ', request('status'))); }
@endphp

<div class="pm-wrap">
    <div class="pm-progress" id="pmProgress"></div>

    {{-- Summary + action --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="pm-summary" id="pmStats">
            <div class="pm-sum">
                <div class="ico"><i class="bi bi-buildings"></i></div>
                <div><strong>{{ number_format($properties->total()) }}</strong><span>Properties</span></div>
            </div>
            <div class="pm-sum">
                <div class="ico"><i class="bi bi-building"></i></div>
                <div><strong>{{ number_format($properties->sum('buildings_count')) }}</strong><span>Buildings on page</span></div>
            </div>
            <div class="pm-sum">
                <div class="ico"><i class="bi bi-door-open"></i></div>
                <div><strong>{{ number_format($properties->sum('units_count')) }}</strong><span>Units on page</span></div>
            </div>
        </div>

        @if(auth()->user()->propertyOwner)
            <a href="{{ route('property-manager.properties.create') }}" class="btn terra-btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Add Property
            </a>
        @endif
    </div>

    {{-- Search + status in one bar --}}
    <form action="{{ route('property-manager.properties.index') }}" method="GET" id="pmForm" class="pm-bar mb-4" role="search">
        <div class="pm-bar-row">
            <div class="pm-search {{ request()->filled('search') ? 'has-value' : '' }}" id="pmSearchBox">
                <i class="bi bi-search"></i>
                <input type="search" id="pmSearch" name="search" value="{{ request('search') }}"
                       placeholder="Search name, code, district or sector" aria-label="Search properties" autocomplete="off">
                <button type="button" class="pm-x" id="pmClear" aria-label="Clear search"><i class="bi bi-x-lg"></i></button>
            </div>

            <div class="pm-pills" role="radiogroup" aria-label="Property status">
                <input type="radio" name="status" id="st-all" value="" @checked(!request()->filled('status'))>
                <label for="st-all">All</label>
                @foreach ($statuses as $status)
                    <input type="radio" name="status" id="st-{{ $status }}" value="{{ $status }}" @checked(request('status') === $status)>
                    <label for="st-{{ $status }}">{{ str_replace('_', ' ', $status) }}</label>
                @endforeach
            </div>
        </div>
    </form>

    {{-- Results (swapped live) --}}
    <section id="pmResults" aria-live="polite">

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <div class="text-muted small me-2">
                Showing <strong>{{ $properties->firstItem() ?? 0 }}–{{ $properties->lastItem() ?? 0 }}</strong>
                of <strong>{{ number_format($properties->total()) }}</strong> properties
            </div>
            @foreach($chips as $key => $label)
                <a class="pm-chip" data-chip="{{ $key }}" href="{{ route('property-manager.properties.index', request()->except($key, 'page')) }}">
                    {{ $label }} <i class="bi bi-x-lg" style="font-size:.62rem"></i>
                </a>
            @endforeach
            @if(count($chips) > 1)
                <a href="{{ route('property-manager.properties.index') }}" class="small fw-bold" data-chip="all">Clear all</a>
            @endif
        </div>

        <div class="pm-panel">
            @if ($properties->isEmpty())
                <div class="pm-empty">
                    <i class="bi bi-search"></i>
                    <h3 class="h6 fw-bold mt-3" style="color:var(--terra-navy)">No properties found</h3>
                    <p class="terra-muted mb-3">Try a different search term or status.</p>
                    <a href="{{ route('property-manager.properties.index') }}" class="btn terra-btn-primary" data-chip="all">Clear filters</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table pm-table align-middle">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Owner</th>
                                <th>Location</th>
                                <th>Buildings</th>
                                <th>Units</th>
                                <th>Status</th>
                                <th class="text-end">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($properties as $property)
                                @php
                                    $url = route('property-manager.properties.show', $property);
                                    $initials = \Illuminate\Support\Str::of($property->name)->explode(' ')->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                                    $ownerName = $property->owner?->company_name ?: $property->owner?->user?->name ?: 'Not specified';
                                @endphp
                                <tr class="pm-row" data-href="{{ $url }}" style="animation-delay: {{ min($loop->index, 10) * 40 }}ms">
                                    <td class="pm-first">
                                        <div class="pm-prop">
                                            <div class="pm-avatar">{{ $initials ?: 'P' }}</div>
                                            <div>
                                                <a href="{{ $url }}" class="pm-name">{{ $property->name }}</a>
                                                <span class="pm-code">{{ $property->property_code ?: 'No code' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Owner"><div class="pm-owner">{{ $ownerName }}</div></td>
                                    <td data-label="Location">
                                        <div class="pm-loc">
                                            <i class="bi bi-geo-alt-fill"></i>{{ $property->district ?: '—' }}
                                            @if ($property->sector)<small>{{ $property->sector }}</small>@endif
                                        </div>
                                    </td>
                                    <td data-label="Buildings"><span class="pm-count"><i class="bi bi-building"></i>{{ number_format($property->buildings_count) }}</span></td>
                                    <td data-label="Units"><span class="pm-count"><i class="bi bi-door-open"></i>{{ number_format($property->units_count) }}</span></td>
                                    <td data-label="Status">
                                        <span class="terra-badge {{ strtolower($property->status ?? '') }}">
                                            {{ str_replace('_', ' ', $property->status ?: 'unspecified') }}
                                        </span>
                                    </td>
                                    <td class="text-end pm-last">
                                        <a href="{{ $url }}" class="pm-go">View <i class="bi bi-arrow-right"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($properties->hasPages())
                    <div class="p-3 border-top d-flex justify-content-center">
                        {{ $properties->withQueryString()->links() }}
                    </div>
                @endif
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('pmForm');
    const results = document.getElementById('pmResults');
    const stats = document.getElementById('pmStats');
    const progress = document.getElementById('pmProgress');
    const search = document.getElementById('pmSearch');
    const box = document.getElementById('pmSearchBox');
    let timer = null, controller = null;

    function buildUrl() {
        const p = new URLSearchParams();
        new FormData(form).forEach(function (v, k) { if (v !== '') p.append(k, v); });
        const qs = p.toString();
        return form.action + (qs ? '?' + qs : '');
    }
    function load(url, scroll, noPush) {
        if (controller) controller.abort();
        controller = new AbortController();
        results.classList.add('is-loading');
        progress.className = 'pm-progress on';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
            .then(function (r) { if (!r.ok) throw new Error(); return r.text(); })
            .then(function (html) {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const fresh = doc.getElementById('pmResults');
                if (!fresh) { window.location.href = url; return; }
                results.innerHTML = fresh.innerHTML;
                const freshStats = doc.getElementById('pmStats');
                if (freshStats) stats.innerHTML = freshStats.innerHTML;
                if (!noPush) history.pushState({}, '', url);
                if (scroll) window.scrollTo({ top: results.getBoundingClientRect().top + window.scrollY - 140, behavior: 'smooth' });
            })
            .catch(function (e) { if (e.name !== 'AbortError') window.location.href = url; })
            .finally(function () {
                results.classList.remove('is-loading');
                progress.className = 'pm-progress done';
                setTimeout(function () { progress.className = 'pm-progress'; }, 350);
            });
    }
    function live(delay) { clearTimeout(timer); timer = setTimeout(function () { load(buildUrl()); }, delay); }

    search.addEventListener('input', function () { box.classList.toggle('has-value', !!search.value); live(400); });
    document.getElementById('pmClear').addEventListener('click', function () {
        search.value = ''; box.classList.remove('has-value'); search.focus(); live(0);
    });
    form.addEventListener('submit', function (e) { e.preventDefault(); live(0); });
    form.querySelectorAll('input[name="status"]').forEach(function (r) { r.addEventListener('change', function () { live(0); }); });

    results.addEventListener('click', function (e) {
        const chip = e.target.closest('[data-chip]');
        if (chip) {
            e.preventDefault();
            const k = chip.dataset.chip;
            if (k === 'all' || k === 'search') { search.value = ''; box.classList.remove('has-value'); }
            if (k === 'all' || k === 'status') document.getElementById('st-all').checked = true;
            live(0);
            return;
        }
        const pg = e.target.closest('.pagination a.page-link');
        if (pg && pg.href) { e.preventDefault(); load(pg.href, true); return; }

        /* Whole row is clickable (links keep native behaviour) */
        const row = e.target.closest('.pm-row');
        if (row && !e.target.closest('a, button') && row.dataset.href) {
            if (e.metaKey || e.ctrlKey) window.open(row.dataset.href, '_blank');
            else window.location.href = row.dataset.href;
        }
    });

    window.addEventListener('popstate', function () { load(window.location.href, false, true); });
    document.addEventListener('keydown', function (e) {
        const t = (document.activeElement || {}).tagName || '';
        if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(t)) { e.preventDefault(); search.focus(); }
    });
});
</script>
@endpush
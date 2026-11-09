@extends('layouts.app')

@section('title', 'Maintenance Requests')

@section('content')
@php
    $chips = [];
    if (request()->filled('search'))    { $chips['search']    = '“' . request('search') . '”'; }
    if (request()->filled('category'))  { $chips['category']  = $categories[request('category')] ?? ucfirst(request('category')); }
    if (request()->filled('priority'))  { $chips['priority']  = ($priorities[request('priority')] ?? ucfirst(request('priority'))) . ' priority'; }
    if (request()->filled('status'))    { $chips['status']    = $statuses[request('status')] ?? ucfirst(str_replace('_', ' ', request('status'))); }
    if (request()->filled('date_from')) { $chips['date_from'] = 'From ' . \Carbon\Carbon::parse(request('date_from'))->format('d M Y'); }
    if (request()->filled('date_to'))   { $chips['date_to']   = 'To ' . \Carbon\Carbon::parse(request('date_to'))->format('d M Y'); }

    $currentStatus = (string) request('status', '');
    $panelCount = collect(['category', 'priority', 'date_from', 'date_to'])->filter(fn ($k) => request()->filled($k))->count();
@endphp

<div class="container-fluid py-4 mt-wrap">
    <div class="mt-progress" id="mtProgress"></div>

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color:var(--mt-navy);letter-spacing:-.03em">Maintenance Management</h3>
            <p class="text-muted mb-0">Monitor, schedule and manage property maintenance requests.</p>
        </div>
        <a href="{{ route('property-manager.maintenance-requests.create') }}" class="btn mt-btn">
            <i class="bi bi-plus-lg me-2"></i>New Request
        </a>
    </div>

    @if(session('success'))
        <div class="mt-alert alert alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Summary (click to filter) --}}
    <div class="mt-stats mb-4" id="mtStats">
        <button type="button" class="mt-stat" data-set="all">
            <div class="ico blue"><i class="bi bi-tools"></i></div>
            <div><strong>{{ number_format($summary['all']) }}</strong><span>All requests</span></div>
        </button>
        <button type="button" class="mt-stat" data-set="open">
            <div class="ico amber"><i class="bi bi-clock-history"></i></div>
            <div><strong>{{ number_format($summary['open']) }}</strong><span>Open</span></div>
        </button>
        <button type="button" class="mt-stat" data-set="urgent">
            <div class="ico red"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div><strong>{{ number_format($summary['urgent']) }}</strong><span>Urgent</span></div>
        </button>
        <button type="button" class="mt-stat" data-set="resolved">
            <div class="ico green"><i class="bi bi-check-circle-fill"></i></div>
            <div><strong>{{ number_format($summary['resolved']) }}</strong><span>Resolved</span></div>
        </button>
    </div>

    {{-- Search + filters in one bar --}}
    <form method="GET" action="{{ route('property-manager.maintenance-requests.index') }}" id="mtForm" class="mt-bar mb-4" role="search">
        <div class="mt-bar-row">
            <div class="mt-search {{ request()->filled('search') ? 'has-value' : '' }}" id="mtSearchBox">
                <i class="bi bi-search"></i>
                <input type="search" name="search" id="mtSearch" value="{{ request('search') }}"
                       placeholder="Request number, tenant, unit…" aria-label="Search requests" autocomplete="off">
                <button type="button" class="mt-x" id="mtClear" aria-label="Clear search"><i class="bi bi-x-lg"></i></button>
            </div>

            <button type="button" class="mt-ctl" data-bs-toggle="collapse" data-bs-target="#mtPanel"
                    aria-expanded="{{ $panelCount ? 'true' : 'false' }}" aria-controls="mtPanel">
                <i class="bi bi-sliders"></i><span class="d-none d-sm-inline">Filters</span>
                <span class="mt-badge {{ $panelCount ? 'show' : '' }}" id="mtBadge">{{ $panelCount }}</span>
            </button>
        </div>

        {{-- Status tabs --}}
        <div class="mt-tabs" role="radiogroup" aria-label="Request status">
            <input type="radio" name="status" id="mt-st-all" value="" @checked($currentStatus === '')>
            <label for="mt-st-all">All</label>
            @foreach($statuses as $key => $label)
                <input type="radio" name="status" id="mt-st-{{ $key }}" value="{{ $key }}" @checked($currentStatus === (string) $key)>
                <label for="mt-st-{{ $key }}"><span class="sd st-{{ $key }}"></span>{{ $label }}</label>
            @endforeach
        </div>

        {{-- Collapsible extra filters --}}
        <div id="mtPanel" class="collapse {{ $panelCount ? 'show' : '' }}">
            <div class="mt-panel">
                <div class="row g-3">
                    <div class="col-6 col-lg-3">
                        <label class="mt-label" for="mtCat">Category</label>
                        <select name="category" id="mtCat" class="form-select">
                            <option value="">All categories</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" @selected(request('category') === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="mt-label" for="mtPri">Priority</label>
                        <select name="priority" id="mtPri" class="form-select">
                            <option value="">All priorities</option>
                            @foreach($priorities as $key => $label)
                                <option value="{{ $key }}" @selected(request('priority') === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="mt-label" for="mtFrom">Date from</label>
                        <input type="date" name="date_from" id="mtFrom" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-6 col-lg-3">
                        <label class="mt-label" for="mtTo">Date to</label>
                        <input type="date" name="date_to" id="mtTo" class="form-control" value="{{ request('date_to') }}">
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top small text-muted">
                    <span><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Results update as you choose</span>
                    <a href="{{ route('property-manager.maintenance-requests.index') }}" id="mtReset" class="fw-bold">Reset all</a>
                </div>
            </div>
        </div>
    </form>

    {{-- Results (swapped live) --}}
    <section id="mtResults" aria-live="polite">

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <div class="text-muted small me-2"><strong>{{ number_format($requests->total()) }}</strong> {{ \Illuminate\Support\Str::plural('record', $requests->total()) }}</div>
            @foreach($chips as $key => $label)
                <a class="mt-chip" data-chip="{{ $key }}" href="{{ route('property-manager.maintenance-requests.index', request()->except($key, 'page')) }}">
                    {{ $label }} <i class="bi bi-x-lg" style="font-size:.62rem"></i>
                </a>
            @endforeach
            @if(count($chips) > 1)
                <a href="{{ route('property-manager.maintenance-requests.index') }}" class="small fw-bold" data-chip="all">Clear all</a>
            @endif
        </div>

        <div class="mt-panelbox">
            <div class="table-responsive">
                <table class="table mt-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Request</th>
                            <th>Tenant</th>
                            <th>Property / Unit</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $maintenance)
                            @php
                                $showUrl = route('property-manager.maintenance-requests.show', $maintenance->id);
                                $priority = $maintenance->priority ?? 'low';
                                $status = $maintenance->status ?? 'open';
                                $priIcon = ['low' => 'bi-arrow-down', 'medium' => 'bi-dash-lg', 'high' => 'bi-arrow-up', 'urgent' => 'bi-exclamation-lg'][$priority] ?? 'bi-dash-lg';
                            @endphp
                            <tr class="mt-row pri-{{ $priority }}" data-href="{{ $showUrl }}" style="animation-delay: {{ min($loop->index, 10) * 40 }}ms">
                                <td class="mt-first">
                                    <a href="{{ $showUrl }}" class="mt-num">{{ $maintenance->request_number }}</a>
                                    <div class="mt-title">{{ $maintenance->title }}</div>
                                </td>
                                <td data-label="Tenant">
                                    <div class="mt-strong">{{ $maintenance->tenant?->full_name ?? '—' }}</div>
                                    @if($maintenance->tenant?->phone)<div class="mt-sub"><i class="bi bi-telephone-fill"></i>{{ $maintenance->tenant->phone }}</div>@endif
                                </td>
                                <td data-label="Property / Unit">
                                    <div class="mt-strong">{{ $maintenance->unit?->building?->property?->name ?? '—' }}</div>
                                    <div class="mt-sub"><i class="bi bi-door-open"></i>Unit {{ $maintenance->unit?->unit_number ?? '—' }}</div>
                                </td>
                                <td data-label="Category"><span class="mt-cat">{{ $categories[$maintenance->category] ?? ucfirst($maintenance->category) }}</span></td>
                                <td data-label="Priority">
                                    <span class="mt-pill pr-{{ $priority }}"><i class="bi {{ $priIcon }}"></i>{{ $priorities[$priority] ?? ucfirst($priority) }}</span>
                                </td>
                                <td data-label="Submitted" class="text-nowrap">
                                    <div class="mt-strong">{{ $maintenance->created_at?->format('d M Y') }}</div>
                                    <div class="mt-sub">{{ $maintenance->created_at?->diffForHumans() }}</div>
                                </td>
                                <td data-label="Status">
                                    <span class="mt-pill st-pill st-{{ $status }}"><span class="sd"></span>{{ $statuses[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</span>
                                </td>
                                <td class="text-end mt-last">
                                    <div class="mt-actions">
                                        <a href="{{ $showUrl }}" class="mt-act" title="View" aria-label="View {{ $maintenance->request_number }}"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('property-manager.maintenance-requests.edit', $maintenance->id) }}" class="mt-act" title="Edit" aria-label="Edit {{ $maintenance->request_number }}"><i class="bi bi-pencil"></i></a>
                                        <form method="POST"
                                              action="{{ route('property-manager.maintenance-requests.destroy', $maintenance->id) }}"
                                              onsubmit="return confirm('Delete this maintenance request?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="mt-act danger" title="Delete" aria-label="Delete {{ $maintenance->request_number }}"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="mt-empty-row">
                                <td colspan="8">
                                    <div class="mt-empty">
                                        <i class="bi bi-tools"></i>
                                        <h6 class="fw-bold mt-3 mb-1" style="color:var(--mt-navy)">No maintenance requests found</h6>
                                        <p class="text-muted small mb-3">Requests will appear here when recorded, or try changing your filters.</p>
                                        <a href="{{ route('property-manager.maintenance-requests.create') }}" class="btn mt-btn btn-sm">Create request</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $requests->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </section>
</div>

<style>
    .mt-wrap { --mt-navy: #19265d; --mt-orange: #D05208; --mt-line: #e4e8f2; --mt-soft: #f4f6fb; }
    .mt-btn { background: var(--mt-orange); border: 1px solid var(--mt-orange); color: #fff; font-weight: 600; padding: 10px 18px; border-radius: 12px; transition: all .2s; }
    .mt-btn:hover { background: #b94707; border-color: #b94707; color: #fff; transform: translateY(-1px); box-shadow: 0 8px 18px rgba(208,82,8,.28); }

    .mt-progress { position: fixed; left: 0; top: 0; height: 3px; width: 0; background: var(--mt-orange); z-index: 2000; opacity: 0; transition: width .4s, opacity .3s; }
    .mt-progress.on { width: 70%; opacity: 1; }
    .mt-progress.done { width: 100%; opacity: 0; }

    .mt-alert { display: flex; align-items: center; gap: 12px; border: 0; border-radius: 14px; padding: 14px 18px; background: #e8f7ee; color: #146c3a; }
    .mt-alert > i { font-size: 1.2rem; }
    .mt-alert .btn-close { margin-left: auto; position: static; }

    /* Stats */
    .mt-stats { display: grid; gap: 12px; grid-template-columns: repeat(2, 1fr); }
    @media (min-width: 992px) { .mt-stats { grid-template-columns: repeat(4, 1fr); } }
    .mt-stat { display: flex; align-items: center; gap: 14px; text-align: left; background: #fff; border: 1px solid var(--mt-line); border-radius: 16px; padding: 16px 18px; cursor: pointer; transition: transform .3s, box-shadow .3s, border-color .3s; }
    .mt-stat:hover { transform: translateY(-3px); box-shadow: 0 12px 26px rgba(17,27,72,.1); border-color: rgba(208,82,8,.35); }
    .mt-stat .ico { width: 46px; height: 46px; border-radius: 13px; display: grid; place-items: center; font-size: 1.2rem; flex-shrink: 0; }
    .ico.blue { background: #e9edfb; color: #3a4fb5; } .ico.amber { background: #fff3da; color: #c98500; }
    .ico.red { background: #fdecec; color: #c0392b; } .ico.green { background: #e8f7ee; color: #1a8a4a; }
    .mt-stat strong { display: block; font-size: 1.5rem; font-weight: 800; letter-spacing: -.03em; color: var(--mt-navy); line-height: 1.1; }
    .mt-stat span { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #7b839c; }

    /* Bar */
    .mt-bar { position: sticky; top: 72px; z-index: 20; background: #fff; border: 1px solid var(--mt-line); border-radius: 16px; padding: 10px; box-shadow: 0 10px 28px rgba(17,27,72,.08); }
    .mt-bar-row { display: flex; gap: 10px; align-items: center; }
    .mt-search { position: relative; flex: 1; min-width: 0; }
    .mt-search > i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--mt-orange); pointer-events: none; }
    .mt-search input { width: 100%; border: 0; background: var(--mt-soft); border-radius: 12px; padding: 12px 40px 12px 42px; font-size: .93rem; font-weight: 500; transition: box-shadow .2s, background .2s; }
    .mt-search input:focus { outline: 0; background: #fff; box-shadow: 0 0 0 3px rgba(208,82,8,.18); }
    .mt-x { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 28px; height: 28px; border: 0; border-radius: 50%; background: transparent; color: #8b93ac; display: none; }
    .mt-search.has-value .mt-x { display: block; }
    .mt-x:hover { background: #ffeadc; color: var(--mt-orange); }
    .mt-ctl { display: inline-flex; align-items: center; gap: 8px; border: 1.5px solid var(--mt-line); background: #fff; color: var(--mt-navy); font-weight: 700; font-size: .88rem; border-radius: 12px; padding: 0 15px; min-height: 46px; transition: all .2s; }
    .mt-ctl:hover { border-color: var(--mt-orange); color: var(--mt-orange); }
    .mt-ctl[aria-expanded="true"] { background: #fff1e8; border-color: var(--mt-orange); color: #a8410a; }
    .mt-badge { background: var(--mt-orange); color: #fff; border-radius: 999px; min-width: 20px; height: 20px; font-size: .7rem; display: none; align-items: center; justify-content: center; padding: 0 6px; }
    .mt-badge.show { display: inline-flex; }

    .mt-tabs { display: flex; gap: 6px; overflow-x: auto; padding: 12px 2px 4px; scrollbar-width: none; }
    .mt-tabs::-webkit-scrollbar { display: none; }
    .mt-tabs input { position: absolute; opacity: 0; pointer-events: none; }
    .mt-tabs label { display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; border: 1.5px solid var(--mt-line); border-radius: 999px; padding: 6px 14px; font-size: .82rem; font-weight: 600; cursor: pointer; user-select: none; background: #fff; transition: all .2s; }
    .mt-tabs label:hover { border-color: var(--mt-orange); color: var(--mt-orange); }
    .mt-tabs input:checked + label { background: var(--mt-navy); border-color: var(--mt-navy); color: #fff; }
    .mt-tabs input:focus-visible + label { outline: 3px solid rgba(208,82,8,.35); outline-offset: 2px; }

    .mt-panel { padding: 14px 4px 4px; }
    .mt-label { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--mt-navy); margin-bottom: 6px; }
    .mt-panel .form-select, .mt-panel .form-control { border-color: var(--mt-line); min-height: 44px; border-radius: 10px; font-size: .9rem; }

    /* Status dots / pills */
    .sd { width: 8px; height: 8px; border-radius: 50%; background: #9aa1b8; display: inline-block; flex-shrink: 0; }
    .st-open { --c: #3a4fb5; --bg: #e9edfb; } .st-in_progress { --c: #0a7ea4; --bg: #e0f4fa; }
    .st-scheduled { --c: #c98500; --bg: #fff3da; } .st-on_hold { --c: #5a6380; --bg: #eef0f6; }
    .st-resolved { --c: #1a8a4a; --bg: #e8f7ee; } .st-closed { --c: #19265d; --bg: #dfe3f2; }
    .st-rejected { --c: #c0392b; --bg: #fdecec; }
    .sd[class*="st-"] { background: var(--c, #9aa1b8); }
    .mt-pill { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 4px 12px; font-size: .78rem; font-weight: 700; white-space: nowrap; }
    .mt-pill.st-pill { background: var(--bg, #eef0f6); color: var(--c, #5a6380); }
    .mt-pill.st-pill .sd { background: currentColor; }
    .mt-pill.pr-low { background: #eef0f6; color: #5a6380; } .mt-pill.pr-medium { background: #e0f4fa; color: #0a7ea4; }
    .mt-pill.pr-high { background: #fff3da; color: #b97500; } .mt-pill.pr-urgent { background: #fdecec; color: #c0392b; }
    .mt-pill.pr-urgent i { animation: mtBlink 1.4s infinite; }
    @keyframes mtBlink { 50% { opacity: .35; } }

    /* Results */
    #mtResults { transition: opacity .2s; min-height: 200px; }
    #mtResults.is-loading { opacity: .4; pointer-events: none; }
    .mt-chip { display: inline-flex; align-items: center; gap: 8px; background: #fff1e8; border: 1px solid #ffd9c2; color: #a8410a; border-radius: 999px; padding: 4px 10px 4px 13px; font-size: .78rem; font-weight: 600; text-decoration: none; transition: all .2s; }
    .mt-chip:hover { background: var(--mt-orange); border-color: var(--mt-orange); color: #fff; }

    .mt-panelbox { background: #fff; border: 1px solid var(--mt-line); border-radius: 18px; overflow: hidden; box-shadow: 0 4px 16px rgba(17,27,72,.05); }
    .mt-table { --bs-table-bg: transparent; }
    .mt-table thead th { background: var(--mt-soft); color: #66708f; font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; padding: 14px 18px; border-bottom: 1px solid var(--mt-line); white-space: nowrap; }
    .mt-table tbody td { padding: 15px 18px; vertical-align: middle; border-bottom: 1px solid #eef0f7; }
    .mt-table tbody tr:last-child td { border-bottom: 0; }
    .mt-row { cursor: pointer; animation: mtIn .4s both; transition: background .2s; }
    .mt-row:hover { background: #fffaf6; }
    .mt-row td:first-child { box-shadow: inset 4px 0 0 0 transparent; transition: box-shadow .25s; }
    .mt-row.pri-urgent td:first-child { box-shadow: inset 4px 0 0 0 #c0392b; }
    .mt-row.pri-high td:first-child { box-shadow: inset 4px 0 0 0 #e0a100; }
    .mt-row:hover td:first-child { box-shadow: inset 4px 0 0 0 var(--mt-orange); }
    @keyframes mtIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

    .mt-num { font-weight: 800; color: var(--mt-navy); text-decoration: none; letter-spacing: .01em; }
    .mt-num:hover { color: var(--mt-orange); }
    .mt-title { font-size: .82rem; color: #7b839c; margin-top: 2px; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mt-strong { font-weight: 600; font-size: .9rem; color: var(--mt-navy); }
    .mt-sub { font-size: .78rem; color: #7b839c; margin-top: 2px; }
    .mt-sub i { margin-right: 5px; color: #a3abc2; font-size: .72rem; }
    .mt-cat { display: inline-block; background: var(--mt-soft); color: var(--mt-navy); font-weight: 600; font-size: .8rem; border-radius: 8px; padding: 4px 10px; }

    .mt-actions { display: inline-flex; gap: 6px; align-items: center; }
    .mt-actions form { margin: 0; }
    .mt-act { width: 36px; height: 36px; display: inline-grid; place-items: center; border: 1.5px solid var(--mt-line); background: #fff; color: var(--mt-navy); border-radius: 10px; text-decoration: none; transition: all .2s; position: relative; z-index: 2; }
    .mt-act:hover { background: var(--mt-navy); border-color: var(--mt-navy); color: #fff; transform: translateY(-2px); }
    .mt-act.danger { color: #c0392b; }
    .mt-act.danger:hover { background: #c0392b; border-color: #c0392b; color: #fff; }

    .mt-empty { padding: 48px 16px; text-align: center; }
    .mt-empty i { font-size: 2.6rem; color: var(--mt-orange); display: inline-block; animation: mtFloat 3s ease-in-out infinite; }
    @keyframes mtFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-7px); } }
    .mt-empty-row td { border: 0 !important; }

    @media (max-width: 991.98px) { .mt-bar { top: 60px; } }
    @media (max-width: 767.98px) {
        .mt-table thead { display: none; }
        .mt-table, .mt-table tbody, .mt-table tr, .mt-table td { display: block; width: 100%; }
        .mt-table tbody tr { padding: 14px 16px; border-bottom: 1px solid #eef0f7; }
        .mt-table tbody td { border: 0; padding: 6px 0; display: flex; justify-content: space-between; align-items: center; gap: 12px; text-align: right; }
        .mt-table tbody td[data-label]::before { content: attr(data-label); font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #7b839c; text-align: left; }
        .mt-table tbody td.mt-first { display: block; text-align: left; padding-bottom: 10px; }
        .mt-table tbody td.mt-last { justify-content: flex-end; padding-top: 10px; }
        .mt-title { max-width: none; white-space: normal; }
        .mt-empty-row td { display: block; }
    }
    @media (prefers-reduced-motion: reduce) { .mt-row, .mt-empty i, .mt-pill.pr-urgent i { animation: none; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('mtForm');
    const results = document.getElementById('mtResults');
    const stats = document.getElementById('mtStats');
    const progress = document.getElementById('mtProgress');
    const search = document.getElementById('mtSearch');
    const box = document.getElementById('mtSearchBox');
    const badge = document.getElementById('mtBadge');
    let timer = null, controller = null;

    function field(name) { return form.elements[name]; }
    function setStatus(v) {
        const r = form.querySelector('input[name="status"][value="' + v + '"]');
        (r || document.getElementById('mt-st-all')).checked = true;
    }
    function updateBadge() {
        let n = 0;
        ['category', 'priority', 'date_from', 'date_to'].forEach(function (k) { if (field(k).value) n++; });
        badge.textContent = n;
        badge.classList.toggle('show', n > 0);
    }
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
        progress.className = 'mt-progress on';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
            .then(function (r) { if (!r.ok) throw new Error(); return r.text(); })
            .then(function (html) {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const fresh = doc.getElementById('mtResults');
                if (!fresh) { window.location.href = url; return; }
                results.innerHTML = fresh.innerHTML;
                const freshStats = doc.getElementById('mtStats');
                if (freshStats) stats.innerHTML = freshStats.innerHTML;
                if (!noPush) history.pushState({}, '', url);
                if (scroll) window.scrollTo({ top: results.getBoundingClientRect().top + window.scrollY - 150, behavior: 'smooth' });
            })
            .catch(function (e) { if (e.name !== 'AbortError') window.location.href = url; })
            .finally(function () {
                results.classList.remove('is-loading');
                progress.className = 'mt-progress done';
                setTimeout(function () { progress.className = 'mt-progress'; }, 350);
            });
    }
    function live(delay) { clearTimeout(timer); timer = setTimeout(function () { load(buildUrl()); }, delay); }
    function resetAll() {
        search.value = ''; box.classList.remove('has-value');
        ['category', 'priority', 'date_from', 'date_to'].forEach(function (k) { field(k).value = ''; });
        setStatus(''); updateBadge(); live(0);
    }

    /* Search + filters */
    search.addEventListener('input', function () { box.classList.toggle('has-value', !!search.value); live(400); });
    document.getElementById('mtClear').addEventListener('click', function () {
        search.value = ''; box.classList.remove('has-value'); search.focus(); live(0);
    });
    form.addEventListener('submit', function (e) { e.preventDefault(); live(0); });
    form.querySelectorAll('input[name="status"]').forEach(function (r) { r.addEventListener('change', function () { live(0); }); });
    ['category', 'priority', 'date_from', 'date_to'].forEach(function (k) {
        field(k).addEventListener('change', function () { updateBadge(); live(0); });
    });
    document.getElementById('mtReset').addEventListener('click', function (e) { e.preventDefault(); resetAll(); });

    /* Summary cards act as quick filters */
    stats.addEventListener('click', function (e) {
        const card = e.target.closest('[data-set]');
        if (!card) return;
        const what = card.dataset.set;
        field('priority').value = '';
        setStatus('');
        if (what === 'urgent') field('priority').value = 'urgent';
        else if (what !== 'all') setStatus(what);
        updateBadge();
        live(0);
    });

    /* Chips, pagination, row click */
    results.addEventListener('click', function (e) {
        const chip = e.target.closest('[data-chip]');
        if (chip) {
            e.preventDefault();
            const k = chip.dataset.chip;
            if (k === 'all') { resetAll(); return; }
            if (k === 'search') { search.value = ''; box.classList.remove('has-value'); }
            else if (k === 'status') setStatus('');
            else field(k).value = '';
            updateBadge(); live(0);
            return;
        }
        const pg = e.target.closest('.pagination a.page-link');
        if (pg && pg.href) { e.preventDefault(); load(pg.href, true); return; }

        const row = e.target.closest('.mt-row');
        if (row && !e.target.closest('a, button, form') && row.dataset.href) {
            if (e.metaKey || e.ctrlKey) window.open(row.dataset.href, '_blank');
            else window.location.href = row.dataset.href;
        }
    });

    window.addEventListener('popstate', function () { load(window.location.href, false, true); });
    document.addEventListener('keydown', function (e) {
        const t = (document.activeElement || {}).tagName || '';
        if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(t)) { e.preventDefault(); search.focus(); }
    });

    updateBadge();
});
</script>
@endsection
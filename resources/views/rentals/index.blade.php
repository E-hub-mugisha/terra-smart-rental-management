@extends('layouts.base')

@section('title', 'Find a Rental | Terra Smart Rental Management')

@push('styles')
<style>
    /* ---------- Page header (slim, replaces hero) ---------- */
    .tr-head { padding: 28px 0 8px; }
    .tr-head h1 {
        font-size: clamp(1.5rem, 3vw, 2.1rem);
        font-weight: 800;
        letter-spacing: -.04em;
        color: var(--tr-navy);
        margin: 0;
    }
    .tr-head h1 span { color: var(--tr-orange); }
    .tr-head p { margin: 4px 0 0; color: var(--tr-muted); font-size: .95rem; }

    /* ---------- Combined search + filter bar ---------- */
    .tr-bar-wrap { position: sticky; top: 76px; z-index: 30; padding: 10px 0 4px; }
    .tr-bar {
        background: var(--tr-surface);
        border: 1px solid var(--tr-line);
        border-radius: 18px;
        box-shadow: var(--tr-shadow-lg);
        padding: 10px;
        transition: box-shadow .3s var(--tr-ease), border-color .3s ease;
    }
    .tr-bar:focus-within { border-color: rgba(208,82,8,.45); }
    .tr-bar-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }

    .tr-search { position: relative; flex: 1 1 260px; min-width: 0; }
    .tr-search > i.bi-search {
        position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
        color: var(--tr-orange); font-size: 1.05rem; pointer-events: none;
    }
    .tr-search input {
        border: 0; background: var(--tr-ground);
        padding: 13px 76px 13px 46px;
        font-size: .95rem; font-weight: 500; width: 100%; border-radius: 12px;
        transition: background .2s ease, box-shadow .2s ease;
    }
    .tr-search input:focus { outline: none; box-shadow: 0 0 0 3px rgba(208,82,8,.18); background: #fff; }
    .tr-search .tr-kbd {
        position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
        font-size: .7rem; font-weight: 700; color: var(--tr-muted);
        border: 1px solid var(--tr-line); border-radius: 6px; padding: 1px 7px;
        background: #fff; pointer-events: none;
    }
    .tr-search input:focus ~ .tr-kbd, .tr-search.has-value .tr-kbd { display: none; }
    .tr-clear {
        position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
        width: 30px; height: 30px; border: 0; border-radius: 50%;
        background: transparent; color: var(--tr-muted); display: none;
        align-items: center; justify-content: center;
    }
    .tr-search.has-value .tr-clear { display: flex; }
    .tr-clear:hover { background: var(--tr-orange-soft); color: var(--tr-orange); }

    .tr-ctl {
        display: inline-flex; align-items: center; gap: 8px;
        border: 1.5px solid var(--tr-line); background: var(--tr-surface);
        color: var(--tr-navy); font-weight: 700; font-size: .88rem;
        border-radius: 12px; padding: 11px 15px; min-height: 46px;
        transition: all .2s var(--tr-ease); cursor: pointer;
    }
    .tr-ctl:hover { border-color: var(--tr-orange); color: var(--tr-orange); }
    .tr-ctl[aria-expanded="true"] { background: var(--tr-orange-soft); border-color: var(--tr-orange); color: var(--tr-orange-dark); }
    .tr-ctl .tr-badge {
        background: var(--tr-orange); color: #fff; border-radius: 999px;
        min-width: 20px; height: 20px; font-size: .7rem; display: none;
        align-items: center; justify-content: center; padding: 0 6px;
    }
    .tr-ctl .tr-badge.show { display: inline-flex; }
    select.tr-ctl { padding-right: 34px; appearance: auto; }

    .tr-seg { display: inline-flex; border: 1.5px solid var(--tr-line); border-radius: 12px; overflow: hidden; }
    .tr-seg button {
        border: 0; background: var(--tr-surface); color: var(--tr-muted);
        width: 44px; min-height: 43px; font-size: 1.05rem; transition: all .2s ease;
    }
    .tr-seg button.active { background: var(--tr-navy); color: #fff; }
    .tr-seg button:not(.active):hover { color: var(--tr-orange); }

    .tr-saved-toggle[aria-pressed="true"] { background: #fde8ef; border-color: #e0245e; color: #e0245e; }

    /* Collapsible filter panel */
    .tr-panel { padding: 16px 6px 6px; }
    .tr-panel-grid { display: grid; gap: 22px 32px; grid-template-columns: 1fr; }
    @media (min-width: 768px) { .tr-panel-grid { grid-template-columns: 1.1fr 1fr; } }
    .tr-label {
        display: block; font-size: .75rem; font-weight: 700; letter-spacing: .05em;
        text-transform: uppercase; color: var(--tr-navy); margin-bottom: 8px;
    }
    .tr-range { display: flex; align-items: center; gap: 8px; }
    .tr-range .input-group-text {
        background: var(--tr-ground); border-color: var(--tr-line);
        color: var(--tr-muted); font-size: .72rem; font-weight: 700;
    }
    .tr-range .form-control { border-color: var(--tr-line); min-height: 44px; font-size: .92rem; }
    .tr-range .sep { color: var(--tr-muted); font-weight: 700; }
    .tr-presets { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
    .tr-preset {
        border: 1px dashed var(--tr-line); background: transparent; color: var(--tr-muted);
        border-radius: 999px; padding: 4px 12px; font-size: .78rem; font-weight: 600;
        transition: all .2s ease;
    }
    .tr-preset:hover, .tr-preset.active {
        border-style: solid; border-color: var(--tr-orange); color: var(--tr-orange);
        background: var(--tr-orange-soft);
    }

    .tr-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .tr-pills input { position: absolute; opacity: 0; pointer-events: none; }
    .tr-pills label {
        border: 1.5px solid var(--tr-line); border-radius: 999px;
        padding: 7px 16px; font-size: .86rem; font-weight: 600; cursor: pointer;
        background: var(--tr-surface); user-select: none;
        transition: all .2s var(--tr-ease);
    }
    .tr-pills label:hover { border-color: var(--tr-orange); color: var(--tr-orange); transform: translateY(-2px); }
    .tr-pills input:checked + label {
        background: var(--tr-orange); border-color: var(--tr-orange); color: #fff;
        box-shadow: 0 6px 14px rgba(208,82,8,.25);
    }
    .tr-pills input:focus-visible + label { outline: 3px solid rgba(208,82,8,.35); outline-offset: 2px; }

    .tr-panel-foot {
        display: flex; justify-content: space-between; align-items: center;
        margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--tr-line);
        font-size: .85rem; color: var(--tr-muted);
    }

    /* ---------- Results header ---------- */
    .tr-results { position: relative; transition: opacity .2s ease; min-height: 240px; }
    .tr-results.is-loading { opacity: .45; pointer-events: none; }
    .tr-progress {
        position: fixed; left: 0; top: 0; height: 3px; width: 0;
        background: linear-gradient(90deg, var(--tr-orange), #ff9a5c);
        z-index: 2000; transition: width .4s var(--tr-ease), opacity .3s ease; opacity: 0;
    }
    .tr-progress.on { opacity: 1; width: 70%; }
    .tr-progress.done { width: 100%; opacity: 0; }

    .tr-count { font-weight: 700; color: var(--tr-navy); }
    .tr-chip {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--tr-orange-soft); border: 1px solid #ffd9c2;
        border-radius: 999px; padding: 5px 10px 5px 14px;
        font-size: .8rem; font-weight: 600; color: var(--tr-orange-dark);
        text-decoration: none; transition: all .2s ease;
    }
    .tr-chip i { transition: transform .25s var(--tr-ease); }
    .tr-chip:hover { background: var(--tr-orange); color: #fff; border-color: var(--tr-orange); }
    .tr-chip:hover i { transform: rotate(90deg); }

    /* ---------- Unit card ---------- */
    .tr-col { animation: trIn .5s var(--tr-ease) both; }
    @keyframes trIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }

    .tr-card {
        background: var(--tr-surface); border: 1px solid var(--tr-line);
        border-radius: 18px; overflow: hidden; height: 100%;
        display: flex; flex-direction: column; box-shadow: var(--tr-shadow-sm);
        transition: transform .4s var(--tr-ease), box-shadow .4s var(--tr-ease), border-color .3s ease;
    }
    .tr-card:hover { transform: translateY(-8px); box-shadow: var(--tr-shadow-lg); border-color: rgba(208,82,8,.35); }
    .tr-media { position: relative; aspect-ratio: 4 / 3; background: #e8ebf3; overflow: hidden; }
    .tr-media .carousel, .tr-media .carousel-inner,
    .tr-media .carousel-item, .tr-media .carousel-item a { height: 100%; }
    .tr-media .carousel-item a { display: block; overflow: hidden; }
    .tr-media img {
        width: 100%; height: 100%; object-fit: cover; display: block;
        transition: transform .9s var(--tr-ease);
    }
    .tr-card:hover .tr-media img { transform: scale(1.07); }
    .tr-media::after {
        content: ""; position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(17,27,72,.35) 0%, transparent 32%, transparent 62%, rgba(17,27,72,.45) 100%);
        pointer-events: none; z-index: 2;
    }
    .tr-nolist {
        position: absolute; inset: 0; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: 6px;
        color: #8b93ac; font-size: .85rem;
        background: repeating-linear-gradient(45deg, #eef0f7, #eef0f7 12px, #e8ebf3 12px, #e8ebf3 24px);
    }
    .tr-nolist i { font-size: 2.2rem; }
    .tr-type {
        position: absolute; top: 12px; left: 12px; z-index: 4;
        background: rgba(255,255,255,.95); color: var(--tr-navy);
        font-size: .72rem; font-weight: 700; letter-spacing: .03em;
        text-transform: uppercase; padding: 5px 11px; border-radius: 999px; pointer-events: none;
    }
    .tr-price-badge {
        position: absolute; left: 12px; bottom: 12px; z-index: 4;
        background: var(--tr-orange); color: #fff; padding: 6px 13px;
        border-radius: 10px; font-weight: 800; font-size: .92rem;
        letter-spacing: -.02em; box-shadow: 0 8px 18px rgba(208,82,8,.4); pointer-events: none;
    }
    .tr-price-badge small { font-weight: 600; opacity: .85; font-size: .7rem; letter-spacing: 0; }
    .tr-fav {
        position: absolute; top: 10px; right: 10px; z-index: 5;
        width: 38px; height: 38px; border: 0; border-radius: 50%;
        background: rgba(255,255,255,.95); color: var(--tr-navy);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(17,27,72,.22);
        transition: transform .25s var(--tr-ease), background .2s ease, color .2s ease;
    }
    .tr-fav:hover { transform: scale(1.12); color: var(--tr-orange); }
    .tr-fav.is-saved { color: #e0245e; }
    .tr-fav.pop { animation: trPop .45s var(--tr-ease); }
    @keyframes trPop { 0% { transform: scale(1); } 40% { transform: scale(1.35); } 100% { transform: scale(1); } }
    .tr-photo-count {
        position: absolute; right: 12px; bottom: 12px; z-index: 4;
        background: rgba(17,27,72,.75); backdrop-filter: blur(4px); color: #fff;
        font-size: .72rem; font-weight: 700; padding: 4px 10px; border-radius: 999px;
        pointer-events: none; font-variant-numeric: tabular-nums;
    }
    .tr-media .tr-arrow { opacity: 0; width: 34px; height: 34px; z-index: 5; }
    .tr-media:hover .tr-arrow, .tr-arrow:focus-visible { opacity: 1; }
    @media (hover: none) { .tr-media .tr-arrow { opacity: 1; } }

    .tr-body { padding: 20px 20px 8px; flex: 1; }
    .tr-title { font-size: 1.1rem; font-weight: 700; margin: 0; }
    .tr-title a {
        color: inherit; text-decoration: none;
        background: linear-gradient(var(--tr-orange), var(--tr-orange)) 0 100% / 0 2px no-repeat;
        transition: background-size .4s var(--tr-ease), color .2s ease;
    }
    .tr-title a:hover { color: var(--tr-orange); background-size: 100% 2px; }
    .tr-specs { display: flex; flex-wrap: wrap; gap: 8px; margin: 14px 0 12px; }
    .tr-specs span {
        display: inline-flex; align-items: center; gap: 6px;
        background: var(--tr-navy-soft); color: var(--tr-navy);
        font-size: .8rem; font-weight: 600; padding: 5px 11px; border-radius: 8px;
        transition: background .2s ease;
    }
    .tr-specs span i { color: var(--tr-orange); }
    .tr-card:hover .tr-specs span { background: var(--tr-orange-soft); }
    .tr-desc {
        font-size: .86rem; color: var(--tr-muted); margin: 0;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .tr-foot { padding: 14px 20px 20px; }
    .tr-foot .btn i { display: inline-block; transition: transform .25s var(--tr-ease); }
    .tr-foot .btn:hover i { transform: translateX(5px); }

    /* List view */
    @media (min-width: 768px) {
        .tr-grid.is-list .tr-col { flex: 0 0 100%; max-width: 100%; }
        .tr-grid.is-list .tr-card { flex-direction: row; }
        .tr-grid.is-list .tr-media { width: 38%; flex-shrink: 0; aspect-ratio: auto; min-height: 240px; }
        .tr-grid.is-list .tr-card:hover { transform: translateY(-4px); }
        .tr-grid.is-list .tr-side { display: flex; flex-direction: column; flex: 1; min-width: 0; }
        .tr-grid.is-list .tr-desc { -webkit-line-clamp: 3; }
    }
    .tr-side { display: contents; }

    /* ---------- Empty ---------- */
    .tr-empty {
        background: var(--tr-surface); border: 2px dashed #cfd5e6;
        border-radius: 18px; padding: 64px 24px; text-align: center;
    }
    .tr-empty i { font-size: 2.8rem; color: var(--tr-orange); display: inline-block; animation: trFloatSm 3s ease-in-out infinite; }
    @keyframes trFloatSm { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
    #trSavedEmpty { display: none; }

    /* ---------- Pagination ---------- */
    .tr-page .pagination { gap: 6px; flex-wrap: wrap; }
    .tr-page .pagination .page-link {
        color: var(--tr-navy); border-color: var(--tr-line);
        border-radius: 10px !important; font-weight: 600; min-width: 42px;
        text-align: center; transition: all .2s var(--tr-ease);
    }
    .tr-page .pagination .page-link:hover { background: var(--tr-orange-soft); color: var(--tr-orange); border-color: var(--tr-orange); transform: translateY(-2px); }
    .tr-page .pagination .active .page-link { background: var(--tr-orange); border-color: var(--tr-orange); color: #fff; }

    /* Back to top */
    .tr-top {
        position: fixed; right: 20px; bottom: 20px; z-index: 40;
        width: 46px; height: 46px; border-radius: 50%; border: 0;
        background: var(--tr-navy); color: #fff; box-shadow: var(--tr-shadow-lg);
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transform: translateY(10px); pointer-events: none;
        transition: all .3s var(--tr-ease);
    }
    .tr-top.show { opacity: 1; transform: none; pointer-events: auto; }

    @media (max-width: 575.98px) {
        .tr-bar-wrap { top: 64px; }
        .tr-ctl span.tr-txt { display: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        .tr-col, .tr-empty i { animation: none; }
        .tr-card:hover, .tr-card:hover .tr-media img { transform: none; }
    }
</style>
@endpush

@section('content')

@php
    $currentBedrooms = (string) request('bedrooms', '');
    $currentSort = request('sort', 'newest');
    $chips = [];

    if (request()->filled('search')) {
        $chips['search'] = '“' . request('search') . '”';
    }
    if (request()->filled('min_rent')) {
        $chips['min_rent'] = 'From RWF ' . number_format((float) request('min_rent'));
    }
    if (request()->filled('max_rent')) {
        $chips['max_rent'] = 'Up to RWF ' . number_format((float) request('max_rent'));
    }
    if ($currentBedrooms !== '') {
        $chips['bedrooms'] = $currentBedrooms === '0'
            ? 'Studio'
            : $currentBedrooms . ($currentBedrooms === '1' ? ' bedroom' : ' bedrooms');
    }
    $panelFilters = collect(['min_rent', 'max_rent', 'bedrooms'])->filter(fn ($k) => isset($chips[$k]))->count();
@endphp

<div class="tr-progress" id="trProgress"></div>

<div class="tr-page">
    <div class="container">

        {{-- Slim page title --}}
        <div class="tr-head">
            <h1>Find your next <span>home</span></h1>
            <p>Browse verified units, compare photos and rent, then apply online.</p>
        </div>

        {{-- Combined search + filter bar --}}
        <div class="tr-bar-wrap">
            <form method="GET" action="{{ route('rentals.index') }}" id="trForm" class="tr-bar" role="search">

                <div class="tr-bar-row">
                    <div class="tr-search {{ request()->filled('search') ? 'has-value' : '' }}" id="trSearchBox">
                        <i class="bi bi-search"></i>
                        <input type="search" name="search" id="trSearch"
                               value="{{ request('search') }}"
                               placeholder="Search by building, unit or type"
                               aria-label="Search rentals" autocomplete="off">
                        <span class="tr-kbd" aria-hidden="true">/</span>
                        <button type="button" class="tr-clear" id="trClearSearch" aria-label="Clear search">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <button type="button" class="tr-ctl" id="trFilterBtn"
                            data-bs-toggle="collapse" data-bs-target="#trPanel"
                            aria-expanded="{{ $panelFilters ? 'true' : 'false' }}" aria-controls="trPanel">
                        <i class="bi bi-sliders"></i>
                        <span class="tr-txt">Filters</span>
                        <span class="tr-badge {{ $panelFilters ? 'show' : '' }}" id="trBadge">{{ $panelFilters }}</span>
                    </button>

                    <select name="sort" id="trSort" class="tr-ctl" aria-label="Sort units">
                        @foreach([
                            'newest'    => 'Newest',
                            'rent_asc'  => 'Rent: low to high',
                            'rent_desc' => 'Rent: high to low',
                            'beds_desc' => 'Most bedrooms',
                            'area_desc' => 'Largest area',
                        ] as $val => $lbl)
                            <option value="{{ $val }}" @selected($currentSort === $val)>{{ $lbl }}</option>
                        @endforeach
                    </select>

                    <button type="button" class="tr-ctl tr-saved-toggle" id="trSavedOnly" aria-pressed="false" title="Show only saved units">
                        <i class="bi bi-heart"></i>
                        <span class="tr-txt">Saved</span>
                        <span class="tr-badge" id="trSavedCount">0</span>
                    </button>

                    <div class="tr-seg d-none d-md-inline-flex" role="group" aria-label="Layout">
                        <button type="button" data-view="grid" class="active" aria-label="Grid view"><i class="bi bi-grid-fill"></i></button>
                        <button type="button" data-view="list" aria-label="List view"><i class="bi bi-list-ul"></i></button>
                    </div>
                </div>

                {{-- Collapsible filters --}}
                <div id="trPanel" class="collapse {{ $panelFilters ? 'show' : '' }}">
                    <div class="tr-panel">
                        <div class="tr-panel-grid">

                            <div>
                                <label class="tr-label" for="trMin">Monthly rent (RWF)</label>
                                <div class="tr-range">
                                    <div class="input-group">
                                        <span class="input-group-text">Min</span>
                                        <input type="number" id="trMin" name="min_rent" min="0" step="10000"
                                               class="form-control" value="{{ request('min_rent') }}"
                                               placeholder="0" aria-label="Minimum rent in RWF">
                                    </div>
                                    <span class="sep">–</span>
                                    <div class="input-group">
                                        <span class="input-group-text">Max</span>
                                        <input type="number" id="trMax" name="max_rent" min="0" step="10000"
                                               class="form-control" value="{{ request('max_rent') }}"
                                               placeholder="Any" aria-label="Maximum rent in RWF">
                                    </div>
                                </div>
                                <div class="tr-presets" id="trPresets">
                                    <button type="button" class="tr-preset" data-min="" data-max="300000">Under 300k</button>
                                    <button type="button" class="tr-preset" data-min="300000" data-max="600000">300k – 600k</button>
                                    <button type="button" class="tr-preset" data-min="600000" data-max="1000000">600k – 1M</button>
                                    <button type="button" class="tr-preset" data-min="1000000" data-max="">1M +</button>
                                </div>
                            </div>

                            <fieldset>
                                <legend class="tr-label float-none">Bedrooms</legend>
                                <div class="tr-pills">
                                    @foreach(['' => 'Any', '0' => 'Studio', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5+'] as $value => $label)
                                        <input type="radio" name="bedrooms"
                                               id="bed-{{ $value === '' ? 'any' : $value }}"
                                               value="{{ $value }}"
                                               @checked($currentBedrooms === (string) $value)>
                                        <label for="bed-{{ $value === '' ? 'any' : $value }}">{{ $label }}</label>
                                    @endforeach
                                </div>
                            </fieldset>
                        </div>

                        <div class="tr-panel-foot">
                            <span><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Results update as you type or choose</span>
                            <a href="{{ route('rentals.index') }}" id="trResetAll" class="fw-bold small">Reset all</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Results (replaced live via fetch) --}}
        <div class="py-4 py-lg-5">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <section id="trResults" class="tr-results" aria-label="Available rental units" aria-live="polite">

                <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                    <div class="tr-count me-2" id="trCountText">
                        {{ number_format($units->total()) }}
                        {{ \Illuminate\Support\Str::plural('unit', $units->total()) }} available
                    </div>

                    @foreach($chips as $key => $label)
                        <a class="tr-chip" data-chip="{{ $key }}"
                           href="{{ route('rentals.index', request()->except($key, 'page')) }}"
                           aria-label="Remove filter: {{ $label }}">
                            {{ $label }}
                            <i class="bi bi-x-lg" style="font-size:.65rem"></i>
                        </a>
                    @endforeach

                    @if(count($chips) > 1)
                        <a href="{{ route('rentals.index', request()->only('sort')) }}" class="small fw-bold ms-1" data-chip="all">Clear all</a>
                    @endif
                </div>

                @if($units->count())
                    <div class="row g-4 tr-grid" id="trGrid">
                        @foreach($units as $unit)
                            @php
                                $images = $unit->images->take(6);
                                $carouselId = 'unit-photos-' . $unit->id;
                                $buildingName = $unit->building?->name ?? 'Rental property';
                                $detailUrl = route('rentals.show', $unit);
                                $currency = $unit->currency ?? 'RWF';
                            @endphp

                            <div class="col-md-6 tr-col" data-unit-id="{{ $unit->id }}"
                                 style="animation-delay: {{ ($loop->index % 6) * 60 }}ms">
                                <article class="tr-card">

                                    <div class="tr-media">
                                        @if($unit->unit_type)
                                            <span class="tr-type">{{ ucfirst(str_replace('_', ' ', $unit->unit_type)) }}</span>
                                        @endif

                                        <button type="button" class="tr-fav"
                                                data-fav="{{ $unit->id }}"
                                                aria-pressed="false"
                                                aria-label="Save {{ $buildingName }}, unit {{ $unit->unit_number }}">
                                            <i class="bi bi-heart"></i>
                                        </button>

                                        <span class="tr-price-badge">
                                            {{ $currency }} {{ number_format((float) $unit->rent_amount, 0) }}
                                            <small>/ mo</small>
                                        </span>

                                        @if($images->isNotEmpty())
                                            <div id="{{ $carouselId }}"
                                                 class="carousel slide"
                                                 data-bs-interval="false"
                                                 data-bs-ride="false"
                                                 aria-label="Photos of {{ $buildingName }}, unit {{ $unit->unit_number }}">

                                                <div class="carousel-inner">
                                                    @foreach($images as $image)
                                                        <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                                            <a href="{{ $detailUrl }}" tabindex="-1" aria-hidden="true">
                                                                <img src="{{ asset('storage/' . $image->image_path) }}"
                                                                     alt="{{ $buildingName }}, unit {{ $unit->unit_number }}, photo {{ $loop->iteration }}"
                                                                     @if(!$loop->first) loading="lazy" @endif>
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                @if($images->count() > 1)
                                                    <button class="tr-arrow prev" type="button"
                                                            data-bs-target="#{{ $carouselId }}"
                                                            data-bs-slide="prev"
                                                            aria-label="Previous photo">
                                                        <i class="bi bi-chevron-left"></i>
                                                    </button>
                                                    <button class="tr-arrow next" type="button"
                                                            data-bs-target="#{{ $carouselId }}"
                                                            data-bs-slide="next"
                                                            aria-label="Next photo">
                                                        <i class="bi bi-chevron-right"></i>
                                                    </button>
                                                    <span class="tr-photo-count" aria-hidden="true">
                                                        <i class="bi bi-images me-1"></i><span data-photo-current>1</span>/{{ $images->count() }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="tr-nolist">
                                                <i class="bi bi-image"></i>
                                                No photos yet
                                            </div>
                                        @endif
                                    </div>

                                    <div class="tr-side">
                                        <div class="tr-body">
                                            <h3 class="tr-title">
                                                <a href="{{ $detailUrl }}">{{ $buildingName }}</a>
                                            </h3>
                                            <div class="small tr-muted">Unit {{ $unit->unit_number }}</div>

                                            <div class="tr-specs">
                                                <span>
                                                    <i class="bi bi-door-open"></i>{{ ($unit->bedrooms ?? 0) == 0 ? 'Studio' : $unit->bedrooms . ' bed' . ($unit->bedrooms == 1 ? '' : 's') }}
                                                </span>
                                                <span>
                                                    <i class="bi bi-droplet"></i>{{ $unit->bathrooms ?? 0 }} bath{{ ($unit->bathrooms ?? 0) == 1 ? '' : 's' }}
                                                </span>
                                                @if($unit->area_sqm)
                                                    <span><i class="bi bi-rulers"></i>{{ $unit->area_sqm }} m²</span>
                                                @endif
                                            </div>

                                            @if($unit->description)
                                                <p class="tr-desc">{{ $unit->description }}</p>
                                            @endif
                                        </div>

                                        <div class="tr-foot">
                                            <a href="{{ $detailUrl }}" class="btn tr-btn-line w-100">
                                                View details and apply <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                    </div>

                                </article>
                            </div>
                        @endforeach
                    </div>

                    <div id="trSavedEmpty" class="tr-empty mt-3">
                        <i class="bi bi-heart"></i>
                        <h2 class="h5 fw-bold mt-3">No saved units on this page</h2>
                        <p class="tr-muted mb-4">Tap the heart on any unit to build your shortlist.</p>
                        <button type="button" class="btn tr-btn" id="trSavedBack">Show all units</button>
                    </div>

                    <div class="mt-5 d-flex justify-content-center">
                        {{ $units->withQueryString()->links() }}
                    </div>
                @else
                    <div class="tr-empty">
                        <i class="bi bi-house-x"></i>
                        <h2 class="h5 fw-bold mt-3">No units match these filters</h2>
                        <p class="tr-muted mb-4">Widen your rent range or remove a filter to see more units.</p>
                        <a href="{{ route('rentals.index') }}" class="btn tr-btn" data-chip="all">Clear all filters</a>
                    </div>
                @endif

            </section>
        </div>
    </div>
</div>

<button type="button" class="tr-top" id="trTop" aria-label="Back to top"><i class="bi bi-arrow-up"></i></button>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const form      = document.getElementById('trForm');
    const results   = document.getElementById('trResults');
    const progress  = document.getElementById('trProgress');
    const searchIn  = document.getElementById('trSearch');
    const searchBox = document.getElementById('trSearchBox');
    const minIn     = document.getElementById('trMin');
    const maxIn     = document.getElementById('trMax');
    const sortSel   = document.getElementById('trSort');
    const badge     = document.getElementById('trBadge');
    const savedBtn  = document.getElementById('trSavedOnly');
    const savedCnt  = document.getElementById('trSavedCount');

    const FAV_KEY  = 'terra_saved_units';
    const VIEW_KEY = 'terra_rentals_view';
    let savedOnly = false;
    let controller = null;
    let timer = null;

    /* ---------- storage helpers ---------- */
    function readSaved() {
        try { return JSON.parse(localStorage.getItem(FAV_KEY)) || []; } catch (e) { return []; }
    }
    function writeSaved(list) {
        try { localStorage.setItem(FAV_KEY, JSON.stringify(list)); } catch (e) {}
    }
    function getView() {
        try { return localStorage.getItem(VIEW_KEY) || 'grid'; } catch (e) { return 'grid'; }
    }

    /* ---------- UI state ---------- */
    function updateBadge() {
        let n = 0;
        if (minIn.value) n++;
        if (maxIn.value) n++;
        const bed = form.querySelector('input[name="bedrooms"]:checked');
        if (bed && bed.value !== '') n++;
        badge.textContent = n;
        badge.classList.toggle('show', n > 0);

        document.querySelectorAll('.tr-preset').forEach(function (p) {
            p.classList.toggle('active', p.dataset.min === minIn.value && p.dataset.max === maxIn.value);
        });
    }
    function updateSavedCount() {
        const n = readSaved().length;
        savedCnt.textContent = n;
        savedCnt.classList.toggle('show', n > 0);
    }
    function applyView(view) {
        const grid = document.getElementById('trGrid');
        if (grid) grid.classList.toggle('is-list', view === 'list');
        document.querySelectorAll('.tr-seg button').forEach(function (b) {
            b.classList.toggle('active', b.dataset.view === view);
        });
    }

    /* ---------- Build URL from form ---------- */
    function buildUrl() {
        const params = new URLSearchParams();
        new FormData(form).forEach(function (v, k) {
            if (v !== '' && !(k === 'sort' && v === 'newest')) params.append(k, v);
        });
        const qs = params.toString();
        return form.action + (qs ? '?' + qs : '');
    }

    /* ---------- Live fetch ---------- */
    function load(url, opts) {
        opts = opts || {};
        if (controller) controller.abort();
        controller = new AbortController();

        results.classList.add('is-loading');
        progress.className = 'tr-progress on';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            signal: controller.signal
        })
        .then(function (r) {
            if (!r.ok) throw new Error('Request failed');
            return r.text();
        })
        .then(function (html) {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.getElementById('trResults');
            if (!fresh) { window.location.href = url; return; }
            results.innerHTML = fresh.innerHTML;
            if (!opts.noPush) history.pushState({ trUrl: url }, '', url);
            initResults();
            if (opts.scroll) {
                const top = results.getBoundingClientRect().top + window.scrollY - 150;
                window.scrollTo({ top: top, behavior: 'smooth' });
            }
        })
        .catch(function (err) {
            if (err.name === 'AbortError') return;
            window.location.href = url; // graceful fallback
        })
        .finally(function () {
            results.classList.remove('is-loading');
            progress.className = 'tr-progress done';
            setTimeout(function () { progress.className = 'tr-progress'; }, 350);
        });
    }
    function submitLive(delay, opts) {
        clearTimeout(timer);
        timer = setTimeout(function () { load(buildUrl(), opts); }, delay || 0);
    }

    /* ---------- Search ---------- */
    searchIn.addEventListener('input', function () {
        searchBox.classList.toggle('has-value', searchIn.value.length > 0);
        submitLive(400);
    });
    document.getElementById('trClearSearch').addEventListener('click', function () {
        searchIn.value = '';
        searchBox.classList.remove('has-value');
        searchIn.focus();
        submitLive(0);
    });
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitLive(0, { scroll: true });
    });

    /* ---------- Filters ---------- */
    [minIn, maxIn].forEach(function (el) {
        el.addEventListener('input', function () { updateBadge(); submitLive(600); });
    });
    form.querySelectorAll('input[name="bedrooms"]').forEach(function (r) {
        r.addEventListener('change', function () { updateBadge(); submitLive(0); });
    });
    sortSel.addEventListener('change', function () { submitLive(0); });

    document.querySelectorAll('.tr-preset').forEach(function (p) {
        p.addEventListener('click', function () {
            const active = p.classList.contains('active');
            minIn.value = active ? '' : p.dataset.min;
            maxIn.value = active ? '' : p.dataset.max;
            updateBadge();
            submitLive(0);
        });
    });

    document.getElementById('trResetAll').addEventListener('click', function (e) {
        e.preventDefault();
        resetForm();
    });
    function resetForm() {
        searchIn.value = '';
        searchBox.classList.remove('has-value');
        minIn.value = '';
        maxIn.value = '';
        sortSel.value = 'newest';
        const any = document.getElementById('bed-any');
        if (any) any.checked = true;
        updateBadge();
        submitLive(0);
    }

    /* ---------- Chips + pagination (delegated, inside results) ---------- */
    results.addEventListener('click', function (e) {
        const chip = e.target.closest('[data-chip]');
        if (chip) {
            e.preventDefault();
            const key = chip.dataset.chip;
            if (key === 'all') { resetForm(); return; }
            if (key === 'search') { searchIn.value = ''; searchBox.classList.remove('has-value'); }
            if (key === 'min_rent') minIn.value = '';
            if (key === 'max_rent') maxIn.value = '';
            if (key === 'bedrooms') { const a = document.getElementById('bed-any'); if (a) a.checked = true; }
            updateBadge();
            submitLive(0);
            return;
        }
        const page = e.target.closest('.pagination a.page-link');
        if (page && page.href) {
            e.preventDefault();
            load(page.href, { scroll: true });
            return;
        }
        if (e.target.closest('#trSavedBack')) toggleSavedOnly(false);
    });

    window.addEventListener('popstate', function () {
        load(window.location.href, { noPush: true });
    });

    /* ---------- Layout toggle ---------- */
    document.querySelectorAll('.tr-seg button').forEach(function (b) {
        b.addEventListener('click', function () {
            try { localStorage.setItem(VIEW_KEY, b.dataset.view); } catch (e) {}
            applyView(b.dataset.view);
        });
    });

    /* ---------- Saved-only filter (client side) ---------- */
    function toggleSavedOnly(on) {
        savedOnly = on;
        savedBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
        savedBtn.querySelector('i').className = on ? 'bi bi-heart-fill' : 'bi bi-heart';
        applySavedFilter();
    }
    function applySavedFilter() {
        const list = readSaved();
        let visible = 0;
        results.querySelectorAll('.tr-col').forEach(function (col) {
            const show = !savedOnly || list.includes(col.dataset.unitId);
            col.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const empty = document.getElementById('trSavedEmpty');
        if (empty) empty.style.display = (savedOnly && visible === 0) ? 'block' : 'none';
    }
    savedBtn.addEventListener('click', function () {
        toggleSavedOnly(savedBtn.getAttribute('aria-pressed') !== 'true');
    });

    /* ---------- Per-results init (runs after every fetch) ---------- */
    function initResults() {
        /* Photo counters */
        results.querySelectorAll('.tr-media .carousel').forEach(function (carousel) {
            const counter = carousel.parentElement.querySelector('[data-photo-current]');
            if (!counter) return;
            carousel.addEventListener('slid.bs.carousel', function (event) {
                counter.textContent = event.to + 1;
            });
        });

        /* Favourites */
        const saved = readSaved();
        results.querySelectorAll('[data-fav]').forEach(function (btn) {
            const id = btn.dataset.fav;
            const apply = function (on) {
                btn.classList.toggle('is-saved', on);
                btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                const icon = btn.querySelector('i');
                icon.classList.toggle('bi-heart-fill', on);
                icon.classList.toggle('bi-heart', !on);
            };
            apply(saved.includes(id));

            btn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                let list = readSaved();
                const on = !list.includes(id);
                list = on ? list.concat(id) : list.filter(function (x) { return x !== id; });
                writeSaved(list);
                apply(on);
                updateSavedCount();
                btn.classList.remove('pop');
                void btn.offsetWidth;
                btn.classList.add('pop');
                if (savedOnly) applySavedFilter();
                if (window.trToast) window.trToast(on ? 'Saved to your shortlist' : 'Removed from your shortlist');
            });
        });

        applyView(getView());
        applySavedFilter();
        updateSavedCount();
    }

    /* ---------- Keyboard shortcut: "/" focuses search ---------- */
    document.addEventListener('keydown', function (e) {
        const tag = (document.activeElement && document.activeElement.tagName) || '';
        if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(tag)) {
            e.preventDefault();
            searchIn.focus();
            searchIn.select();
        }
        if (e.key === 'Escape' && document.activeElement === searchIn && searchIn.value) {
            searchIn.value = '';
            searchBox.classList.remove('has-value');
            submitLive(0);
        }
    });

    /* ---------- Back to top ---------- */
    const topBtn = document.getElementById('trTop');
    window.addEventListener('scroll', function () {
        topBtn.classList.toggle('show', window.scrollY > 600);
    }, { passive: true });
    topBtn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    updateBadge();
    initResults();
});
</script>
@endpush
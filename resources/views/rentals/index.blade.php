@extends('layouts.base')

@section('title', 'Find a Rental | Terra Smart Rental Management')

@push('styles')
<style>
    /* ---------- Hero ---------- */
    .tr-hero {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, var(--tr-navy-deep) 0%, var(--tr-navy) 55%, #26378a 100%);
        color: #fff;
        padding: 64px 0 96px;
    }
    .tr-hero::before,
    .tr-hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }
    .tr-hero::before {
        width: 520px; height: 520px;
        right: -140px; top: -220px;
        background: radial-gradient(circle, rgba(208,82,8,.55), transparent 68%);
        animation: trFloat 9s ease-in-out infinite;
    }
    .tr-hero::after {
        width: 380px; height: 380px;
        left: -120px; bottom: -200px;
        background: radial-gradient(circle, rgba(255,255,255,.12), transparent 70%);
        animation: trFloat 11s ease-in-out infinite reverse;
    }
    @keyframes trFloat {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(-24px, 22px); }
    }
    .tr-hero .container { position: relative; z-index: 2; }
    .tr-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.18);
        backdrop-filter: blur(8px);
        padding: 6px 14px;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #ffd2b6;
    }
    .tr-eyebrow i { color: #ff9a5c; }
    .tr-hero h1 {
        color: #fff;
        font-size: clamp(2.1rem, 4.6vw, 3.4rem);
        font-weight: 800;
        margin: 18px 0 14px;
        letter-spacing: -.045em;
    }
    .tr-hero h1 span { color: #ff9a5c; }
    .tr-hero p.lead-text {
        max-width: 54ch;
        color: rgba(255,255,255,.78);
        font-size: 1.02rem;
        margin: 0;
    }
    .tr-hero-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 14px 40px;
        margin-top: 30px;
    }
    .tr-hero-stat strong {
        display: block;
        font-size: 1.7rem;
        font-weight: 800;
        letter-spacing: -.04em;
        line-height: 1.1;
    }
    .tr-hero-stat span {
        font-size: .76rem;
        color: rgba(255,255,255,.65);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    /* Quick search (floats over hero edge) */
    .tr-quick {
        position: relative;
        z-index: 5;
        margin-top: -44px;
    }
    .tr-quick-box {
        background: var(--tr-surface);
        border: 1px solid var(--tr-line);
        border-radius: 18px;
        box-shadow: var(--tr-shadow-lg);
        padding: 10px;
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .tr-quick-box .tr-q-field {
        position: relative;
        flex: 1;
    }
    .tr-quick-box .tr-q-field i {
        position: absolute;
        left: 16px; top: 50%;
        transform: translateY(-50%);
        color: var(--tr-orange);
        font-size: 1.05rem;
        pointer-events: none;
    }
    .tr-quick-box input {
        border: 0;
        background: transparent;
        padding: 14px 14px 14px 46px;
        font-size: .95rem;
        font-weight: 500;
        width: 100%;
        border-radius: 12px;
    }
    .tr-quick-box input:focus { outline: none; box-shadow: none; background: var(--tr-ground); }
    @media (max-width: 575.98px) {
        .tr-quick-box { flex-direction: column; align-items: stretch; }
        .tr-quick-box .tr-btn { width: 100%; }
    }

    /* ---------- Filters ---------- */
    .tr-filters {
        background: var(--tr-surface);
        border: 1px solid var(--tr-line);
        border-radius: 16px;
        padding: 22px;
        box-shadow: var(--tr-shadow-sm);
    }
    @media (min-width: 992px) {
        .tr-filters-wrap { position: sticky; top: 92px; }
    }
    .tr-filters label.tr-label {
        display: block;
        font-size: .8rem;
        font-weight: 700;
        letter-spacing: .02em;
        text-transform: uppercase;
        color: var(--tr-navy);
        margin-bottom: 8px;
    }
    .tr-filters .form-control,
    .tr-filters .input-group-text {
        border-color: var(--tr-line);
        min-height: 44px;
        font-size: .92rem;
    }
    .tr-filters .input-group-text {
        background: var(--tr-ground);
        color: var(--tr-muted);
        font-size: .75rem;
        font-weight: 700;
        border-radius: 10px 0 0 10px;
    }
    .tr-filters .input-group .form-control { border-radius: 0 10px 10px 0; }

    .tr-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .tr-pills input { position: absolute; opacity: 0; pointer-events: none; }
    .tr-pills label {
        border: 1.5px solid var(--tr-line);
        border-radius: 999px;
        padding: 6px 15px;
        font-size: .85rem;
        font-weight: 600;
        cursor: pointer;
        background: var(--tr-surface);
        user-select: none;
        transition: all .2s var(--tr-ease);
    }
    .tr-pills label:hover { border-color: var(--tr-orange); color: var(--tr-orange); transform: translateY(-2px); }
    .tr-pills input:checked + label {
        background: var(--tr-orange);
        border-color: var(--tr-orange);
        color: #fff;
        box-shadow: 0 6px 14px rgba(208,82,8,.25);
    }
    .tr-pills input:focus-visible + label { outline: 3px solid rgba(208,82,8,.35); outline-offset: 2px; }

    /* ---------- Result bar ---------- */
    .tr-count { font-weight: 700; color: var(--tr-navy); }
    .tr-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--tr-orange-soft);
        border: 1px solid #ffd9c2;
        border-radius: 999px;
        padding: 5px 10px 5px 14px;
        font-size: .8rem;
        font-weight: 600;
        color: var(--tr-orange-dark);
        text-decoration: none;
    }
    .tr-chip i { transition: transform .25s var(--tr-ease); }
    .tr-chip:hover { background: var(--tr-orange); color: #fff; border-color: var(--tr-orange); }
    .tr-chip:hover i { transform: rotate(90deg); }

    /* ---------- Unit card ---------- */
    .tr-card {
        background: var(--tr-surface);
        border: 1px solid var(--tr-line);
        border-radius: 18px;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        box-shadow: var(--tr-shadow-sm);
        transition: transform .4s var(--tr-ease), box-shadow .4s var(--tr-ease), border-color .3s ease;
    }
    .tr-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--tr-shadow-lg);
        border-color: rgba(208,82,8,.35);
    }
    .tr-media {
        position: relative;
        aspect-ratio: 4 / 3;
        background: #e8ebf3;
        overflow: hidden;
    }
    .tr-media .carousel,
    .tr-media .carousel-inner,
    .tr-media .carousel-item,
    .tr-media .carousel-item a { height: 100%; }
    .tr-media .carousel-item a { display: block; overflow: hidden; }
    .tr-media img {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
        transition: transform .9s var(--tr-ease);
    }
    .tr-card:hover .tr-media img { transform: scale(1.07); }

    /* gradient for badge legibility */
    .tr-media::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(17,27,72,.35) 0%, transparent 32%, transparent 62%, rgba(17,27,72,.45) 100%);
        pointer-events: none;
        z-index: 2;
    }

    .tr-nolist {
        position: absolute; inset: 0;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        gap: 6px;
        color: #8b93ac;
        font-size: .85rem;
        background: repeating-linear-gradient(45deg, #eef0f7, #eef0f7 12px, #e8ebf3 12px, #e8ebf3 24px);
    }
    .tr-nolist i { font-size: 2.2rem; }

    .tr-type {
        position: absolute;
        top: 12px; left: 12px;
        z-index: 4;
        background: rgba(255,255,255,.95);
        color: var(--tr-navy);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .03em;
        text-transform: uppercase;
        padding: 5px 11px;
        border-radius: 999px;
        pointer-events: none;
    }
    .tr-price-badge {
        position: absolute;
        left: 12px; bottom: 12px;
        z-index: 4;
        background: var(--tr-orange);
        color: #fff;
        padding: 6px 13px;
        border-radius: 10px;
        font-weight: 800;
        font-size: .92rem;
        letter-spacing: -.02em;
        box-shadow: 0 8px 18px rgba(208,82,8,.4);
        pointer-events: none;
    }
    .tr-price-badge small { font-weight: 600; opacity: .85; font-size: .7rem; letter-spacing: 0; }

    .tr-fav {
        position: absolute;
        top: 10px; right: 10px;
        z-index: 5;
        width: 38px; height: 38px;
        border: 0;
        border-radius: 50%;
        background: rgba(255,255,255,.95);
        color: var(--tr-navy);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(17,27,72,.22);
        transition: transform .25s var(--tr-ease), background .2s ease, color .2s ease;
    }
    .tr-fav:hover { transform: scale(1.12); color: var(--tr-orange); }
    .tr-fav.is-saved { color: #e0245e; }
    .tr-fav.pop { animation: trPop .45s var(--tr-ease); }
    @keyframes trPop {
        0% { transform: scale(1); } 40% { transform: scale(1.35); } 100% { transform: scale(1); }
    }

    .tr-photo-count {
        position: absolute;
        right: 12px; bottom: 12px;
        z-index: 4;
        background: rgba(17,27,72,.75);
        backdrop-filter: blur(4px);
        color: #fff;
        font-size: .72rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 999px;
        pointer-events: none;
        font-variant-numeric: tabular-nums;
    }
    .tr-media .tr-arrow { opacity: 0; width: 34px; height: 34px; z-index: 5; }
    .tr-media:hover .tr-arrow, .tr-arrow:focus-visible { opacity: 1; }
    @media (hover: none) { .tr-media .tr-arrow { opacity: 1; } }

    .tr-body { padding: 20px 20px 8px; flex: 1; }
    .tr-title { font-size: 1.1rem; font-weight: 700; margin: 0; }
    .tr-title a { color: inherit; text-decoration: none; background: linear-gradient(var(--tr-orange), var(--tr-orange)) 0 100% / 0 2px no-repeat; transition: background-size .4s var(--tr-ease), color .2s ease; }
    .tr-title a:hover { color: var(--tr-orange); background-size: 100% 2px; }

    .tr-specs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 14px 0 12px;
    }
    .tr-specs span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--tr-navy-soft);
        color: var(--tr-navy);
        font-size: .8rem;
        font-weight: 600;
        padding: 5px 11px;
        border-radius: 8px;
        transition: background .2s ease, color .2s ease;
    }
    .tr-specs span i { color: var(--tr-orange); }
    .tr-card:hover .tr-specs span { background: var(--tr-orange-soft); }

    .tr-desc {
        font-size: .86rem;
        color: var(--tr-muted);
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .tr-foot { padding: 14px 20px 20px; }
    .tr-foot .btn i { display: inline-block; transition: transform .25s var(--tr-ease); }
    .tr-foot .btn:hover i { transform: translateX(5px); }

    /* ---------- Empty ---------- */
    .tr-empty {
        background: var(--tr-surface);
        border: 2px dashed #cfd5e6;
        border-radius: 18px;
        padding: 64px 24px;
        text-align: center;
    }
    .tr-empty i { font-size: 2.8rem; color: var(--tr-orange); display: inline-block; animation: trFloatSm 3s ease-in-out infinite; }
    @keyframes trFloatSm { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

    /* ---------- Pagination ---------- */
    .tr-page .pagination { gap: 6px; flex-wrap: wrap; }
    .tr-page .pagination .page-link {
        color: var(--tr-navy);
        border-color: var(--tr-line);
        border-radius: 10px !important;
        font-weight: 600;
        min-width: 42px;
        text-align: center;
        transition: all .2s var(--tr-ease);
    }
    .tr-page .pagination .page-link:hover { background: var(--tr-orange-soft); color: var(--tr-orange); border-color: var(--tr-orange); transform: translateY(-2px); }
    .tr-page .pagination .active .page-link { background: var(--tr-orange); border-color: var(--tr-orange); color: #fff; }

    @media (prefers-reduced-motion: reduce) {
        .tr-hero::before, .tr-hero::after, .tr-empty i { animation: none; }
        .tr-card:hover { transform: none; }
        .tr-card:hover .tr-media img { transform: none; }
    }
</style>
@endpush

@section('content')

@php
    $currentBedrooms = (string) request('bedrooms', '');
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
@endphp

<div class="tr-page">

    {{-- Hero --}}
    <header class="tr-hero">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <span class="tr-eyebrow" data-aos="fade-down">
                        <i class="bi bi-stars"></i> Verified rentals in Kigali
                    </span>
                    <h1 data-aos="fade-up" data-aos-delay="80">Find your next <span>home</span>, apply in minutes</h1>
                    <p class="lead-text" data-aos="fade-up" data-aos-delay="160">
                        Browse available units on Terra Smart Rental Management,
                        compare photos and rent, and apply online.
                    </p>

                    <div class="tr-hero-stats" data-aos="fade-up" data-aos-delay="240">
                        <div class="tr-hero-stat">
                            <strong data-count="{{ $units->total() }}">{{ number_format($units->total()) }}</strong>
                            <span>Units available</span>
                        </div>
                        <div class="tr-hero-stat">
                            <strong><i class="bi bi-lightning-charge-fill" style="color:#ff9a5c"></i> Online</strong>
                            <span>Application</span>
                        </div>
                        <div class="tr-hero-stat">
                            <strong><i class="bi bi-shield-check" style="color:#ff9a5c"></i> Secure</strong>
                            <span>Tenant portal</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Quick search --}}
    <div class="container tr-quick" data-aos="fade-up" data-aos-delay="300">
        <form method="GET" action="{{ route('rentals.index') }}" class="tr-quick-box" role="search">
            <div class="tr-q-field">
                <i class="bi bi-search"></i>
                <input type="search" name="search" value="{{ request('search') }}"
                       placeholder="Search by building, unit or type"
                       aria-label="Search rentals">
            </div>
            @foreach(['min_rent', 'max_rent', 'bedrooms'] as $keep)
                @if(request()->filled($keep) || ($keep === 'bedrooms' && $currentBedrooms !== ''))
                    <input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">
                @endif
            @endforeach
            <button type="submit" class="btn tr-btn">Search</button>
        </form>
    </div>

    <div class="container py-4 py-lg-5 mt-3">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">

            {{-- Filters --}}
            <aside class="col-lg-3" data-aos="fade-right">
                <div class="tr-filters-wrap">

                    <button class="tr-btn-line btn w-100 d-lg-none mb-3"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#trFilters"
                            aria-expanded="{{ count($chips) ? 'true' : 'false' }}"
                            aria-controls="trFilters">
                        <i class="bi bi-sliders me-2"></i>
                        Filters{{ count($chips) ? ' (' . count($chips) . ')' : '' }}
                    </button>

                    <div id="trFilters" class="collapse d-lg-block {{ count($chips) ? 'show' : '' }}">
                        <form method="GET" action="{{ route('rentals.index') }}" class="tr-filters">

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h6 fw-bold mb-0"><i class="bi bi-funnel text-primary me-2"></i>Filter units</h2>
                                @if(count($chips))
                                    <a href="{{ route('rentals.index') }}" class="small fw-bold">Clear all</a>
                                @endif
                            </div>

                            <div class="mb-4">
                                <label for="f-search" class="tr-label">Search</label>
                                <input type="search" id="f-search" name="search"
                                       class="form-control"
                                       value="{{ request('search') }}"
                                       placeholder="Building, unit or type">
                            </div>

                            <div class="mb-4">
                                <label for="f-min" class="tr-label">Monthly rent</label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Min</span>
                                    <input type="number" id="f-min" name="min_rent" min="0"
                                           class="form-control"
                                           value="{{ request('min_rent') }}"
                                           placeholder="RWF"
                                           aria-label="Minimum rent in RWF">
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text">Max</span>
                                    <input type="number" name="max_rent" min="0"
                                           class="form-control"
                                           value="{{ request('max_rent') }}"
                                           placeholder="RWF"
                                           aria-label="Maximum rent in RWF">
                                </div>
                            </div>

                            <fieldset class="mb-4">
                                <legend class="tr-label float-none">Bedrooms</legend>
                                <div class="tr-pills">
                                    @foreach(['' => 'Any', '0' => 'Studio', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'] as $value => $label)
                                        <input type="radio" name="bedrooms"
                                               id="bed-{{ $value === '' ? 'any' : $value }}"
                                               value="{{ $value }}"
                                               @checked($currentBedrooms === (string) $value)>
                                        <label for="bed-{{ $value === '' ? 'any' : $value }}">{{ $label }}</label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <button type="submit" class="btn tr-btn w-100">
                                <i class="bi bi-search me-2"></i>Show results
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            {{-- Results --}}
            <section class="col-lg-9" aria-label="Available rental units">

                <div class="d-flex flex-wrap align-items-center gap-2 mb-4" data-aos="fade-up">
                    <div class="tr-count me-2">
                        {{ number_format($units->total()) }}
                        {{ \Illuminate\Support\Str::plural('unit', $units->total()) }} available
                    </div>

                    @foreach($chips as $key => $label)
                        <a class="tr-chip"
                           href="{{ route('rentals.index', request()->except($key, 'page')) }}"
                           aria-label="Remove filter: {{ $label }}">
                            {{ $label }}
                            <i class="bi bi-x-lg" style="font-size:.65rem"></i>
                        </a>
                    @endforeach
                </div>

                @if($units->count())
                    <div class="row g-4">
                        @foreach($units as $unit)
                            @php
                                $images = $unit->images->take(6);
                                $carouselId = 'unit-photos-' . $unit->id;
                                $buildingName = $unit->building?->name ?? 'Rental property';
                                $detailUrl = route('rentals.show', $unit);
                                $currency = $unit->currency ?? 'RWF';
                            @endphp

                            <div class="col-md-6"
                                 data-aos="fade-up"
                                 data-aos-delay="{{ ($loop->index % 2) * 120 }}">
                                <article class="tr-card">

                                    <div class="tr-media">
                                        @if($unit->unit_type)
                                            <span class="tr-type">
                                                {{ ucfirst(str_replace('_', ' ', $unit->unit_type)) }}
                                            </span>
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

                                </article>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 d-flex justify-content-center" data-aos="fade-up">
                        {{ $units->withQueryString()->links() }}
                    </div>
                @else
                    <div class="tr-empty" data-aos="zoom-in">
                        <i class="bi bi-house-x"></i>
                        <h2 class="h5 fw-bold mt-3">No units match these filters</h2>
                        <p class="tr-muted mb-4">
                            Widen your rent range or remove a filter to see more units.
                        </p>
                        <a href="{{ route('rentals.index') }}" class="btn tr-btn">Clear all filters</a>
                    </div>
                @endif

            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* Photo counter in sync with each card carousel */
    document.querySelectorAll('.tr-media .carousel').forEach(function (carousel) {
        const counter = carousel.parentElement.querySelector('[data-photo-current]');
        if (!counter) return;
        carousel.addEventListener('slid.bs.carousel', function (event) {
            counter.textContent = event.to + 1;
        });
    });

    /* Saved units (stored in this browser only) */
    const KEY = 'terra_saved_units';
    function readSaved() {
        try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; }
    }
    function writeSaved(list) {
        try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {}
    }

    const saved = readSaved();
    document.querySelectorAll('[data-fav]').forEach(function (btn) {
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
            btn.classList.remove('pop');
            void btn.offsetWidth;
            btn.classList.add('pop');
            if (window.trToast) window.trToast(on ? 'Saved to your shortlist' : 'Removed from your shortlist');
        });
    });

    /* Hero counter animation */
    const counter = document.querySelector('[data-count]');
    if (counter && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const target = parseInt(counter.dataset.count, 10) || 0;
        if (target > 0) {
            const duration = 1100;
            const start = performance.now();
            (function tick(now) {
                const p = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - p, 3);
                counter.textContent = Math.round(target * eased).toLocaleString('en-US');
                if (p < 1) requestAnimationFrame(tick);
            })(start);
        }
    }
});
</script>
@endpush
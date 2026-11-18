@extends('layouts.base')

@section('title', 'Unit ' . $unit->unit_number . ' | Terra Smart Rental Management')

@push('styles')
<style>
    /* ---------- Breadcrumb bar ---------- */
    .tr-crumbs {
        background: var(--tr-surface);
        border-bottom: 1px solid var(--tr-line);
        padding: 14px 0;
    }
    .tr-back {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--tr-muted);
        font-weight: 700;
        font-size: .86rem;
        text-decoration: none;
    }
    .tr-back i { transition: transform .25s var(--tr-ease); }
    .tr-back:hover { color: var(--tr-orange); }
    .tr-back:hover i { transform: translateX(-4px); }

    .tr-tools { display: flex; gap: 8px; }
    .tr-tool {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: var(--tr-surface);
        border: 1.5px solid var(--tr-line);
        color: var(--tr-navy);
        font-size: .82rem;
        font-weight: 700;
        padding: 7px 14px;
        border-radius: 999px;
        transition: all .25s var(--tr-ease);
    }
    .tr-tool:hover { border-color: var(--tr-orange); color: var(--tr-orange); transform: translateY(-2px); box-shadow: var(--tr-shadow-sm); }
    .tr-tool.is-saved { border-color: #f3b3c8; color: #e0245e; background: #fff2f6; }
    .tr-tool.pop i { animation: trPop .45s var(--tr-ease); }
    @keyframes trPop { 0% { transform: scale(1); } 40% { transform: scale(1.4); } 100% { transform: scale(1); } }

    /* ---------- Gallery ---------- */
    .tr-gallery {
        background: var(--tr-surface);
        border: 1px solid var(--tr-line);
        border-radius: 20px;
        padding: 10px;
        box-shadow: var(--tr-shadow-md);
    }
    .tr-stage {
        position: relative;
        aspect-ratio: 16 / 10;
        border-radius: 14px;
        overflow: hidden;
        background: #e8ebf3;
        cursor: zoom-in;
    }
    .tr-stage .carousel,
    .tr-stage .carousel-inner,
    .tr-stage .carousel-item { height: 100%; }
    .tr-stage img {
        width: 100%; height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 1s var(--tr-ease);
    }
    .tr-stage:hover img { transform: scale(1.04); }
    .tr-stage::after {
        content: "";
        position: absolute; inset: 0;
        background: linear-gradient(180deg, transparent 60%, rgba(17,27,72,.4));
        pointer-events: none;
        z-index: 2;
    }
    .tr-nophoto {
        position: absolute; inset: 0;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        gap: 8px;
        color: #8b93ac;
        cursor: default;
        background: repeating-linear-gradient(45deg, #eef0f7, #eef0f7 14px, #e8ebf3 14px, #e8ebf3 28px);
    }
    .tr-nophoto i { font-size: 3rem; }
    .tr-stage .tr-arrow { width: 44px; height: 44px; z-index: 5; }
    .tr-counter {
        position: absolute;
        right: 14px; bottom: 14px;
        z-index: 4;
        background: rgba(17,27,72,.75);
        backdrop-filter: blur(4px);
        color: #fff;
        font-size: .78rem;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 999px;
        font-variant-numeric: tabular-nums;
        pointer-events: none;
    }
    .tr-expand {
        position: absolute;
        left: 14px; bottom: 14px;
        z-index: 5;
        background: rgba(255,255,255,.95);
        border: 0;
        color: var(--tr-navy);
        font-size: .78rem;
        font-weight: 700;
        padding: 6px 13px;
        border-radius: 999px;
        display: inline-flex; align-items: center; gap: 6px;
        transition: all .2s ease;
    }
    .tr-expand:hover { background: var(--tr-orange); color: #fff; }

    .tr-thumbs {
        display: flex;
        gap: 10px;
        margin-top: 10px;
        overflow-x: auto;
        padding: 2px 2px 4px;
        scrollbar-width: thin;
    }
    .tr-thumb {
        flex: 0 0 auto;
        width: 96px; height: 66px;
        padding: 0;
        border: 2px solid transparent;
        border-radius: 10px;
        overflow: hidden;
        background: #e8ebf3;
        opacity: .6;
        transition: opacity .2s ease, transform .25s var(--tr-ease), border-color .2s ease;
    }
    .tr-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .tr-thumb:hover { opacity: 1; transform: translateY(-3px); }
    .tr-thumb.is-active { border-color: var(--tr-orange); opacity: 1; }

    /* ---------- Lightbox ---------- */
    .tr-lightbox {
        position: fixed; inset: 0;
        z-index: 3000;
        background: rgba(10, 16, 46, .94);
        backdrop-filter: blur(6px);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity .3s ease, visibility .3s ease;
    }
    .tr-lightbox.is-open { opacity: 1; visibility: visible; }
    .tr-lightbox img {
        max-width: min(1200px, 92vw);
        max-height: 84vh;
        border-radius: 12px;
        box-shadow: 0 30px 80px rgba(0,0,0,.5);
        transform: scale(.96);
        transition: transform .35s var(--tr-ease), opacity .2s ease;
    }
    .tr-lightbox.is-open img { transform: scale(1); }
    .tr-lb-btn {
        position: absolute;
        width: 48px; height: 48px;
        border: 0;
        border-radius: 50%;
        background: rgba(255,255,255,.14);
        color: #fff;
        font-size: 1.2rem;
        display: flex; align-items: center; justify-content: center;
        transition: background .2s ease, transform .2s ease;
    }
    .tr-lb-btn:hover { background: var(--tr-orange); transform: scale(1.08); }
    .tr-lb-close { top: 20px; right: 20px; }
    .tr-lb-prev { left: 20px; top: 50%; margin-top: -24px; }
    .tr-lb-next { right: 20px; top: 50%; margin-top: -24px; }
    .tr-lb-count {
        position: absolute;
        bottom: 22px; left: 50%;
        transform: translateX(-50%);
        color: #fff;
        font-weight: 700;
        font-size: .85rem;
        background: rgba(255,255,255,.14);
        padding: 6px 16px;
        border-radius: 999px;
        font-variant-numeric: tabular-nums;
    }

    /* ---------- Heading ---------- */
    .tr-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: #0f8a4f;
        background: #e6f7ee;
        padding: 5px 13px;
        border-radius: 999px;
    }
    .tr-kicker::before {
        content: '';
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #12b76a;
        animation: trPulse 1.8s ease-out infinite;
    }
    @keyframes trPulse {
        0% { box-shadow: 0 0 0 0 rgba(18,183,106,.55); }
        100% { box-shadow: 0 0 0 10px rgba(18,183,106,0); }
    }
    .tr-unit-title {
        font-size: clamp(1.8rem, 3.4vw, 2.6rem);
        font-weight: 800;
        margin: 12px 0 4px;
        letter-spacing: -.045em;
    }
    .tr-rent-card {
        background: linear-gradient(135deg, var(--tr-navy), #2a3b93);
        color: #fff;
        border-radius: 16px;
        padding: 14px 22px;
        box-shadow: 0 14px 30px rgba(25,38,93,.28);
        text-align: right;
    }
    .tr-rent-card span {
        display: block;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #ffc9a6;
    }
    .tr-rent-card strong { font-size: 1.7rem; font-weight: 800; letter-spacing: -.04em; line-height: 1.1; }
    .tr-rent-card small { font-size: .85rem; font-weight: 500; opacity: .75; }

    /* ---------- Key facts ---------- */
    .tr-facts {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin: 0;
    }
    .tr-fact {
        background: var(--tr-surface);
        border: 1px solid var(--tr-line);
        border-radius: 14px;
        padding: 16px;
        transition: transform .3s var(--tr-ease), box-shadow .3s ease, border-color .3s ease;
    }
    .tr-fact:hover { transform: translateY(-5px); box-shadow: var(--tr-shadow-md); border-color: rgba(208,82,8,.4); }
    .tr-fact-icon {
        width: 38px; height: 38px;
        border-radius: 11px;
        background: var(--tr-orange-soft);
        color: var(--tr-orange);
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 10px;
        font-size: 1.05rem;
        transition: background .3s ease, color .3s ease;
    }
    .tr-fact:hover .tr-fact-icon { background: var(--tr-orange); color: #fff; }
    .tr-fact dt { font-size: .74rem; font-weight: 600; color: var(--tr-muted); margin-bottom: 2px; text-transform: uppercase; letter-spacing: .04em; }
    .tr-fact dd { margin: 0; font-weight: 800; font-size: 1.02rem; color: var(--tr-navy); }
    @media (max-width: 767px) { .tr-facts { grid-template-columns: 1fr 1fr; } }

    .tr-section-title { font-size: 1.2rem; font-weight: 800; margin-bottom: 10px; }

    .tr-cost {
        background: var(--tr-surface);
        border: 1px solid var(--tr-line);
        border-radius: 16px;
        padding: 20px 22px;
    }
    .tr-cost-row { display: flex; justify-content: space-between; gap: 16px; padding: 8px 0; font-size: .92rem; }
    .tr-cost-row + .tr-cost-row { border-top: 1px dashed var(--tr-line); }
    .tr-cost-row span { color: var(--tr-muted); font-weight: 500; }
    .tr-cost-row strong { color: var(--tr-navy); }
    .tr-cost-total { border-top: 2px solid var(--tr-navy) !important; margin-top: 4px; padding-top: 12px; }
    .tr-cost-total strong { color: var(--tr-orange); font-size: 1.08rem; }

    .tr-note {
        border-left: 4px solid var(--tr-orange);
        background: var(--tr-orange-soft);
        border-radius: 0 14px 14px 0;
        padding: 16px 20px;
        font-size: .9rem;
        color: #6b4a38;
    }
    .tr-note strong { color: var(--tr-orange-dark); }

    /* ---------- Application panel ---------- */
    .tr-apply {
        background: var(--tr-surface);
        border: 1px solid var(--tr-line);
        border-radius: 20px;
        box-shadow: var(--tr-shadow-lg);
        overflow: hidden;
    }
    @media (min-width: 992px) { .tr-apply-wrap { position: sticky; top: 92px; } }

    .tr-apply-top {
        background: linear-gradient(135deg, var(--tr-navy-deep), var(--tr-navy));
        color: #fff;
        padding: 22px 24px 20px;
        position: relative;
        overflow: hidden;
    }
    .tr-apply-top::after {
        content: "";
        position: absolute;
        right: -60px; top: -80px;
        width: 220px; height: 220px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(208,82,8,.5), transparent 68%);
    }
    .tr-apply-top > * { position: relative; z-index: 1; }
    .tr-apply-top h2 { color: #fff; }
    .tr-apply-top p { color: rgba(255,255,255,.72); }

    .tr-apply-body { padding: 22px 24px 26px; }

    /* Progress bar + stepper */
    .tr-bar { height: 5px; background: rgba(255,255,255,.18); border-radius: 99px; margin-top: 16px; overflow: hidden; }
    .tr-bar i { display: block; height: 100%; width: 33%; background: linear-gradient(90deg, #ff9a5c, #ffd2b6); border-radius: 99px; transition: width .5s var(--tr-ease); }

    .tr-stepper { display: flex; list-style: none; margin: 18px 0 0; padding: 0; }
    .tr-stepper li { flex: 1; position: relative; }
    .tr-stepper li::before {
        content: '';
        position: absolute;
        top: 15px;
        left: calc(-50% + 18px);
        right: calc(50% + 18px);
        height: 2px;
        background: rgba(255,255,255,.2);
        transition: background .4s ease;
    }
    .tr-stepper li:first-child::before { display: none; }
    .tr-stepper li.is-done::before,
    .tr-stepper li.is-current::before { background: #ff9a5c; }

    .tr-dot {
        display: flex; flex-direction: column; align-items: center; gap: 6px;
        width: 100%;
        background: none; border: 0; padding: 0;
        font-size: .74rem; font-weight: 700;
        color: rgba(255,255,255,.6);
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .tr-dot span {
        width: 32px; height: 32px;
        border-radius: 50%;
        border: 2px solid rgba(255,255,255,.28);
        background: transparent;
        display: flex; align-items: center; justify-content: center;
        font-size: .85rem;
        position: relative; z-index: 1;
        transition: all .35s var(--tr-ease);
    }
    .is-current .tr-dot { color: #fff; }
    .is-current .tr-dot span { border-color: #ff9a5c; background: var(--tr-orange); color: #fff; transform: scale(1.12); box-shadow: 0 0 0 5px rgba(255,154,92,.22); }
    .is-done .tr-dot span { background: #ff9a5c; border-color: #ff9a5c; color: var(--tr-navy-deep); }
    .is-done .tr-dot { cursor: pointer; color: #ffd2b6; }

    /* Form */
    .tr-step-title { font-size: 1.05rem; font-weight: 800; margin: 0 0 2px; }
    .tr-form .form-label { font-size: .8rem; font-weight: 700; margin-bottom: 6px; color: var(--tr-navy); }
    .tr-form .form-control, .tr-form .form-select { min-height: 46px; }
    .tr-form textarea.form-control { min-height: 0; }
    [data-step]:not([hidden]) { animation: trStepIn .45s var(--tr-ease); }
    @keyframes trStepIn { from { opacity: 0; transform: translateX(18px); } to { opacity: 1; transform: none; } }

    .tr-account {
        display: flex; align-items: center; gap: 12px;
        background: var(--tr-orange-soft);
        border: 1px solid #ffe0cc;
        border-radius: 12px;
        padding: 12px 14px;
        font-size: .88rem;
    }
    .tr-account i { font-size: 1.5rem; color: var(--tr-orange); }

    .tr-actions { display: flex; gap: 10px; margin-top: 24px; }
    .tr-actions .tr-btn { flex: 1; }
    .tr-actions .tr-btn-quiet { flex: 0 0 auto; }

    .tr-summary { border: 1px solid var(--tr-line); border-radius: 12px; margin: 16px 0 0; overflow: hidden; background: var(--tr-ground); }
    .tr-summary div { display: flex; justify-content: space-between; gap: 16px; padding: 10px 14px; font-size: .88rem; }
    .tr-summary div + div { border-top: 1px solid var(--tr-line); }
    .tr-summary dt { font-weight: 500; color: var(--tr-muted); }
    .tr-summary dd { margin: 0; font-weight: 700; text-align: right; overflow-wrap: anywhere; color: var(--tr-navy); }

    /* Guest panel */
    .tr-flow { list-style: none; padding: 0; margin: 16px 0 22px; counter-reset: flow; }
    .tr-flow li {
        counter-increment: flow;
        display: flex; gap: 12px; align-items: center;
        padding: 11px 0;
        border-top: 1px solid var(--tr-line);
        font-size: .92rem;
        font-weight: 600;
        transition: transform .25s var(--tr-ease), color .2s ease;
    }
    .tr-flow li:hover { transform: translateX(6px); color: var(--tr-orange); }
    .tr-flow li:first-child { border-top: 0; }
    .tr-flow li::before {
        content: counter(flow);
        flex: 0 0 28px; height: 28px;
        border-radius: 50%;
        background: var(--tr-orange-soft);
        color: var(--tr-orange);
        font-size: .8rem; font-weight: 800;
        display: inline-flex; align-items: center; justify-content: center;
    }

    /* Modal */
    .tr-modal .nav-pills .nav-link { color: var(--tr-ink); font-weight: 700; border-radius: 10px; }
    .tr-modal .nav-pills .nav-link.active { background: var(--tr-orange); color: #fff; }

    /* Mobile sticky CTA */
    .tr-mobile-cta {
        position: fixed;
        left: 0; right: 0; bottom: 0;
        z-index: 1200;
        background: rgba(255,255,255,.96);
        backdrop-filter: blur(12px);
        border-top: 1px solid var(--tr-line);
        box-shadow: 0 -10px 30px rgba(25,38,93,.1);
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        transform: translateY(100%);
        transition: transform .4s var(--tr-ease);
    }
    .tr-mobile-cta.is-visible { transform: none; }
    .tr-mobile-cta strong { color: var(--tr-navy); font-size: 1.05rem; font-weight: 800; letter-spacing: -.03em; display: block; line-height: 1.2; }
    .tr-mobile-cta small { color: var(--tr-muted); font-size: .72rem; }
    @media (min-width: 992px) { .tr-mobile-cta { display: none; } }
    @media (max-width: 991.98px) { .tr-page { padding-bottom: 76px; } }

    @media (prefers-reduced-motion: reduce) {
        .tr-kicker::before { animation: none; }
        [data-step]:not([hidden]) { animation: none; }
        .tr-stage:hover img { transform: none; }
    }
</style>
@endpush

@section('content')

@php
    $images = $unit->images->take(12);
    $imageUrls = $images->map(fn ($i) => upload_url($i->image_path))->values();
    $buildingName = $unit->building?->name ?? 'Rental property';
    $currency = $unit->currency ?? 'RWF';
    $bedrooms = (int) ($unit->bedrooms ?? 0);
    $bathrooms = (int) ($unit->bathrooms ?? 0);
    $rent = (float) $unit->rent_amount;
    $deposit = (float) ($unit->deposit_amount ?? 0);

    // After a failed submission, reopen the step that holds the first error.
    $startStep = 1;
    if ($errors->hasAny(['phone', 'alternative_phone', 'address'])) {
        $startStep = 1;
    } elseif ($errors->hasAny(['desired_move_in_date', 'occupants', 'employment_status'])) {
        $startStep = 2;
    } elseif ($errors->has('offered_rent')) {
        $startStep = 3;
    }
@endphp

<div class="tr-page">

    {{-- Breadcrumb / tools --}}
    <div class="tr-crumbs">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <a href="{{ route('rentals.index') }}" class="tr-back">
                    <i class="bi bi-arrow-left"></i> Back to rentals
                </a>
                @if($unit->building?->property && $unit->building->property->status === 'active')
                    <a href="{{ route('listings.show', $unit->building->property) }}" class="tr-back">
                        <i class="bi bi-buildings"></i> All units in {{ $unit->building->property->name }}
                    </a>
                @endif
            </div>
            <div class="tr-tools">
                <button type="button" class="tr-tool" id="shareBtn">
                    <i class="bi bi-share"></i> Share
                </button>
                <button type="button" class="tr-tool" id="saveBtn" data-unit="{{ $unit->id }}" aria-pressed="false">
                    <i class="bi bi-heart"></i> <span>Save</span>
                </button>
            </div>
        </div>
    </div>

    <div class="container py-4 py-lg-5">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Please review the following:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4 g-xl-5">

            {{-- ================= Unit information ================= --}}
            <div class="col-lg-7">

                {{-- Gallery --}}
                <div class="tr-gallery mb-4" data-aos="fade-up">
                    <div class="tr-stage" id="stage">
                        @if($images->isNotEmpty())
                            <div id="unitGallery" class="carousel slide"
                                 data-bs-interval="false" data-bs-ride="false"
                                 aria-label="Photos of {{ $buildingName }}, unit {{ $unit->unit_number }}">
                                <div class="carousel-inner">
                                    @foreach($images as $image)
                                        <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                            <img src="{{ upload_url($image->image_path) }}"
                                                 alt="{{ $buildingName }}, unit {{ $unit->unit_number }}, photo {{ $loop->iteration }}"
                                                 @if(!$loop->first) loading="lazy" @endif>
                                        </div>
                                    @endforeach
                                </div>

                                @if($images->count() > 1)
                                    <button class="tr-arrow prev" type="button"
                                            data-bs-target="#unitGallery" data-bs-slide="prev"
                                            aria-label="Previous photo">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <button class="tr-arrow next" type="button"
                                            data-bs-target="#unitGallery" data-bs-slide="next"
                                            aria-label="Next photo">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                    <span class="tr-counter" aria-hidden="true">
                                        <span id="galleryCurrent">1</span> / {{ $images->count() }}
                                    </span>
                                @endif
                            </div>

                            <button type="button" class="tr-expand" id="openLightbox">
                                <i class="bi bi-arrows-fullscreen"></i> View full size
                            </button>
                        @else
                            <div class="tr-nophoto">
                                <i class="bi bi-image"></i>
                                <span>No photos have been added for this unit yet</span>
                            </div>
                        @endif
                    </div>

                    @if($images->count() > 1)
                        <div class="tr-thumbs" role="group" aria-label="Choose a photo">
                            @foreach($images as $image)
                                <button type="button"
                                        class="tr-thumb {{ $loop->first ? 'is-active' : '' }}"
                                        data-bs-target="#unitGallery"
                                        data-bs-slide-to="{{ $loop->index }}"
                                        aria-label="Show photo {{ $loop->iteration }}">
                                    <img src="{{ upload_url($image->image_path) }}" alt="" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Heading --}}
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4" data-aos="fade-up">
                    <div>
                        <span class="tr-kicker">Available for rent</span>
                        <h1 class="tr-unit-title">{{ $buildingName }}</h1>
                        <div class="tr-muted fw-semibold">
                            Unit {{ $unit->unit_number }}
                            @if($unit->unit_type)
                                &ndash; {{ ucfirst(str_replace('_', ' ', $unit->unit_type)) }}
                            @endif
                        </div>
                    </div>

                    <div class="tr-rent-card">
                        <span>Monthly rent</span>
                        <strong>{{ $currency }} {{ number_format($rent, 0) }}</strong>
                        <small>/ month</small>
                    </div>
                </div>

                {{-- Key facts --}}
                <dl class="tr-facts mb-5">
                    <div class="tr-fact" data-aos="fade-up" data-aos-delay="0">
                        <div class="tr-fact-icon"><i class="bi bi-door-open"></i></div>
                        <dt>Bedrooms</dt>
                        <dd>{{ $bedrooms === 0 ? 'Studio' : $bedrooms }}</dd>
                    </div>
                    <div class="tr-fact" data-aos="fade-up" data-aos-delay="80">
                        <div class="tr-fact-icon"><i class="bi bi-droplet"></i></div>
                        <dt>Bathrooms</dt>
                        <dd>{{ $bathrooms }}</dd>
                    </div>
                    <div class="tr-fact" data-aos="fade-up" data-aos-delay="160">
                        <div class="tr-fact-icon"><i class="bi bi-rulers"></i></div>
                        <dt>Floor area</dt>
                        <dd>{{ $unit->area_sqm ? $unit->area_sqm . ' m²' : 'Not specified' }}</dd>
                    </div>
                    <div class="tr-fact" data-aos="fade-up" data-aos-delay="240">
                        <div class="tr-fact-icon"><i class="bi bi-shield-lock"></i></div>
                        <dt>Deposit</dt>
                        <dd>{{ $currency }} {{ number_format($deposit, 0) }}</dd>
                    </div>
                </dl>

                {{-- Description --}}
                <div class="mb-5" data-aos="fade-up">
                    <h2 class="tr-section-title">About this unit</h2>
                    <p class="tr-muted mb-0" style="max-width: 68ch; line-height: 1.8;">
                        {{ $unit->description
                            ?: 'Contact the property manager for more information about this unit.' }}
                    </p>
                </div>

                {{-- Move-in cost --}}
                <div class="mb-4" data-aos="fade-up">
                    <h2 class="tr-section-title">Cost to move in</h2>
                    <div class="tr-cost">
                        <div class="tr-cost-row">
                            <span>First month's rent</span>
                            <strong>{{ $currency }} {{ number_format($rent, 0) }}</strong>
                        </div>
                        <div class="tr-cost-row">
                            <span>Security deposit</span>
                            <strong>{{ $currency }} {{ number_format($deposit, 0) }}</strong>
                        </div>
                        <div class="tr-cost-row tr-cost-total">
                            <span class="fw-bold">Estimated total</span>
                            <strong>{{ $currency }} {{ number_format($rent + $deposit, 0) }}</strong>
                        </div>
                    </div>
                </div>

                <div class="tr-note" data-aos="fade-up">
                    <strong><i class="bi bi-info-circle me-1"></i>Before you apply.</strong>
                    Check the monthly rent, security deposit and unit details. The
                    property manager will review your application and send the
                    decision through Terra.
                </div>
            </div>

            {{-- ================= Application panel ================= --}}
            <div class="col-lg-5" id="applyPanel">
                <div class="tr-apply-wrap" data-aos="fade-left" data-aos-delay="150">
                    <div class="tr-apply">

                        @auth
                            <div class="tr-apply-top">
                                <h2 class="h5 fw-bold mb-1">Apply for this unit</h2>
                                <p class="small mb-0" id="stepLabel" aria-live="polite">Step 1 of 3</p>

                                <div class="tr-bar" aria-hidden="true"><i id="stepBar"></i></div>

                                <ol class="tr-stepper" aria-label="Application progress">
                                    <li data-step-dot>
                                        <button type="button" class="tr-dot" tabindex="-1"><span>1</span>Contact</button>
                                    </li>
                                    <li data-step-dot>
                                        <button type="button" class="tr-dot" tabindex="-1"><span>2</span>Tenancy</button>
                                    </li>
                                    <li data-step-dot>
                                        <button type="button" class="tr-dot" tabindex="-1"><span>3</span>Offer</button>
                                    </li>
                                </ol>
                            </div>

                            <div class="tr-apply-body">
                                <form method="POST"
                                      action="{{ route('rentals.apply', $unit) }}"
                                      id="applicationForm"
                                      class="tr-form"
                                      data-start="{{ $startStep }}"
                                      data-currency="{{ $currency }}">
                                    @csrf

                                    {{-- Step 1: contact --}}
                                    <div data-step>
                                        <h3 class="tr-step-title">Your contact details</h3>
                                        <p class="small tr-muted mb-3">
                                            {{ $tenant
                                                ? 'We will use your existing tenant profile.'
                                                : 'A tenant profile is created when you submit your first application.' }}
                                        </p>

                                        <div class="tr-account mb-3">
                                            <i class="bi bi-person-check"></i>
                                            <div>
                                                <div class="fw-bold">{{ auth()->user()->name }}</div>
                                                <div class="tr-muted">{{ auth()->user()->email }}</div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="phone" class="form-label">
                                                Phone number <span class="text-danger">*</span>
                                            </label>
                                            <input type="tel" id="phone" name="phone" data-intl-phone
                                                   class="form-control @error('phone') is-invalid @enderror"
                                                   value="{{ old('phone', $tenant?->phone) }}"
                                                   maxlength="30" autocomplete="tel" required>
                                            @error('phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="alternative_phone" class="form-label">Alternative phone</label>
                                            <input type="tel" id="alternative_phone" name="alternative_phone"
                                                   class="form-control @error('alternative_phone') is-invalid @enderror"
                                                   value="{{ old('alternative_phone', $tenant?->alternative_phone) }}"
                                                   maxlength="30">
                                            @error('alternative_phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-1">
                                            <label for="address" class="form-label">Residential address</label>
                                            <textarea id="address" name="address" rows="2"
                                                      class="form-control @error('address') is-invalid @enderror"
                                                      maxlength="2000" autocomplete="street-address"
                                                      placeholder="Where you live now">{{ old('address', $tenant?->address) }}</textarea>
                                            @error('address')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Step 2: tenancy --}}
                                    <div data-step hidden>
                                        <h3 class="tr-step-title">Your tenancy</h3>
                                        <p class="small tr-muted mb-3">
                                            Tell the property manager when you want to move in and who will live here.
                                        </p>

                                        <div class="mb-3">
                                            <label for="desired_move_in_date" class="form-label">
                                                Desired move-in date <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" id="desired_move_in_date" name="desired_move_in_date"
                                                   class="form-control @error('desired_move_in_date') is-invalid @enderror"
                                                   min="{{ now()->toDateString() }}"
                                                   value="{{ old('desired_move_in_date') }}" required>
                                            @error('desired_move_in_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="occupants" class="form-label">
                                                Number of occupants <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" id="occupants" name="occupants"
                                                   class="form-control @error('occupants') is-invalid @enderror"
                                                   min="1" max="50"
                                                   value="{{ old('occupants', 1) }}" required>
                                            @error('occupants')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-1">
                                            <label for="employment_status" class="form-label">Employment status</label>
                                            <select id="employment_status" name="employment_status"
                                                    class="form-select @error('employment_status') is-invalid @enderror">
                                                <option value="">Select employment status</option>
                                                @foreach(['Employed', 'Self-employed', 'Business owner', 'Student', 'Unemployed', 'Other'] as $employment)
                                                    <option value="{{ $employment }}"
                                                        @selected(old('employment_status', $tenantApplicationEmploymentStatus ?? '') === $employment)>
                                                        {{ $employment }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('employment_status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Step 3: offer and review --}}
                                    <div data-step hidden>
                                        <h3 class="tr-step-title">Your offer</h3>
                                        <p class="small tr-muted mb-3">
                                            Confirm the monthly rent you are offering, then review your answers.
                                        </p>

                                        <div class="mb-1">
                                            <label for="offered_rent" class="form-label">
                                                Offered monthly rent ({{ $currency }})
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" id="offered_rent" name="offered_rent"
                                                   class="form-control @error('offered_rent') is-invalid @enderror"
                                                   min="0.01" step="0.01"
                                                   value="{{ old('offered_rent', $unit->rent_amount) }}" required>
                                            @error('offered_rent')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">
                                                Listed rent: {{ $currency }} {{ number_format($rent, 0) }}
                                            </div>
                                        </div>

                                        <dl class="tr-summary" id="applicationSummary"></dl>

                                        <p class="small tr-muted mt-3 mb-0">
                                            Submitting does not guarantee the unit will be allocated to you.
                                            The property manager reviews every application.
                                        </p>
                                    </div>

                                    <div class="tr-actions">
                                        <button type="button" class="btn tr-btn-quiet" id="stepBack" hidden>
                                            <i class="bi bi-arrow-left me-1"></i>Back
                                        </button>
                                        <button type="button" class="btn tr-btn" id="stepNext">
                                            Continue <i class="bi bi-arrow-right ms-1"></i>
                                        </button>
                                        <button type="submit" class="btn tr-btn" id="stepSubmit" hidden>
                                            <i class="bi bi-send me-2"></i>Submit application
                                        </button>
                                    </div>
                                </form>
                            </div>

                        @else

                            <div class="tr-apply-top">
                                <h2 class="h5 fw-bold mb-1">Apply for this unit</h2>
                                <p class="small mb-0">Sign in or create an account to start your application.</p>
                            </div>

                            <div class="tr-apply-body">
                                <p class="small tr-muted mb-0">
                                    An account lets you submit and track your application, receive the
                                    property manager's decision and see your rental details in your tenant portal.
                                </p>

                                <ol class="tr-flow">
                                    <li>Contact details</li>
                                    <li>Move-in date and household</li>
                                    <li>Your monthly rent offer</li>
                                </ol>

                                <div class="d-grid gap-2">
                                    <button type="button" class="btn tr-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#applicationAuthModal"
                                            data-auth-tab="applicationRegister">
                                        Create an account to apply
                                    </button>

                                    <button type="button" class="btn tr-btn-line"
                                            data-bs-toggle="modal"
                                            data-bs-target="#applicationAuthModal"
                                            data-auth-tab="applicationLogin">
                                        I already have an account
                                    </button>
                                </div>
                            </div>

                        @endauth

                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Mobile sticky CTA --}}
    <div class="tr-mobile-cta" id="mobileCta">
        <div>
            <strong>{{ $currency }} {{ number_format($rent, 0) }}</strong>
            <small>per month &middot; Unit {{ $unit->unit_number }}</small>
        </div>
        <a href="#applyPanel" class="btn tr-btn py-2 px-4">Apply now</a>
    </div>

    {{-- Lightbox --}}
    @if($images->isNotEmpty())
        <div class="tr-lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Photo viewer">
            <button type="button" class="tr-lb-btn tr-lb-close" id="lbClose" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            @if($images->count() > 1)
                <button type="button" class="tr-lb-btn tr-lb-prev" id="lbPrev" aria-label="Previous photo"><i class="bi bi-chevron-left"></i></button>
                <button type="button" class="tr-lb-btn tr-lb-next" id="lbNext" aria-label="Next photo"><i class="bi bi-chevron-right"></i></button>
            @endif
            <img id="lbImage" src="" alt="{{ $buildingName }}, unit {{ $unit->unit_number }}">
            <div class="tr-lb-count"><span id="lbCurrent">1</span> / {{ $images->count() }}</div>
        </div>
    @endif

    {{-- ================= Login / registration modal ================= --}}
    @guest
    <div class="modal fade tr-modal"
         id="applicationAuthModal"
         tabindex="-1"
         aria-labelledby="applicationAuthModalLabel"
         aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold" id="applicationAuthModalLabel">
                            Continue your rental application
                        </h5>
                        <p class="text-muted small mb-0 mt-1">
                            {{ $buildingName }} &ndash; Unit {{ $unit->unit_number }}
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">

                    <ul class="nav nav-pills nav-fill mb-4" id="applicationAuthTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active"
                                    id="applicationLoginTab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#applicationLogin"
                                    type="button" role="tab"
                                    aria-controls="applicationLogin"
                                    aria-selected="true">
                                Login
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link"
                                    id="applicationRegisterTab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#applicationRegister"
                                    type="button" role="tab"
                                    aria-controls="applicationRegister"
                                    aria-selected="false">
                                Sign up
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">

                        {{-- Login --}}
                        <div class="tab-pane fade show active"
                             id="applicationLogin" role="tabpanel"
                             aria-labelledby="applicationLoginTab" tabindex="0">

                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                <div class="mb-3">
                                    <label for="application_login_email" class="form-label fw-semibold small">Email address</label>
                                    <input type="email" id="application_login_email" name="email"
                                           class="form-control" value="{{ old('email') }}"
                                           required autocomplete="username">
                                </div>

                                <div class="mb-3">
                                    <label for="application_login_password" class="form-label fw-semibold small">Password</label>
                                    <input type="password" id="application_login_password" name="password"
                                           class="form-control" required autocomplete="current-password">
                                </div>

                                <div class="form-check mb-3">
                                    <input type="checkbox" name="remember" value="1"
                                           class="form-check-input" id="applicationRemember">
                                    <label class="form-check-label" for="applicationRemember">Remember me</label>
                                </div>

                                <button type="submit" class="btn tr-btn w-100">Log in and continue</button>
                            </form>
                        </div>

                        {{-- Registration --}}
                        <div class="tab-pane fade"
                             id="applicationRegister" role="tabpanel"
                             aria-labelledby="applicationRegisterTab" tabindex="0">

                            <p class="small text-muted">
                                Your tenant profile is created or linked when you submit your first application.
                            </p>

                            <form method="POST" action="{{ route('register') }}">
                                @csrf

                                <div class="mb-3">
                                    <label for="application_register_name" class="form-label fw-semibold small">Full name</label>
                                    <input type="text" id="application_register_name" name="name"
                                           class="form-control" value="{{ old('name') }}"
                                           required maxlength="255" autocomplete="name">
                                </div>

                                <div class="mb-3">
                                    <label for="application_register_email" class="form-label fw-semibold small">Email address</label>
                                    <input type="email" id="application_register_email" name="email"
                                           class="form-control" value="{{ old('email') }}"
                                           required autocomplete="email">
                                </div>

                                <div class="mb-3">
                                    <label for="application_register_password" class="form-label fw-semibold small">Password</label>
                                    <input type="password" id="application_register_password" name="password"
                                           class="form-control" required minlength="8" autocomplete="new-password">
                                </div>

                                <div class="mb-3">
                                    <label for="application_register_password_confirmation" class="form-label fw-semibold small">
                                        Confirm password
                                    </label>
                                    <input type="password" id="application_register_password_confirmation"
                                           name="password_confirmation" class="form-control"
                                           required minlength="8" autocomplete="new-password">
                                </div>

                                <button type="submit" class="btn tr-btn w-100">Create account and continue</button>
                            </form>
                        </div>
                    </div>

                    <p class="text-muted small text-center mt-4 mb-0">
                        After logging in or creating an account, return to this page to finish your application.
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endguest
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const photos = @json($imageUrls);

    /* ---------- Gallery: counter and thumbnail sync ---------- */
    const gallery = document.getElementById('unitGallery');
    let galleryIndex = 0;

    if (gallery) {
        const counter = document.getElementById('galleryCurrent');
        const thumbs = document.querySelectorAll('.tr-thumb');

        gallery.addEventListener('slid.bs.carousel', function (event) {
            galleryIndex = event.to;
            if (counter) counter.textContent = event.to + 1;

            thumbs.forEach(function (thumb, index) {
                thumb.classList.toggle('is-active', index === event.to);
            });

            const active = thumbs[event.to];
            if (active) active.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
        });
    }

    /* ---------- Lightbox ---------- */
    const lightbox = document.getElementById('lightbox');
    if (lightbox && photos.length) {
        const lbImage = document.getElementById('lbImage');
        const lbCurrent = document.getElementById('lbCurrent');
        let lbIndex = 0;

        function lbShow(index) {
            lbIndex = (index + photos.length) % photos.length;
            lbImage.src = photos[lbIndex];
            lbCurrent.textContent = lbIndex + 1;
        }
        function lbOpen(index) {
            lbShow(index);
            lightbox.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
        function lbClose() {
            lightbox.classList.remove('is-open');
            document.body.style.overflow = '';
            if (gallery && window.bootstrap) {
                bootstrap.Carousel.getOrCreateInstance(gallery).to(lbIndex);
            }
        }

        document.getElementById('openLightbox').addEventListener('click', function (e) {
            e.stopPropagation();
            lbOpen(galleryIndex);
        });
        document.getElementById('stage').addEventListener('click', function (e) {
            if (e.target.closest('.tr-arrow, .tr-expand')) return;
            lbOpen(galleryIndex);
        });
        document.getElementById('lbClose').addEventListener('click', lbClose);
        lightbox.addEventListener('click', function (e) { if (e.target === lightbox) lbClose(); });

        const prev = document.getElementById('lbPrev');
        const next = document.getElementById('lbNext');
        if (prev) prev.addEventListener('click', function () { lbShow(lbIndex - 1); });
        if (next) next.addEventListener('click', function () { lbShow(lbIndex + 1); });

        document.addEventListener('keydown', function (e) {
            if (!lightbox.classList.contains('is-open')) return;
            if (e.key === 'Escape') lbClose();
            if (e.key === 'ArrowLeft') lbShow(lbIndex - 1);
            if (e.key === 'ArrowRight') lbShow(lbIndex + 1);
        });
    }

    /* ---------- Share and save ---------- */
    const shareBtn = document.getElementById('shareBtn');
    if (shareBtn) {
        shareBtn.addEventListener('click', async function () {
            const data = {
                title: document.title,
                url: window.location.href
            };
            try {
                if (navigator.share) {
                    await navigator.share(data);
                } else {
                    await navigator.clipboard.writeText(data.url);
                    if (window.trToast) window.trToast('Link copied to clipboard');
                }
            } catch (e) { /* share dismissed */ }
        });
    }

    const saveBtn = document.getElementById('saveBtn');
    if (saveBtn) {
        const KEY = 'terra_saved_units';
        const id = saveBtn.dataset.unit;
        const read = function () { try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; } };
        const write = function (list) { try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {} };
        const icon = saveBtn.querySelector('i');
        const text = saveBtn.querySelector('span');

        const paint = function (on) {
            saveBtn.classList.toggle('is-saved', on);
            saveBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
            icon.classList.toggle('bi-heart-fill', on);
            icon.classList.toggle('bi-heart', !on);
            text.textContent = on ? 'Saved' : 'Save';
        };
        paint(read().includes(id));

        saveBtn.addEventListener('click', function () {
            let list = read();
            const on = !list.includes(id);
            list = on ? list.concat(id) : list.filter(function (x) { return x !== id; });
            write(list);
            paint(on);
            saveBtn.classList.remove('pop');
            void saveBtn.offsetWidth;
            saveBtn.classList.add('pop');
            if (window.trToast) window.trToast(on ? 'Saved to your shortlist' : 'Removed from your shortlist');
        });
    }

    /* ---------- Mobile CTA: show after scrolling past the gallery, hide at the form ---------- */
    const cta = document.getElementById('mobileCta');
    const panel = document.getElementById('applyPanel');
    if (cta && panel && 'IntersectionObserver' in window) {
        let panelVisible = false;
        const update = function () {
            const scrolled = window.scrollY > 380;
            cta.classList.toggle('is-visible', scrolled && !panelVisible);
        };
        new IntersectionObserver(function (entries) {
            panelVisible = entries[0].isIntersecting;
            update();
        }, { threshold: 0.15 }).observe(panel);
        window.addEventListener('scroll', update, { passive: true });
        update();
    }

    /* ---------- Multistep application form ---------- */
    const form = document.getElementById('applicationForm');

    if (form) {
        const steps = Array.from(form.querySelectorAll('[data-step]'));
        const dots = Array.from(document.querySelectorAll('[data-step-dot]'));
        const label = document.getElementById('stepLabel');
        const bar = document.getElementById('stepBar');
        const backBtn = document.getElementById('stepBack');
        const nextBtn = document.getElementById('stepNext');
        const submitBtn = document.getElementById('stepSubmit');
        const summary = document.getElementById('applicationSummary');
        const currency = form.dataset.currency || 'RWF';
        const titles = ['Contact', 'Tenancy', 'Offer'];
        const last = steps.length - 1;
        let current = 0;

        function field(id) { return document.getElementById(id); }

        function validStep(index) {
            const inputs = steps[index].querySelectorAll('input, select, textarea');
            for (const input of inputs) {
                if (!input.checkValidity()) {
                    input.reportValidity();
                    return false;
                }
            }
            return true;
        }

        function addRow(term, value) {
            const row = document.createElement('div');
            const dt = document.createElement('dt');
            const dd = document.createElement('dd');
            dt.textContent = term;
            dd.textContent = value || 'Not provided';
            row.append(dt, dd);
            summary.appendChild(row);
        }

        function fillSummary() {
            summary.replaceChildren();

            const date = field('desired_move_in_date').value;
            const employment = field('employment_status');
            const rent = parseFloat(field('offered_rent').value);

            addRow('Phone', field('phone').value.trim());
            addRow('Address', field('address').value.trim());
            addRow('Move-in date', date
                ? new Date(date + 'T00:00:00').toLocaleDateString('en-GB', {
                    day: 'numeric', month: 'long', year: 'numeric'
                })
                : '');
            addRow('Occupants', field('occupants').value);
            addRow('Employment', employment.value ? employment.options[employment.selectedIndex].text : '');
            addRow('Offered rent', isNaN(rent)
                ? ''
                : currency + ' ' + rent.toLocaleString('en-US') + ' / month');
        }

        function show(index) {
            current = index;

            steps.forEach(function (step, i) { step.hidden = i !== index; });

            dots.forEach(function (dot, i) {
                dot.classList.toggle('is-current', i === index);
                dot.classList.toggle('is-done', i < index);
                if (i === index) {
                    dot.setAttribute('aria-current', 'step');
                } else {
                    dot.removeAttribute('aria-current');
                }
            });

            label.textContent = 'Step ' + (index + 1) + ' of ' + steps.length + ': ' + titles[index];
            if (bar) bar.style.width = (((index + 1) / steps.length) * 100) + '%';
            backBtn.hidden = index === 0;
            nextBtn.hidden = index === last;
            submitBtn.hidden = index !== last;

            if (index === last) fillSummary();
        }

        nextBtn.addEventListener('click', function () {
            if (validStep(current)) show(current + 1);
        });

        backBtn.addEventListener('click', function () { show(current - 1); });

        // Completed steps in the progress bar can be clicked to go back.
        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () {
                if (i < current) show(i);
            });
        });

        // Enter moves to the next step instead of submitting early.
        form.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' || event.target.tagName === 'TEXTAREA') return;
            if (current < last) {
                event.preventDefault();
                nextBtn.click();
            }
        });

        // Final check across every step before the form is sent.
        form.addEventListener('submit', function (event) {
            for (let i = 0; i < steps.length; i++) {
                if (!validStep(i)) {
                    event.preventDefault();
                    show(i);
                    steps[i].querySelector(':invalid')?.reportValidity();
                    return;
                }
            }
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Submitting…';
        });

        show(Math.max(0, (parseInt(form.dataset.start, 10) || 1) - 1));
    }

    /* ---------- Login / sign-up modal ---------- */
    const authModal = document.getElementById('applicationAuthModal');

    if (authModal && window.bootstrap) {
        authModal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            const tabId = trigger?.getAttribute('data-auth-tab');
            if (!tabId) return;

            const tabButton = document.getElementById(
                tabId === 'applicationRegister'
                    ? 'applicationRegisterTab'
                    : 'applicationLoginTab'
            );

            if (tabButton) bootstrap.Tab.getOrCreateInstance(tabButton).show();
        });

        // Reopen the modal on the right tab after a login or registration error.
        @if($errors->any() && !auth()->check())
            bootstrap.Modal.getOrCreateInstance(authModal).show();

            @if(old('name') !== null)
                bootstrap.Tab.getOrCreateInstance(
                    document.getElementById('applicationRegisterTab')
                ).show();
            @else
                bootstrap.Tab.getOrCreateInstance(
                    document.getElementById('applicationLoginTab')
                ).show();
            @endif
        @endif
    }
});
</script>
@endpush
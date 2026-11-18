@extends('layouts.base')

@php
    $cover = $property->images->first();
    $plainDescription = \Illuminate\Support\Str::limit(strip_tags($property->description ?? ''), 155)
        ?: $property->name . ' — rental units in ' . ($property->location_line ?: 'Rwanda') . '.';
@endphp

@section('title', $property->name . ' | Terra Smart Rental Management')
@section('meta_description', $plainDescription)
@section('og_title', $property->name)
@section('og_description', $plainDescription)
@if($cover)
    @section('og_image', upload_url($cover->image_path))
@endif

@push('styles')
<style>
    .tpp-crumbs { padding: 14px 0; border-bottom: 1px solid var(--tr-line); background: var(--tr-surface); font-size: .85rem; }
    .tpp-crumbs a { color: var(--tr-muted); text-decoration: none; font-weight: 600; }
    .tpp-crumbs a:hover { color: var(--tr-orange); }
    .tpp-crumbs .sep { color: #b6bccb; margin: 0 8px; }
    .tpp-crumbs .here { color: var(--tr-navy); font-weight: 700; }
    .tpp-section-title { font-size: 1.15rem; font-weight: 800; color: var(--tr-navy); margin-bottom: 12px; }
    .tpp-box { border: 1px solid var(--tr-line); border-radius: 16px; padding: 20px; background: #fff; height: 100%; }
    .tpp-dl { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; margin: 0; }
    .tpp-dl dt { font-size: .74rem; text-transform: uppercase; letter-spacing: .06em; color: var(--tr-muted); font-weight: 700; }
    .tpp-dl dd { margin: 2px 0 0; font-weight: 600; color: var(--tr-ink); }
    .tpp-map { border: 0; width: 100%; height: 260px; border-radius: 12px; }
</style>
@endpush

@section('content')
<div class="tpp-crumbs">
    <div class="container">
        <a href="{{ url('/') }}">Home</a><span class="sep">/</span>
        <a href="{{ route('listings.index') }}">Properties</a><span class="sep">/</span>
        <span class="here">{{ $property->name }}</span>
    </div>
</div>

<div class="container py-4 py-lg-5">
    <x-property-showcase :property="$property" mode="public" />

    <div class="row g-4 mt-2">
        <div class="col-lg-7">
            <div class="tpp-box">
                <h2 class="tpp-section-title">About this property</h2>
                <p class="mb-4" style="line-height:1.8; color:#3b4357;">
                    {{ $property->description ?: 'Contact the property manager for more information about this property.' }}
                </p>
                <dl class="tpp-dl">
                    <div><dt>Property type</dt><dd>{{ ucfirst(str_replace('_', ' ', $property->property_type)) }}</dd></div>
                    @if($property->year_built)<div><dt>Year built</dt><dd>{{ $property->year_built }}</dd></div>@endif
                    <div><dt>Buildings</dt><dd>{{ $property->buildings->count() }}</dd></div>
                    @foreach(['province' => 'Province', 'district' => 'District', 'sector' => 'Sector', 'cell' => 'Cell'] as $field => $label)
                        @if($property->$field)<div><dt>{{ $label }}</dt><dd>{{ $property->$field }}</dd></div>@endif
                    @endforeach
                    @if($property->upi)<div><dt>UPI</dt><dd>{{ $property->upi }}</dd></div>@endif
                </dl>
                @if($property->video_url)
                    <a href="{{ $property->video_url }}" target="_blank" rel="noopener" class="btn btn-outline-primary rounded-pill mt-4">
                        <i class="bi bi-play-circle me-1"></i> Watch video tour
                    </a>
                @endif
            </div>
        </div>
        <div class="col-lg-5">
            <div class="tpp-box">
                <h2 class="tpp-section-title">Location</h2>
                <p class="mb-3"><i class="bi bi-geo-alt-fill text-primary me-1"></i>
                    {{ collect([$property->address, $property->village, $property->cell, $property->sector, $property->district, $property->province])->filter()->implode(', ') ?: 'Location details available on request.' }}
                </p>
                @if($property->latitude !== null && $property->longitude !== null)
                    <iframe class="tpp-map" loading="lazy" title="Map of {{ $property->name }}"
                        src="https://maps.google.com/maps?q={{ $property->latitude }},{{ $property->longitude }}&z=15&output=embed"></iframe>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.base')

@section('title', 'Properties for rent | Terra Smart Rental Management')
@section('meta_description', 'Browse apartment buildings and rental properties in Rwanda, see every unit and its availability, and apply online with Terra.')

@push('styles')
<style>
    .tpl-hero { background: var(--tr-navy-soft); padding: 36px 0 28px; border-bottom: 1px solid var(--tr-line); }
    .tpl-hero h1 { font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 800; margin-bottom: 6px; }
    .tpl-search { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) auto auto; gap: 10px; margin-top: 18px; }
    @media (max-width: 767.98px) { .tpl-search { grid-template-columns: 1fr; } }
    .tpl-search .form-control, .tpl-search .form-select { min-height: 48px; border-radius: 12px; }
    .tpl-search .btn { min-height: 48px; border-radius: 12px; font-weight: 700; padding: 0 22px; }
    .tpl-card { display: block; height: 100%; border: 1px solid var(--tr-line); border-radius: 16px; overflow: hidden; background: #fff;
        text-decoration: none; color: inherit; transition: transform .2s var(--tr-ease), box-shadow .2s var(--tr-ease); }
    .tpl-card:hover { transform: translateY(-4px); box-shadow: var(--tr-shadow-md); color: inherit; }
    .tpl-img { position: relative; aspect-ratio: 16 / 10; background: #e7ebf3; }
    .tpl-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .tpl-img .tpl-noimg { position: absolute; inset: 0; display: grid; place-items: center; color: var(--tr-muted); font-size: 2rem; }
    .tpl-units { position: absolute; top: 12px; left: 12px; background: rgba(17,24,52,.82); color: #fff; font-weight: 700; font-size: .78rem; padding: 6px 10px; border-radius: 8px; }
    .tpl-avail { position: absolute; top: 12px; right: 12px; background: #16a34a; color: #fff; font-weight: 700; font-size: .75rem; padding: 5px 10px; border-radius: 999px; }
    .tpl-body { padding: 16px 18px 18px; }
    .tpl-loc { font-size: .82rem; color: var(--tr-muted); font-weight: 600; }
    .tpl-name { font-size: 1.1rem; font-weight: 800; color: var(--tr-navy); margin: 4px 0 10px; }
    .tpl-price { color: var(--tr-orange); font-weight: 700; }
    .tpl-price strong { font-size: 1.15rem; }
</style>
@endpush

@section('content')
<section class="tpl-hero">
    <div class="container">
        <h1>Properties for rent</h1>
        <p class="text-secondary mb-0">See whole buildings, check which units are free, and apply for the one you like.</p>

        <form method="GET" action="{{ route('listings.index') }}" class="tpl-search" role="search">
            <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name, sector or district" aria-label="Search properties">
            <select name="district" class="form-select" aria-label="District">
                <option value="">All districts</option>
                @foreach($districts as $district)
                    <option value="{{ $district }}" @selected(request('district') === $district)>{{ $district }}</option>
                @endforeach
            </select>
            <label class="d-flex align-items-center gap-2 px-2 fw-semibold small">
                <input type="checkbox" name="available" value="1" class="form-check-input m-0" @checked(request()->boolean('available'))> Available now
            </label>
            <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Search</button>
        </form>
    </div>
</section>

<div class="container py-4 py-lg-5">
    <p class="text-secondary small mb-3">{{ number_format($properties->total()) }} {{ Str::plural('property', $properties->total()) }} found</p>

    <div class="row g-4">
        @forelse($properties as $property)
            @php $img = $property->images->first() ?? $property->buildings->flatMap->images->first(); @endphp
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('listings.show', $property) }}" class="tpl-card">
                    <div class="tpl-img">
                        @if($img)
                            <img src="{{ asset('storage/' . $img->image_path) }}" alt="{{ $property->name }}" loading="lazy">
                        @else
                            <span class="tpl-noimg"><i class="bi bi-building"></i></span>
                        @endif
                        <span class="tpl-units"><i class="bi bi-building me-1"></i>{{ $property->units_listed_count }} {{ Str::plural('Unit', $property->units_listed_count) }}</span>
                        @if($property->units_available_count)
                            <span class="tpl-avail">{{ $property->units_available_count }} available</span>
                        @endif
                    </div>
                    <div class="tpl-body">
                        <div class="tpl-loc"><i class="bi bi-geo-alt-fill me-1"></i>{{ $property->location_line ?: 'Rwanda' }}</div>
                        <div class="tpl-name">{{ $property->name }}</div>
                        @if($property->rent_from)
                            <div class="tpl-price">From <strong>RWF {{ number_format((float) $property->rent_from, 0) }}</strong> / month</div>
                        @else
                            <div class="text-secondary small fw-semibold">Fully let — check back soon</div>
                        @endif
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12 text-center py-5 text-secondary">
                <i class="bi bi-buildings fs-1 d-block mb-2"></i>
                No properties match your search.
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $properties->links() }}</div>
</div>
@endsection

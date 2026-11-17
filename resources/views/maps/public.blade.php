@extends('layouts.base')

@section('title', __('Map of rentals') . ' | Terra Smart Rental Management')
@section('meta_description', 'Find rental properties in Rwanda on a map and see which units are available.')

@include('maps._leaflet')

@section('content')
<section style="background:var(--tr-navy-soft);padding:28px 0 20px;border-bottom:1px solid var(--tr-line);">
    <div class="container">
        <h1 style="font-size:clamp(1.5rem,3vw,2rem);font-weight:800;">{{ __('Find a home on the map') }}</h1>
        <form class="d-flex flex-wrap gap-2 mt-3" id="mapFilters" role="search">
            <input type="search" name="q" class="form-control" style="max-width:280px" placeholder="{{ __('Search by name, sector or district') }}" aria-label="{{ __('Search') }}">
            <select name="district" class="form-select" style="max-width:220px" aria-label="{{ __('District') }}">
                <option value="">{{ __('All districts') }}</option>
                @foreach($districts as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach
            </select>
            <label class="d-flex align-items-center gap-2 px-2 fw-semibold small"><input type="checkbox" name="available" value="1" class="form-check-input m-0" checked> {{ __('Available now') }}</label>
            <button class="btn btn-primary">{{ __('Search') }}</button>
            <a href="{{ route('listings.index') }}" class="btn btn-outline-secondary ms-auto"><i class="bi bi-grid me-1"></i> {{ __('List view') }}</a>
        </form>
    </div>
</section>

<div class="container py-4">
    <div class="d-flex justify-content-between small text-secondary mb-2">
        <span id="mapCount" aria-live="polite"></span>
        <span><span style="color:#16a34a">●</span> {{ __('Units available') }} &nbsp; <span style="color:#8a91a3">●</span> {{ __('Fully let') }}</span>
    </div>
    <div id="map" class="terra-map" role="region" aria-label="{{ __('Map of rentals') }}"></div>
    <p class="small text-secondary mt-2">{{ __('Only properties with a recorded location appear on the map.') }}</p>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var map = TerraMap.create('map');
    var layer = L.markerClusterGroup({ showCoverageOnHover: false }).addTo(map);
    var form = document.getElementById('mapFilters'), count = document.getElementById('mapCount');
    var t = { units: @json(__('units available')), from: @json(__('from')), view: @json(__('View property')), none: @json(__('No properties match.')) , shown: @json(__('properties on the map')) };

    function load() {
        var params = new URLSearchParams(new FormData(form));
        if (!form.available.checked) params.delete('available');
        fetch(@json(route('api.listings')) + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                layer.clearLayers();
                var pts = (res.data || []).filter(function (p) { return p.latitude !== null && p.longitude !== null; });
                pts.forEach(function (p) {
                    var html = '<div class="terra-pop">' + (p.image ? '<img src="' + TerraMap.esc(p.image) + '" alt="">' : '')
                        + '<strong>' + TerraMap.esc(p.name) + '</strong><div class="small text-secondary">' + TerraMap.esc(p.location) + '</div>'
                        + '<div class="small mt-1">' + p.units_available + ' ' + t.units + (p.rent_from ? ' · ' + t.from + ' RWF ' + Math.round(p.rent_from).toLocaleString('en-US') : '') + '</div>'
                        + '<a class="btn btn-sm btn-primary mt-2 w-100" href="' + TerraMap.esc(p.url) + '">' + t.view + '</a></div>';
                    L.marker([p.latitude, p.longitude], { icon: TerraMap.pin(p.units_available > 0 ? '#16a34a' : '#8a91a3', p.units_available || '') })
                        .bindPopup(html).addTo(layer);
                });
                count.textContent = pts.length ? pts.length + ' ' + t.shown : t.none;
                if (pts.length) map.fitBounds(layer.getBounds(), { padding: [30, 30], maxZoom: 15 });
            });
    }
    form.addEventListener('submit', function (e) { e.preventDefault(); load(); });
    load();
});
</script>
@endpush

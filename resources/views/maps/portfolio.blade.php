@extends('layouts.app')

@section('title', __('Portfolio map'))
@section('page_heading', __('Portfolio map'))
@section('page_subtitle', __('Your properties by location, coloured by occupancy.'))

@include('maps._leaflet')

@section('content')
<div class="d-flex flex-wrap gap-3 small mb-2">
    <span><span style="color:#16a34a">●</span> 90%+ {{ __('occupied') }}</span>
    <span><span style="color:#d97706">●</span> 60–89%</span>
    <span><span style="color:#dc2626">●</span> {{ __('below') }} 60%</span>
    <span><span style="color:#8a91a3">●</span> {{ __('no units') }}</span>
</div>
<div id="map" class="terra-map mb-3" role="region" aria-label="{{ __('Portfolio map') }}"></div>

@if($missing->isNotEmpty())
    <div class="alert alert-light border small">
        <i class="bi bi-geo me-1"></i> {{ $missing->count() }} {{ \Illuminate\Support\Str::plural('property', $missing->count()) }} {{ __('have no coordinates yet:') }}
        {{ $missing->pluck('name')->implode(', ') }}. {{ __('Add latitude and longitude on the property form to show them here.') }}
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var points = @json($points);
    var map = TerraMap.create('map');
    var layer = L.markerClusterGroup({ showCoverageOnHover: false }).addTo(map);
    points.forEach(function (p) {
        var color = p.occupancy === null ? '#8a91a3' : (p.occupancy >= 90 ? '#16a34a' : (p.occupancy >= 60 ? '#d97706' : '#dc2626'));
        L.marker([p.lat, p.lng], { icon: TerraMap.pin(color, p.occupancy === null ? '' : p.occupancy) })
            .bindPopup('<div class="terra-pop"><strong>' + TerraMap.esc(p.name) + '</strong><div class="small text-secondary">' + TerraMap.esc(p.location) + '</div>'
                + '<div class="small mt-1">' + p.occupied + '/' + p.units + ' occupied · ' + p.available + ' available</div>'
                + '<a class="btn btn-sm btn-dark mt-2 w-100" href="' + TerraMap.esc(p.url) + '">Open</a></div>')
            .addTo(layer);
    });
    if (points.length) map.fitBounds(layer.getBounds(), { padding: [30, 30], maxZoom: 15 });
});
</script>
@endpush

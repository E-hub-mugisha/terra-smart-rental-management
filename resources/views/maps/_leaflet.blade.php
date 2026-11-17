{{-- Leaflet + marker clustering (OpenStreetMap tiles). Include once per page. --}}
@once
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" crossorigin="">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" crossorigin="">
<style>
    .terra-map { width: 100%; height: 520px; border-radius: 14px; border: 1px solid #e5e9f1; z-index: 0; }
    @media (max-width: 767.98px) { .terra-map { height: 420px; } }
    .terra-pin { width: 30px; height: 30px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 3px solid #fff; box-shadow: 0 3px 10px rgba(0,0,0,.25); }
    .terra-pin span { display: block; transform: rotate(45deg); color: #fff; font: 700 11px/24px sans-serif; text-align: center; }
    .leaflet-popup-content { font-family: var(--terra-font, Georgia, serif); margin: 12px 14px; }
    .terra-pop img { width: 100%; height: 110px; object-fit: cover; border-radius: 8px; margin-bottom: 8px; }
    .terra-pop strong { display: block; color: #19265d; font-size: 1rem; }
</style>
@endpush
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js" crossorigin=""></script>
<script>
window.TerraMap = {
    // Rwanda, centred on Kigali.
    create: function (el) {
        var map = L.map(el, { scrollWheelZoom: false }).setView([-1.9441, 30.0619], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        map.on('focus', function () { map.scrollWheelZoom.enable(); });
        return map;
    },
    pin: function (color, label) {
        return L.divIcon({ className: '', iconSize: [30, 30], iconAnchor: [15, 30], popupAnchor: [0, -28],
            html: '<div class="terra-pin" style="background:' + color + '"><span>' + (label == null ? '' : label) + '</span></div>' });
    },
    esc: function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
};
</script>
@endpush
@endonce

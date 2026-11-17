{{--
    Property showcase — the shared "property detail" layout used on the
    public property page and inside the admin / manager property views.

    Layout:  [ gallery            ] [ title, facts, price, actions ]
             [ units in building  ] [ selected unit card           ]

    Props
      property       App\Models\Property with buildings.units.images, images, activeManagers.user
      mode           'public' | 'admin'
      editUrl        (admin) link for the "Edit property" button
      publicUrl      (admin) link to the public page, shown when the property is active
      unitModalPrefix (admin) id prefix of the existing edit-unit modals, e.g. 'editUnit'
--}}
@props([
    'property',
    'mode' => 'public',
    'editUrl' => null,
    'publicUrl' => null,
    'unitModalPrefix' => null,
])

@php
    $isPublic = $mode === 'public';
    $rootId = 'tps-' . $property->id;

    $buildings = $property->buildings
        ->when($isPublic, fn ($c) => $c->where('status', 'active'))
        ->values();

    $units = $buildings
        ->flatMap(fn ($b) => $b->units->each(fn ($u) => $u->setRelation('building', $b)))
        ->when($isPublic, fn ($c) => $c->where('status', '!=', 'inactive'))
        ->sort(fn ($a, $b) => strnatcasecmp((string) $a->building?->name, (string) $b->building?->name)
            ?: strnatcasecmp((string) $a->unit_number, (string) $b->unit_number))
        ->values();

    $availableUnits = $units->where('status', 'available');

    // Gallery: property photos first, then building and unit photos as a fallback.
    $gallery = $property->images
        ->concat($buildings->flatMap->images)
        ->concat($units->flatMap->images)
        ->unique('image_path')
        ->take(10)
        ->map(fn ($img) => [
            'src' => asset('storage/' . $img->image_path),
            'alt' => $img->caption ?: $property->name,
        ])
        ->values();
    $cover = $gallery->first()['src'] ?? null;

    $range = function ($values, $zeroLabel = null) {
        $values = $values->filter(fn ($v) => $v !== null)->map(fn ($v) => (int) $v);
        if ($values->isEmpty()) return null;
        [$min, $max] = [$values->min(), $values->max()];
        $fmt = fn ($v) => ($v === 0 && $zeroLabel) ? $zeroLabel : $v;
        return $min === $max ? (string) $fmt($min) : $fmt($min) . '–' . $max;
    };
    $bedRange = $range($units->pluck('bedrooms'), 'Studio');
    $bathRange = $range($units->pluck('bathrooms'));

    $priceSource = ($availableUnits->isNotEmpty() ? $availableUnits : $units)->sortBy(fn ($u) => (float) $u->rent_amount)->first();
    $money = fn ($amount, $currency) => ($currency ?: 'RWF') . ' ' . number_format((float) $amount, ($currency ?: 'RWF') === 'RWF' ? 0 : 2);

    $amenities = $property->amenityList();
    $manager = $property->relationLoaded('activeManagers') ? $property->activeManagers->first() : null;
    $contactHref = $manager?->phone
        ? 'tel:' . preg_replace('/[^0-9+]/', '', $manager->phone)
        : ($manager?->user?->email ? 'mailto:' . $manager->user->email . '?subject=' . rawurlencode('Enquiry: ' . $property->name) : null);

    $typeLabel = fn ($v) => ucfirst(str_replace('_', ' ', (string) $v));

    // Per-unit payload for the selected-unit card.
    $unitPayload = $units->map(function ($u) use ($isPublic, $cover, $money, $typeLabel) {
        $features = $u->features;
        if (empty($features)) {
            $features = collect([
                $u->unit_type ? $typeLabel($u->unit_type) : null,
                $u->floor?->name ? 'Located on ' . $u->floor->name : null,
                (float) $u->deposit_amount > 0 ? 'Deposit ' . $money($u->deposit_amount, $u->currency) : null,
            ])->filter()->values()->all();
        }

        return [
            'id' => $u->id,
            'number' => $u->unit_number,
            'building' => $u->building_id,
            'buildingName' => $u->building?->name,
            'status' => $u->status,
            'statusLabel' => $u->status_label,
            'beds' => (int) $u->bedrooms,
            'baths' => (int) $u->bathrooms,
            'area' => $u->area_sqm ? rtrim(rtrim(number_format((float) $u->area_sqm, 2), '0'), '.') . ' m²' : null,
            'rent' => $money($u->rent_amount, $u->currency),
            'image' => $u->images->first() ? asset('storage/' . $u->images->first()->image_path) : $cover,
            'features' => array_slice($features, 0, 4),
            'url' => $isPublic && $u->status === 'available' ? route('rentals.show', $u) : null,
        ];
    })->values();

    $selected = $availableUnits->first() ?? $units->first();
    $selectedData = $selected ? $unitPayload->firstWhere('id', $selected->id) : null;

    $statusClass = fn ($s) => match ($s) {
        'available' => 'is-available',
        'occupied' => 'is-occupied',
        'reserved' => 'is-reserved',
        'maintenance', 'under_maintenance' => 'is-maintenance',
        'vacant' => 'is-vacant',
        default => 'is-inactive',
    };
    $legend = $units->pluck('status')->unique()->sortBy(fn ($s) => array_search($s, array_keys(\App\Models\Unit::STATUSES)))->values();
@endphp

@once
<style>
.tps {
    --tps-navy: #19265d; --tps-navy-2: #0f1d55; --tps-orange: #D05208; --tps-orange-2: #b64706;
    --tps-ink: #1d2433; --tps-mute: #5f6880; --tps-line: #e3e7ef; --tps-soft: #f5f7fb; --tps-card: #fff;
    --tps-green: #16a34a; --tps-green-bg: #e9f8ef; --tps-gray: #8a91a3; --tps-blue: #2563eb; --tps-amber: #d97706; --tps-teal: #0f9488;
    color: var(--tps-ink);
    font-size: .95rem;
}
.tps *, .tps *::before, .tps *::after { box-sizing: border-box; }
.tps-grid { display: grid; grid-template-columns: minmax(0, 2.05fr) minmax(0, 1fr); gap: 28px; align-items: start; }
.tps-grid + .tps-grid { margin-top: 28px; padding-top: 28px; border-top: 1px solid var(--tps-line); }
@media (max-width: 991.98px) { .tps-grid { grid-template-columns: minmax(0, 1fr); gap: 20px; } }

/* Gallery */
.tps-stage { position: relative; border-radius: 16px; overflow: hidden; background: #e7ebf3; aspect-ratio: 16 / 7.6; }
.tps-stage img { width: 100%; height: 100%; object-fit: cover; display: block; transition: opacity .25s ease; }
.tps-empty { position: absolute; inset: 0; display: grid; place-items: center; align-content: center; gap: 6px; color: var(--tps-mute); font-weight: 600; }
.tps-empty i { font-size: 2.4rem; opacity: .6; }
.tps-badge { position: absolute; display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 10px;
    background: rgba(17, 24, 52, .82); color: #fff; font-weight: 700; font-size: .88rem; backdrop-filter: blur(4px); }
.tps-badge.is-top { top: 16px; left: 16px; }
.tps-badge.is-bottom { bottom: 16px; left: 16px; }
.tps-arrow { position: absolute; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; border-radius: 50%; border: 0;
    background: rgba(255,255,255,.92); color: var(--tps-navy); display: grid; place-items: center; box-shadow: 0 6px 18px rgba(0,0,0,.15); opacity: 0; transition: opacity .2s; }
.tps-stage:hover .tps-arrow, .tps-arrow:focus-visible { opacity: 1; }
.tps-arrow.is-prev { left: 14px; } .tps-arrow.is-next { right: 14px; }
@media (hover: none) { .tps-arrow { opacity: 1; } }
.tps-thumbs { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin-top: 10px; }
.tps-thumb { padding: 0; border: 2px solid transparent; border-radius: 10px; overflow: hidden; aspect-ratio: 16 / 9; background: #e7ebf3; cursor: pointer; transition: border-color .2s, transform .2s; }
.tps-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.tps-thumb:hover { transform: translateY(-2px); }
.tps-thumb.is-active { border-color: var(--tps-orange); }
.tps-thumb.is-more { position: relative; }
.tps-thumb.is-more span { position: absolute; inset: 0; display: grid; place-items: center; background: rgba(17,24,52,.6); color: #fff; font-weight: 700; }

/* Info column */
.tps-loc { display: flex; align-items: center; gap: 6px; color: var(--tps-ink); font-weight: 500; margin-bottom: 8px; }
.tps-loc i { color: var(--tps-navy); }
.tps-title { color: var(--tps-navy); font-size: clamp(1.6rem, 2.6vw, 2.2rem); font-weight: 800; letter-spacing: -.03em; line-height: 1.12; margin: 0 0 12px; }
.tps-desc { color: #3b4357; line-height: 1.6; margin: 0 0 18px; }
.tps-facts { display: flex; flex-wrap: wrap; gap: 0; padding-bottom: 18px; margin: 0 0 18px; border-bottom: 1px solid var(--tps-line); }
.tps-fact { display: flex; align-items: center; gap: 10px; padding: 2px 16px; border-left: 1px solid var(--tps-line); }
.tps-fact:first-child { padding-left: 0; border-left: 0; }
.tps-fact i { font-size: 1.45rem; color: var(--tps-navy); }
.tps-fact strong { display: block; font-weight: 700; line-height: 1.15; color: var(--tps-ink); }
.tps-fact small { display: block; color: var(--tps-mute); font-size: .85rem; line-height: 1.15; }
@media (max-width: 420px) { .tps-fact { padding: 6px 10px; } }
.tps-price { color: var(--tps-orange); font-weight: 700; font-size: 1.2rem; margin: 0 0 18px; }
.tps-price strong { font-size: 1.75rem; font-weight: 800; letter-spacing: -.02em; }
.tps-price small { font-size: 1.05rem; font-weight: 600; }
.tps-actions { display: flex; flex-wrap: wrap; gap: 12px; }
.tps-btn { display: inline-flex; align-items: center; justify-content: center; gap: 10px; min-height: 50px; padding: 0 24px; border-radius: 999px;
    font-weight: 700; font-size: .98rem; text-decoration: none; border: 2px solid transparent; cursor: pointer; transition: all .2s ease; }
.tps-btn.is-navy { background: var(--tps-navy-2); color: #fff; flex: 1 1 auto; }
.tps-btn.is-navy:hover { background: var(--tps-navy); color: #fff; transform: translateY(-1px); box-shadow: 0 10px 22px rgba(15,29,85,.25); }
.tps-btn.is-ghost { background: #fff; color: var(--tps-navy); border-color: var(--tps-navy-2); }
.tps-btn.is-ghost:hover { background: var(--tps-soft); color: var(--tps-navy); }
.tps-btn.is-ghost.is-saved { color: #e0245e; border-color: #e0245e; }
.tps-btn.is-orange { background: var(--tps-orange); color: #fff; width: 100%; min-height: 46px; border-radius: 12px; }
.tps-btn.is-orange:hover { background: var(--tps-orange-2); color: #fff; }
.tps-btn[aria-disabled="true"] { background: #c9cedb; pointer-events: none; }
.tps-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
.tps-chip { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 999px; background: var(--tps-soft); border: 1px solid var(--tps-line); font-size: .8rem; font-weight: 600; color: var(--tps-navy); }

/* Units panel */
.tps-panel { background: var(--tps-card); border: 1px solid var(--tps-line); border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(25,38,93,.04); }
.tps-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
.tps-panel-title { display: flex; align-items: center; gap: 12px; margin: 0; color: var(--tps-navy); font-size: 1.45rem; font-weight: 800; letter-spacing: -.02em; }
.tps-panel-title i { font-size: 1.7rem; }
.tps-legend { display: flex; flex-wrap: wrap; gap: 16px; font-size: .9rem; }
.tps-legend span { display: inline-flex; align-items: center; gap: 7px; }
.tps-dot { width: 11px; height: 11px; border-radius: 50%; background: var(--tps-gray); flex: none; }
.tps-dot.is-available { background: var(--tps-green); } .tps-dot.is-occupied { background: var(--tps-gray); }
.tps-dot.is-reserved { background: var(--tps-blue); } .tps-dot.is-maintenance { background: var(--tps-amber); }
.tps-dot.is-vacant { background: var(--tps-teal); } .tps-dot.is-inactive { background: #c5cad6; }
.tps-filter { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
.tps-filter button { border: 1px solid var(--tps-line); background: #fff; color: var(--tps-navy); border-radius: 999px; padding: 5px 13px; font-size: .82rem; font-weight: 700; }
.tps-filter button.is-active { background: var(--tps-navy); border-color: var(--tps-navy); color: #fff; }
.tps-units { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
@media (max-width: 575.98px) { .tps-units { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.tps-unit { display: flex; align-items: center; justify-content: space-between; gap: 8px; width: 100%; text-align: left; padding: 14px 16px;
    background: #fff; border: 1.5px solid var(--tps-line); border-radius: 10px; cursor: pointer; transition: border-color .2s, box-shadow .2s, transform .2s; }
.tps-unit:hover { border-color: #c6cde0; transform: translateY(-2px); box-shadow: 0 8px 18px rgba(25,38,93,.08); }
.tps-unit.is-selected { border-color: var(--tps-green); box-shadow: 0 0 0 1px var(--tps-green); }
.tps-unit-num { display: block; font-weight: 600; font-size: 1.05rem; color: var(--tps-ink); line-height: 1.3; }
.tps-unit-st { display: inline-flex; align-items: center; gap: 7px; font-size: .9rem; color: var(--tps-mute); }
.tps-unit-st.is-available { color: var(--tps-green); font-weight: 600; }
.tps-unit i.bi-chevron-right { color: var(--tps-ink); font-size: 1.05rem; }
.tps-none { padding: 30px 12px; text-align: center; color: var(--tps-mute); }

/* Selected unit card */
.tps-card { background: var(--tps-card); border: 1px solid var(--tps-line); border-radius: 16px; padding: 16px; box-shadow: 0 2px 10px rgba(25,38,93,.04); }
.tps-card-top { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr); gap: 14px; padding-bottom: 14px; border-bottom: 1px solid var(--tps-line); }
.tps-pill { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: .85rem; font-weight: 600; color: #fff; background: var(--tps-gray); }
.tps-pill.is-available { background: var(--tps-green); } .tps-pill.is-reserved { background: var(--tps-blue); }
.tps-pill.is-maintenance { background: var(--tps-amber); } .tps-pill.is-vacant { background: var(--tps-teal); }
.tps-card-title { color: var(--tps-navy-2); font-size: 1.7rem; font-weight: 800; letter-spacing: -.02em; margin: 10px 0 8px; line-height: 1.1; }
.tps-specs { list-style: none; margin: 0; padding: 0; display: grid; gap: 4px; }
.tps-specs li { display: flex; align-items: center; gap: 9px; color: var(--tps-ink); }
.tps-specs i { color: var(--tps-navy); width: 18px; text-align: center; }
.tps-card-img { border-radius: 10px; overflow: hidden; background: #e7ebf3; min-height: 150px; }
.tps-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.tps-card-rent { margin: 12px 0 0; font-weight: 700; color: var(--tps-navy); }
.tps-card-rent span { color: var(--tps-mute); font-weight: 500; }
.tps-features-title { font-weight: 700; margin: 12px 0 6px; color: var(--tps-ink); }
.tps-features { list-style: none; margin: 0 0 14px; padding: 0; display: grid; gap: 3px; }
.tps-features li { display: flex; align-items: flex-start; gap: 9px; }
.tps-features i { color: var(--tps-green); font-size: 1.05rem; line-height: 1.4; }
@media (max-width: 420px) { .tps-card-top { grid-template-columns: 1fr; } }
</style>
@endonce

<div class="tps" id="{{ $rootId }}" data-tps>

    {{-- ============ Row 1: gallery + summary ============ --}}
    <div class="tps-grid">
        <div>
            <div class="tps-stage">
                @if($gallery->isNotEmpty())
                    <img src="{{ $gallery[0]['src'] }}" alt="{{ $gallery[0]['alt'] }}" data-tps-main>
                    @if($gallery->count() > 1)
                        <button type="button" class="tps-arrow is-prev" data-tps-step="-1" aria-label="Previous photo"><i class="bi bi-chevron-left"></i></button>
                        <button type="button" class="tps-arrow is-next" data-tps-step="1" aria-label="Next photo"><i class="bi bi-chevron-right"></i></button>
                    @endif
                    <span class="tps-badge is-bottom" aria-live="polite"><i class="bi bi-camera-fill"></i> <span data-tps-counter>1</span> / {{ $gallery->count() }}</span>
                @else
                    <div class="tps-empty"><i class="bi bi-image"></i><span>No photos yet</span></div>
                @endif
                <span class="tps-badge is-top"><i class="bi bi-building"></i> {{ $units->count() }} {{ Str::plural('Unit', $units->count()) }}</span>
            </div>

            @if($gallery->count() > 1)
                <div class="tps-thumbs" role="group" aria-label="Choose a photo">
                    @foreach($gallery->take(5) as $i => $img)
                        <button type="button" class="tps-thumb {{ $i === 0 ? 'is-active' : '' }} {{ $i === 4 && $gallery->count() > 5 ? 'is-more' : '' }}"
                                data-tps-thumb="{{ $i }}" aria-label="Show photo {{ $i + 1 }}">
                            <img src="{{ $img['src'] }}" alt="" loading="lazy">
                            @if($i === 4 && $gallery->count() > 5)<span>+{{ $gallery->count() - 5 }}</span>@endif
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            @if($property->location_line)
                <div class="tps-loc"><i class="bi bi-geo-alt-fill"></i> {{ $property->location_line }}</div>
            @endif

            <h1 class="tps-title">{{ $property->name }}</h1>

            <p class="tps-desc">
                {{ Str::limit($property->description ?: ($typeLabel($property->property_type) . ' property with ' . $units->count() . ' ' . Str::plural('unit', $units->count()) . '.'), 190) }}
            </p>

            <div class="tps-facts">
                @if($bedRange !== null)
                    <div class="tps-fact"><i class="bi bi-door-closed"></i><div><strong>{{ $bedRange }}</strong><small>Beds</small></div></div>
                @endif
                @if($bathRange !== null)
                    <div class="tps-fact"><i class="bi bi-droplet"></i><div><strong>{{ $bathRange }}</strong><small>Baths</small></div></div>
                @endif
                @forelse(array_slice($amenities, 0, $bedRange !== null ? 1 : 3) as $amenity)
                    <div class="tps-fact"><i class="bi {{ $amenity['icon'] }}"></i><div><strong>{{ $amenity['label'] }}</strong></div></div>
                @empty
                    <div class="tps-fact"><i class="bi bi-buildings"></i><div><strong>{{ $buildings->count() }}</strong><small>{{ Str::plural('Building', $buildings->count()) }}</small></div></div>
                @endforelse
            </div>

            @if($priceSource)
                <p class="tps-price">
                    From <strong>{{ $money($priceSource->rent_amount, $priceSource->currency) }}</strong> <small>/ month</small>
                </p>
            @endif

            <div class="tps-actions">
                @if($isPublic)
                    @if($contactHref)
                        <a href="{{ $contactHref }}" class="tps-btn is-navy"><i class="bi bi-telephone-fill"></i> Contact Landlord</a>
                    @elseif($selectedData && $selectedData['url'])
                        <a href="{{ $selectedData['url'] }}#applyPanel" class="tps-btn is-navy" data-tps-contact><i class="bi bi-chat-dots-fill"></i> Contact Landlord</a>
                    @endif
                    <button type="button" class="tps-btn is-ghost" data-tps-save="{{ $property->id }}" aria-pressed="false">
                        <i class="bi bi-heart"></i> <span>Save</span>
                    </button>
                @else
                    @if($editUrl)
                        <a href="{{ $editUrl }}" class="tps-btn is-navy"><i class="bi bi-pencil-square"></i> Edit property</a>
                    @endif
                    @if($publicUrl && $property->status === 'active')
                        <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="tps-btn is-ghost"><i class="bi bi-box-arrow-up-right"></i> Public page</a>
                    @endif
                @endif
            </div>

            @if(count($amenities) > 1 || $property->utilityList())
                <div class="tps-chips">
                    @foreach(array_merge($amenities, $property->utilityList()) as $chip)
                        <span class="tps-chip"><i class="bi {{ $chip['icon'] }}"></i> {{ $chip['label'] }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ============ Row 2: unit grid + selected unit ============ --}}
    <div class="tps-grid">
        <section class="tps-panel" aria-labelledby="{{ $rootId }}-units">
            <div class="tps-panel-head">
                <h2 class="tps-panel-title" id="{{ $rootId }}-units">
                    <i class="bi bi-buildings-fill"></i>
                    Units in this {{ $buildings->count() > 1 ? 'Property' : 'Building' }} ({{ $units->count() }})
                </h2>
                <div class="tps-legend">
                    @foreach($legend as $status)
                        <span><i class="tps-dot {{ $statusClass($status) }}"></i>{{ \App\Models\Unit::STATUSES[$status] ?? $typeLabel($status) }}</span>
                    @endforeach
                </div>
            </div>

            @if($buildings->count() > 1)
                <div class="tps-filter" role="group" aria-label="Filter by building">
                    <button type="button" class="is-active" data-tps-building="all">All buildings</button>
                    @foreach($buildings as $b)
                        <button type="button" data-tps-building="{{ $b->id }}">{{ $b->name }} <span style="opacity:.7">({{ $units->where('building_id', $b->id)->count() }})</span></button>
                    @endforeach
                </div>
            @endif

            @if($units->isNotEmpty())
                <div class="tps-units">
                    @foreach($units as $u)
                        <button type="button" class="tps-unit {{ $selected && $selected->id === $u->id ? 'is-selected' : '' }}"
                                data-tps-unit="{{ $u->id }}" data-tps-unit-building="{{ $u->building_id }}"
                                aria-pressed="{{ $selected && $selected->id === $u->id ? 'true' : 'false' }}"
                                aria-controls="{{ $rootId }}-card">
                            <span>
                                <span class="tps-unit-num">Unit {{ $u->unit_number }}</span>
                                <span class="tps-unit-st {{ $statusClass($u->status) }}"><i class="tps-dot {{ $statusClass($u->status) }}"></i>{{ $u->status_label }}</span>
                            </span>
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </button>
                    @endforeach
                </div>
            @else
                <div class="tps-none"><i class="bi bi-door-closed fs-3 d-block mb-2"></i>No units have been listed for this property yet.</div>
            @endif
        </section>

        <aside class="tps-card" id="{{ $rootId }}-card" aria-live="polite">
            @if($selectedData)
                <div class="tps-card-top">
                    <div>
                        <span class="tps-pill {{ $statusClass($selectedData['status']) }}" data-tps-f="status">{{ $selectedData['statusLabel'] }}</span>
                        <h3 class="tps-card-title" data-tps-f="title">Unit {{ $selectedData['number'] }}</h3>
                        <ul class="tps-specs">
                            <li><i class="bi bi-house-door"></i> <span data-tps-f="beds"></span></li>
                            <li><i class="bi bi-droplet"></i> <span data-tps-f="baths"></span></li>
                            <li data-tps-f="area-row"><i class="bi bi-arrows-angle-expand"></i> <span data-tps-f="area"></span></li>
                        </ul>
                    </div>
                    <div class="tps-card-img">
                        <img src="{{ $selectedData['image'] }}" alt="" data-tps-f="image" @if(!$selectedData['image']) hidden @endif>
                    </div>
                </div>
                <p class="tps-card-rent"><span data-tps-f="rent"></span> <span>/ month</span></p>
                <div class="tps-features-title">Key Features:</div>
                <ul class="tps-features" data-tps-f="features"></ul>

                @if($isPublic)
                    <a href="{{ $selectedData['url'] ?: '#' }}" class="tps-btn is-orange" data-tps-f="cta"
                       @if(!$selectedData['url']) aria-disabled="true" @endif>
                        <span data-tps-f="cta-label">View Unit Details</span> <i class="bi bi-arrow-right"></i>
                    </a>
                @elseif($unitModalPrefix)
                    <button type="button" class="tps-btn is-orange" data-tps-f="modal" data-bs-toggle="modal" data-bs-target="#{{ $unitModalPrefix }}{{ $selectedData['id'] }}">
                        Edit Unit Details <i class="bi bi-arrow-right"></i>
                    </button>
                @endif
            @else
                <div class="tps-none"><i class="bi bi-info-circle fs-3 d-block mb-2"></i>Select a unit to see its details.</div>
            @endif
        </aside>
    </div>

    @php
        $tpsJson = json_encode([
            'units' => $unitPayload,
            'gallery' => $gallery,
            'selected' => $selectedData['id'] ?? null,
            'modalPrefix' => $unitModalPrefix,
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    @endphp
    <script type="application/json" data-tps-data>{!! $tpsJson !!}</script>
</div>

@once
<script>
(function () {
    function plural(n, word) { return n + ' ' + word + (n === 1 ? '' : 's'); }

    function init(root) {
        var data;
        try { data = JSON.parse(root.querySelector('[data-tps-data]').textContent); } catch (e) { return; }
        var units = {}; (data.units || []).forEach(function (u) { units[u.id] = u; });

        /* ---- Gallery ---- */
        var main = root.querySelector('[data-tps-main]');
        var counter = root.querySelector('[data-tps-counter]');
        var thumbs = root.querySelectorAll('[data-tps-thumb]');
        var current = 0, gallery = data.gallery || [];
        function show(i) {
            if (!main || !gallery.length) return;
            current = (i + gallery.length) % gallery.length;
            main.style.opacity = '.35';
            main.src = gallery[current].src; main.alt = gallery[current].alt;
            main.onload = function () { main.style.opacity = '1'; };
            if (counter) counter.textContent = current + 1;
            thumbs.forEach(function (t) { t.classList.toggle('is-active', +t.dataset.tpsThumb === current); });
        }
        thumbs.forEach(function (t) { t.addEventListener('click', function () { show(+t.dataset.tpsThumb); }); });
        root.querySelectorAll('[data-tps-step]').forEach(function (b) {
            b.addEventListener('click', function () { show(current + (+b.dataset.tpsStep)); });
        });

        /* ---- Selected unit card ---- */
        var card = root.querySelector('.tps-card');
        function f(name) { return card.querySelector('[data-tps-f="' + name + '"]'); }
        var classes = ['is-available', 'is-occupied', 'is-reserved', 'is-maintenance', 'is-vacant', 'is-inactive'];
        function cls(s) {
            return { available: 'is-available', occupied: 'is-occupied', reserved: 'is-reserved', maintenance: 'is-maintenance',
                     under_maintenance: 'is-maintenance', vacant: 'is-vacant' }[s] || 'is-inactive';
        }
        function render(u) {
            if (!u || !f('title')) return;
            var pill = f('status');
            classes.forEach(function (c) { pill.classList.remove(c); });
            pill.classList.add(cls(u.status)); pill.textContent = u.statusLabel;
            f('title').textContent = 'Unit ' + u.number;
            f('beds').textContent = u.beds === 0 ? 'Studio' : plural(u.beds, 'Bedroom');
            f('baths').textContent = plural(u.baths, 'Bathroom');
            f('area-row').hidden = !u.area; f('area').textContent = u.area || '';
            f('rent').textContent = u.rent;
            var img = f('image');
            if (u.image) { img.hidden = false; img.src = u.image; img.alt = 'Unit ' + u.number; } else { img.hidden = true; }
            var list = f('features'); list.innerHTML = '';
            (u.features.length ? u.features : ['Contact us for more details']).forEach(function (text) {
                var li = document.createElement('li');
                li.innerHTML = '<i class="bi bi-check-lg" aria-hidden="true"></i>';
                li.appendChild(document.createTextNode(text));
                list.appendChild(li);
            });
            var cta = f('cta');
            if (cta) {
                if (u.url) { cta.href = u.url; cta.removeAttribute('aria-disabled'); f('cta-label').textContent = 'View Unit Details'; }
                else { cta.href = '#'; cta.setAttribute('aria-disabled', 'true'); f('cta-label').textContent = u.statusLabel + ' — not open for applications'; }
            }
            var modal = f('modal');
            if (modal && data.modalPrefix) modal.setAttribute('data-bs-target', '#' + data.modalPrefix + u.id);
            var contact = root.querySelector('[data-tps-contact]');
            if (contact && u.url) contact.href = u.url + '#applyPanel';
        }
        var tiles = root.querySelectorAll('[data-tps-unit]');
        tiles.forEach(function (t) {
            t.addEventListener('click', function () {
                tiles.forEach(function (x) { x.classList.remove('is-selected'); x.setAttribute('aria-pressed', 'false'); });
                t.classList.add('is-selected'); t.setAttribute('aria-pressed', 'true');
                render(units[t.dataset.tpsUnit]);
                if (window.matchMedia('(max-width: 991.98px)').matches) card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        });
        if (data.selected) render(units[data.selected]);

        /* ---- Building filter ---- */
        var filters = root.querySelectorAll('[data-tps-building]');
        filters.forEach(function (btn) {
            btn.addEventListener('click', function () {
                filters.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
                var id = btn.dataset.tpsBuilding;
                tiles.forEach(function (t) { t.hidden = id !== 'all' && t.dataset.tpsUnitBuilding !== id; });
            });
        });

        /* ---- Save (per-browser convenience) ---- */
        var save = root.querySelector('[data-tps-save]');
        if (save) {
            var key = 'terra_saved_properties', id = save.dataset.tpsSave;
            function read() { try { return JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) { return []; } }
            function paint(on) {
                save.classList.toggle('is-saved', on); save.setAttribute('aria-pressed', on ? 'true' : 'false');
                save.querySelector('i').className = on ? 'bi bi-heart-fill' : 'bi bi-heart';
                save.querySelector('span').textContent = on ? 'Saved' : 'Save';
            }
            paint(read().indexOf(id) !== -1);
            save.addEventListener('click', function () {
                var list = read(), i = list.indexOf(id);
                if (i === -1) list.push(id); else list.splice(i, 1);
                try { localStorage.setItem(key, JSON.stringify(list)); } catch (e) {}
                paint(i === -1);
            });
        }
    }

    function boot() { document.querySelectorAll('[data-tps]').forEach(init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
</script>
@endonce

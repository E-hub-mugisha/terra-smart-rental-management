@extends('layouts.app')

@section('title', $inspection->type_label . ' ' . $inspection->inspection_number)
@section('page_heading', $inspection->type_label)
@section('page_subtitle', 'Unit ' . $inspection->unit?->unit_number . ' · ' . $inspection->unit?->building?->property?->name . ' · ' . ($inspection->lease?->tenant?->full_name ?? 'Tenant'))

@include('inspections._styles')

@php
    $moveOut = $inspection->isMoveOut();
    $conditions = \App\Models\Inspection::CONDITIONS;
    $currency = $inspection->currency ?: 'RWF';
    $areaNames = $areas->keys();
@endphp

@section('content')
@if($inspection->status === 'disputed')
    <div class="alert alert-danger d-flex gap-2">
        <i class="bi bi-exclamation-octagon fs-5"></i>
        <div><strong>The tenant disputed this inspection.</strong> “{{ $inspection->tenant_comment }}”<br>
            <span class="small">Review the items they mention, update the report, then send it again.</span></div>
    </div>
@endif
@error('items')<div class="alert alert-warning">{{ $message }}</div>@enderror

<div class="row g-4">
    <div class="col-xl-8">
        <form method="POST" action="{{ route('inspections.update', $inspection) }}" id="inspectionForm">
            @csrf @method('PUT')

            <div class="ti-card">
                <div class="ti-card-head">
                    <h2 class="ti-card-title">Details &amp; meter readings</h2>
                    <span class="ti-pill st-{{ $inspection->status }}">{{ $inspection->status_label }}</span>
                </div>
                <div class="ti-card-body">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label fw-semibold" for="inspection_date">Inspection date</label>
                            <input type="date" class="form-control" id="inspection_date" name="inspection_date" max="{{ now()->toDateString() }}" required
                                   value="{{ old('inspection_date', $inspection->inspection_date?->toDateString()) }}">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label fw-semibold" for="electricity_reading">Electricity meter</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="electricity_reading" name="electricity_reading"
                                   value="{{ old('electricity_reading', $inspection->electricity_reading) }}" placeholder="{{ $inspection->unit?->electricity_meter ? 'No. ' . $inspection->unit->electricity_meter : 'kWh' }}">
                            @if($moveOut && $inspection->baseline?->electricity_reading !== null)
                                <div class="form-text">Move-in: {{ $inspection->baseline->electricity_reading }}</div>
                            @endif
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label fw-semibold" for="water_reading">Water meter</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="water_reading" name="water_reading"
                                   value="{{ old('water_reading', $inspection->water_reading) }}" placeholder="{{ $inspection->unit?->water_meter ? 'No. ' . $inspection->unit->water_meter : 'm³' }}">
                            @if($moveOut && $inspection->baseline?->water_reading !== null)
                                <div class="form-text">Move-in: {{ $inspection->baseline->water_reading }}</div>
                            @endif
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label fw-semibold" for="keys_handed">Keys {{ $moveOut ? 'returned' : 'handed over' }}</label>
                            <input type="number" min="0" class="form-control" id="keys_handed" name="keys_handed" value="{{ old('keys_handed', $inspection->keys_handed) }}">
                            @if($moveOut && $inspection->baseline?->keys_handed !== null)
                                <div class="form-text">Move-in: {{ $inspection->baseline->keys_handed }}</div>
                            @endif
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="general_notes">General notes</label>
                            <textarea class="form-control" id="general_notes" name="general_notes" rows="2" placeholder="Overall cleanliness, smells, anything not covered below…">{{ old('general_notes', $inspection->general_notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ti-card">
                <div class="ti-card-head">
                    <h2 class="ti-card-title">Condition checklist</h2>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="small text-secondary" id="unratedCount"></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="markGood">Mark unrated as Good</button>
                    </div>
                </div>

                @foreach($areas as $area => $items)
                    <div class="ti-area">{{ $area }}</div>
                    @foreach($items as $item)
                        @php $base = $item->baseline; @endphp
                        <div class="ti-row" style="grid-template-columns: minmax(0,1.3fr) {{ $moveOut ? 'minmax(0,.9fr)' : '' }} 150px 70px minmax(0,1.6fr) {{ $moveOut ? '120px' : '' }} 34px;">
                            <div>
                                <div class="fw-semibold">{{ $item->item }}</div>
                                @if($item->media->isNotEmpty())
                                    <div class="ti-thumbs mt-1">
                                        @foreach($item->media->take(4) as $m)
                                            <a href="{{ route('inspections.media.show', $m) }}" target="_blank" class="ti-thumb is-sm">
                                                @if($m->isVideo())<span class="ti-play"><i class="bi bi-play-fill"></i></span>@else<img src="{{ route('inspections.media.show', $m) }}" alt="" loading="lazy">@endif
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            @if($moveOut)
                                <div class="small">
                                    <span class="text-secondary d-block">At move-in</span>
                                    @include('inspections._condition', ['condition' => $base?->condition])
                                    @if($base?->notes)<div class="text-secondary text-truncate" title="{{ $base->notes }}">{{ $base->notes }}</div>@endif
                                </div>
                            @endif
                            <div>
                                <select name="items[{{ $item->id }}][condition]" class="form-select form-select-sm js-cond" aria-label="Condition of {{ $item->item }}">
                                    <option value="">Rate…</option>
                                    @foreach($conditions as $key => $label)
                                        <option value="{{ $key }}" @selected(old("items.{$item->id}.condition", $item->condition) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <input type="number" min="0" name="items[{{ $item->id }}][quantity]" class="form-control form-control-sm" placeholder="Qty"
                                       value="{{ old("items.{$item->id}.quantity", $item->quantity) }}" aria-label="Quantity">
                            </div>
                            <div>
                                <input type="text" maxlength="1000" name="items[{{ $item->id }}][notes]" class="form-control form-control-sm"
                                       placeholder="Notes / defects" value="{{ old("items.{$item->id}.notes", $item->notes) }}" aria-label="Notes for {{ $item->item }}">
                            </div>
                            @if($moveOut)
                                <div>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">{{ $currency }}</span>
                                        <input type="number" min="0" step="1" name="items[{{ $item->id }}][deduction_amount]" class="form-control js-deduct"
                                               value="{{ old("items.{$item->id}.deduction_amount", (float) $item->deduction_amount ?: '') }}" placeholder="0" aria-label="Deduction">
                                    </div>
                                </div>
                            @endif
                            <div class="text-end">
                                @unless($item->baseline_item_id)
                                    <label class="btn btn-sm btn-light mb-0" title="Remove item">
                                        <input type="checkbox" name="items[{{ $item->id }}][remove]" value="1" class="d-none js-remove">
                                        <i class="bi bi-trash3"></i>
                                    </label>
                                @endunless
                            </div>
                        </div>
                    @endforeach
                @endforeach

                <div class="ti-area">Add items</div>
                <datalist id="areaNames">
                    @foreach($areaNames as $a)<option value="{{ $a }}">@endforeach
                    <option value="Balcony"><option value="Garage / parking"><option value="Garden">
                </datalist>
                @for($i = 0; $i < 3; $i++)
                    <div class="ti-row" style="grid-template-columns: minmax(0,1fr) minmax(0,1.2fr) 150px minmax(0,1.6fr);">
                        <input type="text" name="new_items[{{ $i }}][area]" list="areaNames" class="form-control form-control-sm" placeholder="Room / area" aria-label="Area">
                        <input type="text" name="new_items[{{ $i }}][item]" class="form-control form-control-sm" placeholder="Item, e.g. Sofa" aria-label="Item">
                        <select name="new_items[{{ $i }}][condition]" class="form-select form-select-sm" aria-label="Condition">
                            <option value="">Rate…</option>
                            @foreach($conditions as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                        <input type="text" name="new_items[{{ $i }}][notes]" class="form-control form-control-sm" placeholder="Notes" aria-label="Notes">
                    </div>
                @endfor
            </div>

            <div class="d-flex flex-wrap justify-content-end gap-2 mt-3 position-sticky bottom-0 py-2" style="background:var(--terra-bg);">
                <a href="{{ route('inspections.show', $inspection) }}" class="btn btn-light rounded-pill px-4">Preview</a>
                <button type="submit" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold"><i class="bi bi-save me-1"></i> Save draft</button>
                <button type="submit" name="then" value="submit" class="btn ti-btn-primary rounded-pill px-4 fw-semibold"
                        onclick="return confirm('Send this report to the tenant? You can still edit it if they dispute it.')">
                    <i class="bi bi-send me-1"></i> Save &amp; send to tenant
                </button>
            </div>
        </form>
    </div>

    <div class="col-xl-4">
        @if($moveOut)
            <div class="ti-card mb-4">
                <div class="ti-card-head"><h2 class="ti-card-title"><i class="bi bi-safe me-1"></i> Deposit</h2></div>
                <div class="ti-card-body">
                    <div class="ti-money"><span>Deposit held</span><strong>{{ $currency }} {{ number_format((float) $inspection->deposit_held) }}</strong></div>
                    <div class="ti-money"><span>Deductions</span><strong id="deductTotal">{{ $currency }} {{ number_format((float) $inspection->deposit_deductions) }}</strong></div>
                    <div class="ti-money"><span>Refund to tenant</span><span id="refundTotal">{{ $currency }} {{ number_format((float) ($inspection->deposit_refund ?? $inspection->deposit_held)) }}</span></div>
                    <p class="small text-secondary mt-3 mb-0">Add a deduction next to any item that is worse than at move-in. Totals update as you type and are saved with the report.</p>
                </div>
            </div>
        @endif

        <div class="ti-card">
            <div class="ti-card-head"><h2 class="ti-card-title"><i class="bi bi-camera me-1"></i> Photos &amp; videos</h2></div>
            <div class="ti-card-body">
                <form method="POST" action="{{ route('inspections.media.store', $inspection) }}" enctype="multipart/form-data" class="mb-3">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small fw-semibold" for="mediaItem">Attach to</label>
                        <select name="inspection_item_id" id="mediaItem" class="form-select form-select-sm">
                            <option value="">Whole unit (general)</option>
                            @foreach($areas as $area => $items)
                                <optgroup label="{{ $area }}">
                                    @foreach($items as $item)<option value="{{ $item->id }}">{{ $item->item }}</option>@endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <input type="text" name="caption" maxlength="255" class="form-control form-control-sm mb-2" placeholder="Caption (optional)">
                    <input type="file" name="media[]" multiple required accept="image/*,video/*" capture="environment" class="form-control form-control-sm mb-2">
                    @error('media')<div class="small text-danger">{{ $message }}</div>@enderror
                    @error('media.*')<div class="small text-danger">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-sm btn-dark rounded-pill w-100"><i class="bi bi-upload me-1"></i> Upload</button>
                    <div class="form-text">Photos or short videos, up to 10 at a time (40 MB each, and 40 MB per upload in total). On a phone this opens the camera.</div>
                </form>

                @if($inspection->media->isNotEmpty())
                    <div class="d-grid gap-2">
                        @foreach($inspection->media as $m)
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('inspections.media.show', $m) }}" target="_blank" class="ti-thumb is-sm flex-shrink-0">
                                    @if($m->isVideo())<span class="ti-play"><i class="bi bi-play-fill"></i></span>@else<img src="{{ route('inspections.media.show', $m) }}" alt="" loading="lazy">@endif
                                </a>
                                <div class="small flex-grow-1 min-w-0">
                                    <div class="text-truncate fw-semibold">{{ $m->item?->item ?? 'General' }}</div>
                                    <div class="text-truncate text-secondary">{{ $m->caption ?: $m->original_name }}</div>
                                </div>
                                <form method="POST" action="{{ route('inspections.media.destroy', [$inspection, $m]) }}" onsubmit="return confirm('Remove this file?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light" aria-label="Remove file"><i class="bi bi-x-lg"></i></button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('inspectionForm');
    var conds = form.querySelectorAll('.js-cond');
    var counter = document.getElementById('unratedCount');
    function count() {
        var n = Array.prototype.filter.call(conds, function (s) { return !s.value; }).length;
        counter.textContent = n ? n + ' not rated yet' : 'All items rated ✓';
    }
    conds.forEach(function (s) { s.addEventListener('change', count); }); count();

    document.getElementById('markGood').addEventListener('click', function () {
        conds.forEach(function (s) { if (!s.value) s.value = 'good'; }); count();
    });

    form.querySelectorAll('.js-remove').forEach(function (box) {
        box.addEventListener('change', function () {
            var row = box.closest('.ti-row');
            row.style.opacity = box.checked ? '.4' : '';
            row.style.textDecoration = box.checked ? 'line-through' : '';
        });
    });

    var deducts = form.querySelectorAll('.js-deduct');
    var held = {{ (float) ($inspection->deposit_held ?? 0) }}, cur = @json($currency . ' ');
    function money(n) { return cur + Math.round(n).toLocaleString('en-US'); }
    function totals() {
        var sum = 0; deducts.forEach(function (d) { sum += parseFloat(d.value) || 0; });
        var t = document.getElementById('deductTotal'), r = document.getElementById('refundTotal');
        if (t) t.textContent = money(sum);
        if (r) r.textContent = money(Math.max(0, held - sum));
    }
    deducts.forEach(function (d) { d.addEventListener('input', totals); });
})();
</script>
@endpush

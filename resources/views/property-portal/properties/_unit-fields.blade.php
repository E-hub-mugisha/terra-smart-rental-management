
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Building *</label>
        <select name="building_id" class="form-select unit-building-select" required>
            <option value="">Select building</option>
            @foreach($buildings as $buildingOption)
                <option value="{{ $buildingOption->id }}"
                    @selected((string) old('building_id', $unit?->building_id) === (string) $buildingOption->id)>
                    {{ $buildingOption->name }} ({{ $buildingOption->building_code }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Floor</label>
        <select name="floor_id" class="form-select unit-floor-select">
            <option value="">No specific floor</option>
            @foreach($floors as $floorOption)
                <option value="{{ $floorOption->id }}"
                        data-building-id="{{ $floorOption->building_id }}"
                    @selected((string) old('floor_id', $unit?->floor_id) === (string) $floorOption->id)>
                    {{ $floorOption->name }} — {{ $floorOption->building?->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Unit number *</label>
        <input name="unit_number" class="form-control" required maxlength="50"
               value="{{ old('unit_number', $unit?->unit_number) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Unit type *</label>
        <select name="unit_type" class="form-select" required>
            @foreach([
                'apartment' => 'Apartment',
                'studio' => 'Studio',
                'house' => 'House',
                'office' => 'Office',
                'shop' => 'Shop',
                'warehouse' => 'Warehouse',
                'other' => 'Other',
            ] as $value => $label)
                <option value="{{ $value }}"
                    @selected(old('unit_type', $unit?->unit_type ?? 'apartment') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Availability *</label>
        <select name="status" class="form-select" required>
            @foreach([
                'available' => 'Available',
                'occupied' => 'Occupied',
                'reserved' => 'Reserved',
                'maintenance' => 'Under maintenance',
                'inactive' => 'Inactive',
            ] as $value => $label)
                <option value="{{ $value }}"
                    @selected(old('status', $unit?->status ?? 'available') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Bedrooms *</label>
        <input type="number" name="bedrooms" class="form-control"
               min="0" max="50" required
               value="{{ old('bedrooms', $unit?->bedrooms ?? 0) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Bathrooms *</label>
        <input type="number" name="bathrooms" class="form-control"
               min="0" max="50" required
               value="{{ old('bathrooms', $unit?->bathrooms ?? 0) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Area (m²)</label>
        <input type="number" name="area_sqm" class="form-control"
               min="0" step="0.01"
               value="{{ old('area_sqm', $unit?->area_sqm) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Currency *</label>
        <select name="currency" class="form-select" required>
            @foreach(['RWF', 'USD', 'EUR'] as $currency)
                <option value="{{ $currency }}"
                    @selected(old('currency', $unit?->currency ?? 'RWF') === $currency)>
                    {{ $currency }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Monthly rent *</label>
        <input type="number" name="rent_amount" class="form-control"
               min="0" step="0.01" required
               value="{{ old('rent_amount', $unit?->rent_amount ?? 0) }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">Security deposit *</label>
        <input type="number" name="deposit_amount" class="form-control"
               min="0" step="0.01" required
               value="{{ old('deposit_amount', $unit?->deposit_amount ?? 0) }}">
    </div>

    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $unit?->description) }}</textarea>
    </div>

    <div class="col-12">
        <label class="form-label">Unit images</label>
        <input type="file" name="images[]" class="form-control"
               accept=".jpg,.jpeg,.png,.webp" multiple>
        <small class="text-muted">JPG, PNG or WebP. Maximum 5 MB per image.</small>
    </div>

    @if($unit && $unit->images->isNotEmpty())
        <div class="col-12 d-flex flex-wrap gap-2">
            @foreach($unit->images as $image)
                <img src="{{ asset('storage/'.$image->image_path) }}"
                     alt="{{ $image->caption ?? $unit->unit_number }}"
                     class="rounded border"
                     style="width:90px;height:75px;object-fit:cover;">
            @endforeach
        </div>
    @endif
</div>

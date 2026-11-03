
<div class="row g-3">
    <div class="col-12">
        <label class="form-label">Building *</label>
        <select name="building_id" class="form-select" required>
            <option value="">Select building</option>
            @foreach($buildings as $buildingOption)
                <option value="{{ $buildingOption->id }}"
                    @selected((string) old('building_id', $floor?->building_id) === (string) $buildingOption->id)>
                    {{ $buildingOption->name }} ({{ $buildingOption->building_code }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-8">
        <label class="form-label">Floor name *</label>
        <input name="name" class="form-control" required
               value="{{ old('name', $floor?->name) }}"
               placeholder="e.g. Ground Floor">
    </div>

    <div class="col-md-4">
        <label class="form-label">Floor number *</label>
        <input type="number" name="floor_number" class="form-control"
               min="-5" max="300" required
               value="{{ old('floor_number', $floor?->floor_number ?? 0) }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">Status *</label>
        <select name="status" class="form-select" required>
            @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                <option value="{{ $value }}"
                    @selected(old('status', $floor?->status ?? 'active') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $floor?->description) }}</textarea>
    </div>

    <div class="col-12">
        <label class="form-label">Floor images</label>
        <input type="file" name="images[]" class="form-control"
               accept=".jpg,.jpeg,.png,.webp" multiple>
        <small class="text-muted">JPG, PNG or WebP. Maximum 5 MB per image.</small>
    </div>

    @if($floor && $floor->images->isNotEmpty())
        <div class="col-12 d-flex flex-wrap gap-2">
            @foreach($floor->images as $image)
                <img src="{{ asset('storage/'.$image->image_path) }}"
                     alt="{{ $image->caption ?? $floor->name }}"
                     class="rounded border"
                     style="width:90px;height:75px;object-fit:cover;">
            @endforeach
        </div>
    @endif
</div>


<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Building code *</label>
        <input name="building_code" class="form-control" required maxlength="50"
               value="{{ old('building_code', $building?->building_code) }}">
    </div>

    <div class="col-md-8">
        <label class="form-label">Building name *</label>
        <input name="name" class="form-control" required
               value="{{ old('name', $building?->name) }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">Number of floors *</label>
        <input type="number" name="number_of_floors" class="form-control"
               min="1" max="200" required
               value="{{ old('number_of_floors', $building?->number_of_floors ?? 1) }}">
    </div>

    <div class="col-md-6">
        <label class="form-label">Status *</label>
        <select name="status" class="form-select" required>
            @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                <option value="{{ $value }}"
                    @selected(old('status', $building?->status ?? 'active') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $building?->description) }}</textarea>
    </div>

    <div class="col-12">
        <label class="form-label">Building images</label>
        <input type="file" name="images[]" class="form-control"
               accept=".jpg,.jpeg,.png,.webp" multiple>
        <small class="text-muted">JPG, PNG or WebP. Maximum 5 MB per image.</small>
    </div>

    @if($building && $building->images->isNotEmpty())
        <div class="col-12 d-flex flex-wrap gap-2">
            @foreach($building->images as $image)
                <img src="{{ upload_url($image->image_path) }}"
                     alt="{{ $image->caption ?? $building->name }}"
                     class="rounded border"
                     style="width:90px;height:75px;object-fit:cover;">
            @endforeach
        </div>
    @endif
</div>

@php
    $isEdit = isset($maintenanceRequest);
@endphp

<div class="row g-4">

    <div class="col-12 col-lg-8">

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0">Request Information</h6>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Property Unit <span class="text-danger">*</span>
                        </label>

                        <select name="unit_id"
                                id="unit_id"
                                class="form-select @error('unit_id') is-invalid @enderror"
                                required>
                            <option value="">Select unit</option>

                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}"
                                    @selected(old('unit_id', $maintenanceRequest->unit_id ?? '') == $unit->id)>
                                    {{ $unit->building?->property?->name }}
                                    — Unit {{ $unit->unit_number }}
                                </option>
                            @endforeach
                        </select>

                        @error('unit_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Tenant <span class="text-danger">*</span>
                        </label>

                        <select name="tenant_id"
                                id="tenant_id"
                                class="form-select @error('tenant_id') is-invalid @enderror"
                                required>
                            <option value="">Select tenant</option>

                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}"
                                    @selected(old('tenant_id', $maintenanceRequest->tenant_id ?? '') == $tenant->id)>
                                    {{ $tenant->full_name }}
                                    @if($tenant->tenant_number)
                                        — {{ $tenant->tenant_number }}
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        @error('tenant_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <small class="text-muted">
                            Select a tenant who has a lease for the selected unit.
                        </small>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            Request Title <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                               name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $maintenanceRequest->title ?? '') }}"
                               placeholder="e.g. Water leakage in bathroom"
                               required>

                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Category <span class="text-danger">*</span>
                        </label>

                        <select name="category"
                                class="form-select @error('category') is-invalid @enderror"
                                required>
                            <option value="">Select category</option>

                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}"
                                    @selected(old('category', $maintenanceRequest->category ?? '') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Priority <span class="text-danger">*</span>
                        </label>

                        <select name="priority"
                                class="form-select @error('priority') is-invalid @enderror"
                                required>
                            @foreach($priorities as $key => $label)
                                <option value="{{ $key }}"
                                    @selected(old('priority', $maintenanceRequest->priority ?? 'medium') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                        @error('priority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            Description <span class="text-danger">*</span>
                        </label>

                        <textarea name="description"
                                  rows="5"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Describe the maintenance issue in detail..."
                                  required>{{ old('description', $maintenanceRequest->description ?? '') }}</textarea>

                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0">Scheduling and Cost</h6>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Preferred Visit Date</label>

                        <input type="datetime-local"
                               name="preferred_visit_at"
                               class="form-control @error('preferred_visit_at') is-invalid @enderror"
                               value="{{ old('preferred_visit_at', isset($maintenanceRequest) && $maintenanceRequest->preferred_visit_at ? $maintenanceRequest->preferred_visit_at->format('Y-m-d\TH:i') : '') }}">

                        @error('preferred_visit_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Estimated Cost (RWF)</label>

                        <input type="number"
                               name="estimated_cost"
                               step="0.01"
                               min="0"
                               class="form-control @error('estimated_cost') is-invalid @enderror"
                               value="{{ old('estimated_cost', $maintenanceRequest->estimated_cost ?? '') }}"
                               placeholder="0.00">

                        @error('estimated_cost')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Supporting Attachment</label>

                        <input type="file"
                               name="attachment"
                               class="form-control @error('attachment') is-invalid @enderror"
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">

                        <small class="text-muted">
                            JPG, PNG, PDF, DOC or DOCX. Maximum 5 MB.
                        </small>

                        @error('attachment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        @if($isEdit && $maintenanceRequest->attachment_path)
                            <div class="mt-3">
                                <a href="{{ asset('storage/' . $maintenanceRequest->attachment_path) }}"
                                   target="_blank"
                                   rel="noopener">
                                    <i class="bi bi-paperclip me-1"></i>
                                    View current attachment
                                </a>

                                <div class="form-check mt-2">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="remove_attachment"
                                           value="1"
                                           id="remove_attachment">

                                    <label class="form-check-label" for="remove_attachment">
                                        Remove current attachment
                                    </label>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>

    </div>

    <div class="col-12 col-lg-4">

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0">Request Guidelines</h6>
            </div>

            <div class="card-body">
                <div class="d-flex gap-3 mb-3">
                    <span class="text-danger">
                        <i class="bi bi-exclamation-circle fs-5"></i>
                    </span>
                    <div>
                        <strong class="d-block">Urgent</strong>
                        <small class="text-muted">
                            Issues that require immediate attention, such as serious water leaks or electrical hazards.
                        </small>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-3">
                    <span class="text-warning">
                        <i class="bi bi-clock fs-5"></i>
                    </span>
                    <div>
                        <strong class="d-block">High / Medium</strong>
                        <small class="text-muted">
                            Issues that should be addressed promptly according to their impact.
                        </small>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <span class="text-secondary">
                        <i class="bi bi-calendar-check fs-5"></i>
                    </span>
                    <div>
                        <strong class="d-block">Low</strong>
                        <small class="text-muted">
                            Minor issues that can be scheduled for routine maintenance.
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>
                {{ $isEdit ? 'Save Changes' : 'Create Request' }}
            </button>

            <a href="{{ route('property-manager.maintenance-requests.index') }}"
               class="btn btn-light border">
                Cancel
            </a>
        </div>

    </div>

</div>
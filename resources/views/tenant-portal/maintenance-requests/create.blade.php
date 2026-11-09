
@extends('layouts.app')

@section('title', 'Report Maintenance')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <a href="{{ route('tenant-portal.maintenance-requests.index') }}"
           class="text-decoration-none text-secondary small">
            <i class="bi bi-arrow-left me-1"></i> My maintenance requests
        </a>
        <h1 class="h3 fw-bold mt-3 mb-1" style="color:#19265d;">Report a maintenance issue</h1>
        <p class="text-secondary mb-0">Tell your property manager what needs attention.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form method="POST"
                  action="{{ route('tenant-portal.maintenance-requests.store') }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Rental unit <span class="text-danger">*</span></label>
                        <select name="unit_id" class="form-select" required>
                            <option value="">Select your rental unit</option>
                            @foreach ($leases as $lease)
                                @if ($lease->unit)
                                    <option value="{{ $lease->unit->id }}"
                                        @selected((string) old('unit_id') === (string) $lease->unit->id)>
                                        {{ $lease->unit->building?->property?->name ?? 'Property' }}
                                        — {{ $lease->unit->unit_number }}
                                        @if ($lease->unit->building)
                                            ({{ $lease->unit->building->name }})
                                        @endif
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        @if ($leases->isEmpty())
                            <div class="form-text text-danger">
                                No active rental was found. Contact your property manager if you believe this is an error.
                            </div>
                        @endif
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Issue title <span class="text-danger">*</span></label>
                        <input name="title" class="form-control" maxlength="180"
                               value="{{ old('title') }}"
                               placeholder="e.g. Water leaking under the kitchen sink" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="">Choose category</option>
                            @foreach ([
                                'plumbing' => 'Plumbing',
                                'electrical' => 'Electrical',
                                'appliance' => 'Appliances',
                                'structural' => 'Structural',
                                'pest_control' => 'Pest control',
                                'security' => 'Security',
                                'cleaning' => 'Cleaning',
                                'other' => 'Other',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('category') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            <option value="low" @selected(old('priority') === 'low')>Low — can wait</option>
                            <option value="normal" @selected(old('priority', 'normal') === 'normal')>Normal</option>
                            <option value="high" @selected(old('priority') === 'high')>High — needs attention soon</option>
                            <option value="emergency" @selected(old('priority') === 'emergency')>Emergency — urgent safety or property risk</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Preferred visit date and time</label>
                        <input type="datetime-local" name="preferred_visit_at"
                               class="form-control" value="{{ old('preferred_visit_at') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Describe the problem <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="5"
                                  maxlength="5000" required
                                  placeholder="Explain where the issue is, when it started, and any other useful details.">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Photo or document</label>
                        <input type="file" name="attachment" class="form-control"
                               accept=".jpg,.jpeg,.png,.pdf">
                        <div class="form-text">Optional. JPG, PNG or PDF, up to 5 MB.</div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                    <a href="{{ route('tenant-portal.maintenance-requests.index') }}"
                       class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn text-white px-4"
                            style="background:#D05208;"
                            @disabled($leases->isEmpty())>
                        <i class="bi bi-send me-2"></i>Submit request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

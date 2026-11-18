@extends('layouts.app')

@section('title', 'Maintenance Request Details')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="{{ route('property-manager.maintenance-requests.index') }}"
               class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back to Maintenance
            </a>

            <h3 class="fw-bold mt-2 mb-1">Maintenance Request</h3>
            <p class="text-muted mb-0">{{ $maintenanceRequest->request_number }}</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('property-manager.maintenance-requests.edit', $maintenanceRequest->id) }}"
               class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Edit Request
            </a>

            <form method="POST"
                  action="{{ route('property-manager.maintenance-requests.destroy', $maintenanceRequest->id) }}"
                  onsubmit="return confirm('Delete this maintenance request?')">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-trash me-1"></i> Delete
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $statusClass = [
            'open' => 'primary',
            'in_progress' => 'info',
            'scheduled' => 'warning',
            'on_hold' => 'secondary',
            'resolved' => 'success',
            'closed' => 'dark',
            'rejected' => 'danger',
        ][$maintenanceRequest->status] ?? 'secondary';

        $priorityClass = [
            'low' => 'secondary',
            'medium' => 'info',
            'high' => 'warning',
            'urgent' => 'danger',
        ][$maintenanceRequest->priority] ?? 'secondary';
    @endphp

    <div class="row g-4">

        <div class="col-12 col-lg-8">

            {{-- Request overview --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">

                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }}">
                                    {{ $statuses[$maintenanceRequest->status] ?? ucfirst(str_replace('_', ' ', $maintenanceRequest->status)) }}
                                </span>

                                <span class="badge bg-{{ $priorityClass }}-subtle text-{{ $priorityClass }}">
                                    {{ $priorities[$maintenanceRequest->priority] ?? ucfirst($maintenanceRequest->priority) }} Priority
                                </span>
                            </div>

                            <h4 class="fw-bold mb-2">
                                {{ $maintenanceRequest->title }}
                            </h4>

                            <p class="text-muted mb-0">
                                {{ $maintenanceRequest->description }}
                            </p>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-sm-4">
                            <small class="text-muted d-block">Category</small>
                            <strong>
                                {{ ucfirst(str_replace('_', ' ', $maintenanceRequest->category)) }}
                            </strong>
                        </div>

                        <div class="col-sm-4">
                            <small class="text-muted d-block">Submitted</small>
                            <strong>
                                {{ $maintenanceRequest->created_at?->format('d M Y, h:i A') }}
                            </strong>
                        </div>

                        <div class="col-sm-4">
                            <small class="text-muted d-block">Created By</small>
                            <strong>{{ $maintenanceRequest->creator?->name ?? '—' }}</strong>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Tenant and property --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Tenant and Property Information</h6>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Tenant</small>
                            <strong>{{ $maintenanceRequest->tenant?->full_name ?? '—' }}</strong>

                            <div class="small text-muted">
                                {{ $maintenanceRequest->tenant?->email }}
                            </div>

                            <div class="small text-muted">
                                {{ $maintenanceRequest->tenant?->phone }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Property</small>
                            <strong>
                                {{ $maintenanceRequest->unit?->building?->property?->name ?? '—' }}
                            </strong>

                            <div class="small text-muted">
                                Unit {{ $maintenanceRequest->unit?->unit_number ?? '—' }}
                            </div>

                            <div class="small text-muted">
                                {{ $maintenanceRequest->unit?->building?->name ?? '' }}
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Attachment --}}
            @if($maintenanceRequest->attachment_path)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0">Supporting Attachment</h6>
                    </div>

                    <div class="card-body">
                        <a href="{{ asset('storage/' . $maintenanceRequest->attachment_path) }}"
                           target="_blank"
                           rel="noopener"
                           class="text-decoration-none">
                            <i class="bi bi-paperclip me-2"></i>
                            View or download attachment
                            <i class="bi bi-box-arrow-up-right ms-1"></i>
                        </a>
                    </div>
                </div>
            @endif

            {{-- Activity history --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Activity History</h6>
                </div>

                <div class="card-body">
                    @forelse($maintenanceRequest->activities as $activity)
                        <div class="d-flex gap-3 mb-4">
                            <div>
                                <span class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                      style="width:38px;height:38px;">
                                    <i class="bi bi-clock-history"></i>
                                </span>
                            </div>

                            <div class="flex-grow-1">
                                <div class="fw-semibold">
                                    {{ $activity->description }}
                                </div>

                                <div class="small text-muted">
                                    {{ $activity->user?->name ?? 'System' }}
                                    &middot;
                                    {{ $activity->created_at?->format('d M Y, h:i A') }}
                                </div>

                                @if($activity->old_status || $activity->new_status)
                                    <div class="small mt-1">
                                        @if($activity->old_status)
                                            {{ ucfirst(str_replace('_', ' ', $activity->old_status)) }}
                                        @endif

                                        @if($activity->old_status && $activity->new_status)
                                            <i class="bi bi-arrow-right mx-1"></i>
                                        @endif

                                        @if($activity->new_status)
                                            <strong>
                                                {{ ucfirst(str_replace('_', ' ', $activity->new_status)) }}
                                            </strong>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No activity has been recorded yet.</p>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Right side --}}
        <div class="col-12 col-lg-4">
            @include('maintenance._assign-panel')

            {{-- Status update --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Update Maintenance Status</h6>
                </div>

                <div class="card-body">
                    <form method="POST"
                          action="{{ route('property-manager.maintenance-requests.status', $maintenanceRequest->id) }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status</label>

                            <select name="status" class="form-select" required>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}"
                                        @selected(old('status', $maintenanceRequest->status) === $key)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Scheduled Visit</label>

                            <input type="datetime-local"
                                   name="scheduled_at"
                                   class="form-control"
                                   value="{{ old('scheduled_at', $maintenanceRequest->scheduled_at?->format('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Resolution Date</label>

                            <input type="datetime-local"
                                   name="resolved_at"
                                   class="form-control"
                                   value="{{ old('resolved_at', $maintenanceRequest->resolved_at?->format('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Actual Cost</label>

                            <input type="number"
                                   name="actual_cost"
                                   step="0.01"
                                   min="0"
                                   class="form-control"
                                   value="{{ old('actual_cost', $maintenanceRequest->actual_cost) }}"
                                   placeholder="0.00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Resolution Notes</label>

                            <textarea name="resolution_notes"
                                      rows="4"
                                      class="form-control"
                                      placeholder="Describe the work completed or current progress...">{{ old('resolution_notes', $maintenanceRequest->resolution_notes) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i> Update Status
                        </button>
                    </form>
                </div>
            </div>

            {{-- Scheduling and cost --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Scheduling and Costs</h6>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Preferred Visit</small>
                        <strong>
                            {{ $maintenanceRequest->preferred_visit_at?->format('d M Y, h:i A') ?? 'Not specified' }}
                        </strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Scheduled Visit</small>
                        <strong>
                            {{ $maintenanceRequest->scheduled_at?->format('d M Y, h:i A') ?? 'Not scheduled' }}
                        </strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Estimated Cost</small>
                        <strong>
                            {{ $maintenanceRequest->estimated_cost !== null
                                ? number_format((float) $maintenanceRequest->estimated_cost, 2)
                                : 'Not estimated' }}
                        </strong>
                    </div>

                    <div>
                        <small class="text-muted d-block">Actual Cost</small>
                        <strong>
                            {{ $maintenanceRequest->actual_cost !== null
                                ? number_format((float) $maintenanceRequest->actual_cost, 2)
                                : 'Not recorded' }}
                        </strong>
                    </div>

                    @if($maintenanceRequest->resolution_notes)
                        <hr>
                        <small class="text-muted d-block mb-1">Resolution Notes</small>
                        <p class="mb-0">{{ $maintenanceRequest->resolution_notes }}</p>
                    @endif
                </div>
            </div>

            {{-- Assigned user --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Assignment</h6>
                </div>

                <div class="card-body">
                    <small class="text-muted d-block mb-1">Assigned To</small>
                    <strong>
                        {{ $maintenanceRequest->assignee?->name ?? 'Not assigned' }}
                    </strong>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
@extends('layouts.app')

@section('title', 'Maintenance Request Details')

@section('content')
@php
    $statusStyles = [
        'submitted'   => 'primary',
        'acknowledged' => 'info',
        'scheduled'   => 'warning',
        'in_progress' => 'warning',
        'resolved'    => 'success',
        'closed'      => 'secondary',
        'rejected'    => 'danger',
    ];

    $priorityStyles = [
        'low'       => 'success',
        'normal'    => 'secondary',
        'high'      => 'warning',
        'emergency' => 'danger',
    ];

    $status = strtolower($maintenanceRequest->status ?? 'submitted');
    $priority = strtolower($maintenanceRequest->priority ?? 'normal');

    $statusClass = $statusStyles[$status] ?? 'secondary';
    $priorityClass = $priorityStyles[$priority] ?? 'secondary';

    $unit = $maintenanceRequest->unit;
    $building = $unit?->building;
    $property = $building?->property;

    $timeline = $maintenanceRequest->activities ?? collect();
@endphp

<div class="container-fluid py-4 maintenance-page">

    {{-- Page header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="{{ route('tenant-portal.maintenance-requests.index') }}"
               class="text-decoration-none text-muted small d-inline-flex align-items-center gap-2 mb-2">
                <i class="bi bi-arrow-left"></i>
                Back to maintenance requests
            </a>

            <h1 class="h3 fw-bold mb-1">Maintenance Request</h1>

            <p class="text-muted mb-0">
                Request #{{ $maintenanceRequest->request_number }}
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill text-bg-{{ $priorityClass }} px-3 py-2">
                {{ ucfirst($priority) }} priority
            </span>

            <span class="badge rounded-pill text-bg-{{ $statusClass }} px-3 py-2">
                {{ ucwords(str_replace('_', ' ', $status)) }}
            </span>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">

        {{-- Main content --}}
        <div class="col-lg-8">

            {{-- Request overview --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                        <div>
                            <div class="request-icon mb-3">
                                <i class="bi bi-tools"></i>
                            </div>

                            <h2 class="h4 fw-bold mb-2">
                                {{ $maintenanceRequest->title }}
                            </h2>

                            <div class="text-muted small">
                                Submitted
                                {{ $maintenanceRequest->created_at?->format('d M Y, h:i A') ?? '—' }}
                            </div>
                        </div>

                        <div class="text-lg-end">
                            <span class="small text-muted d-block mb-1">Request status</span>
                            <span class="fw-semibold">
                                {{ ucwords(str_replace('_', ' ', $status)) }}
                            </span>
                        </div>
                    </div>

                    <hr class="border-light-subtle">

                    <h3 class="h6 fw-bold mb-3">Issue description</h3>

                    <div class="description-box">
                        {!! nl2br(e($maintenanceRequest->description)) !!}
                    </div>

                    <div class="row g-3 mt-3">
                        <div class="col-sm-6">
                            <div class="detail-tile">
                                <span class="detail-label">Category</span>
                                <span class="detail-value">
                                    {{ ucwords(str_replace('_', ' ', $maintenanceRequest->category)) }}
                                </span>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="detail-tile">
                                <span class="detail-label">Preferred visit</span>
                                <span class="detail-value">
                                    {{ $maintenanceRequest->preferred_visit_at?->format('d M Y, h:i A') ?? 'Not specified' }}
                                </span>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="detail-tile">
                                <span class="detail-label">Scheduled visit</span>
                                <span class="detail-value">
                                    {{ $maintenanceRequest->scheduled_at?->format('d M Y, h:i A') ?? 'Not scheduled yet' }}
                                </span>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="detail-tile">
                                <span class="detail-label">Last updated</span>
                                <span class="detail-value">
                                    {{ $maintenanceRequest->updated_at?->format('d M Y, h:i A') ?? '—' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Attachment --}}
                    @if ($maintenanceRequest->attachment_path)
                        <div class="attachment-panel mt-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="attachment-icon">
                                    <i class="bi bi-paperclip"></i>
                                </div>

                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold">Attached file</div>
                                    <div class="text-muted small text-truncate">
                                        {{ basename($maintenanceRequest->attachment_path) }}
                                    </div>
                                </div>

                                <a href="{{ upload_url($maintenanceRequest->attachment_path) }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>
                                    View file
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Property and unit --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <i class="bi bi-building text-primary fs-5"></i>
                        <h2 class="h5 fw-bold mb-0">Property information</h2>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-tile h-100">
                                <span class="detail-label">Property</span>
                                <span class="detail-value">
                                    {{ $property?->name ?? 'Property information unavailable' }}
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-tile h-100">
                                <span class="detail-label">Building</span>
                                <span class="detail-value">
                                    {{ $building?->name ?? '—' }}
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-tile h-100">
                                <span class="detail-label">Unit number</span>
                                <span class="detail-value">
                                    {{ $unit?->unit_number ?? '—' }}
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-tile h-100">
                                <span class="detail-label">Floor</span>
                                <span class="detail-value">
                                    {{ $unit?->floor?->name ?? '—' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Resolution information --}}
            @if (in_array($status, ['resolved', 'closed']))
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <h2 class="h5 fw-bold mb-0">Resolution details</h2>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="detail-tile">
                                    <span class="detail-label">Resolved on</span>
                                    <span class="detail-value">
                                        {{ $maintenanceRequest->resolved_at?->format('d M Y, h:i A') ?? '—' }}
                                    </span>
                                </div>
                            </div>

                            @if ($maintenanceRequest->actual_cost !== null)
                                <div class="col-md-6">
                                    <div class="detail-tile">
                                        <span class="detail-label">Recorded maintenance cost</span>
                                        <span class="detail-value">
                                            {{ number_format((float) $maintenanceRequest->actual_cost, 0) }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if ($maintenanceRequest->resolution_notes)
                            <div class="mt-3">
                                <span class="detail-label d-block mb-2">Manager's notes</span>
                                <div class="description-box">
                                    {!! nl2br(e($maintenanceRequest->resolution_notes)) !!}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            @include('maintenance._tenant-confirm')

            {{-- Progress --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-4">Request progress</h2>

                    @php
                        $progressSteps = [
                            'submitted' => 'Request submitted',
                            'acknowledged' => 'Request acknowledged',
                            'scheduled' => 'Visit scheduled',
                            'in_progress' => 'Work in progress',
                            'resolved' => 'Issue resolved',
                            'closed' => 'Request closed',
                        ];

                        $progressOrder = [
                            'submitted' => 0,
                            'acknowledged' => 1,
                            'scheduled' => 2,
                            'in_progress' => 3,
                            'resolved' => 4,
                            'closed' => 5,
                        ];

                        $currentStep = $progressOrder[$status] ?? -1;
                        $isRejected = $status === 'rejected';
                    @endphp

                    @if ($isRejected)
                        <div class="alert alert-danger mb-0">
                            <div class="fw-semibold mb-1">Request rejected</div>
                            <div class="small">
                                Your request was rejected. Check the activity history for any available explanation.
                            </div>
                        </div>
                    @else
                        <div class="progress-timeline">
                            @foreach ($progressSteps as $step => $label)
                                @php
                                    $stepIndex = $progressOrder[$step];
                                    $completed = $currentStep >= $stepIndex;
                                    $isCurrent = $currentStep === $stepIndex;
                                @endphp

                                <div class="progress-step {{ $completed ? 'completed' : '' }} {{ $isCurrent ? 'current' : '' }}">
                                    <div class="progress-marker">
                                        @if ($completed)
                                            <i class="bi bi-check-lg"></i>
                                        @else
                                            <span>{{ $stepIndex + 1 }}</span>
                                        @endif
                                    </div>

                                    <div class="progress-step-content">
                                        <div class="fw-semibold {{ $completed ? '' : 'text-muted' }}">
                                            {{ $label }}
                                        </div>

                                        @if ($isCurrent)
                                            <small class="text-muted">Current stage</small>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Activity history --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-4">Activity history</h2>

                    @forelse ($timeline as $activity)
                        <div class="activity-item">
                            <div class="activity-dot"></div>

                            <div class="activity-content">
                                <div class="fw-semibold">
                                    {{ $activity->description }}
                                </div>

                                @if ($activity->old_status || $activity->new_status)
                                    <div class="small text-muted mt-1">
                                        @if ($activity->old_status)
                                            {{ ucwords(str_replace('_', ' ', $activity->old_status)) }}
                                        @else
                                            New request
                                        @endif

                                        @if ($activity->new_status)
                                            <i class="bi bi-arrow-right mx-1"></i>
                                            {{ ucwords(str_replace('_', ' ', $activity->new_status)) }}
                                        @endif
                                    </div>
                                @endif

                                <div class="small text-muted mt-2">
                                    {{ $activity->created_at?->format('d M Y, h:i A') ?? '—' }}
                                    @if ($activity->user)
                                        <span> · {{ $activity->user->name }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-3">
                            <i class="bi bi-clock-history fs-3 text-muted"></i>
                            <p class="text-muted small mt-2 mb-0">
                                No activity has been recorded yet.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Help --}}
            <div class="help-card rounded-4 p-4">
                <div class="help-icon mb-3">
                    <i class="bi bi-info-circle"></i>
                </div>

                <h3 class="h6 fw-bold">Need assistance?</h3>
                <p class="text-muted small mb-3">
                    If the issue persists after resolution, submit a new maintenance request and include the relevant details.
                </p>

                <a href="{{ route('tenant-portal.maintenance-requests.create') }}"
                   class="btn btn-primary w-100">
                    <i class="bi bi-plus-lg me-1"></i>
                    New maintenance request
                </a>
            </div>

        </div>
    </div>
</div>

<style>
    .maintenance-page {
        color: #202938;
        font-family: var(--terra-font);
    }

    .maintenance-page .card {
        background: #fff;
    }

    .maintenance-page .text-primary {
        color: #D05208 !important;
    }

    .maintenance-page .btn-primary {
        background-color: #D05208;
        border-color: #D05208;
    }

    .maintenance-page .btn-primary:hover {
        background-color: #b84808;
        border-color: #b84808;
    }

    .maintenance-page .btn-outline-primary {
        color: #D05208;
        border-color: #D05208;
    }

    .maintenance-page .btn-outline-primary:hover {
        color: #fff;
        background-color: #D05208;
        border-color: #D05208;
    }

    .request-icon,
    .attachment-icon,
    .help-icon {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
        background: #fff1e8;
        color: #D05208;
        font-size: 1.25rem;
    }

    .detail-tile {
        display: flex;
        flex-direction: column;
        gap: 7px;
        padding: 15px;
        border: 1px solid #edf0f3;
        border-radius: 12px;
        background: #fafbfc;
        min-width: 0;
    }

    .detail-label {
        color: #737d8b;
        font-size: .78rem;
        font-weight: 500;
    }

    .detail-value {
        color: #202938;
        font-size: .92rem;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .description-box {
        padding: 16px;
        background: #fafbfc;
        border: 1px solid #edf0f3;
        border-radius: 12px;
        color: #4b5563;
        line-height: 1.7;
        overflow-wrap: anywhere;
    }

    .attachment-panel {
        border: 1px solid #edf0f3;
        border-radius: 12px;
        padding: 14px;
    }

    .min-w-0 {
        min-width: 0;
    }

    .progress-timeline {
        display: flex;
        flex-direction: column;
    }

    .progress-step {
        position: relative;
        display: flex;
        gap: 13px;
        min-height: 62px;
    }

    .progress-step:not(:last-child)::before {
        content: "";
        position: absolute;
        top: 32px;
        left: 14px;
        width: 2px;
        height: calc(100% - 15px);
        background: #e5e7eb;
    }

    .progress-step.completed:not(:last-child)::before {
        background: #D05208;
    }

    .progress-marker {
        z-index: 1;
        width: 30px;
        height: 30px;
        flex: 0 0 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        border: 2px solid #e5e7eb;
        background: #fff;
        color: #8992a0;
        font-size: .8rem;
    }

    .progress-step.completed .progress-marker {
        background: #D05208;
        border-color: #D05208;
        color: #fff;
    }

    .progress-step.current .progress-marker {
        box-shadow: 0 0 0 4px #fff1e8;
    }

    .progress-step-content {
        padding-top: 4px;
        padding-bottom: 18px;
        font-size: .88rem;
    }

    .activity-item {
        position: relative;
        display: flex;
        gap: 13px;
        padding-bottom: 23px;
    }

    .activity-item:last-child {
        padding-bottom: 0;
    }

    .activity-item:not(:last-child)::before {
        content: "";
        position: absolute;
        top: 12px;
        bottom: 0;
        left: 5px;
        width: 2px;
        background: #edf0f3;
    }

    .activity-dot {
        z-index: 1;
        flex: 0 0 12px;
        width: 12px;
        height: 12px;
        margin-top: 4px;
        border-radius: 50%;
        background: #D05208;
        border: 3px solid #fff1e8;
    }

    .activity-content {
        min-width: 0;
        font-size: .88rem;
        overflow-wrap: anywhere;
    }

    .help-card {
        background: #fff8f3;
        border: 1px solid #fbe5d7;
    }

    @media (max-width: 575.98px) {
        .maintenance-page .card-body {
            padding: 1.15rem !important;
        }

        .maintenance-page .badge {
            font-size: .72rem;
        }
    }
</style>
@endsection
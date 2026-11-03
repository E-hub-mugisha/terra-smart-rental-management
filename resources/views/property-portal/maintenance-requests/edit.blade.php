@extends('layouts.property-management')

@section('title', 'Edit Maintenance Request')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <a href="{{ route('property-manager.maintenance-requests.show', $maintenanceRequest->id) }}"
           class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i> Back to Request
        </a>

        <h3 class="fw-bold mt-2 mb-1">Edit Maintenance Request</h3>
        <p class="text-muted mb-0">
            {{ $maintenanceRequest->request_number }}
        </p>
    </div>

    <form method="POST"
          action="{{ route('property-manager.maintenance-requests.update', $maintenanceRequest->id) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('property-portal.maintenance-requests._form')
    </form>

</div>
@endsection
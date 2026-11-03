@extends('layouts.app')

@section('title', 'Create Maintenance Request')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <a href="{{ route('property-manager.maintenance-requests.index') }}"
           class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i> Back to Maintenance
        </a>

        <h3 class="fw-bold mt-2 mb-1">New Maintenance Request</h3>
        <p class="text-muted mb-0">
            Record a maintenance issue reported by a tenant.
        </p>
    </div>

    <form method="POST"
          action="{{ route('property-manager.maintenance-requests.store') }}"
          enctype="multipart/form-data">
        @csrf

        @include('property-portal.maintenance-requests._form')
    </form>

</div>
@endsection
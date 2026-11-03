
@extends('layouts.app')

@section('title', 'Add Property')

@section('content')

<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <a href="{{ route('property-manager.properties.index') }}"
                   class="text-decoration-none text-muted">
                    Properties
                </a>

                <i class="bi bi-chevron-right small text-muted"></i>

                <span class="text-dark fw-semibold">Add Property</span>
            </div>

            <h3 class="fw-bold mb-1">Register a Property</h3>

            <p class="text-muted mb-0">
                Add a new property to your portfolio.
            </p>
        </div>

        <a href="{{ route('property-manager.properties.index') }}"
           class="btn btn-light border">
            <i class="bi bi-arrow-left me-2"></i>
            Back to Properties
        </a>

    </div>

    <div class="alert alert-light border d-flex align-items-start gap-3 mb-4">
        <i class="bi bi-person-check fs-4 text-success"></i>

        <div>
            <div class="fw-semibold">Property ownership</div>
            <div class="small text-muted">
                This property will automatically be registered under
                your owner profile.
            </div>
        </div>
    </div>

    <form method="POST"
          action="{{ route('property-manager.properties.store') }}"
          enctype="multipart/form-data">

        @csrf

        @include('property-portal.properties._form', [
            'property' => $property,
        ])

    </form>

</div>

@endsection

@extends('layouts.app')

@section('title', 'Edit Property')

@section('content')
<div class="container-fluid px-0 py-2">

    <div class="mb-3">
        <a href="{{ route('admin.properties.show', $property) }}"
           class="text-decoration-none small fw-semibold"
           style="color:#19265d;">
            <i class="bi bi-arrow-left me-1"></i> Back to property
        </a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="text-uppercase small fw-bold mb-2"
                 style="color:#D05208;letter-spacing:.09em;">
                Property administration
            </div>
            <h1 class="h3 fw-bold mb-1" style="color:#19265d;">Edit property</h1>
            <p class="text-secondary mb-0">
                Update property information, location, assigned managers and images.
            </p>
        </div>

        <span class="badge rounded-pill px-3 py-2"
              style="background:#edf0fa;color:#19265d;">
            <i class="bi bi-hash me-1"></i>
            {{ $property->property_code ?: 'Property record' }}
        </span>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"
             role="alert" style="border-radius:12px;">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"
                    aria-label="Close"></button>
        </div>
    @endif

    <form method="POST"
          action="{{ route('admin.properties.update', $property) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.properties._form')
    </form>
</div>
@endsection

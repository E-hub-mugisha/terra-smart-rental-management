@extends('layouts.app')

@section('title', 'Edit Tenant')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="mb-1">Edit Tenant</h3>
            <p class="text-muted mb-0">
                Update {{ $tenant->full_name }}'s details.
            </p>
        </div>

        <a href="{{ route('admin.tenants.show', $tenant) }}"
           class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Back to Details
        </a>
    </div>

    <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}">
        @csrf
        @method('PUT')

        @include('admin.tenants._form')
    </form>
</div>
@endsection
@extends('layouts.app')

@section('title', 'Add Tenant')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="{{ route('property-manager.tenants.index') }}"
            class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i> Back to tenants
        </a>
        <h3 class="fw-bold mt-2 mb-1">Add tenant</h3>
        <p class="text-muted mb-0">Create a tenant profile and login account.</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('property-manager.tenants.store') }}">
        @csrf
        @include('property-portal.tenants._form')
    </form>
</div>
@endsection
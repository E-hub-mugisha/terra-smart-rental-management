@extends('layouts.app')

@section('title', 'Create Tenant')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="mb-1">Create Tenant</h3>
            <p class="text-muted mb-0">
                Register a tenant and create their login account. The credentials are
                emailed to the tenant automatically.
            </p>
        </div>

        <a href="{{ route('admin.tenants.index') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Back to Tenants
        </a>
    </div>

    <form method="POST" action="{{ route('admin.tenants.store') }}">
        @csrf
        @include('admin.tenants._form')
    </form>
</div>
@endsection
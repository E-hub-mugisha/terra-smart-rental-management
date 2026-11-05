@extends('layouts.app')

@section('title', 'Create Tenant')

@section('page_heading', 'Create Tenant')

@section('page_subtitle', 'Register a tenant and create their login account.')

@section('content')
<div class="tenant-form-page">

    {{-- Page heading --}}
    <div class="tf-intro">
        <div>
            <div class="tf-breadcrumb">
                <a href="{{ \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/') }}">Dashboard</a>
                <i class="bi bi-chevron-right"></i>
                <a href="{{ route('admin.tenants.index') }}">Tenants</a>
                <i class="bi bi-chevron-right"></i>
                <span>Create tenant</span>
            </div>

            <h1 class="tf-title">Create tenant</h1>
            <p class="tf-description">
                Register a tenant and create their login account. A secure password is generated
                automatically and the credentials are emailed to the tenant, so you do not need to set one.
            </p>
        </div>

        <a href="{{ route('admin.tenants.index') }}" class="tf-btn tf-btn-outline">
            <i class="bi bi-arrow-left"></i> Back to tenants
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm" role="alert" style="border-radius:12px;font-size:13px;">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-2"></i>The tenant could not be saved. Please fix the highlighted fields.</div>
            <ul class="mb-0 ps-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.tenants.store') }}" novalidate>
        @csrf
        @include('admin.tenants._form')
    </form>
</div>
@endsection
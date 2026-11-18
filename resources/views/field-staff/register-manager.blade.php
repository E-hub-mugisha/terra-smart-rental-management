@extends('layouts.app')

@section('title', 'Register property manager')
@section('page_heading', 'Register a property manager')
@section('page_subtitle', 'The manager receives a Terra login by email and can then be assigned to properties by the owner or Terra.')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a href="{{ route('field-staff.dashboard') }}" class="small text-decoration-none text-secondary d-inline-flex align-items-center gap-1"><i class="bi bi-arrow-left"></i> Field dashboard</a>
    <a href="{{ route('field-staff.owners.create') }}" class="small">Registering a property owner instead?</a>
</div>

@if($errors->any())
    <div class="alert alert-danger small"><i class="bi bi-exclamation-circle me-1"></i> Some details need attention. Check the highlighted fields.</div>
@endif

<div class="manager-page">
    <form method="POST" action="{{ route('field-staff.managers.store') }}">
        @csrf
        @include('admin.managers._form', ['registrationOnly' => true, 'cancelUrl' => route('field-staff.dashboard')])
    </form>
</div>
@endsection

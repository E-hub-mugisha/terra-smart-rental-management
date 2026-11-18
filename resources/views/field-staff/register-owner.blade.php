@extends('layouts.app')

@section('title', 'Register property owner')
@section('page_heading', 'Register a property owner')
@section('page_subtitle', 'The owner receives a Terra login by email. A Terra administrator verifies the details afterwards.')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a href="{{ route('field-staff.dashboard') }}" class="small text-decoration-none text-secondary d-inline-flex align-items-center gap-1"><i class="bi bi-arrow-left"></i> Field dashboard</a>
    <a href="{{ route('field-staff.managers.create') }}" class="small">Registering a property manager instead?</a>
</div>

@if($errors->any())
    <div class="alert alert-danger small"><i class="bi bi-exclamation-circle me-1"></i> Some details need attention. Check the highlighted fields.</div>
@endif

<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body p-3 p-md-4">
        <form method="POST" action="{{ route('field-staff.owners.store') }}">
            @csrf
            @include('admin.owners._form', ['registrationOnly' => true, 'cancelUrl' => route('field-staff.dashboard')])
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Record Property Expense')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <a href="{{ route('property-manager.expenses.index') }}"
           class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i> Back to Expenses
        </a>

        <h3 class="fw-bold mt-2 mb-1">Record Property Expense</h3>
        <p class="text-muted mb-0">
            Add a new expense and associate it with one of your properties.
        </p>
    </div>

    <form method="POST" action="{{ route('property-manager.expenses.store') }}">
        @csrf

        @include('property-portal.expenses._form')
    </form>

</div>
@endsection
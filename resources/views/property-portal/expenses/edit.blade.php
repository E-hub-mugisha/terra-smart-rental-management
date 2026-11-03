@extends('layouts.app')

@section('title', 'Edit Property Expense')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <a href="{{ route('property-manager.expenses.show', $expense->id) }}"
           class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i> Back to Expense
        </a>

        <h3 class="fw-bold mt-2 mb-1">Edit Property Expense</h3>
        <p class="text-muted mb-0">
            Update {{ $expense->expense_number }}.
        </p>
    </div>

    <form method="POST"
          action="{{ route('property-manager.expenses.update', $expense->id) }}">
        @csrf
        @method('PUT')

        @include('property-portal.expenses._form')
    </form>

</div>
@endsection

@extends('layouts.app')

@section('title', 'Edit Property Expense')
@section('page_heading', 'Edit Property Expense')
@section('page_subtitle', 'Update expense information and payment details.')

@push('styles')
<style>
    .expense-edit-page {
        --expense-orange: var(--terra-orange, #D05208);
        --expense-navy: var(--terra-navy, #19265d);
        --expense-border: var(--terra-border, #e7eaf0);
    }

    .expense-edit-page .expense-form-card {
        background: #fff;
        border: 1px solid var(--expense-border);
        border-radius: 16px;
        box-shadow: 0 6px 24px rgba(25, 38, 93, .045);
        padding: 24px;
    }

    .expense-edit-page .expense-form-section {
        padding: 20px;
        border: 1px solid var(--expense-border);
        border-radius: 12px;
        background: #fff;
    }

    .expense-edit-page .expense-form-icon {
        width: 40px;
        height: 40px;
        display: inline-grid;
        place-items: center;
        border-radius: 11px;
        background: #fff0e7;
        color: var(--expense-orange);
        font-size: 19px;
        flex-shrink: 0;
    }

    .expense-edit-page .form-label {
        color: #344054;
        font-size: .86rem;
        font-weight: 600;
    }

    .expense-edit-page .form-control,
    .expense-edit-page .form-select {
        min-height: 44px;
        border-color: #dfe3eb;
        border-radius: 9px;
    }

    .expense-edit-page textarea.form-control {
        min-height: auto;
    }

    .expense-edit-page .form-control:focus,
    .expense-edit-page .form-select:focus {
        border-color: var(--expense-orange);
        box-shadow: 0 0 0 .2rem rgba(208, 82, 8, .1);
    }

    .expense-edit-page .expense-form-submit {
        background: var(--expense-orange);
        border-color: var(--expense-orange);
        color: #fff;
        font-weight: 600;
        min-height: 42px;
    }

    .expense-edit-page .expense-form-submit:hover {
        background: #b94706;
        color: #fff;
    }

    @media (max-width: 575.98px) {
        .expense-edit-page .expense-form-card {
            padding: 14px;
        }

        .expense-edit-page .expense-form-section {
            padding: 15px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 expense-edit-page">
    <div class="mb-4">
        <div class="small text-muted mb-2">
            <a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">Dashboard</a>
            <span class="mx-2">/</span>
            <a href="{{ route('admin.property-expenses.index') }}" class="text-decoration-none text-muted">
                Property expenses
            </a>
            <span class="mx-2">/</span>
            <span class="text-dark">Edit expense</span>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1" style="color: var(--expense-navy)">
                    Edit expense
                </h1>
                <p class="text-muted mb-0">
                    Update the recorded information for
                    <strong>{{ $expense->expense_number }}</strong>.
                </p>
            </div>

            <a href="{{ route('admin.property-expenses.show', $expense) }}"
               class="btn btn-light border">
                <i class="bi bi-eye me-1"></i>View details
            </a>
        </div>
    </div>

    <div class="expense-form-card">
        <form method="POST" action="{{ route('admin.property-expenses.update', $expense) }}">
            @csrf
            @method('PUT')
            @include('admin.property-expenses._form', ['expense' => $expense])
        </form>
    </div>
</div>
@endsection

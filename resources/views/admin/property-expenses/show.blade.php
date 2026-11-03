
@extends('layouts.app')

@section('title', 'Expense Details')
@section('page_heading', 'Expense Details')
@section('page_subtitle', 'Review the recorded expense and payment information.')

@php
    $status = strtolower((string) $propertyExpense->status);

    $statusClasses = [
        'paid' => 'expense-detail-status-paid',
        'pending' => 'expense-detail-status-pending',
        'cancelled' => 'expense-detail-status-cancelled',
    ];

    $statusClass = $statusClasses[$status] ?? 'expense-detail-status-pending';

    $expenseDate = $propertyExpense->expense_date
        ? \Illuminate\Support\Carbon::parse($propertyExpense->expense_date)->format('d M Y')
        : '—';

    $createdDate = $propertyExpense->created_at
        ? $propertyExpense->created_at->format('d M Y, H:i')
        : '—';

    $updatedDate = $propertyExpense->updated_at
        ? $propertyExpense->updated_at->format('d M Y, H:i')
        : '—';
@endphp

@push('styles')
<style>
    .expense-detail-page {
        --expense-orange: var(--terra-orange, #D05208);
        --expense-navy: var(--terra-navy, #19265d);
        --expense-border: var(--terra-border, #e7eaf0);
        --expense-muted: var(--terra-muted, #697386);
        color: #20283a;
    }

    .expense-detail-page .expense-detail-panel {
        background: #fff;
        border: 1px solid var(--expense-border);
        border-radius: 16px;
        box-shadow: 0 6px 24px rgba(25, 38, 93, .045);
        overflow: hidden;
    }

    .expense-detail-page .expense-detail-panel-header {
        padding: 20px 22px;
        border-bottom: 1px solid var(--expense-border);
    }

    .expense-detail-page .expense-detail-panel-body {
        padding: 22px;
    }

    .expense-detail-page .expense-detail-label {
        color: var(--expense-muted);
        font-size: .78rem;
        margin-bottom: 6px;
    }

    .expense-detail-page .expense-detail-value {
        color: var(--expense-navy);
        font-size: .94rem;
        font-weight: 650;
        overflow-wrap: anywhere;
    }

    .expense-detail-page .expense-detail-muted {
        color: var(--expense-muted);
    }

    .expense-detail-page .expense-detail-number {
        color: var(--expense-orange);
        font-weight: 750;
        overflow-wrap: anywhere;
    }

    .expense-detail-page .expense-detail-amount {
        padding: 24px;
        background: #fff8f3;
        border: 1px solid #f6dfd0;
        border-radius: 14px;
    }

    .expense-detail-page .expense-detail-amount-value {
        color: var(--expense-navy);
        font-size: clamp(1.6rem, 3vw, 2.2rem);
        font-weight: 800;
        letter-spacing: -.04em;
        overflow-wrap: anywhere;
    }

    .expense-detail-page .expense-detail-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 11px;
        border-radius: 30px;
        font-size: .76rem;
        font-weight: 750;
        text-transform: capitalize;
    }

    .expense-detail-page .expense-detail-status::before {
        content: "";
        width: 7px;
        height: 7px;
        background: currentColor;
        border-radius: 50%;
    }

    .expense-detail-page .expense-detail-status-paid {
        color: #18794e;
        background: #e8f7ef;
    }

    .expense-detail-page .expense-detail-status-pending {
        color: #956300;
        background: #fff5d8;
    }

    .expense-detail-page .expense-detail-status-cancelled {
        color: #b42318;
        background: #ffebe9;
    }

    .expense-detail-page .expense-detail-property-icon {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        border-radius: 12px;
        background: #fff0e7;
        color: var(--expense-orange);
        font-size: 21px;
    }

    .expense-detail-page .expense-detail-divider {
        border-top: 1px solid var(--expense-border);
        margin: 18px 0;
    }

    .expense-detail-page .expense-detail-action {
        min-height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border-radius: 9px;
        padding: 9px 14px;
        font-size: .88rem;
        font-weight: 600;
        text-decoration: none;
    }

    .expense-detail-page .expense-detail-primary {
        color: #fff;
        background: var(--expense-orange);
        border: 1px solid var(--expense-orange);
    }

    .expense-detail-page .expense-detail-primary:hover {
        color: #fff;
        background: #b94706;
    }

    .expense-detail-page .expense-detail-secondary {
        color: var(--expense-navy);
        background: #fff;
        border: 1px solid var(--expense-border);
    }

    .expense-detail-page .expense-detail-secondary:hover {
        border-color: var(--expense-orange);
        color: var(--expense-orange);
    }

    .expense-detail-page .expense-detail-note {
        background: #f8f9fc;
        border: 1px solid var(--expense-border);
        border-radius: 11px;
        padding: 15px;
        white-space: pre-line;
        overflow-wrap: anywhere;
        font-size: .9rem;
    }

    @media (max-width: 575.98px) {
        .expense-detail-page .expense-detail-panel-body,
        .expense-detail-page .expense-detail-panel-header {
            padding: 16px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 expense-detail-page">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="small expense-detail-muted mb-2">
                <a href="{{ route('dashboard') }}" class="text-decoration-none expense-detail-muted">
                    Dashboard
                </a>
                <span class="mx-2">/</span>
                <a href="{{ route('admin.property-expenses.index') }}"
                   class="text-decoration-none expense-detail-muted">
                    Property expenses
                </a>
                <span class="mx-2">/</span>
                <span class="text-dark">Expense details</span>
            </div>

            <h1 class="h3 fw-bold mb-1" style="color: var(--expense-navy)">
                Expense details
            </h1>
            <p class="expense-detail-muted mb-0">
                Review the complete record and its associated payment information.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.property-expenses.index') }}"
               class="expense-detail-action expense-detail-secondary">
                <i class="bi bi-arrow-left"></i>Back
            </a>

            <a href="{{ route('admin.property-expenses.edit', $propertyExpense) }}"
               class="expense-detail-action expense-detail-primary">
                <i class="bi bi-pencil"></i>Edit expense
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Main expense information --}}
        <div class="col-12 col-xl-8">
            <div class="expense-detail-panel mb-4">
                <div class="expense-detail-panel-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="small expense-detail-muted mb-1">Expense number</div>
                        <div class="expense-detail-number">
                            {{ $propertyExpense->expense_number }}
                        </div>
                    </div>

                    <span class="expense-detail-status {{ $statusClass }}">
                        {{ $propertyExpense->status }}
                    </span>
                </div>

                <div class="expense-detail-panel-body">
                    <div class="expense-detail-amount mb-4">
                        <div class="small expense-detail-muted mb-2">Recorded expense amount</div>
                        <div class="expense-detail-amount-value">
                            {{ $propertyExpense->currency }}
                            {{ number_format((float) $propertyExpense->amount, 2) }}
                        </div>
                        <div class="small expense-detail-muted mt-2">
                            <i class="bi bi-calendar3 me-1"></i>
                            Expense date: {{ $expenseDate }}
                        </div>
                    </div>

                    <h2 class="h6 fw-bold mb-3">Expense information</h2>

                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="expense-detail-label">Expense title</div>
                            <div class="expense-detail-value">
                                {{ $propertyExpense->title }}
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="expense-detail-label">Category</div>
                            <div class="expense-detail-value">
                                {{ $propertyExpense->category }}
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="expense-detail-label">Description</div>
                            <div class="expense-detail-value fw-normal">
                                {{ $propertyExpense->description ?: 'No description provided.' }}
                            </div>
                        </div>
                    </div>

                    <div class="expense-detail-divider"></div>

                    <h2 class="h6 fw-bold mb-3">Payment information</h2>

                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="expense-detail-label">Payment method</div>
                            <div class="expense-detail-value">
                                {{ $propertyExpense->payment_method ?: 'Not specified' }}
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="expense-detail-label">Vendor / supplier</div>
                            <div class="expense-detail-value">
                                {{ $propertyExpense->vendor_name ?: 'Not specified' }}
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="expense-detail-label">Payment reference</div>
                            <div class="expense-detail-value">
                                {{ $propertyExpense->reference_number ?: 'No reference provided' }}
                            </div>
                        </div>
                    </div>

                    <div class="expense-detail-divider"></div>

                    <h2 class="h6 fw-bold mb-3">Internal notes</h2>

                    @if($propertyExpense->notes)
                        <div class="expense-detail-note">{{ $propertyExpense->notes }}</div>
                    @else
                        <p class="small expense-detail-muted mb-0">No additional notes were recorded.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Property and audit sidebar --}}
        <div class="col-12 col-xl-4">
            <div class="expense-detail-panel mb-4">
                <div class="expense-detail-panel-header">
                    <h2 class="h6 fw-bold mb-0">
                        <i class="bi bi-buildings me-2" style="color: var(--expense-orange)"></i>
                        Associated property
                    </h2>
                </div>

                <div class="expense-detail-panel-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="expense-detail-property-icon">
                            <i class="bi bi-building"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="fw-bold mb-1" style="color: var(--expense-navy)">
                                {{ $propertyExpense->property?->name ?? 'Property unavailable' }}
                            </div>

                            @if($propertyExpense->property?->property_code)
                                <div class="small expense-detail-muted mb-2">
                                    Code: {{ $propertyExpense->property->property_code }}
                                </div>
                            @endif

                            <div class="small expense-detail-muted">
                                Expense assigned to this property.
                            </div>
                        </div>
                    </div>

                    @if($propertyExpense->property && \Illuminate\Support\Facades\Route::has('admin.properties.show'))
                        <div class="expense-detail-divider"></div>
                        <a href="{{ route('admin.properties.show', $propertyExpense->property) }}"
                           class="expense-detail-action expense-detail-secondary w-100">
                            <i class="bi bi-box-arrow-up-right"></i>View property
                        </a>
                    @endif
                </div>
            </div>

            <div class="expense-detail-panel mb-4">
                <div class="expense-detail-panel-header">
                    <h2 class="h6 fw-bold mb-0">
                        <i class="bi bi-clock-history me-2" style="color: var(--expense-orange)"></i>
                        Record history
                    </h2>
                </div>

                <div class="expense-detail-panel-body">
                    <div class="mb-3">
                        <div class="expense-detail-label">Recorded by</div>
                        <div class="expense-detail-value">
                            {{ $propertyExpense->recorder?->name ?? 'User unavailable' }}
                        </div>
                        @if($propertyExpense->recorder?->email)
                            <div class="small expense-detail-muted mt-1">
                                {{ $propertyExpense->recorder->email }}
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <div class="expense-detail-label">Created on</div>
                        <div class="expense-detail-value">{{ $createdDate }}</div>
                    </div>

                    <div>
                        <div class="expense-detail-label">Last updated</div>
                        <div class="expense-detail-value">{{ $updatedDate }}</div>
                    </div>
                </div>
            </div>

            <div class="expense-detail-panel">
                <div class="expense-detail-panel-body">
                    <h2 class="h6 fw-bold mb-2">Delete expense</h2>
                    <p class="small expense-detail-muted">
                        Deleting this record soft-deletes it. It will no longer appear in the normal expense listing.
                    </p>

                    <form method="POST"
                          action="{{ route('admin.property-expenses.destroy', $propertyExpense) }}"
                          onsubmit="return confirm('Delete this expense record? This action will soft-delete the record.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash3 me-1"></i>Delete expense
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

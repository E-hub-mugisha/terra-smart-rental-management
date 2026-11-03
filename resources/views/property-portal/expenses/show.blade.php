@extends('layouts.app')

@section('title', 'Expense Details')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <a href="{{ route('property-manager.expenses.index') }}"
               class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back to Expenses
            </a>

            <h3 class="fw-bold mt-2 mb-1">Expense Details</h3>
            <p class="text-muted mb-0">{{ $expense->expense_number }}</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('property-manager.expenses.edit', $expense->id) }}"
               class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Edit Expense
            </a>

            <form method="POST"
                  action="{{ route('property-manager.expenses.destroy', $expense->id) }}"
                  onsubmit="return confirm('Are you sure you want to delete this expense?')">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-trash me-1"></i> Delete
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">

        <div class="col-12 col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">

                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div>
                            <p class="text-muted small mb-1">Expense Amount</p>
                            <h2 class="fw-bold mb-1">
                                {{ $expense->currency }}
                                {{ number_format((float) $expense->amount, 2) }}
                            </h2>
                            <span class="text-muted small">
                                {{ $expense->expense_date?->format('d F Y') }}
                            </span>
                        </div>

                        <div>
                            @if($expense->status === 'paid')
                                <span class="badge bg-success-subtle text-success px-3 py-2">
                                    Paid
                                </span>
                            @elseif($expense->status === 'pending')
                                <span class="badge bg-warning-subtle text-warning px-3 py-2">
                                    Pending
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary px-3 py-2">
                                    Cancelled
                                </span>
                            @endif
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="fw-bold mb-2">{{ $expense->title }}</h5>

                    <p class="text-muted mb-0">
                        {{ $expense->description ?: 'No description provided.' }}
                    </p>

                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Expense Information</h6>
                </div>

                <div class="card-body">
                    <div class="row g-4">

                        <div class="col-sm-6">
                            <small class="text-muted d-block mb-1">Expense Number</small>
                            <strong>{{ $expense->expense_number }}</strong>
                        </div>

                        <div class="col-sm-6">
                            <small class="text-muted d-block mb-1">Category</small>
                            <strong>
                                {{ $categories[$expense->category] ?? ucfirst(str_replace('_', ' ', $expense->category)) }}
                            </strong>
                        </div>

                        <div class="col-sm-6">
                            <small class="text-muted d-block mb-1">Payment Method</small>
                            <strong>
                                {{ $expense->payment_method
                                    ? ucfirst(str_replace('_', ' ', $expense->payment_method))
                                    : 'Not specified' }}
                            </strong>
                        </div>

                        <div class="col-sm-6">
                            <small class="text-muted d-block mb-1">Vendor / Supplier</small>
                            <strong>{{ $expense->vendor_name ?: 'Not specified' }}</strong>
                        </div>

                        <div class="col-sm-6">
                            <small class="text-muted d-block mb-1">Reference Number</small>
                            <strong>{{ $expense->reference_number ?: 'Not provided' }}</strong>
                        </div>

                        <div class="col-sm-6">
                            <small class="text-muted d-block mb-1">Recorded By</small>
                            <strong>{{ $expense->recorder?->name ?? 'Unknown' }}</strong>
                        </div>

                    </div>

                    @if($expense->notes)
                        <hr>
                        <small class="text-muted d-block mb-2">Additional Notes</small>
                        <p class="mb-0">{{ $expense->notes }}</p>
                    @endif
                </div>
            </div>

        </div>

        <div class="col-12 col-lg-4">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Property</h6>
                </div>

                <div class="card-body">
                    <h6 class="fw-bold mb-1">
                        {{ $expense->property?->name ?? 'Property unavailable' }}
                    </h6>

                    <p class="text-muted small mb-0">
                        Associated property
                    </p>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Record Information</h6>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Created</small>
                        <strong>{{ $expense->created_at?->format('d M Y, h:i A') }}</strong>
                    </div>

                    <div>
                        <small class="text-muted d-block">Last Updated</small>
                        <strong>{{ $expense->updated_at?->format('d M Y, h:i A') }}</strong>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
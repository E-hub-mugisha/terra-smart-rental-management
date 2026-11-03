@extends('layouts.app')

@section('title', 'Property Expenses')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Property Expenses</h3>
            <p class="text-muted mb-0">
                Track and manage expenses across your properties.
            </p>
        </div>

        <a href="{{ route('property-manager.expenses.create') }}"
           class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>
            Record Expense
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Summary --}}
    <div class="row g-3 mb-4">

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Total Expenses</p>
                            <h4 class="fw-bold mb-0">
                                {{ number_format((float) $summary['total_amount'], 0) }}
                            </h4>
                        </div>
                        <span class="rounded-3 bg-primary-subtle text-primary p-3">
                            <i class="bi bi-wallet2 fs-5"></i>
                        </span>
                    </div>
                    <small class="text-muted">
                        {{ $summary['all'] }} recorded expenses
                    </small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Paid Expenses</p>
                            <h4 class="fw-bold mb-0">
                                {{ number_format((float) $summary['paid_amount'], 0) }}
                            </h4>
                        </div>
                        <span class="rounded-3 bg-success-subtle text-success p-3">
                            <i class="bi bi-check-circle fs-5"></i>
                        </span>
                    </div>
                    <small class="text-muted">
                        {{ $summary['paid_count'] }} paid records
                    </small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Pending Expenses</p>
                            <h4 class="fw-bold mb-0">
                                {{ number_format((float) $summary['pending_amount'], 0) }}
                            </h4>
                        </div>
                        <span class="rounded-3 bg-warning-subtle text-warning p-3">
                            <i class="bi bi-clock-history fs-5"></i>
                        </span>
                    </div>
                    <small class="text-muted">
                        {{ $summary['pending_count'] }} pending records
                    </small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted small mb-1">Expense Records</p>
                            <h4 class="fw-bold mb-0">
                                {{ number_format($summary['all']) }}
                            </h4>
                        </div>
                        <span class="rounded-3 bg-info-subtle text-info p-3">
                            <i class="bi bi-receipt fs-5"></i>
                        </span>
                    </div>
                    <small class="text-muted">Across accessible properties</small>
                </div>
            </div>
        </div>

    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('property-manager.expenses.index') }}">
                <div class="row g-3 align-items-end">

                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold">Search</label>
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Expense number, title, vendor..."
                               value="{{ request('search') }}">
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-semibold">Property</label>
                        <select name="property_id" class="form-select">
                            <option value="">All properties</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}"
                                    @selected(request('property_id') == $property->id)>
                                    {{ $property->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-semibold">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All categories</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}"
                                    @selected(request('category') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                            <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit">
                            <i class="bi bi-search me-1"></i> Filter
                        </button>
                        <a href="{{ route('property-manager.expenses.index') }}"
                           class="btn btn-light border"
                           title="Clear filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold">Date From</label>
                        <input type="date"
                               name="date_from"
                               class="form-control"
                               value="{{ request('date_from') }}">
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold">Date To</label>
                        <input type="date"
                               name="date_to"
                               class="form-control"
                               value="{{ request('date_to') }}">
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- Expense table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Expense Records</h6>
                <span class="text-muted small">
                    {{ $expenses->total() }} records
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Expense</th>
                        <th>Property</th>
                        <th>Category</th>
                        <th>Vendor</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td class="ps-3">
                                <a href="{{ route('property-manager.expenses.show', $expense->id) }}"
                                   class="fw-semibold text-decoration-none">
                                    {{ $expense->expense_number }}
                                </a>
                                <div class="small text-muted">
                                    {{ $expense->title }}
                                </div>
                            </td>

                            <td>
                                <span class="fw-medium">
                                    {{ $expense->property?->name ?? '—' }}
                                </span>
                            </td>

                            <td>
                                {{ $categories[$expense->category] ?? ucfirst(str_replace('_', ' ', $expense->category)) }}
                            </td>

                            <td>{{ $expense->vendor_name ?: '—' }}</td>

                            <td>
                                {{ $expense->expense_date?->format('d M Y') }}
                            </td>

                            <td class="fw-semibold text-nowrap">
                                {{ $expense->currency }}
                                {{ number_format((float) $expense->amount, 2) }}
                            </td>

                            <td>
                                @if($expense->status === 'paid')
                                    <span class="badge bg-success-subtle text-success">Paid</span>
                                @elseif($expense->status === 'pending')
                                    <span class="badge bg-warning-subtle text-warning">Pending</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Cancelled</span>
                                @endif
                            </td>

                            <td class="text-end pe-3">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border"
                                            type="button"
                                            data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item"
                                               href="{{ route('property-manager.expenses.show', $expense->id) }}">
                                                <i class="bi bi-eye me-2"></i>View
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item"
                                               href="{{ route('property-manager.expenses.edit', $expense->id) }}">
                                                <i class="bi bi-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST"
                                                  action="{{ route('property-manager.expenses.destroy', $expense->id) }}"
                                                  onsubmit="return confirm('Are you sure you want to delete this expense?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-receipt fs-1 text-muted"></i>
                                <h6 class="fw-semibold mt-3">No expenses found</h6>
                                <p class="text-muted small mb-3">
                                    Record your first property expense to start tracking costs.
                                </p>
                                <a href="{{ route('property-manager.expenses.create') }}"
                                   class="btn btn-primary btn-sm">
                                    Record Expense
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
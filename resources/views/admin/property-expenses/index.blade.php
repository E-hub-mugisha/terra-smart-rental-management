
@extends('layouts.app')

@section('title', 'Property Expenses')
@section('page_heading', 'Property Expenses')
@section('page_subtitle', 'Track property operating costs and payments.')

@php
    $hasFilters = request()->anyFilled([
        'search', 'property_id', 'category', 'currency',
        'status', 'from_date', 'to_date'
    ]);

    $statusStyles = [
        'paid' => 'expense-status-paid',
        'pending' => 'expense-status-pending',
        'cancelled' => 'expense-status-cancelled',
    ];

    $formatAmount = fn ($amount) => number_format((float) $amount, 2);
@endphp

@push('styles')
<style>
    .expense-page {
        --expense-orange: var(--terra-orange, #D05208);
        --expense-navy: var(--terra-navy, #19265d);
        --expense-border: var(--terra-border, #e7eaf0);
        --expense-muted: var(--terra-muted, #697386);
        color: #20283a;
    }

    .expense-page .expense-panel {
        background: #fff;
        border: 1px solid var(--expense-border);
        border-radius: 16px;
        box-shadow: 0 6px 24px rgba(25, 38, 93, .045);
    }

    .expense-page .expense-btn-primary {
        background: var(--expense-orange);
        border-color: var(--expense-orange);
        color: #fff;
        font-weight: 600;
    }

    .expense-page .expense-btn-primary:hover {
        background: #b94706;
        border-color: #b94706;
        color: #fff;
    }

    .expense-page .expense-stat {
        height: 100%;
        padding: 20px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }

    .expense-page .expense-stat-icon {
        width: 44px;
        height: 44px;
        flex: 0 0 44px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: #fff0e7;
        color: var(--expense-orange);
        font-size: 20px;
    }

    .expense-page .expense-stat-label {
        color: var(--expense-muted);
        font-size: .8rem;
        margin-bottom: 5px;
    }

    .expense-page .expense-stat-value {
        color: var(--expense-navy);
        font-size: 1.3rem;
        font-weight: 750;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .expense-page .expense-muted {
        color: var(--expense-muted);
    }

    .expense-page .form-control,
    .expense-page .form-select {
        min-height: 43px;
        border-color: #dfe3eb;
        border-radius: 9px;
        font-size: .9rem;
    }

    .expense-page .form-control:focus,
    .expense-page .form-select:focus {
        border-color: var(--expense-orange);
        box-shadow: 0 0 0 .2rem rgba(208, 82, 8, .1);
    }

    .expense-page .expense-table {
        min-width: 1020px;
        margin-bottom: 0;
    }

    .expense-page .expense-table thead th {
        padding: 14px 16px;
        background: #f8f9fc;
        border-bottom: 1px solid var(--expense-border);
        color: #697386;
        font-size: .72rem;
        font-weight: 750;
        letter-spacing: .045em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .expense-page .expense-table tbody td {
        padding: 16px;
        border-bottom: 1px solid #edf0f5;
        vertical-align: middle;
        font-size: .86rem;
    }

    .expense-page .expense-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .expense-page .expense-table tbody tr:hover {
        background: #fcfcfe;
    }

    .expense-page .expense-number {
        color: var(--expense-orange);
        font-weight: 700;
        text-decoration: none;
    }

    .expense-page .expense-number:hover {
        text-decoration: underline;
    }

    .expense-page .expense-property {
        color: var(--expense-navy);
        font-weight: 650;
    }

    .expense-page .expense-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 30px;
        font-size: .74rem;
        font-weight: 700;
        white-space: nowrap;
        text-transform: capitalize;
    }

    .expense-page .expense-status::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    .expense-page .expense-status-paid {
        color: #18794e;
        background: #e8f7ef;
    }

    .expense-page .expense-status-pending {
        color: #956300;
        background: #fff5d8;
    }

    .expense-page .expense-status-cancelled {
        color: #b42318;
        background: #ffebe9;
    }

    .expense-page .expense-action {
        width: 34px;
        height: 34px;
        display: inline-grid;
        place-items: center;
        border: 1px solid var(--expense-border);
        border-radius: 9px;
        background: #fff;
        color: var(--expense-navy);
        text-decoration: none;
    }

    .expense-page .expense-action:hover {
        border-color: var(--expense-orange);
        color: var(--expense-orange);
    }

    .expense-page .expense-empty {
        padding: 54px 20px;
        text-align: center;
    }

    .expense-page .expense-empty-icon {
        width: 58px;
        height: 58px;
        margin: 0 auto 16px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: #fff0e7;
        color: var(--expense-orange);
        font-size: 25px;
    }

    .expense-page .expense-currency-total {
        border: 1px solid var(--expense-border);
        border-radius: 12px;
        padding: 13px 16px;
        background: #fff;
    }

    @media (max-width: 767.98px) {
        .expense-page .expense-stat {
            padding: 15px;
        }

        .expense-page .expense-stat-value {
            font-size: 1.1rem;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 expense-page">

    {{-- Page heading --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="small expense-muted mb-2">
                <a href="{{ route('dashboard') }}" class="text-decoration-none expense-muted">
                    Dashboard
                </a>
                <span class="mx-2">/</span>
                Finance
                <span class="mx-2">/</span>
                <span class="text-dark">Property expenses</span>
            </div>

            <h1 class="h3 fw-bold mb-1" style="color: var(--expense-navy)">
                Property expenses
            </h1>
            <p class="expense-muted mb-0">
                Monitor operating costs, suppliers and property-related spending.
            </p>
        </div>

        <a href="{{ route('admin.property-expenses.create') }}"
           class="btn expense-btn-primary px-3 py-2">
            <i class="bi bi-plus-lg me-2"></i>Record expense
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="expense-panel expense-stat">
                <div class="expense-stat-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="min-w-0">
                    <div class="expense-stat-label">Matching records</div>
                    <div class="expense-stat-value">{{ number_format($expenses->total()) }}</div>
                    <div class="small expense-muted mt-1">Based on current filters</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="expense-panel expense-stat">
                <div class="expense-stat-icon">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="min-w-0">
                    <div class="expense-stat-label">Paid expenses</div>
                    <div class="expense-stat-value">
                        {{ number_format((float) ($totals['RWF'] ?? 0), 2) }}
                        <span class="fs-6 fw-semibold">RWF</span>
                    </div>
                    <div class="small expense-muted mt-1">RWF total for selected records</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="expense-panel expense-stat">
                <div class="expense-stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="min-w-0">
                    <div class="expense-stat-label">USD expenses</div>
                    <div class="expense-stat-value">
                        {{ number_format((float) ($totals['USD'] ?? 0), 2) }}
                        <span class="fs-6 fw-semibold">USD</span>
                    </div>
                    <div class="small expense-muted mt-1">Kept separate from RWF</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="expense-panel expense-stat">
                <div class="expense-stat-icon">
                    <i class="bi bi-calendar3"></i>
                </div>
                <div class="min-w-0">
                    <div class="expense-stat-label">Current page</div>
                    <div class="expense-stat-value">
                        {{ $expenses->count() }}
                    </div>
                    <div class="small expense-muted mt-1">Records displayed below</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="expense-panel p-3 p-lg-4 mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-funnel" style="color: var(--expense-orange)"></i>
            <h2 class="h6 fw-bold mb-0">Filter expenses</h2>
        </div>

        <form method="GET" action="{{ route('admin.property-expenses.index') }}">
            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-4">
                    <label for="expense-search" class="form-label small fw-semibold">Search</label>
                    <input
                        id="expense-search"
                        type="search"
                        name="search"
                        class="form-control"
                        value="{{ request('search') }}"
                        placeholder="Expense no., title, vendor or reference"
                    >
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                    <label for="property_id" class="form-label small fw-semibold">Property</label>
                    <select name="property_id" id="property_id" class="form-select">
                        <option value="">All properties</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}"
                                @selected((string) request('property_id') === (string) $property->id)>
                                {{ $property->name }}
                                @if($property->property_code)
                                    ({{ $property->property_code }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-4">
                    <label for="category" class="form-label small fw-semibold">Category</label>
                    <select name="category" id="category" class="form-select">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" @selected(request('category') === $category)>
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <label for="currency" class="form-label small fw-semibold">Currency</label>
                    <select name="currency" id="currency" class="form-select">
                        <option value="">All currencies</option>
                        <option value="RWF" @selected(request('currency') === 'RWF')>RWF</option>
                        <option value="USD" @selected(request('currency') === 'USD')>USD</option>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <label for="status" class="form-label small fw-semibold">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <label for="from_date" class="form-label small fw-semibold">From date</label>
                    <input type="date" name="from_date" id="from_date"
                           class="form-control" value="{{ request('from_date') }}">
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <label for="to_date" class="form-label small fw-semibold">To date</label>
                    <input type="date" name="to_date" id="to_date"
                           class="form-control" value="{{ request('to_date') }}">
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="submit" class="btn expense-btn-primary px-3">
                    <i class="bi bi-search me-1"></i>Apply filters
                </button>

                @if($hasFilters)
                    <a href="{{ route('admin.property-expenses.index') }}"
                       class="btn btn-light border px-3">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Expense table --}}
    <div class="expense-panel overflow-hidden">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 p-lg-4 border-bottom">
            <div>
                <h2 class="h5 fw-bold mb-1">Expense records</h2>
                <p class="small expense-muted mb-0">
                    Review and manage recorded property expenses.
                </p>
            </div>
            <span class="badge rounded-pill text-bg-light border px-3 py-2">
                {{ number_format($expenses->total()) }} records
            </span>
        </div>

        @if($expenses->count())
            <div class="table-responsive">
                <table class="table expense-table align-middle">
                    <thead>
                        <tr>
                            <th>Expense</th>
                            <th>Property</th>
                            <th>Category</th>
                            <th>Expense date</th>
                            <th>Vendor</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expenses as $expense)
                            @php
                                $statusKey = strtolower((string) $expense->status);
                                $statusClass = $statusStyles[$statusKey] ?? 'expense-status-pending';
                            @endphp

                            <tr>
                                <td>
                                    <a href="{{ route('admin.property-expenses.show', $expense) }}"
                                       class="expense-number d-block">
                                        {{ $expense->expense_number }}
                                    </a>
                                    <span class="small expense-muted">
                                        {{ \Illuminate\Support\Str::limit($expense->title, 42) }}
                                    </span>
                                </td>

                                <td>
                                    <div class="expense-property">
                                        {{ $expense->property?->name ?? 'Property unavailable' }}
                                    </div>
                                    @if($expense->property?->property_code)
                                        <div class="small expense-muted">
                                            {{ $expense->property->property_code }}
                                        </div>
                                    @endif
                                </td>

                                <td>{{ $expense->category }}</td>

                                <td class="text-nowrap">
                                    {{ $expense->expense_date?->format('d M Y') ?? \Illuminate\Support\Carbon::parse($expense->expense_date)->format('d M Y') }}
                                </td>

                                <td>{{ $expense->vendor_name ?: '—' }}</td>

                                <td class="text-end text-nowrap fw-bold">
                                    {{ $expense->currency }}
                                    {{ $formatAmount($expense->amount) }}
                                </td>

                                <td>
                                    <span class="expense-status {{ $statusClass }}">
                                        {{ $expense->status }}
                                    </span>
                                </td>

                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('admin.property-expenses.show', $expense) }}"
                                           class="expense-action"
                                           title="View expense"
                                           aria-label="View expense">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <a href="{{ route('admin.property-expenses.edit', $expense) }}"
                                           class="expense-action"
                                           title="Edit expense"
                                           aria-label="Edit expense">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <form method="POST"
                                              action="{{ route('admin.property-expenses.destroy', $expense) }}"
                                              onsubmit="return confirm('Delete this expense record? This action will soft-delete the record.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="expense-action"
                                                    title="Delete expense"
                                                    aria-label="Delete expense">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 p-lg-4 border-top">
                <div class="small expense-muted">
                    Showing
                    <strong>{{ $expenses->firstItem() ?? 0 }}</strong>
                    to
                    <strong>{{ $expenses->lastItem() ?? 0 }}</strong>
                    of
                    <strong>{{ $expenses->total() }}</strong>
                    records
                </div>

                <div>
                    {{ $expenses->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @else
            <div class="expense-empty">
                <div class="expense-empty-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <h3 class="h5 fw-bold">No expenses found</h3>
                <p class="expense-muted mb-3">
                    @if($hasFilters)
                        No expenses match your current filters. Try changing or resetting them.
                    @else
                        Start tracking your property costs by recording your first expense.
                    @endif
                </p>

                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @if($hasFilters)
                        <a href="{{ route('admin.property-expenses.index') }}"
                           class="btn btn-light border">
                            Reset filters
                        </a>
                    @endif

                    <a href="{{ route('admin.property-expenses.create') }}"
                       class="btn expense-btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Record expense
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Tenant Portal')

@section('content')
<div class="mb-4">
    <h1 class="h3 fw-bold">My Rental Portal</h1>
    <p class="text-secondary">
        View your rental information and manage your requests.
    </p>
</div>

<div class="row g-3">
    @foreach([
        ['My Lease', 'View your agreement and lease dates.'],
        ['Rent and Invoices', 'Review your balances and payment history.'],
        ['Maintenance Requests', 'Submit and track repair requests.'],
    ] as [$title, $description])
        <div class="col-md-4">
            <div class="pp-card p-4 h-100">
                <h2 class="h6 fw-semibold">{{ $title }}</h2>
                <p class="text-secondary small mb-0">{{ $description }}</p>
            </div>
        </div>
    @endforeach
</div>
@endsection
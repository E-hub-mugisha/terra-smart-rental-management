@extends('layouts.app')

@section('title', 'Property Manager Dashboard')

@section('content')
<div class="mb-4">
    <h1 class="h3 fw-bold">Property Manager Dashboard</h1>
    <p class="text-secondary">
        Manage your properties and rental operations.
    </p>
</div>

<div class="row g-3">
    @foreach([
        ['Properties', 'Property records will appear here.'],
        ['Units', 'Track availability and occupancy.'],
        ['Leases', 'Manage active leases and renewals.'],
        ['Maintenance', 'Monitor maintenance requests.'],
    ] as [$title, $description])
        <div class="col-sm-6 col-xl-3">
            <div class="pp-card p-4 h-100">
                <h2 class="h6 fw-semibold">{{ $title }}</h2>
                <p class="text-secondary small mb-0">{{ $description }}</p>
            </div>
        </div>
    @endforeach
</div>
@endsection
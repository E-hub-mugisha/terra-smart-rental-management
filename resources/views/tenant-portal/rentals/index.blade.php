@extends('layouts.app')

@section('title', 'My Rentals | Terra Smart Rental Management')

@section('content')
@php
    $activeLeases = $leases->filter(fn ($lease) =>
        strtolower($lease->status ?? '') === 'active'
    );

    $upcomingLeases = $leases->filter(fn ($lease) =>
        strtolower($lease->status ?? '') === 'draft'
    );

    $currency = fn ($amount, $code = 'RWF') =>
        ($code ?: 'RWF') . ' ' . number_format((float) $amount, 0);

    $statusStyles = [
        'active' => 'success',
        'draft' => 'warning',
        'expired' => 'secondary',
        'terminated' => 'danger',
        'cancelled' => 'secondary',
    ];
@endphp

<div class="container-fluid py-4 rentals-page">

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="small text-muted mb-2">
                Tenant portal / My rentals
            </div>
            <h1 class="h3 fw-bold mb-1">My Rentals</h1>
            <p class="text-muted mb-0">
                View your rented properties, lease information and rental payments.
            </p>
        </div>

        <a href="{{ route('tenant-portal.maintenance-requests.index') }}"
           class="btn btn-outline-primary">
            <i class="bi bi-tools me-2"></i>Maintenance requests
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="summary-card">
                <div class="summary-icon orange">
                    <i class="bi bi-house-door"></i>
                </div>
                <div>
                    <div class="summary-label">Total leases</div>
                    <div class="summary-number">{{ $leases->count() }}</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="summary-card">
                <div class="summary-icon green">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="summary-label">Active rentals</div>
                    <div class="summary-number">{{ $activeLeases->count() }}</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="summary-card">
                <div class="summary-icon blue">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div>
                    <div class="summary-label">Pending leases</div>
                    <div class="summary-number">{{ $upcomingLeases->count() }}</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="summary-card">
                <div class="summary-icon purple">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="summary-label">Past rentals</div>
                    <div class="summary-number">
                        {{ $leases->filter(fn ($lease) => in_array(strtolower($lease->status ?? ''), ['expired', 'terminated', 'cancelled']))->count() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Rentals list --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h5 fw-bold mb-1">Your rental properties</h2>
            <p class="text-muted small mb-0">
                Your leases and accommodation details.
            </p>
        </div>

        <span class="text-muted small">
            {{ $leases->count() }} {{ Str::plural('record', $leases->count()) }}
        </span>
    </div>

    @forelse($leases as $lease)
        @php
            $unit = $lease->unit;
            $building = $unit?->building;
            $property = $building?->property;
            $status = strtolower($lease->status ?? 'draft');
            $badge = $statusStyles[$status] ?? 'secondary';

            $startDate = $lease->start_date
                ? \Illuminate\Support\Carbon::parse($lease->start_date)
                : null;

            $endDate = $lease->end_date
                ? \Illuminate\Support\Carbon::parse($lease->end_date)
                : null;

            $daysRemaining = $endDate
                ? (int) now()->startOfDay()->diffInDays($endDate->copy()->startOfDay(), false)
                : null;

            $imagePath = $unit?->images
                ?->sortBy('sort_order')
                ->first()?->path;

            $imageUrl = $imagePath
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath)
                : null;
        @endphp

        <div class="rental-card mb-4">
            <div class="row g-0">
                {{-- Property image --}}
                <div class="col-lg-4">
                    <div class="rental-image">
                        @if($imageUrl)
                            <img src="{{ $imageUrl }}"
                                 alt="{{ $property?->name ?? 'Rental property' }}"
                                 loading="lazy">
                        @else
                            <div class="image-placeholder">
                                <i class="bi bi-house-door"></i>
                                <span>Property image unavailable</span>
                            </div>
                        @endif

                        <span class="badge rounded-pill text-bg-{{ $badge }} image-status">
                            {{ ucwords(str_replace('_', ' ', $status)) }}
                        </span>
                    </div>
                </div>

                {{-- Property information --}}
                <div class="col-lg-8">
                    <div class="rental-content">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <div class="text-muted small mb-1">
                                    {{ $property?->code ?? $lease->lease_number ?? 'Rental property' }}
                                </div>

                                <h3 class="h5 fw-bold mb-2">
                                    {{ $property?->name ?? 'Rental property' }}
                                </h3>

                                <div class="text-muted small">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    {{ collect([
                                        $property?->village,
                                        $property?->cell,
                                        $property?->sector,
                                        $property?->district,
                                        $property?->province,
                                    ])->filter()->implode(', ') ?: 'Location not provided' }}
                                </div>
                            </div>

                            <div class="rent-amount">
                                <span class="d-block text-muted small">Monthly rent</span>
                                <strong>
                                    {{ $currency($lease->monthly_rent, $lease->currency ?? 'RWF') }}
                                </strong>
                                <span class="text-muted small">/ month</span>
                            </div>
                        </div>

                        <div class="unit-strip mb-3">
                            <div>
                                <span class="detail-label">Building</span>
                                <strong>{{ $building?->name ?? '—' }}</strong>
                            </div>

                            <div>
                                <span class="detail-label">Unit</span>
                                <strong>{{ $unit?->unit_number ?? '—' }}</strong>
                            </div>

                            <div>
                                <span class="detail-label">Bedrooms</span>
                                <strong>{{ $unit?->bedrooms ?? '—' }}</strong>
                            </div>

                            <div>
                                <span class="detail-label">Bathrooms</span>
                                <strong>{{ $unit?->bathrooms ?? '—' }}</strong>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <div class="lease-detail">
                                    <span class="detail-label">Lease starts</span>
                                    <strong>{{ $startDate?->format('d M Y') ?? '—' }}</strong>
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="lease-detail">
                                    <span class="detail-label">Lease ends</span>
                                    <strong>{{ $endDate?->format('d M Y') ?? 'Ongoing' }}</strong>
                                </div>
                            </div>

                            <div class="col-sm-4">
                                <div class="lease-detail">
                                    <span class="detail-label">
                                        {{ $status === 'active' ? 'Time remaining' : 'Lease reference' }}
                                    </span>
                                    <strong>
                                        @if($status === 'active')
                                            @if($daysRemaining === null)
                                                Ongoing
                                            @elseif($daysRemaining < 0)
                                                Expired
                                            @elseif($daysRemaining === 0)
                                                Ends today
                                            @else
                                                {{ $daysRemaining }} days
                                            @endif
                                        @else
                                            {{ $lease->lease_number ?? '—' }}
                                        @endif
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <div class="rental-footer">
                            <div class="small text-muted">
                                <i class="bi bi-file-earmark-text me-1"></i>
                                Lease: {{ $lease->lease_number ?? 'Not assigned' }}
                                @if($lease->deposit_amount !== null)
                                    <span class="mx-2">·</span>
                                    Deposit: {{ $currency($lease->deposit_amount, $lease->currency ?? 'RWF') }}
                                @endif
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                {{-- Change this route if your lease detail route has another name --}}
                                @if(\Illuminate\Support\Facades\Route::has('tenant-portal.leases.show'))
                                    <a href="{{ route('tenant-portal.leases.show', $lease) }}"
                                       class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-file-text me-1"></i>View lease
                                    </a>
                                @endif

                                <a href="{{ route('tenant-portal.maintenance-requests.create') }}"
                                   class="btn btn-primary btn-sm">
                                    <i class="bi bi-tools me-1"></i>Request maintenance
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="empty-state text-center py-5 px-3">
            <div class="empty-icon mb-3">
                <i class="bi bi-house-door"></i>
            </div>
            <h3 class="h5 fw-bold">No rentals to display</h3>
            <p class="text-muted mx-auto mb-4" style="max-width: 460px;">
                Your rental properties will appear here when you have a lease associated with your tenant account.
            </p>

            @if(\Illuminate\Support\Facades\Route::has('tenant-portal.applications.index'))
                <a href="{{ route('tenant-portal.applications.index') }}"
                   class="btn btn-primary">
                    View my applications
                </a>
            @endif
        </div>
    @endforelse

    @if(method_exists($leases, 'links'))
        <div class="mt-4">
            {{ $leases->links() }}
        </div>
    @endif
</div>

<style>
    .rentals-page {
        color: #202938;
        font-family: "DM Sans", sans-serif;
    }

    .rentals-page .btn-primary {
        background: #D05208;
        border-color: #D05208;
    }

    .rentals-page .btn-primary:hover {
        background: #b84808;
        border-color: #b84808;
    }

    .rentals-page .btn-outline-primary {
        color: #D05208;
        border-color: #D05208;
    }

    .rentals-page .btn-outline-primary:hover {
        color: #fff;
        background: #D05208;
        border-color: #D05208;
    }

    .summary-card {
        display: flex;
        align-items: center;
        gap: 14px;
        height: 100%;
        padding: 20px;
        background: #fff;
        border: 1px solid #edf0f3;
        border-radius: 16px;
    }

    .summary-icon {
        width: 45px;
        height: 45px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
        font-size: 1.2rem;
    }

    .summary-icon.orange { background: #fff0e6; color: #D05208; }
    .summary-icon.green { background: #e8f7ee; color: #198754; }
    .summary-icon.blue { background: #eaf2ff; color: #2563eb; }
    .summary-icon.purple { background: #f1eafe; color: #7952b3; }

    .summary-label {
        color: #737d8b;
        font-size: .8rem;
        margin-bottom: 3px;
    }

    .summary-number {
        font-size: 1.5rem;
        font-weight: 750;
        line-height: 1.2;
    }

    .rental-card {
        background: #fff;
        border: 1px solid #e9edf2;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(25, 38, 93, .035);
        transition: box-shadow .2s ease, transform .2s ease;
    }

    .rental-card:hover {
        box-shadow: 0 9px 28px rgba(25, 38, 93, .08);
        transform: translateY(-2px);
    }

    .rental-image {
        position: relative;
        min-height: 280px;
        height: 100%;
        background: #f2f4f7;
    }

    .rental-image img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .image-placeholder {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 10px;
        color: #8992a0;
        font-size: .85rem;
    }

    .image-placeholder i {
        font-size: 3rem;
    }

    .image-status {
        position: absolute;
        top: 14px;
        left: 14px;
        text-transform: capitalize;
    }

    .rental-content {
        padding: 24px;
    }

    .rent-amount {
        min-width: 145px;
        text-align: right;
    }

    .rent-amount strong {
        display: inline-block;
        margin-top: 3px;
        font-size: 1.12rem;
        color: #D05208;
    }

    .unit-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        padding: 14px;
        border-radius: 12px;
        background: #f8f9fb;
    }

    .unit-strip > div,
    .lease-detail {
        display: flex;
        flex-direction: column;
        gap: 5px;
        min-width: 0;
    }

    .detail-label {
        color: #737d8b;
        font-size: .76rem;
    }

    .unit-strip strong,
    .lease-detail strong {
        font-size: .87rem;
        overflow-wrap: anywhere;
    }

    .rental-footer {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        padding-top: 17px;
        border-top: 1px solid #edf0f3;
    }

    .empty-state {
        background: #fff;
        border: 1px dashed #d9dfe7;
        border-radius: 18px;
    }

    .empty-icon {
        display: inline-flex;
        width: 68px;
        height: 68px;
        align-items: center;
        justify-content: center;
        border-radius: 20px;
        background: #fff0e6;
        color: #D05208;
        font-size: 2rem;
    }

    @media (max-width: 991.98px) {
        .rental-image {
            min-height: 230px;
        }
    }

    @media (max-width: 575.98px) {
        .summary-card {
            padding: 14px;
            gap: 10px;
        }

        .summary-icon {
            width: 38px;
            height: 38px;
        }

        .summary-number {
            font-size: 1.25rem;
        }

        .rental-content {
            padding: 17px;
        }

        .rent-amount {
            text-align: left;
        }

        .unit-strip {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .rental-footer {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endsection

@extends('layouts.app')

@section('title', 'Assigned Properties')
@section('page_heading', 'Assigned Properties')
@section('page_subtitle', 'View and monitor properties assigned to you.')

@section('content')
    <div class="terra-card mb-4">
        <div class="terra-card-body">
            <form action="{{ route('property-manager.properties.index') }}"
                  method="GET"
                  class="row g-3 align-items-end">

                <div class="col-12 col-lg-7">
                    <label for="search" class="form-label fw-semibold small">
                        Search properties
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search terra-muted"></i>
                        </span>
                        <input type="text"
                               id="search"
                               name="search"
                               value="{{ request('search') }}"
                               class="form-control terra-form-control"
                               placeholder="Property name, code, district or sector">
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="status" class="form-label fw-semibold small">
                        Property status
                    </label>
                    <select name="status" id="status" class="form-select terra-form-control">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}"
                                @selected(request('status') === $status)>
                                {{ ucwords(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-2 d-flex gap-2">
                    <button class="btn terra-btn-primary flex-grow-1" type="submit">
                        Search
                    </button>
                    <a href="{{ route('property-manager.properties.index') }}"
                       class="btn btn-light border"
                       aria-label="Clear filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">

    <div class="text-muted small">
        Showing {{ $properties->firstItem() ?? 0 }}
        – {{ $properties->lastItem() ?? 0 }}
        of {{ $properties->total() }} properties
    </div>

    @if(auth()->user()->propertyOwner)
        <a href="{{ route('property-manager.properties.create') }}"
           class="btn terra-btn-primary">

            <i class="bi bi-plus-lg me-2"></i>
            Add Property

        </a>
    @endif

</div>

    @if ($properties->isEmpty())
        <div class="terra-card">
            <div class="terra-card-body text-center py-5">
                <div class="terra-stat-icon mx-auto mb-3">
                    <i class="bi bi-search"></i>
                </div>
                <h3 class="h6 fw-bold" style="color:var(--terra-navy)">
                    No properties found
                </h3>
                <p class="terra-muted mb-3">
                    Try changing your search or status filter.
                </p>
                <a href="{{ route('property-manager.properties.index') }}"
                   class="btn terra-btn-primary">
                    Clear filters
                </a>
            </div>
        </div>
    @else
        <div class="terra-card">
            <div class="table-responsive">
                <table class="table terra-table">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Owner</th>
                            <th>Location</th>
                            <th>Buildings</th>
                            <th>Units</th>
                            <th>Status</th>
                            <th class="text-end">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($properties as $property)
                            <tr>
                                <td>
                                    <a href="{{ route('property-manager.properties.show', $property) }}"
                                       class="terra-link">
                                        {{ $property->name }}
                                    </a>
                                    <div class="terra-muted small mt-1">
                                        {{ $property->property_code ?: 'No code assigned' }}
                                    </div>
                                </td>
                                <td>
                                    {{ $property->owner?->company_name
                                        ?: $property->owner?->user?->name
                                        ?: 'Not specified' }}
                                </td>
                                <td>
                                    {{ $property->district ?: '—' }}
                                    @if ($property->sector)
                                        <div class="terra-muted small">{{ $property->sector }}</div>
                                    @endif
                                </td>
                                <td>{{ number_format($property->buildings_count) }}</td>
                                <td>{{ number_format($property->units_count) }}</td>
                                <td>
                                    <span class="terra-badge {{ strtolower($property->status ?? '') }}">
                                        {{ str_replace('_', ' ', $property->status ?: 'unspecified') }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('property-manager.properties.show', $property) }}"
                                       class="btn btn-sm btn-light border">
                                        View <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($properties->hasPages())
                <div class="p-3 border-top">
                    {{ $properties->links() }}
                </div>
            @endif
        </div>
    @endif
@endsection
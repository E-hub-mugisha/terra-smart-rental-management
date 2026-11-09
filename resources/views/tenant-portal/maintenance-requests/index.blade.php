
@extends('layouts.app')

@section('title', 'My Maintenance Requests')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color:#19265d;">My maintenance requests</h1>
            <p class="text-secondary mb-0">Report a problem and follow its progress.</p>
        </div>
        <a href="{{ route('tenant-portal.maintenance-requests.create') }}"
           class="btn text-white" style="background:#D05208;">
            <i class="bi bi-plus-lg me-2"></i>Report an issue
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-4">
        @foreach ([
            ['All requests', $summary['total']],
            ['Open', $summary['open']],
            ['In progress', $summary['in_progress']],
            ['Resolved', $summary['resolved']],
        ] as [$label, $count])
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <div class="small text-secondary">{{ $label }}</div>
                        <div class="h3 fw-bold mb-0">{{ $count }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        @forelse ($requests as $item)
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between gap-3">
                            <div>
                                <div class="small text-secondary mb-1">{{ $item->request_number }}</div>
                                <h5 class="fw-bold mb-2">{{ $item->title }}</h5>
                                <div class="small text-secondary">
                                    {{ $item->unit?->building?->property?->name ?? 'Property' }}
                                    &middot; Unit {{ $item->unit?->unit_number ?? '—' }}
                                </div>
                                <p class="mt-3 mb-2">
                                    {{ \Illuminate\Support\Str::limit($item->description, 180) }}
                                </p>
                                <div class="small text-secondary">
                                    Submitted {{ $item->created_at?->format('d M Y') }}
                                    &middot; {{ ucfirst($item->priority) }} priority
                                </div>
                            </div>

                            <div class="text-md-end">
                                <span class="badge text-bg-secondary">
                                    {{ ucwords(str_replace('_', ' ', $item->status)) }}
                                </span>
                                <div class="mt-3">
                                    <a href="{{ route('tenant-portal.maintenance-requests.show', $item) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        View progress
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-tools fs-1 text-secondary"></i>
                        <h5 class="fw-bold mt-3">No maintenance requests yet</h5>
                        <p class="text-secondary">Your submitted maintenance requests will appear here.</p>
                        <a href="{{ route('tenant-portal.maintenance-requests.create') }}"
                           class="btn text-white" style="background:#D05208;">
                            Report your first issue
                        </a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
</div>
@endsection

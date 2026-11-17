@extends('layouts.app')

@section('title', 'Inspections')
@section('page_heading', 'Move-in & move-out inspections')
@section('page_subtitle', $isStaff
    ? 'Record the condition of each unit when tenants move in and out, and settle deposits fairly.'
    : 'Inspection reports for your home. Review and confirm them when your property manager sends them.')

@include('inspections._styles')

@section('content')
@if($isStaff && $ready->isNotEmpty())
    <div class="ti-card mb-4">
        <div class="ti-card-head">
            <h2 class="ti-card-title"><i class="bi bi-clipboard-plus me-1"></i> Ready to inspect</h2>
            <span class="small text-secondary">Leases that still need a move-in or move-out inspection</span>
        </div>
        <div class="ti-card-body">
            <div class="row g-3">
                @foreach($ready as $r)
                    <div class="col-md-6 col-xl-3">
                        <div class="border rounded-3 p-3 h-100 d-flex flex-column">
                            <span class="ti-pill ty-{{ $r['type'] }} align-self-start mb-2">{{ $r['type'] === 'move_in' ? 'Move-in' : 'Move-out' }}</span>
                            <div class="fw-bold">{{ $r['lease']->tenant?->full_name ?? 'Tenant' }}</div>
                            <div class="small text-secondary mb-3">
                                Unit {{ $r['lease']->unit?->unit_number }} · {{ $r['lease']->unit?->building?->property?->name }}<br>
                                Lease {{ $r['lease']->lease_number }} · {{ ucwords(str_replace('_', ' ', $r['lease']->status)) }}
                            </div>
                            <form method="POST" action="{{ route('inspections.store') }}" class="mt-auto">
                                @csrf
                                <input type="hidden" name="lease_id" value="{{ $r['lease']->id }}">
                                <input type="hidden" name="type" value="{{ $r['type'] }}">
                                <button type="submit" class="btn btn-sm ti-btn-primary rounded-pill w-100">
                                    <i class="bi bi-play-fill"></i> Start {{ $r['type'] === 'move_in' ? 'move-in' : 'move-out' }} inspection
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

<div class="ti-card">
    <div class="ti-card-head">
        <nav class="d-flex flex-wrap gap-2" aria-label="Filter inspections">
            @foreach(['' => 'All', 'move_in' => 'Move-in', 'move_out' => 'Move-out'] as $key => $label)
                <a href="{{ route('inspections.index', array_filter(['type' => $key, 'status' => $status])) }}"
                   class="btn btn-sm rounded-pill {{ (string) $type === $key ? 'btn-dark' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <nav class="d-flex flex-wrap gap-2" aria-label="Filter by status">
            @foreach(\App\Models\Inspection::STATUSES as $key => $label)
                <a href="{{ route('inspections.index', array_filter(['type' => $type, 'status' => $status === $key ? null : $key])) }}"
                   class="ti-pill st-{{ $key }} text-decoration-none {{ $status === $key ? 'border border-dark' : '' }}">
                    {{ $label }} <strong>{{ $counts[$key] ?? 0 }}</strong>
                </a>
            @endforeach
        </nav>
    </div>

    @forelse($inspections as $inspection)
        <a href="{{ route('inspections.show', $inspection) }}" class="ti-row text-decoration-none text-reset"
           style="grid-template-columns: minmax(0, 2fr) minmax(0, 1.4fr) auto auto;">
            <div>
                <div class="fw-bold" style="color:var(--terra-navy);">{{ $inspection->type_label }}</div>
                <div class="small text-secondary">{{ $inspection->inspection_number }} · {{ $inspection->inspection_date?->format('d M Y') }}</div>
            </div>
            <div class="small">
                <div class="fw-semibold">{{ $inspection->lease?->tenant?->full_name ?? '—' }}</div>
                <div class="text-secondary">Unit {{ $inspection->unit?->unit_number }} · {{ $inspection->unit?->building?->property?->name }}</div>
            </div>
            <span class="ti-pill st-{{ $inspection->status }}">{{ $inspection->status_label }}</span>
            <i class="bi bi-chevron-right text-secondary ti-hide-sm"></i>
        </a>
    @empty
        <div class="text-center text-secondary py-5">
            <i class="bi bi-clipboard-check fs-1 d-block mb-2 opacity-50"></i>
            {{ $isStaff ? 'No inspections yet. Start one from a lease above or from the lease page.' : 'No inspection reports have been shared with you yet.' }}
        </div>
    @endforelse

    @if($inspections->hasPages())
        <div class="p-3 border-top">{{ $inspections->links() }}</div>
    @endif
</div>
@endsection

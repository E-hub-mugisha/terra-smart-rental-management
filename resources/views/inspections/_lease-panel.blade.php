{{-- Inspections for one lease, shown in the lease page sidebar. Requires $lease. --}}
@php
    $leaseInspections = $lease->inspections()->get(['id', 'type', 'status', 'inspection_date', 'deposit_settled_at']);
    $viewer = auth()->user();
    $audience = app(\App\Services\PropertyAudience::class);
    $propertyIds = $audience->accessiblePropertyIds($viewer);
    $lease->loadMissing('unit.building');
    $canManage = $propertyIds === null || $propertyIds->contains($lease->unit?->building?->property_id);
    $service = app(\App\Services\InspectionService::class);
    $visible = $canManage ? $leaseInspections : $leaseInspections->where('status', '!=', 'draft');
@endphp

@if($canManage || $visible->isNotEmpty())
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-body">
        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color:#19265d;">
            <i class="bi bi-clipboard-check"></i> Move-in &amp; move-out inspections
        </h6>

        @forelse($visible as $ins)
            <a href="{{ route('inspections.show', $ins) }}" class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom text-decoration-none text-reset">
                <span>
                    <span class="fw-semibold d-block">{{ $ins->type_label }}</span>
                    <small class="text-secondary">{{ $ins->inspection_date?->format('d M Y') }}{{ $ins->deposit_settled_at ? ' · deposit settled' : '' }}</small>
                </span>
                <span class="badge rounded-pill {{ ['draft' => 'text-bg-light border', 'awaiting_tenant' => 'text-bg-warning', 'disputed' => 'text-bg-danger', 'completed' => 'text-bg-success'][$ins->status] ?? 'text-bg-light' }}">
                    {{ $ins->status_label }}
                </span>
            </a>
        @empty
            <p class="small text-secondary mb-2">No inspections recorded for this lease yet.</p>
        @endforelse

        @if($canManage)
            <div class="d-grid gap-2 mt-3">
                @foreach([\App\Models\Inspection::TYPE_MOVE_IN => 'Start move-in inspection', \App\Models\Inspection::TYPE_MOVE_OUT => 'Start move-out inspection'] as $type => $label)
                    @if(! $service->blockerFor($lease, $type))
                        <form method="POST" action="{{ route('inspections.store') }}">
                            @csrf
                            <input type="hidden" name="lease_id" value="{{ $lease->id }}">
                            <input type="hidden" name="type" value="{{ $type }}">
                            <button type="submit" class="btn btn-sm w-100 rounded-pill {{ $type === 'move_in' ? 'btn-outline-primary' : 'btn-outline-dark' }}">
                                <i class="bi {{ $type === 'move_in' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right' }} me-1"></i> {{ $label }}
                            </button>
                        </form>
                    @endif
                @endforeach
            </div>
            @if($lease->status === 'pending_activation' && ! $leaseInspections->where('type', 'move_in')->where('status', 'completed')->count())
                <p class="small text-secondary mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Tip: complete the move-in inspection before activating, so the deposit can be settled fairly later.</p>
            @endif
        @endif
    </div>
</div>
@endif

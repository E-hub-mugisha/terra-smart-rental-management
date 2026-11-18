
<div class="border rounded p-3 mb-2">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div class="d-flex gap-3">
            @if($unit->images->isNotEmpty())
                <img src="{{ upload_url($unit->images->first()->image_path) }}"
                     alt="{{ $unit->unit_number }}"
                     class="rounded border"
                     style="width:85px;height:75px;object-fit:cover;">
            @endif

            <div>
                <strong>Unit {{ $unit->unit_number }}</strong>
                <div class="small text-muted">
                    {{ ucfirst(str_replace('_', ' ', $unit->unit_type)) }}
                    · {{ $unit->bedrooms }} bedrooms
                    · {{ $unit->bathrooms }} bathrooms
                </div>
                <div class="small text-muted">
                    {{ $unit->area_sqm ? number_format($unit->area_sqm, 2).' m²' : 'Area not specified' }}
                </div>
                <div class="fw-semibold mt-1">
                    {{ $unit->currency }} {{ number_format((float) $unit->rent_amount, 2) }}
                    <small class="text-muted fw-normal">/ month</small>
                </div>
                <div class="small">
                    Deposit: {{ $unit->currency }} {{ number_format((float) $unit->deposit_amount, 2) }}
                </div>

                @php
                    $unitStatusClasses = [
                        'available' => 'success',
                        'occupied' => 'primary',
                        'reserved' => 'info text-dark',
                        'maintenance' => 'warning text-dark',
                        'inactive' => 'secondary',
                    ];
                @endphp

                <span class="badge bg-{{ $unitStatusClasses[$unit->status] ?? 'secondary' }} mt-1">
                    {{ ucfirst(str_replace('_', ' ', $unit->status)) }}
                </span>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#editUnit{{ $unit->id }}">
                Edit
            </button>

            @if($unit->status !== 'inactive')
                <form method="POST"
                      action="{{ route('admin.properties.units.destroy', [$property, $unit]) }}"
                      onsubmit="return confirm('Deactivate this unit? Units with lease history cannot be deactivated using this action.')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Deactivate</button>
                </form>
            @endif
        </div>
    </div>
</div>

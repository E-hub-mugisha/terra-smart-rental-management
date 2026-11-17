{{-- Tenant confirms a resolved repair or reopens it. Requires $maintenanceRequest. --}}
@php $maintenanceRequest->loadMissing('serviceProvider'); @endphp
@if($maintenanceRequest->serviceProvider && ! in_array($maintenanceRequest->status, ['closed', 'rejected'], true))
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body small">
            <strong><i class="bi bi-person-gear me-1"></i> Your technician</strong><br>
            {{ $maintenanceRequest->serviceProvider->company_name }} · <a href="tel:{{ $maintenanceRequest->serviceProvider->phone }}">{{ $maintenanceRequest->serviceProvider->phone }}</a>
            @if($maintenanceRequest->scheduled_at)<br>Visit: {{ $maintenanceRequest->scheduled_at->format('D d M, H:i') }}@endif
        </div>
    </div>
@endif

@if($maintenanceRequest->status === 'resolved')
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="border:1px solid #f4d3bf !important;">
        <div class="card-body">
            <h6 class="fw-bold mb-2" style="color:#19265d;"><i class="bi bi-patch-question me-1"></i> Is it fixed?</h6>
            @if($maintenanceRequest->resolution_notes)<p class="small text-secondary">Technician: “{{ $maintenanceRequest->resolution_notes }}”</p>@endif
            @if($errors->hasAny(['rating', 'feedback']))<div class="alert alert-danger small py-2">{{ $errors->first('rating') ?: $errors->first('feedback') }}</div>@endif
            <form method="POST" action="{{ route('maintenance.tenant-confirm', $maintenanceRequest) }}" class="mb-3">
                @csrf <input type="hidden" name="fixed" value="1">
                <div class="d-flex gap-1 mb-2" role="radiogroup" aria-label="Rate the repair">
                    @for($s = 1; $s <= 5; $s++)
                        <input type="radio" class="btn-check" name="rating" value="{{ $s }}" id="star{{ $s }}" @checked($s === 5)>
                        <label class="btn btn-sm btn-outline-warning" for="star{{ $s }}">{{ $s }}★</label>
                    @endfor
                </div>
                <button class="btn btn-sm btn-success rounded-pill w-100"><i class="bi bi-check2-circle me-1"></i> Yes, it's fixed</button>
            </form>
            <form method="POST" action="{{ route('maintenance.tenant-confirm', $maintenanceRequest) }}" class="d-grid gap-2">
                @csrf <input type="hidden" name="fixed" value="0">
                <textarea name="feedback" rows="2" maxlength="2000" class="form-control form-control-sm" placeholder="What is still wrong?" required></textarea>
                <button class="btn btn-sm btn-outline-danger rounded-pill">No, still a problem</button>
            </form>
        </div>
    </div>
@endif

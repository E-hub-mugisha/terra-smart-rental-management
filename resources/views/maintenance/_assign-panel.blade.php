{{-- Assign a maintenance request to a service provider (staff). Requires $maintenanceRequest. --}}
@php
    $maintenanceRequest->loadMissing('serviceProvider');
    $assigned = $maintenanceRequest->serviceProvider;
    $closed = in_array($maintenanceRequest->status, ['closed', 'rejected'], true);
    $category = $maintenanceRequest->category;
    $providers = \App\Models\ServiceProvider::where('status', 'active')
        ->withAvg('jobs as rating_avg', 'tenant_rating')
        ->orderByRaw('verified_at is null')->orderBy('company_name')->get();
@endphp
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-body">
        <h6 class="fw-bold mb-3" style="color:#19265d;"><i class="bi bi-person-gear me-1"></i> Service provider</h6>
        @if($assigned)
            <div class="border rounded-3 p-2 small mb-3">
                <strong>{{ $assigned->company_name }}</strong>
                @if($assigned->isVerified())<i class="bi bi-patch-check-fill text-success" title="Verified"></i>@endif<br>
                <a href="tel:{{ $assigned->phone }}">{{ $assigned->phone }}</a>{{ $assigned->user_id ? ' · gets updates in Terra' : ' · no Terra login' }}
            </div>
        @endif
        @if($maintenanceRequest->tenant_confirmed_at)
            <div class="alert alert-success small py-2">Tenant confirmed the fix {{ $maintenanceRequest->tenant_confirmed_at->format('d M') }}{{ $maintenanceRequest->tenant_rating ? ' · ' . $maintenanceRequest->tenant_rating . '/5' : '' }}.</div>
        @elseif($maintenanceRequest->tenant_feedback)
            <div class="alert alert-danger small py-2">Tenant: “{{ $maintenanceRequest->tenant_feedback }}”</div>
        @endif

        @unless($closed)
            @if($providers->isEmpty())
                <p class="small text-secondary mb-0">No providers yet. <a href="{{ route('service-providers.index') }}">Add one to your network</a>.</p>
            @else
                <form method="POST" action="{{ route('maintenance.assign', $maintenanceRequest) }}" class="d-grid gap-2">
                    @csrf
                    <select name="service_provider_id" class="form-select form-select-sm" required aria-label="Service provider">
                        <option value="">{{ $assigned ? 'Reassign to…' : 'Choose a provider…' }}</option>
                        @foreach($providers as $sp)
                            <option value="{{ $sp->id }}" @selected($assigned?->id === $sp->id)>
                                {{ in_array($category, $sp->categories ?? [], true) ? '★ ' : '' }}{{ $sp->company_name }}{{ $sp->isVerified() ? ' ✓' : '' }}{{ $sp->rating_avg ? ' · ' . number_format($sp->rating_avg, 1) . '/5' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <input type="datetime-local" name="scheduled_at" class="form-control form-control-sm" aria-label="Visit time (optional)" min="{{ now()->format('Y-m-d\TH:i') }}">
                    <input type="text" name="note" maxlength="1000" class="form-control form-control-sm" placeholder="Note for the record (optional)">
                    <button class="btn btn-sm btn-dark rounded-pill">{{ $assigned ? 'Reassign' : 'Assign' }}</button>
                    <div class="form-text">★ offers this kind of work · ✓ verified by Terra.</div>
                </form>
            @endif
        @endunless
    </div>
</div>

{{-- Ledger, documents/KYC and renewal/move-out for a lease (lease page sidebar). Requires $lease. --}}
@php
    $audience = app(\App\Services\PropertyAudience::class);
    $viewer = auth()->user();
    $isStaffViewer = $audience->canManageProperty($viewer, $audience->propertyIdForLease($lease));
    $isTenantViewer = $audience->isTenantOf($viewer, $lease);
    $requests = $lease->requests()->with('responder:id,name')->limit(6)->get();
    $openRenewal = $requests->first(fn ($r) => $r->type === 'renewal' && $r->isOpen());
    $openMoveOut = $requests->first(fn ($r) => $r->type === 'move_out' && $r->isOpen());
    $tenantRecord = $lease->tenant;
    $leaseDocs = $lease->documents()->count();
    $cur = $lease->currency ?: 'RWF';
@endphp

@if($isStaffViewer || $isTenantViewer)
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-body">
        <h6 class="fw-bold mb-3" style="color:#19265d;"><i class="bi bi-journal-text me-1"></i> Tenancy</h6>

        <div class="d-grid gap-2 mb-3">
            <a href="{{ route('ledger.show', $lease) }}" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-journal-arrow-down me-1"></i> Rent ledger</a>
            <a href="{{ route('documents.index', ['owner' => 'lease:' . $lease->id]) }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                <i class="bi bi-folder2 me-1"></i> Lease documents ({{ $leaseDocs }})</a>
            @php $contract = $lease->contract; @endphp
            @if($isStaffViewer)
                <a href="{{ route('contracts.edit', $lease) }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-file-earmark-ruled me-1"></i> {{ $contract ? ($contract->isFinal() ? 'Contract (final)' : 'Contract (draft)') : 'Generate contract' }}</a>
            @elseif($contract?->isFinal())
                <a href="{{ route('contracts.show', $lease) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-file-earmark-ruled me-1"></i> Read the contract</a>
            @endif
        </div>

        @if($isStaffViewer && $tenantRecord)
            <div class="d-flex justify-content-between align-items-center small border rounded-3 p-2 mb-3">
                <span><i class="bi bi-person-badge me-1"></i> Tenant ID check</span>
                <a href="{{ route('documents.index', ['owner' => 'tenant:' . $tenantRecord->id]) }}"
                   class="badge rounded-pill text-decoration-none {{ ['verified' => 'text-bg-success', 'pending' => 'text-bg-warning', 'rejected' => 'text-bg-danger'][$tenantRecord->kyc_status] ?? 'text-bg-light border' }}">
                    {{ \App\Models\Tenant::KYC_LABELS[$tenantRecord->kyc_status] ?? 'Not submitted' }}
                </a>
            </div>
        @endif

        @error('lease_request')<div class="alert alert-danger small py-2">{{ $message }}</div>@enderror

        {{-- Open requests --}}
        @foreach($requests->filter->isOpen() as $req)
            <div class="border rounded-3 p-2 mb-2 small" style="background:#fffaf4;">
                <div class="d-flex justify-content-between"><strong>{{ $req->type_label }}</strong><span class="badge text-bg-warning rounded-pill">{{ $req->status_label }}</span></div>
                @if($req->type === 'move_out')
                    <div>Leaving on <strong>{{ $req->move_out_date?->format('d M Y') }}</strong></div>
                @elseif($req->status === 'requested')
                    <div>Tenant asks to renew for <strong>{{ $req->requested_months }} months</strong></div>
                @else
                    <div>Offer: until <strong>{{ $req->offered_end_date?->format('d M Y') }}</strong> at <strong>{{ $cur }} {{ number_format((float) $req->offered_rent) }}</strong>/month
                        @if((float) $req->offered_rent !== (float) $req->current_rent)<span class="text-secondary">(was {{ number_format((float) $req->current_rent) }})</span>@endif</div>
                @endif
                @if($req->message)<div class="text-secondary fst-italic">“{{ $req->message }}”</div>@endif
                @if($req->response)<div class="text-secondary">Manager: {{ $req->response }}</div>@endif

                <div class="mt-2 d-grid gap-1">
                    @if($isStaffViewer && $req->type === 'renewal' && $req->status === 'requested')
                        <form method="POST" action="{{ route('lease-requests.respond', $req) }}" class="d-grid gap-1">
                            @csrf <input type="hidden" name="action" value="offer">
                            <input type="date" name="offered_end_date" class="form-control form-control-sm" required
                                   value="{{ $lease->end_date->copy()->addMonths($req->requested_months ?: 12)->toDateString() }}" aria-label="New end date">
                            <input type="number" name="offered_rent" min="0" class="form-control form-control-sm" required value="{{ (float) $lease->monthly_rent }}" aria-label="Monthly rent">
                            <input type="text" name="response" maxlength="2000" class="form-control form-control-sm" placeholder="Message (optional)">
                            <button class="btn btn-sm btn-success rounded-pill">Send offer</button>
                        </form>
                        <form method="POST" action="{{ route('lease-requests.respond', $req) }}">@csrf <input type="hidden" name="action" value="decline">
                            <button class="btn btn-sm btn-link text-danger p-0" onclick="return confirm('Decline this renewal request?')">Decline request</button></form>
                    @elseif($isStaffViewer && $req->type === 'move_out')
                        <form method="POST" action="{{ route('lease-requests.respond', $req) }}">@csrf <input type="hidden" name="action" value="acknowledge">
                            <button class="btn btn-sm btn-success rounded-pill w-100">Acknowledge notice</button></form>
                    @elseif($isStaffViewer && $req->status === 'offered')
                        <form method="POST" action="{{ route('lease-requests.respond', $req) }}">@csrf <input type="hidden" name="action" value="withdraw">
                            <button class="btn btn-sm btn-link text-secondary p-0">Withdraw offer</button></form>
                    @elseif($isTenantViewer && $req->status === 'offered')
                        <form method="POST" action="{{ route('lease-requests.respond', $req) }}">@csrf <input type="hidden" name="action" value="accept">
                            <button class="btn btn-sm btn-success rounded-pill w-100" onclick="return confirm('Accept the new terms? Your lease will be extended.')">Accept renewal</button></form>
                        <form method="POST" action="{{ route('lease-requests.respond', $req) }}">@csrf <input type="hidden" name="action" value="decline">
                            <button class="btn btn-sm btn-link text-danger p-0">Decline offer</button></form>
                    @elseif($isTenantViewer && $req->status === 'requested')
                        <form method="POST" action="{{ route('lease-requests.respond', $req) }}">@csrf <input type="hidden" name="action" value="withdraw">
                            <button class="btn btn-sm btn-link text-secondary p-0">Withdraw</button></form>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- New requests --}}
        @if($lease->status === 'active')
            @if($isTenantViewer)
                @unless($openRenewal)
                    <details class="mb-2">
                        <summary class="small fw-semibold" style="cursor:pointer;color:#19265d;"><i class="bi bi-arrow-repeat me-1"></i> Request lease renewal</summary>
                        <form method="POST" action="{{ route('lease-requests.store', $lease) }}" class="d-grid gap-2 mt-2">
                            @csrf <input type="hidden" name="type" value="renewal">
                            <select name="requested_months" class="form-select form-select-sm" aria-label="Renewal length">
                                @foreach([6, 12, 24, 36] as $m)<option value="{{ $m }}" @selected($m === 12)>Renew for {{ $m }} months</option>@endforeach
                            </select>
                            <textarea name="message" rows="2" maxlength="2000" class="form-control form-control-sm" placeholder="Message to your manager (optional)"></textarea>
                            <button class="btn btn-sm btn-outline-primary rounded-pill">Send request</button>
                        </form>
                    </details>
                @endunless
                @unless($openMoveOut)
                    <details>
                        <summary class="small fw-semibold" style="cursor:pointer;color:#19265d;"><i class="bi bi-box-arrow-right me-1"></i> Give move-out notice</summary>
                        <form method="POST" action="{{ route('lease-requests.store', $lease) }}" class="d-grid gap-2 mt-2">
                            @csrf <input type="hidden" name="type" value="move_out">
                            <label class="small text-secondary mb-0" for="moveOutDate">Moving out on</label>
                            <input type="date" name="move_out_date" id="moveOutDate" class="form-control form-control-sm" required min="{{ now()->addDay()->toDateString() }}">
                            <textarea name="message" rows="2" maxlength="2000" class="form-control form-control-sm" placeholder="Reason or forwarding address (optional)"></textarea>
                            <button class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Send move-out notice to your property manager?')">Send notice</button>
                        </form>
                    </details>
                @endunless
            @elseif($isStaffViewer && ! $openRenewal)
                <details>
                    <summary class="small fw-semibold" style="cursor:pointer;color:#19265d;"><i class="bi bi-arrow-repeat me-1"></i> Offer renewal to tenant</summary>
                    <form method="POST" action="{{ route('lease-requests.store', $lease) }}" class="d-grid gap-2 mt-2">
                        @csrf <input type="hidden" name="type" value="renewal">
                        <label class="small text-secondary mb-0">New end date (now {{ $lease->end_date?->format('d M Y') }})</label>
                        <input type="date" name="offered_end_date" class="form-control form-control-sm" required value="{{ $lease->end_date?->copy()->addYear()->toDateString() }}">
                        <input type="number" name="offered_rent" min="0" class="form-control form-control-sm" required value="{{ (float) $lease->monthly_rent }}" aria-label="Monthly rent">
                        <input type="text" name="response" maxlength="2000" class="form-control form-control-sm" placeholder="Message (optional)">
                        <button class="btn btn-sm btn-outline-primary rounded-pill">Send offer</button>
                    </form>
                </details>
            @endif
        @endif

        @php $history = $requests->reject->isOpen(); @endphp
        @if($history->isNotEmpty())
            <div class="small text-secondary mt-3">
                @foreach($history as $h)
                    <div>{{ $h->type_label }} · {{ $h->status_label }} · {{ ($h->closed_at ?? $h->updated_at)->format('d M Y') }}</div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endif

@extends('layouts.app')

@section('title', 'Documents')
@section('page_heading', 'Documents')
@section('page_subtitle', $isStaff
    ? 'Title deeds, contracts and tenant identity documents for your properties, stored privately.'
    : 'Your identity documents and tenancy papers, stored privately and shared only with your property manager.')

@php
    $statusBadge = ['pending' => 'text-bg-warning', 'verified' => 'text-bg-success', 'rejected' => 'text-bg-danger', 'not_required' => 'text-bg-light border'];
    $statusText = ['pending' => 'Awaiting verification', 'verified' => 'Verified', 'rejected' => 'Rejected', 'not_required' => 'Stored'];
@endphp

@section('content')
<div class="row g-4">
    <div class="col-xl-8 order-2 order-xl-1">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header bg-white p-3">
                <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                    <select name="category" class="form-select form-select-sm" style="max-width:200px" onchange="this.form.submit()" aria-label="Document type">
                        <option value="">All document types</option>
                        @foreach(\App\Models\Document::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select form-select-sm" style="max-width:200px" onchange="this.form.submit()" aria-label="Status">
                        <option value="">Any status</option>
                        @foreach(['pending' => 'Awaiting verification', 'verified' => 'Verified', 'rejected' => 'Rejected'] as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if(request('owner'))<input type="hidden" name="owner" value="{{ request('owner') }}">@endif
                    @if(request()->hasAny(['category', 'status', 'owner']))
                        <a href="{{ route('documents.index') }}" class="small">Clear filters</a>
                    @endif
                    @if($isStaff && $pendingKyc)
                        <a href="{{ route('documents.index', ['status' => 'pending']) }}" class="badge text-bg-warning ms-auto text-decoration-none">{{ $pendingKyc }} to verify</a>
                    @endif
                </form>
            </div>

            @forelse($documents as $doc)
                <div class="d-flex flex-wrap gap-3 align-items-start p-3 border-bottom">
                    <span class="d-grid rounded-3 flex-shrink-0" style="width:42px;height:42px;place-items:center;background:#f2f4f9;color:var(--terra-orange);">
                        <i class="bi {{ $doc->mime_type === 'application/pdf' ? 'bi-file-earmark-pdf' : (str_starts_with((string) $doc->mime_type, 'image/') ? 'bi-file-earmark-image' : 'bi-file-earmark-text') }} fs-5"></i>
                    </span>
                    <div class="flex-grow-1" style="min-width:200px">
                        <a href="{{ route('documents.download', $doc) }}" target="_blank" class="fw-semibold text-decoration-none" style="color:var(--terra-navy);">{{ $doc->title }}</a>
                        <span class="badge rounded-pill {{ $statusBadge[$doc->status] ?? 'text-bg-light' }} ms-1">{{ $statusText[$doc->status] ?? $doc->status }}</span>
                        @if($doc->isExpired())<span class="badge rounded-pill text-bg-danger ms-1">Expired</span>@endif
                        <div class="small text-secondary">
                            {{ $doc->category_label }} ·
                            @switch($doc->owner_key)
                                @case('tenant') Tenant: {{ $doc->documentable?->full_name }} @break
                                @case('lease') Lease {{ $doc->documentable?->lease_number }} @break
                                @case('property') Property: {{ $doc->documentable?->name }} @break
                                @case('unit') Unit {{ $doc->documentable?->unit_number }} @break
                                @case('owner') Owner: {{ $doc->documentable?->name }} @break
                                @case('subscription') Subscription {{ $doc->documentable?->contract_number }} @break
                            @endswitch
                            · {{ $doc->human_size }} · uploaded {{ $doc->created_at->diffForHumans() }} by {{ $doc->uploader?->name ?? '—' }}
                            @if($doc->expires_at) · expires {{ $doc->expires_at->format('d M Y') }}@endif
                        </div>
                        @if($doc->status === 'rejected' && $doc->rejection_reason)
                            <div class="small text-danger mt-1"><i class="bi bi-exclamation-circle me-1"></i>{{ $doc->rejection_reason }}</div>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-1 align-items-center">
                        @if($isStaff && $doc->owner_key === 'tenant' && $doc->status === 'pending')
                            <form method="POST" action="{{ route('documents.review', $doc) }}">@csrf<input type="hidden" name="decision" value="verify">
                                <button class="btn btn-sm btn-success rounded-pill"><i class="bi bi-patch-check"></i> Verify</button></form>
                            <form method="POST" action="{{ route('documents.review', $doc) }}" class="d-flex gap-1">@csrf<input type="hidden" name="decision" value="reject">
                                <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason" required maxlength="255" style="width:130px">
                                <button class="btn btn-sm btn-outline-danger rounded-pill">Reject</button></form>
                        @endif
                        <a href="{{ route('documents.download', [$doc, 'download' => 1]) }}" class="btn btn-sm btn-light" title="Download"><i class="bi bi-download"></i></a>
                        @if($isStaff || ($doc->uploaded_by === auth()->id() && $doc->status !== 'verified'))
                            <form method="POST" action="{{ route('documents.destroy', $doc) }}" onsubmit="return confirm('Remove this document?')">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-light" title="Remove"><i class="bi bi-trash3"></i></button></form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-secondary py-5"><i class="bi bi-folder2-open fs-1 d-block mb-2 opacity-50"></i>No documents yet.</div>
            @endforelse

            @if($documents->hasPages())<div class="p-3">{{ $documents->links() }}</div>@endif
        </div>
    </div>

    <div class="col-xl-4 order-1 order-xl-2">
        @if($tenant)
            <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h6 fw-bold mb-0" style="color:var(--terra-navy);"><i class="bi bi-person-badge me-1"></i> Identity verification</h2>
                        <span class="badge rounded-pill {{ ['verified' => 'text-bg-success', 'pending' => 'text-bg-warning', 'rejected' => 'text-bg-danger'][$tenant->kyc_status] ?? 'text-bg-light border' }}">
                            {{ \App\Models\Tenant::KYC_LABELS[$tenant->kyc_status] ?? $tenant->kyc_status }}
                        </span>
                    </div>
                    <p class="small text-secondary">Landlords need a verified ID before signing a lease. Your details are only visible to your property manager.</p>
                    <form method="POST" action="{{ route('documents.identity') }}">
                        @csrf @method('PUT')
                        <div class="mb-2">
                            <select name="id_type" class="form-select form-select-sm @error('id_type') is-invalid @enderror" required aria-label="ID type">
                                @foreach(\App\Models\Tenant::ID_TYPES as $key => $label)
                                    <option value="{{ $key }}" @selected(old('id_type', $tenant->id_type) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <input type="text" name="id_number" class="form-control form-control-sm @error('id_number') is-invalid @enderror" required maxlength="40"
                                   placeholder="ID / passport number" value="{{ old('id_number', $tenant->id_number) }}" aria-label="ID number">
                            @error('id_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-2">
                            <input type="number" name="occupants" min="1" max="30" class="form-control form-control-sm" placeholder="Number of occupants"
                                   value="{{ old('occupants', $tenant->occupants) }}" aria-label="Occupants">
                        </div>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill w-100">Save details</button>
                    </form>
                </div>
            </div>
        @endif

        @if($owners->isNotEmpty())
            <div class="card border-0 shadow-sm" style="border-radius:14px;">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3" style="color:var(--terra-navy);"><i class="bi bi-cloud-upload me-1"></i> Upload a document</h2>
                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-semibold" for="docOwner">Belongs to</label>
                            <select name="owner" id="docOwner" class="form-select form-select-sm" required>
                                @foreach($owners->groupBy('type') as $type => $items)
                                    <optgroup label="{{ ucfirst($type) }}">
                                        @foreach($items as $o)
                                            <option value="{{ $o['value'] }}" @selected(old('owner', request('owner')) === $o['value'])>{{ $o['label'] }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold" for="docCategory">Document type</label>
                            <select name="category" id="docCategory" class="form-select form-select-sm @error('category') is-invalid @enderror" required>
                                @foreach(\App\Models\Document::OWNER_TYPES as $type => [, $cats])
                                    @foreach($cats as $cat)
                                        @continue(! $isStaff && in_array($cat, \App\Models\Document::CONTRACT_CATEGORIES, true))
                                        <option value="{{ $cat }}" data-owner="{{ $type }}" @selected(old('category') === $cat)>{{ \App\Models\Document::CATEGORIES[$cat] }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <input type="text" name="title" maxlength="150" class="form-control form-control-sm mb-2" placeholder="Title (optional)" value="{{ old('title') }}">
                        <label class="form-label small text-secondary mb-1" for="docExpiry">Expiry date (optional)</label>
                        <input type="date" name="expires_at" id="docExpiry" class="form-control form-control-sm mb-2" min="{{ now()->addDay()->toDateString() }}">
                        <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" class="form-control form-control-sm mb-2 @error('file') is-invalid @enderror">
                        @error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <button class="btn btn-sm rounded-pill w-100 text-white" style="background:var(--terra-orange);"><i class="bi bi-upload me-1"></i> Upload</button>
                        <div class="form-text">PDF, image or Word file up to 10 MB. Stored privately.</div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var owner = document.getElementById('docOwner'), cat = document.getElementById('docCategory');
    if (!owner || !cat) return;
    function sync() {
        var type = (owner.value || '').split(':')[0], first = null;
        Array.prototype.forEach.call(cat.options, function (o) {
            var ok = o.dataset.owner === type;
            o.hidden = !ok; o.disabled = !ok;
            if (ok && !first) first = o;
        });
        if (cat.selectedOptions[0] && cat.selectedOptions[0].disabled && first) first.selected = true;
    }
    owner.addEventListener('change', sync); sync();
})();
</script>
@endpush

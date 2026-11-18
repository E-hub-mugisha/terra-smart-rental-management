{{--
    Documents & contracts on a profile (tenant or property owner).
    Params: $owner (model), $ownerKey ('tenant'|'owner'), $documents (collection),
            $canUpload (bool), $heading (string, optional).
    Contracts must be PDF; the server enforces type, size and who may upload/view.
--}}
@php
    $categories = \App\Models\Document::OWNER_TYPES[$ownerKey][1] ?? ['other'];
    $contractCats = array_values(array_intersect($categories, \App\Models\Document::CONTRACT_CATEGORIES));
    $panelId = 'docs-' . $ownerKey . '-' . $owner->getKey();
@endphp
<div class="card border-0 shadow-sm mb-3" style="border-radius:14px;" id="{{ $panelId }}">
    <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2 p-3">
        <div>
            <h3 class="h6 mb-0"><i class="bi bi-folder2-open me-1"></i> {{ $heading ?? 'Documents & contracts' }}</h3>
            <div class="small text-muted">{{ $documents->count() }} {{ \Illuminate\Support\Str::plural('file', $documents->count()) }} on record · stored privately</div>
        </div>
        @if($canUpload)
            <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $panelId }}-upload" aria-expanded="{{ $errors->has('file') ? 'true' : 'false' }}">
                <i class="bi bi-upload"></i> Upload
            </button>
        @endif
    </div>

    @if($canUpload)
        <div class="collapse {{ $errors->has('file') || $errors->has('category') ? 'show' : '' }}" id="{{ $panelId }}-upload">
            <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="p-3 border-bottom row g-2 align-items-end">
                @csrf
                <input type="hidden" name="owner" value="{{ $ownerKey }}:{{ $owner->getKey() }}">
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="{{ $panelId }}-cat">Type</label>
                    <select name="category" id="{{ $panelId }}-cat" class="form-select form-select-sm" data-contract-cats="{{ implode(',', $contractCats) }}">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" @selected(old('category', $contractCats[0] ?? null) === $cat)>{{ \App\Models\Document::CATEGORIES[$cat] ?? $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="{{ $panelId }}-title">Title <span class="text-muted">(optional)</span></label>
                    <input name="title" id="{{ $panelId }}-title" maxlength="150" class="form-control form-control-sm" placeholder="e.g. Signed agreement 2026">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="{{ $panelId }}-exp">Expires <span class="text-muted">(optional)</span></label>
                    <input type="date" name="expires_at" id="{{ $panelId }}-exp" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="{{ $panelId }}-file">File</label>
                    <input type="file" name="file" id="{{ $panelId }}-file" required accept="application/pdf,.pdf" class="form-control form-control-sm @error('file') is-invalid @enderror">
                    @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('category')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-1 d-grid"><button class="btn btn-sm btn-dark">Save</button></div>
                <div class="col-12 small text-muted" data-hint>Contracts must be PDF files, up to 10 MB.</div>
            </form>
        </div>
    @endif

    <ul class="list-unstyled m-0">
        @forelse($documents as $doc)
            <li class="d-flex flex-wrap align-items-center gap-2 px-3 py-2 border-top">
                <i class="bi {{ $doc->mime_type === 'application/pdf' ? 'bi-file-earmark-pdf text-danger' : (str_starts_with((string) $doc->mime_type, 'image/') ? 'bi-file-earmark-image text-primary' : 'bi-file-earmark-text') }} fs-5"></i>
                <div style="flex:1 1 200px;min-width:0">
                    <div class="fw-semibold text-break">
                        {{ $doc->title }}
                        @if($doc->is_contract)<span class="badge text-bg-light border fw-normal ms-1">Contract</span>@endif
                        @if(in_array($doc->status, ['pending', 'verified', 'rejected'], true))
                            <span class="badge fw-normal {{ ['pending' => 'text-bg-warning', 'verified' => 'text-bg-success', 'rejected' => 'text-bg-danger'][$doc->status] }}">{{ ucfirst($doc->status) }}</span>
                        @endif
                    </div>
                    <div class="small text-muted">
                        {{ $doc->category_label }} · {{ $doc->human_size }} · {{ $doc->created_at->format('d M Y') }}
                        @if($doc->uploader) · by {{ $doc->uploader->name }} @endif
                        @if($doc->expires_at) · <span class="{{ $doc->expires_at->isPast() ? 'text-danger' : '' }}">expires {{ $doc->expires_at->format('d M Y') }}</span> @endif
                    </div>
                </div>
                <div class="ms-auto text-nowrap">
                    <a href="{{ route('documents.download', $doc) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light" title="View" aria-label="View {{ $doc->title }}"><i class="bi bi-eye"></i></a>
                    <a href="{{ route('documents.download', [$doc, 'download' => 1]) }}" class="btn btn-sm btn-light" title="Download" aria-label="Download {{ $doc->title }}"><i class="bi bi-download"></i></a>
                    @if($canUpload)
                        <form method="POST" action="{{ route('documents.destroy', $doc) }}" class="d-inline" onsubmit="return confirm('Remove this document? A copy is kept in the audit trail.');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-light text-danger" title="Remove" aria-label="Remove {{ $doc->title }}"><i class="bi bi-trash"></i></button>
                        </form>
                    @endif
                </div>
            </li>
        @empty
            <li class="text-center text-muted small py-4 border-top"><i class="bi bi-file-earmark-pdf d-block fs-4 mb-1 opacity-50"></i>No documents yet.{{ $canUpload ? ' Upload the signed contract as a PDF.' : '' }}</li>
        @endforelse
    </ul>
</div>
@once
@push('scripts')
<script>
document.querySelectorAll('select[data-contract-cats]').forEach(function (sel) {
    var contracts = (sel.dataset.contractCats || '').split(',').filter(Boolean);
    var form = sel.form, file = form.querySelector('input[type=file]'), hint = form.querySelector('[data-hint]');
    function sync() {
        var isContract = contracts.indexOf(sel.value) !== -1;
        file.accept = isContract ? 'application/pdf,.pdf' : '.pdf,.jpg,.jpeg,.png,.webp,.doc,.docx';
        if (hint) hint.textContent = isContract ? 'Contracts must be PDF files, up to 10 MB.' : 'PDF, image or Word file, up to 10 MB.';
    }
    sel.addEventListener('change', sync); sync();
});
</script>
@endpush
@endonce

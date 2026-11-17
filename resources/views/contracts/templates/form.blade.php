@extends('layouts.app')

@section('title', $template->exists ? 'Edit template' : 'New template')
@section('page_heading', $template->exists ? 'Edit lease template' : 'New lease template')
@section('page_subtitle', 'Use placeholders like ' . str_repeat('{', 2) . 'tenant_name' . str_repeat('}', 2) . ' — they are replaced with the lease details when a contract is generated.')

@section('content')
<form method="POST" action="{{ $template->exists ? route('lease-templates.update', $template) : route('lease-templates.store') }}">
    @csrf @if($template->exists) @method('PUT') @endif
    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
                <div class="card-body row g-3">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold" for="name">Name</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" required maxlength="150" value="{{ old('name', $template->name) }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold" for="property_id">Property</label>
                        <select name="property_id" id="property_id" class="form-select">
                            @if(auth()->user()->isAdmin())<option value="">All properties</option>@endif
                            @foreach($properties as $p)<option value="{{ $p->id }}" @selected(old('property_id', $template->property_id) == $p->id)>{{ $p->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="description">Description</label>
                        <input type="text" name="description" id="description" class="form-control" maxlength="1000" value="{{ old('description', $template->description) }}">
                    </div>
                    @if(auth()->user()->isAdmin())
                        <div class="col-12 form-check ms-2">
                            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default" @checked(old('is_default', $template->is_default))>
                            <label class="form-check-label" for="is_default">Platform default template</label>
                        </div>
                    @endif
                </div>
            </div>

            @error('clauses')<div class="alert alert-danger">{{ $message }}</div>@enderror
            <div id="clauses">
                @foreach(old('clauses', $template->clauses ?? []) as $i => $c)
                    <div class="card border-0 shadow-sm mb-3 js-clause" style="border-radius:14px;">
                        <div class="card-body">
                            <div class="d-flex gap-2 mb-2">
                                <input type="text" name="clauses[{{ $i }}][title]" class="form-control form-control-sm fw-semibold" placeholder="Clause title" value="{{ $c['title'] ?? '' }}" maxlength="150">
                                <button type="button" class="btn btn-sm btn-light js-del" title="Remove"><i class="bi bi-trash3"></i></button>
                            </div>
                            <textarea name="clauses[{{ $i }}][body]" rows="3" class="form-control form-control-sm" placeholder="Clause text" maxlength="10000">{{ $c['body'] ?? '' }}</textarea>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="add"><i class="bi bi-plus-lg"></i> Add clause</button>
                <button class="btn rounded-pill px-4 text-white" style="background:var(--terra-orange);">Save template</button>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm position-sticky" style="border-radius:14px;top:90px;">
                <div class="card-body">
                    <h2 class="h6 fw-bold" style="color:var(--terra-navy);">Placeholders</h2>
                    <p class="small text-secondary">Click to copy, then paste into a clause.</p>
                    <div class="d-grid gap-1">
                        @foreach(\App\Models\LeaseTemplate::PLACEHOLDERS as $key => $label)
                            @php $ph = str_repeat('{', 2) . $key . str_repeat('}', 2); @endphp
                            <button type="button" class="btn btn-sm btn-light text-start js-ph" data-ph="{{ $ph }}">
                                <code>{{ $ph }}</code> <span class="small text-secondary">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var box = document.getElementById('clauses'), n = 1000;
    document.getElementById('add').addEventListener('click', function () {
        var d = document.createElement('div');
        d.className = 'card border-0 shadow-sm mb-3 js-clause'; d.style.borderRadius = '14px';
        d.innerHTML = '<div class="card-body"><div class="d-flex gap-2 mb-2"><input type="text" name="clauses[' + n + '][title]" class="form-control form-control-sm fw-semibold" placeholder="Clause title" maxlength="150">'
            + '<button type="button" class="btn btn-sm btn-light js-del" title="Remove"><i class="bi bi-trash3"></i></button></div>'
            + '<textarea name="clauses[' + n + '][body]" rows="3" class="form-control form-control-sm" placeholder="Clause text" maxlength="10000"></textarea></div>';
        n++; box.appendChild(d); d.querySelector('input').focus();
    });
    box.addEventListener('click', function (e) { var b = e.target.closest('.js-del'); if (b) b.closest('.js-clause').remove(); });
    document.querySelectorAll('.js-ph').forEach(function (b) {
        b.addEventListener('click', function () {
            if (navigator.clipboard) navigator.clipboard.writeText(b.dataset.ph);
            var t = b.querySelector('.text-secondary'), old = t.textContent; t.textContent = 'Copied ✓'; setTimeout(function () { t.textContent = old; }, 1200);
        });
    });
})();
</script>
@endpush

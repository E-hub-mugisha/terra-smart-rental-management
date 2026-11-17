@extends('layouts.app')

@section('title', 'Contract · ' . $lease->lease_number)
@section('page_heading', 'Lease contract')
@section('page_subtitle', ($lease->tenant?->full_name ?? 'Tenant') . ' · Unit ' . $lease->unit?->unit_number . ' · ' . $lease->unit?->building?->property?->name)

@section('content')
@error('contract')<div class="alert alert-danger">{{ $message }}</div>@enderror

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3" style="color:var(--terra-navy);"><i class="bi bi-magic me-1"></i> {{ $contract ? 'Regenerate from a template' : 'Generate the contract' }}</h2>
                @if($contract?->isFinal())
                    <div class="alert alert-success small mb-0">This contract is final ({{ $contract->finalized_at?->format('d M Y') }}). <a href="{{ route('contracts.show', $lease) }}">View it</a>.</div>
                @else
                    @if($missing)
                        <div class="alert alert-warning small"><strong>Missing details</strong> will show as blanks:<ul class="mb-0 ps-3">@foreach($missing as $m)<li>{{ $m }}</li>@endforeach</ul></div>
                    @endif
                    <form method="POST" action="{{ route('contracts.generate', $lease) }}" @if($contract) onsubmit="return confirm('Replace the current draft with a fresh copy of the template?')" @endif>
                        @csrf
                        <label class="form-label small fw-semibold" for="tpl">Template</label>
                        <select name="lease_template_id" id="tpl" class="form-select form-select-sm mb-2" required>
                            @foreach($templates as $t)
                                <option value="{{ $t->id }}" @selected(old('lease_template_id', $contract?->lease_template_id) == $t->id || (! $contract && $t->is_default))>
                                    {{ $t->name }}{{ $t->is_default ? ' (default)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <label class="form-label small fw-semibold" for="notice">Notice period (days)</label>
                        <input type="number" name="notice_period_days" id="notice" min="0" max="365" class="form-control form-control-sm mb-2" required
                               value="{{ old('notice_period_days', $contract?->variables['notice_period_days'] ?? 30) }}">
                        <label class="form-label small fw-semibold" for="special">Special conditions</label>
                        <textarea name="special_conditions" id="special" rows="3" maxlength="5000" class="form-control form-control-sm mb-3"
                                  placeholder="e.g. Tenant may keep one small pet.">{{ old('special_conditions', ($contract?->variables['special_conditions'] ?? '') === 'None.' ? '' : ($contract?->variables['special_conditions'] ?? '')) }}</textarea>
                        <button class="btn btn-sm btn-dark rounded-pill w-100">{{ $contract ? 'Regenerate draft' : 'Generate draft' }}</button>
                    </form>
                    <p class="small text-secondary mt-3 mb-0">Lease details (names, unit, rent, deposit, dates) are filled in automatically.
                        <a href="{{ route('lease-templates.index') }}">Manage templates</a></p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        @if(! $contract)
            <div class="card border-0 shadow-sm text-center p-5 text-secondary" style="border-radius:14px;">
                <i class="bi bi-file-earmark-text fs-1 d-block mb-2 opacity-50"></i>Choose a template on the left to generate the contract.
            </div>
        @elseif($contract->isFinal())
            <div class="card border-0 shadow-sm p-4" style="border-radius:14px;">
                @foreach($contract->clauses as $i => $c)
                    <h3 class="h6 fw-bold mt-2">{{ $i + 1 }}. {{ $c['title'] }}</h3>
                    <p class="mb-2" style="white-space:pre-line;">{{ $c['body'] }}</p>
                @endforeach
            </div>
        @else
            <form method="POST" action="{{ route('contracts.update', $lease) }}">
                @csrf @method('PUT')
                <div class="card border-0 shadow-sm" style="border-radius:14px;">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3">
                        <strong style="color:var(--terra-navy);">Draft · {{ count($contract->clauses) }} clauses</strong>
                        <a href="{{ route('contracts.show', $lease) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-eye me-1"></i> Preview</a>
                    </div>
                    <div class="card-body" id="clauseList">
                        @foreach($contract->clauses as $i => $c)
                            <div class="border rounded-3 p-3 mb-3 js-clause">
                                <div class="d-flex gap-2 mb-2 align-items-center">
                                    <span class="fw-bold text-secondary js-num">{{ $i + 1 }}.</span>
                                    <input type="text" name="clauses[{{ $i }}][title]" class="form-control form-control-sm fw-semibold" value="{{ $c['title'] }}" required maxlength="150" aria-label="Clause title">
                                    <label class="btn btn-sm btn-light mb-0" title="Remove clause"><input type="checkbox" name="clauses[{{ $i }}][remove]" value="1" class="d-none js-remove"><i class="bi bi-trash3"></i></label>
                                </div>
                                <textarea name="clauses[{{ $i }}][body]" rows="3" class="form-control form-control-sm" required maxlength="10000" aria-label="Clause text">{{ $c['body'] }}</textarea>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer bg-white d-flex flex-wrap justify-content-between gap-2 p-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="addClause"><i class="bi bi-plus-lg"></i> Add clause</button>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-dark rounded-pill px-3">Save draft</button>
                            <button name="then" value="finalize" class="btn btn-sm rounded-pill px-3 text-white" style="background:var(--terra-orange);"
                                    onclick="return confirm('Finalise the contract? It can no longer be edited, and the tenant will be able to read it.')">Save &amp; finalise</button>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var list = document.getElementById('clauseList'), add = document.getElementById('addClause');
    if (!list || !add) return;
    add.addEventListener('click', function () {
        var i = list.querySelectorAll('.js-clause').length + Date.now() % 1000;
        var div = document.createElement('div');
        div.className = 'border rounded-3 p-3 mb-3 js-clause';
        div.innerHTML = '<div class="d-flex gap-2 mb-2 align-items-center"><span class="fw-bold text-secondary js-num"></span>'
            + '<input type="text" name="clauses[' + i + '][title]" class="form-control form-control-sm fw-semibold" placeholder="Clause title" required maxlength="150"></div>'
            + '<textarea name="clauses[' + i + '][body]" rows="3" class="form-control form-control-sm" placeholder="Clause text" required maxlength="10000"></textarea>';
        list.appendChild(div);
        div.querySelector('input').focus();
        renumber();
    });
    list.addEventListener('change', function (e) {
        if (!e.target.classList.contains('js-remove')) return;
        var box = e.target.closest('.js-clause');
        box.style.opacity = e.target.checked ? '.4' : '';
        box.querySelectorAll('input[type=text],textarea').forEach(function (f) { f.required = !e.target.checked; });
    });
    function renumber() { list.querySelectorAll('.js-num').forEach(function (n, k) { n.textContent = (k + 1) + '.'; }); }
})();
</script>
@endpush

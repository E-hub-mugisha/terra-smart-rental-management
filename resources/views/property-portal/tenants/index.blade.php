@extends('layouts.app')

@section('title', 'Tenants')

@section('content')
@php
    $chips = [];
    if (request()->filled('search')) { $chips['search'] = '“' . request('search') . '”'; }
    if (request()->filled('status')) { $chips['status'] = ucfirst(request('status')); }
    $currentStatus = (string) request('status', '');
@endphp

<div class="container-fluid py-4 pm-tenants">
    <div class="tn-progress" id="tnProgress"></div>

    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color:var(--terra-navy);letter-spacing:-.03em">Tenants</h3>
            <p class="text-muted mb-0">Manage tenant profiles and their linked accounts.</p>
        </div>
        <a href="{{ route('property-manager.tenants.create') }}" class="btn btn-brand tn-add">
            <i class="bi bi-person-plus me-2"></i>Add tenant
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert tn-alert tn-alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('generated_password'))
        <div class="tn-cred" role="alert">
            <div class="tn-cred-head">
                <div class="tn-cred-ico"><i class="bi bi-key-fill"></i></div>
                <div>
                    <h6 class="fw-bold mb-0">Temporary tenant login credentials</h6>
                    <small>Share these securely. The password will not be shown again.</small>
                </div>
            </div>
            <div class="tn-cred-grid">
                <div class="tn-cred-field">
                    <span>Email</span>
                    <strong>{{ session('generated_email') }}</strong>
                </div>
                <div class="tn-cred-field">
                    <span>Temporary password</span>
                    <div class="d-flex align-items-center gap-2">
                        <code id="tenantGeneratedPassword" data-hidden="1">••••••••••</code>
                        <button type="button" class="tn-mini" id="tnReveal" aria-label="Show or hide password"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-dark tn-copy" id="tnCopy">
                    <i class="bi bi-clipboard me-1"></i><span>Copy password</span>
                </button>
            </div>
            <input type="hidden" id="tnPwdValue" value="{{ session('generated_password') }}">
        </div>
    @endif

    @if($errors->any())
        <div class="alert tn-alert tn-alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>Please review the validation errors in the form.</div>
        </div>
    @endif

    {{-- Summary --}}
    <div class="tn-summary mb-4" id="tnStats">
        <div class="tn-sum">
            <div class="ico"><i class="bi bi-people-fill"></i></div>
            <div><strong>{{ number_format($tenants->total()) }}</strong><span>Tenants</span></div>
        </div>
        <div class="tn-sum">
            <div class="ico ok"><i class="bi bi-person-check-fill"></i></div>
            <div><strong>{{ number_format($tenants->getCollection()->where('status', 'active')->count()) }}</strong><span>Active on page</span></div>
        </div>
        <div class="tn-sum">
            <div class="ico link"><i class="bi bi-link-45deg"></i></div>
            <div><strong>{{ number_format($tenants->getCollection()->filter(fn ($t) => $t->user)->count()) }}</strong><span>Linked accounts on page</span></div>
        </div>
    </div>

    {{-- Search + status in one bar --}}
    <form method="GET" action="{{ route('property-manager.tenants.index') }}" id="tnForm" class="tn-bar mb-4" role="search">
        <div class="tn-bar-row">
            <div class="tn-search {{ request()->filled('search') ? 'has-value' : '' }}" id="tnSearchBox">
                <i class="bi bi-search"></i>
                <input type="search" name="search" id="tnSearch" value="{{ request('search') }}"
                       placeholder="Search name, tenant number, email or phone"
                       aria-label="Search tenants" autocomplete="off">
                <button type="button" class="tn-x" id="tnClear" aria-label="Clear search"><i class="bi bi-x-lg"></i></button>
            </div>

            <div class="tn-pills" role="radiogroup" aria-label="Tenant status">
                @foreach(['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $val => $lbl)
                    <input type="radio" name="status" id="tn-st-{{ $val ?: 'all' }}" value="{{ $val }}" @checked($currentStatus === $val)>
                    <label for="tn-st-{{ $val ?: 'all' }}">{{ $lbl }}</label>
                @endforeach
            </div>
        </div>
    </form>

    {{-- Results (swapped live) --}}
    <section id="tnResults" aria-live="polite">

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <div class="text-muted small me-2">
                <strong>{{ number_format($tenants->total()) }}</strong> {{ \Illuminate\Support\Str::plural('record', $tenants->total()) }}
            </div>
            @foreach($chips as $key => $label)
                <a class="tn-chip" data-chip="{{ $key }}" href="{{ route('property-manager.tenants.index', request()->except($key, 'page')) }}">
                    {{ $label }} <i class="bi bi-x-lg" style="font-size:.62rem"></i>
                </a>
            @endforeach
            @if(count($chips) > 1)
                <a href="{{ route('property-manager.tenants.index') }}" class="small fw-bold" data-chip="all">Clear all</a>
            @endif
        </div>

        <div class="tn-panel">
            <div class="table-responsive">
                <table class="table tn-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Contact</th>
                            <th>Tenant no.</th>
                            <th>Account</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenants as $tenant)
                            @php
                                $showUrl = route('property-manager.tenants.show', $tenant);
                                $initials = \Illuminate\Support\Str::of($tenant->full_name)->explode(' ')->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                                $isActive = $tenant->status === 'active';
                            @endphp
                            <tr class="tn-row" data-href="{{ $showUrl }}" style="animation-delay: {{ min($loop->index, 10) * 40 }}ms">
                                <td class="tn-first">
                                    <div class="tn-person">
                                        <div class="tn-avatar {{ $isActive ? '' : 'off' }}">{{ $initials ?: 'T' }}</div>
                                        <div class="min-w-0">
                                            <a href="{{ $showUrl }}" class="tn-name">{{ $tenant->full_name }}</a>
                                            <div class="tn-sub"><i class="bi bi-envelope"></i>{{ $tenant->email ?: 'No email' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Contact">
                                    <div class="tn-phone"><i class="bi bi-telephone-fill"></i>{{ $tenant->phone ?: '—' }}</div>
                                    <div class="tn-sub ps-0">{{ $tenant->alternative_phone ?: 'No alternative phone' }}</div>
                                </td>
                                <td data-label="Tenant no."><span class="tn-code">{{ $tenant->tenant_number }}</span></td>
                                <td data-label="Account">
                                    @if($tenant->user)
                                        <span class="tn-pill linked"><i class="bi bi-link-45deg"></i>Linked</span>
                                    @else
                                        <span class="tn-pill none"><i class="bi bi-dash-circle"></i>No account</span>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    <span class="tn-pill {{ $isActive ? 'active' : 'inactive' }}">
                                        <span class="dot"></span>{{ ucfirst($tenant->status) }}
                                    </span>
                                </td>
                                <td class="text-end tn-last">
                                    <div class="tn-actions">
                                        <a href="{{ $showUrl }}" class="tn-act" title="View" aria-label="View {{ $tenant->full_name }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('property-manager.tenants.edit', $tenant) }}" class="tn-act" title="Edit" aria-label="Edit {{ $tenant->full_name }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST"
                                              action="{{ route('property-manager.tenants.destroy', $tenant) }}"
                                              onsubmit="return confirm('Archive this tenant? Their rental and payment history will be preserved.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tn-act danger" title="Archive tenant" aria-label="Archive {{ $tenant->full_name }}">
                                                <i class="bi bi-archive"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="tn-empty-row">
                                <td colspan="6">
                                    <div class="tn-empty">
                                        <i class="bi bi-people"></i>
                                        <h6 class="fw-bold mt-3 mb-1" style="color:var(--terra-navy)">No tenants found</h6>
                                        <p class="text-muted small mb-3">Add a tenant or adjust your search filters.</p>
                                        <a href="{{ route('property-manager.tenants.create') }}" class="btn btn-brand btn-sm">
                                            <i class="bi bi-person-plus me-1"></i>Add tenant
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tenants->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $tenants->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </section>
</div>

<style>
    .pm-tenants { --terra-orange: #D05208; --terra-navy: #19265d; --tn-line: #e4e8f2; --tn-soft: #f4f6fb; }
    .pm-tenants .btn-brand { background: var(--terra-orange); border-color: var(--terra-orange); color: #fff; font-weight: 600; transition: all .2s; }
    .pm-tenants .btn-brand:hover { background: #b94707; border-color: #b94707; color: #fff; transform: translateY(-1px); box-shadow: 0 8px 18px rgba(208,82,8,.28); }
    .tn-add { padding: 10px 18px; border-radius: 12px; }

    /* Progress */
    .tn-progress { position: fixed; left: 0; top: 0; height: 3px; width: 0; background: var(--terra-orange); z-index: 2000; opacity: 0; transition: width .4s, opacity .3s; }
    .tn-progress.on { width: 70%; opacity: 1; }
    .tn-progress.done { width: 100%; opacity: 0; }

    /* Alerts */
    .tn-alert { display: flex; align-items: center; gap: 12px; border: 0; border-radius: 14px; padding: 14px 18px; }
    .tn-alert > i { font-size: 1.2rem; }
    .tn-alert .btn-close { margin-left: auto; position: static; }
    .tn-alert-success { background: #e8f7ee; color: #146c3a; }
    .tn-alert-danger { background: #fdecec; color: #a32121; }

    /* Credentials card */
    .tn-cred { background: linear-gradient(135deg, #fff7ee, #fff); border: 1px solid #ffd9b0; border-left: 5px solid var(--terra-orange); border-radius: 16px; padding: 18px 20px; margin-bottom: 22px; box-shadow: 0 8px 24px rgba(208,82,8,.08); }
    .tn-cred-head { display: flex; gap: 14px; align-items: center; margin-bottom: 14px; }
    .tn-cred-head small { color: #7a5a3a; }
    .tn-cred-ico { width: 40px; height: 40px; border-radius: 12px; background: var(--terra-orange); color: #fff; display: grid; place-items: center; }
    .tn-cred-grid { display: grid; gap: 12px; grid-template-columns: 1fr; align-items: end; }
    @media (min-width: 768px) { .tn-cred-grid { grid-template-columns: 1.3fr 1fr auto; } }
    .tn-cred-field { background: #fff; border: 1px solid #f1e2cf; border-radius: 12px; padding: 10px 14px; min-width: 0; }
    .tn-cred-field span { display: block; font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #9a7b57; margin-bottom: 2px; }
    .tn-cred-field strong, .tn-cred-field code { font-size: .95rem; word-break: break-all; color: var(--terra-navy); }
    .tn-mini { border: 0; background: var(--tn-soft); border-radius: 8px; width: 30px; height: 30px; color: var(--terra-navy); }
    .tn-mini:hover { background: #ffe3d0; color: var(--terra-orange); }
    .tn-copy { border-radius: 12px; padding: 12px 16px; font-weight: 600; }

    /* Summary */
    .tn-summary { display: flex; flex-wrap: wrap; gap: 12px 30px; }
    .tn-sum { display: flex; align-items: center; gap: 12px; }
    .tn-sum .ico { width: 40px; height: 40px; border-radius: 12px; background: #fff1e8; color: var(--terra-orange); display: grid; place-items: center; font-size: 1.1rem; }
    .tn-sum .ico.ok { background: #e8f7ee; color: #1a8a4a; }
    .tn-sum .ico.link { background: #e9edfb; color: #3a4fb5; }
    .tn-sum strong { display: block; font-size: 1.2rem; font-weight: 800; letter-spacing: -.02em; color: var(--terra-navy); line-height: 1.1; }
    .tn-sum span { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #7b839c; }

    /* Bar */
    .tn-bar { position: sticky; top: 72px; z-index: 20; background: #fff; border: 1px solid var(--tn-line); border-radius: 16px; padding: 10px; box-shadow: 0 10px 28px rgba(17,27,72,.08); }
    .tn-bar-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .tn-search { position: relative; flex: 1 1 280px; min-width: 0; }
    .tn-search > i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--terra-orange); pointer-events: none; }
    .tn-search input { width: 100%; border: 0; background: var(--tn-soft); border-radius: 12px; padding: 12px 40px 12px 42px; font-size: .93rem; font-weight: 500; transition: box-shadow .2s, background .2s; }
    .tn-search input:focus { outline: 0; background: #fff; box-shadow: 0 0 0 3px rgba(208,82,8,.18); }
    .tn-x { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 28px; height: 28px; border: 0; border-radius: 50%; background: transparent; color: #8b93ac; display: none; }
    .tn-search.has-value .tn-x { display: block; }
    .tn-x:hover { background: #ffeadc; color: var(--terra-orange); }
    .tn-pills { display: flex; gap: 6px; }
    .tn-pills input { position: absolute; opacity: 0; pointer-events: none; }
    .tn-pills label { border: 1.5px solid var(--tn-line); border-radius: 999px; padding: 6px 16px; font-size: .84rem; font-weight: 600; cursor: pointer; user-select: none; background: #fff; transition: all .2s; }
    .tn-pills label:hover { border-color: var(--terra-orange); color: var(--terra-orange); }
    .tn-pills input:checked + label { background: var(--terra-navy); border-color: var(--terra-navy); color: #fff; }
    .tn-pills input:focus-visible + label { outline: 3px solid rgba(208,82,8,.35); outline-offset: 2px; }

    /* Results */
    #tnResults { transition: opacity .2s; min-height: 200px; }
    #tnResults.is-loading { opacity: .4; pointer-events: none; }
    .tn-chip { display: inline-flex; align-items: center; gap: 8px; background: #fff1e8; border: 1px solid #ffd9c2; color: #a8410a; border-radius: 999px; padding: 4px 10px 4px 13px; font-size: .78rem; font-weight: 600; text-decoration: none; transition: all .2s; }
    .tn-chip:hover { background: var(--terra-orange); border-color: var(--terra-orange); color: #fff; }

    /* Table */
    .tn-panel { background: #fff; border: 1px solid var(--tn-line); border-radius: 18px; overflow: hidden; box-shadow: 0 4px 16px rgba(17,27,72,.05); }
    .tn-table { --bs-table-bg: transparent; }
    .tn-table thead th { background: var(--tn-soft); color: #66708f; font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; padding: 14px 18px; border-bottom: 1px solid var(--tn-line); white-space: nowrap; }
    .tn-table tbody td { padding: 15px 18px; vertical-align: middle; border-bottom: 1px solid #eef0f7; }
    .tn-table tbody tr:last-child td { border-bottom: 0; }
    .tn-row { cursor: pointer; animation: tnIn .4s both; transition: background .2s; }
    .tn-row:hover { background: #fffaf6; }
    .tn-row td:first-child { box-shadow: inset 0 0 0 0 var(--terra-orange); transition: box-shadow .25s; }
    .tn-row:hover td:first-child { box-shadow: inset 4px 0 0 0 var(--terra-orange); }
    @keyframes tnIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

    .tn-person { display: flex; align-items: center; gap: 14px; min-width: 230px; }
    .tn-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, var(--terra-navy), #3a4fb5); color: #fff; font-weight: 800; font-size: .88rem; display: grid; place-items: center; flex-shrink: 0; }
    .tn-avatar.off { background: linear-gradient(135deg, #9aa1b8, #bcc2d4); }
    .tn-name { font-weight: 700; color: var(--terra-navy); text-decoration: none; display: block; line-height: 1.25; }
    .tn-name:hover { color: var(--terra-orange); }
    .tn-sub { font-size: .78rem; color: #7b839c; margin-top: 2px; }
    .tn-sub i { margin-right: 5px; color: #a3abc2; }
    .tn-phone { font-weight: 600; font-size: .9rem; color: var(--terra-navy); }
    .tn-phone i { color: var(--terra-orange); font-size: .75rem; margin-right: 6px; }
    .tn-code { display: inline-block; font-weight: 700; font-size: .8rem; letter-spacing: .03em; background: var(--tn-soft); color: var(--terra-navy); border-radius: 8px; padding: 4px 10px; font-variant-numeric: tabular-nums; }

    .tn-pill { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 4px 12px; font-size: .78rem; font-weight: 700; white-space: nowrap; }
    .tn-pill.active { background: #e8f7ee; color: #146c3a; }
    .tn-pill.inactive { background: #eef0f6; color: #5a6380; }
    .tn-pill .dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .tn-pill.active .dot { animation: tnPulse 2s infinite; }
    @keyframes tnPulse { 0% { box-shadow: 0 0 0 0 rgba(26,138,74,.5); } 70% { box-shadow: 0 0 0 6px rgba(26,138,74,0); } 100% { box-shadow: 0 0 0 0 rgba(26,138,74,0); } }
    .tn-pill.linked { background: #e9edfb; color: #3a4fb5; }
    .tn-pill.none { background: #f3f4f8; color: #7b839c; }

    .tn-actions { display: inline-flex; gap: 6px; align-items: center; }
    .tn-actions form { margin: 0; }
    .tn-act { width: 36px; height: 36px; display: inline-grid; place-items: center; border: 1.5px solid var(--tn-line); background: #fff; color: var(--terra-navy); border-radius: 10px; text-decoration: none; transition: all .2s; position: relative; z-index: 2; }
    .tn-act:hover { background: var(--terra-navy); border-color: var(--terra-navy); color: #fff; transform: translateY(-2px); }
    .tn-act.danger { color: #c0392b; }
    .tn-act.danger:hover { background: #c0392b; border-color: #c0392b; color: #fff; }

    .tn-empty { padding: 48px 16px; text-align: center; }
    .tn-empty i { font-size: 2.6rem; color: var(--terra-orange); display: inline-block; animation: tnFloat 3s ease-in-out infinite; }
    @keyframes tnFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-7px); } }
    .tn-empty-row td { border: 0 !important; }

    /* Mobile: rows stack */
    @media (max-width: 767.98px) {
        .tn-table thead { display: none; }
        .tn-table, .tn-table tbody, .tn-table tr, .tn-table td { display: block; width: 100%; }
        .tn-table tbody tr { padding: 14px 16px; border-bottom: 1px solid #eef0f7; }
        .tn-table tbody td { border: 0; padding: 6px 0; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .tn-table tbody td[data-label]::before { content: attr(data-label); font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #7b839c; }
        .tn-table tbody td.tn-first { display: block; padding-bottom: 10px; }
        .tn-table tbody td.tn-last { justify-content: flex-end; padding-top: 10px; }
        .tn-bar { top: 60px; }
        .tn-empty-row td { display: block; }
    }
    @media (prefers-reduced-motion: reduce) { .tn-row, .tn-empty i, .tn-pill.active .dot { animation: none; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('tnForm');
    const results = document.getElementById('tnResults');
    const stats = document.getElementById('tnStats');
    const progress = document.getElementById('tnProgress');
    const search = document.getElementById('tnSearch');
    const box = document.getElementById('tnSearchBox');
    let timer = null, controller = null;

    /* Credentials: reveal + copy */
    const pwdEl = document.getElementById('tenantGeneratedPassword');
    const pwdVal = document.getElementById('tnPwdValue');
    if (pwdEl && pwdVal) {
        document.getElementById('tnReveal').addEventListener('click', function () {
            const hidden = pwdEl.dataset.hidden === '1';
            pwdEl.textContent = hidden ? pwdVal.value : '••••••••••';
            pwdEl.dataset.hidden = hidden ? '0' : '1';
            this.querySelector('i').className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
        document.getElementById('tnCopy').addEventListener('click', function () {
            const label = this.querySelector('span');
            const done = function () { label.textContent = 'Copied!'; setTimeout(function () { label.textContent = 'Copy password'; }, 1800); };
            if (navigator.clipboard) { navigator.clipboard.writeText(pwdVal.value).then(done); }
        });
    }

    /* Live search / filter */
    function buildUrl() {
        const p = new URLSearchParams();
        new FormData(form).forEach(function (v, k) { if (v !== '') p.append(k, v); });
        const qs = p.toString();
        return form.action + (qs ? '?' + qs : '');
    }
    function load(url, scroll, noPush) {
        if (controller) controller.abort();
        controller = new AbortController();
        results.classList.add('is-loading');
        progress.className = 'tn-progress on';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
            .then(function (r) { if (!r.ok) throw new Error(); return r.text(); })
            .then(function (html) {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const fresh = doc.getElementById('tnResults');
                if (!fresh) { window.location.href = url; return; }
                results.innerHTML = fresh.innerHTML;
                const freshStats = doc.getElementById('tnStats');
                if (freshStats) stats.innerHTML = freshStats.innerHTML;
                if (!noPush) history.pushState({}, '', url);
                if (scroll) window.scrollTo({ top: results.getBoundingClientRect().top + window.scrollY - 140, behavior: 'smooth' });
            })
            .catch(function (e) { if (e.name !== 'AbortError') window.location.href = url; })
            .finally(function () {
                results.classList.remove('is-loading');
                progress.className = 'tn-progress done';
                setTimeout(function () { progress.className = 'tn-progress'; }, 350);
            });
    }
    function live(delay) { clearTimeout(timer); timer = setTimeout(function () { load(buildUrl()); }, delay); }

    search.addEventListener('input', function () { box.classList.toggle('has-value', !!search.value); live(400); });
    document.getElementById('tnClear').addEventListener('click', function () {
        search.value = ''; box.classList.remove('has-value'); search.focus(); live(0);
    });
    form.addEventListener('submit', function (e) { e.preventDefault(); live(0); });
    form.querySelectorAll('input[name="status"]').forEach(function (r) { r.addEventListener('change', function () { live(0); }); });

    results.addEventListener('click', function (e) {
        const chip = e.target.closest('[data-chip]');
        if (chip) {
            e.preventDefault();
            const k = chip.dataset.chip;
            if (k === 'all' || k === 'search') { search.value = ''; box.classList.remove('has-value'); }
            if (k === 'all' || k === 'status') document.getElementById('tn-st-all').checked = true;
            live(0);
            return;
        }
        const pg = e.target.closest('.pagination a.page-link');
        if (pg && pg.href) { e.preventDefault(); load(pg.href, true); return; }

        /* Whole row opens the tenant; links, buttons and forms keep their own behaviour */
        const row = e.target.closest('.tn-row');
        if (row && !e.target.closest('a, button, form') && row.dataset.href) {
            if (e.metaKey || e.ctrlKey) window.open(row.dataset.href, '_blank');
            else window.location.href = row.dataset.href;
        }
    });

    window.addEventListener('popstate', function () { load(window.location.href, false, true); });
    document.addEventListener('keydown', function (e) {
        const t = (document.activeElement || {}).tagName || '';
        if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(t)) { e.preventDefault(); search.focus(); }
    });
});
</script>
@endsection
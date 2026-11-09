@extends('layouts.app')

@section('title', 'My Rent Invoices')
@section('page_title', 'My Rent Invoices')
@section('page_subtitle', 'View your rent bills, due dates and outstanding balances.')

@section('content')
@php
    $money = fn ($amount, $currency = 'RWF') =>
        ($currency ?: 'RWF') . ' ' . number_format((float) $amount, 0);

    $statusFor = function ($invoice) {
        if ((float) $invoice->balance_due <= 0) { return ['Paid', 'paid']; }
        if ($invoice->is_overdue) { return ['Overdue', 'overdue']; }
        if ((float) $invoice->amount_paid > 0) { return ['Partially Paid', 'partial']; }
        return ['Unpaid', 'unpaid'];
    };

    $statusOptions = ['unpaid' => 'Unpaid', 'partial' => 'Partially paid', 'paid' => 'Paid', 'overdue' => 'Overdue'];
    $currentStatus = (string) request('status', '');

    $chips = [];
    if (request()->filled('search')) { $chips['search'] = '“' . request('search') . '”'; }
    if (request()->filled('status')) { $chips['status'] = $statusOptions[request('status')] ?? ucfirst(request('status')); }
@endphp

<div class="iv-wrap">
    <div class="iv-progress" id="ivProgress"></div>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h2 class="h5 fw-bold mb-1" style="color:var(--iv-navy)">Invoice overview</h2>
            <p class="text-muted mb-0 small">Keep track of your rental charges and payment status.</p>
        </div>
        @if (\Illuminate\Support\Facades\Route::has('tenant-portal.payments.index'))
            <a href="{{ route('tenant-portal.payments.index') }}" class="iv-btn-line">
                <i class="bi bi-cash-stack"></i> My Payments
            </a>
        @endif
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4" id="ivStats">
        <div class="col-12 col-xl-5">
            <div class="iv-hero h-100">
                <div class="eyebrow"><i class="bi bi-wallet2 me-1"></i> Outstanding balance</div>
                <div class="amount" data-count="{{ (float) $summary['outstanding'] }}">{{ number_format($summary['outstanding'], 0) }}</div>
                <div class="sub">
                    @if($summary['outstanding'] > 0) Amount still due across your invoices @else You are all caught up — nothing is due @endif
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <span class="iv-hchip"><strong>{{ number_format($summary['paid'], 0) }}</strong> paid so far</span>
                    <span class="iv-hchip"><strong>{{ number_format($summary['total']) }}</strong> {{ \Illuminate\Support\Str::plural('invoice', $summary['total']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="row g-3 h-100">
                <div class="col-6">
                    <div class="iv-stat">
                        <div class="ico o"><i class="bi bi-receipt"></i></div>
                        <div><strong>{{ number_format($summary['total']) }}</strong><span>Total invoices</span></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="iv-stat">
                        <div class="ico g"><i class="bi bi-check2-circle"></i></div>
                        <div><strong>{{ number_format($summary['paid'], 0) }}</strong><span>Payments recorded</span></div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="iv-stat {{ $summary['overdue'] > 0 ? 'attn' : '' }}">
                        <div class="ico r"><i class="bi bi-exclamation-circle"></i></div>
                        <div class="flex-grow-1">
                            <strong>{{ number_format($summary['overdue']) }}</strong>
                            <span>Overdue · past their due date</span>
                        </div>
                        @if($summary['overdue'] > 0)
                            <button type="button" class="iv-go" data-set-status="overdue">Review <i class="bi bi-arrow-right"></i></button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search + status --}}
    <form method="GET" action="{{ route('tenant-portal.rent-invoices.index') }}" id="ivForm" class="iv-bar mb-4" role="search">
        <div class="iv-search {{ request()->filled('search') ? 'has-value' : '' }}" id="ivSearchBox">
            <i class="bi bi-search"></i>
            <input type="search" name="search" id="ivSearch" value="{{ request('search') }}"
                   placeholder="Search invoice number, unit or description" aria-label="Search invoices" autocomplete="off">
            <button type="button" class="iv-x" id="ivClear" aria-label="Clear search"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="iv-tabs" role="radiogroup" aria-label="Invoice status">
            <input type="radio" name="status" id="iv-st-all" value="" @checked($currentStatus === '')>
            <label for="iv-st-all">All</label>
            @foreach ($statusOptions as $val => $lbl)
                <input type="radio" name="status" id="iv-st-{{ $val }}" value="{{ $val }}" @checked($currentStatus === $val)>
                <label for="iv-st-{{ $val }}"><span class="sd {{ $val }}"></span>{{ $lbl }}</label>
            @endforeach
        </div>
    </form>

    {{-- Results (swapped live) --}}
    <section id="ivResults" aria-live="polite">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <div class="text-muted small me-2">
                <strong>{{ number_format($invoices->total()) }}</strong> {{ \Illuminate\Support\Str::plural('invoice', $invoices->total()) }}
            </div>
            @foreach($chips as $key => $label)
                <a class="iv-chip" data-chip="{{ $key }}" href="{{ route('tenant-portal.rent-invoices.index', request()->except($key, 'page')) }}">
                    {{ $label }} <i class="bi bi-x-lg" style="font-size:.62rem"></i>
                </a>
            @endforeach
            @if(count($chips) > 1)
                <a href="{{ route('tenant-portal.rent-invoices.index') }}" class="small fw-bold" data-chip="all">Clear all</a>
            @endif
        </div>

        <div class="iv-panel">
            @if ($invoices->count())
                <div class="table-responsive">
                    <table class="table iv-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Billing period</th>
                                <th>Due date</th>
                                <th class="text-end">Total</th>
                                <th>Balance due</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                @php
                                    [$statusLabel, $statusKey] = $statusFor($invoice);
                                    $showUrl = route('tenant-portal.rent-invoices.show', $invoice->id);
                                    $total = (float) $invoice->total_amount;
                                    $balance = (float) $invoice->balance_due;
                                    $paidPct = $total > 0 ? max(0, min(100, round((($total - $balance) / $total) * 100))) : 0;
                                    $due = $invoice->due_date;
                                    $daysDiff = $due ? (int) now()->startOfDay()->diffInDays($due->copy()->startOfDay(), false) : null;
                                @endphp
                                <tr class="iv-row st-{{ $statusKey }}" data-href="{{ $showUrl }}" style="animation-delay: {{ min($loop->index, 10) * 40 }}ms">
                                    <td class="iv-first">
                                        <a href="{{ $showUrl }}" class="iv-num">{{ $invoice->invoice_number }}</a>
                                        <div class="iv-sub">
                                            <i class="bi bi-tag"></i>{{ \Illuminate\Support\Str::headline($invoice->invoice_type ?? 'rent') }}
                                            @if ($invoice->unit)<span class="mx-1">·</span>Unit {{ $invoice->unit->unit_number }}@endif
                                        </div>
                                    </td>
                                    <td data-label="Billing period" class="text-nowrap">
                                        <div class="iv-strong">{{ $invoice->billing_period_start?->format('d M Y') ?? '—' }}</div>
                                        <div class="iv-sub">to {{ $invoice->billing_period_end?->format('d M Y') ?? '—' }}</div>
                                    </td>
                                    <td data-label="Due date" class="text-nowrap">
                                        <div class="iv-strong">{{ $due?->format('d M Y') ?? '—' }}</div>
                                        @if ($statusKey === 'overdue')
                                            <div class="iv-sub bad"><i class="bi bi-exclamation-circle"></i>{{ $daysDiff !== null ? abs($daysDiff) . ' ' . \Illuminate\Support\Str::plural('day', abs($daysDiff)) . ' overdue' : 'Past due' }}</div>
                                        @elseif ($statusKey !== 'paid' && $daysDiff !== null)
                                            <div class="iv-sub {{ $daysDiff <= 7 ? 'warn' : '' }}"><i class="bi bi-clock"></i>{{ $daysDiff === 0 ? 'Due today' : ($daysDiff > 0 ? 'in ' . $daysDiff . ' ' . \Illuminate\Support\Str::plural('day', $daysDiff) : '') }}</div>
                                        @endif
                                    </td>
                                    <td data-label="Total" class="text-end text-nowrap">
                                        <span class="iv-strong">{{ $money($invoice->total_amount, $invoice->currency) }}</span>
                                    </td>
                                    <td data-label="Balance due">
                                        <div class="iv-bal {{ $balance > 0 ? '' : 'zero' }}">{{ $money($invoice->balance_due, $invoice->currency) }}</div>
                                        <div class="iv-meter" title="{{ $paidPct }}% paid"><i style="width: {{ $paidPct }}%"></i></div>
                                        <div class="iv-sub">{{ $paidPct }}% paid</div>
                                    </td>
                                    <td data-label="Status">
                                        <span class="iv-pill {{ $statusKey }}"><span class="sd"></span>{{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-end iv-last">
                                        <a href="{{ $showUrl }}" class="iv-go-link">View <i class="bi bi-arrow-up-right"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-3 border-top">
                    <span class="small text-muted">
                        Showing {{ $invoices->firstItem() }}–{{ $invoices->lastItem() }} of {{ $invoices->total() }} invoices
                    </span>
                    {{ $invoices->withQueryString()->links() }}
                </div>
            @else
                <div class="iv-empty">
                    <div class="iv-empty-icon mb-3"><i class="bi bi-receipt-cutoff"></i></div>
                    <h3 class="h6 fw-bold" style="color:var(--iv-navy)">No invoices found</h3>
                    <p class="text-muted small mb-3">There are no invoices matching your search or filter.</p>
                    @if (count($chips))
                        <a href="{{ route('tenant-portal.rent-invoices.index') }}" class="iv-btn-line" data-chip="all">Clear filters</a>
                    @endif
                </div>
            @endif
        </div>
    </section>
</div>

<style>
    .iv-wrap { --iv-navy: var(--terra-navy, #19265d); --iv-orange: var(--terra-orange, #D05208); --iv-line: #e4e8f2; --iv-soft: #f4f6fb; --iv-muted: #748094; }

    .iv-progress { position: fixed; left: 0; top: 0; height: 3px; width: 0; background: var(--iv-orange); z-index: 2000; opacity: 0; transition: width .4s, opacity .3s; }
    .iv-progress.on { width: 70%; opacity: 1; }
    .iv-progress.done { width: 100%; opacity: 0; }

    .iv-btn-line { display: inline-flex; align-items: center; gap: 8px; border: 1.5px solid var(--iv-line); background: #fff; color: var(--iv-navy); font-weight: 700; font-size: .85rem; border-radius: 12px; padding: 9px 16px; text-decoration: none; transition: all .2s; }
    .iv-btn-line:hover { background: var(--iv-navy); border-color: var(--iv-navy); color: #fff; }

    /* Stats */
    .iv-hero { position: relative; overflow: hidden; border-radius: 20px; padding: 24px; color: #fff; background: linear-gradient(120deg, #19265d, #2b3f94); }
    .iv-hero::before { content: ""; position: absolute; width: 300px; height: 300px; right: -80px; top: -150px; border-radius: 50%; background: radial-gradient(circle, rgba(208,82,8,.55), transparent 68%); pointer-events: none; }
    .iv-hero > * { position: relative; }
    .iv-hero .eyebrow { font-size: .72rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #ffd2b6; }
    .iv-hero .amount { font-size: clamp(1.9rem, 4.5vw, 2.8rem); font-weight: 800; letter-spacing: -.05em; line-height: 1.1; margin: 8px 0 2px; overflow-wrap: anywhere; }
    .iv-hero .amount::before { content: "RWF "; font-size: .45em; font-weight: 700; opacity: .75; letter-spacing: 0; }
    .iv-hero .sub { color: rgba(255,255,255,.75); font-size: .86rem; }
    .iv-hchip { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18); border-radius: 10px; padding: 6px 12px; font-size: .8rem; }
    .iv-stat { display: flex; align-items: center; gap: 14px; background: #fff; border: 1px solid var(--iv-line); border-radius: 16px; padding: 16px 18px; height: 100%; transition: transform .3s, box-shadow .3s; }
    .iv-stat:hover { transform: translateY(-3px); box-shadow: 0 12px 26px rgba(25,38,93,.1); }
    .iv-stat.attn { border-color: #f5c2bd; background: #fff8f7; }
    .iv-stat .ico { width: 46px; height: 46px; border-radius: 13px; display: grid; place-items: center; font-size: 1.2rem; flex-shrink: 0; }
    .ico.o { background: #fff1e8; color: var(--iv-orange); } .ico.g { background: #e8f7ee; color: #1a8a4a; } .ico.r { background: #fdecec; color: #c0392b; }
    .iv-stat strong { display: block; font-size: 1.45rem; font-weight: 800; letter-spacing: -.03em; color: var(--iv-navy); line-height: 1.1; }
    .iv-stat span { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--iv-muted); }
    .iv-go { border: 0; background: #c0392b; color: #fff; font-weight: 700; font-size: .8rem; border-radius: 10px; padding: 7px 14px; transition: all .2s; }
    .iv-go:hover { background: #a32e22; transform: translateX(2px); }

    /* Bar */
    .iv-bar { position: sticky; top: 72px; z-index: 20; background: #fff; border: 1px solid var(--iv-line); border-radius: 16px; padding: 10px; box-shadow: 0 10px 28px rgba(17,27,72,.08); }
    .iv-search { position: relative; }
    .iv-search > i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--iv-orange); pointer-events: none; }
    .iv-search input { width: 100%; border: 0; background: var(--iv-soft); border-radius: 12px; padding: 12px 40px 12px 42px; font-size: .93rem; font-weight: 500; transition: box-shadow .2s, background .2s; }
    .iv-search input:focus { outline: 0; background: #fff; box-shadow: 0 0 0 3px rgba(208,82,8,.18); }
    .iv-x { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 28px; height: 28px; border: 0; border-radius: 50%; background: transparent; color: #8b93ac; display: none; }
    .iv-search.has-value .iv-x { display: block; }
    .iv-x:hover { background: #ffeadc; color: var(--iv-orange); }
    .iv-tabs { display: flex; gap: 6px; overflow-x: auto; padding: 12px 2px 4px; scrollbar-width: none; }
    .iv-tabs::-webkit-scrollbar { display: none; }
    .iv-tabs input { position: absolute; opacity: 0; pointer-events: none; }
    .iv-tabs label { display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; border: 1.5px solid var(--iv-line); border-radius: 999px; padding: 6px 14px; font-size: .82rem; font-weight: 600; cursor: pointer; user-select: none; background: #fff; transition: all .2s; }
    .iv-tabs label:hover { border-color: var(--iv-orange); color: var(--iv-orange); }
    .iv-tabs input:checked + label { background: var(--iv-navy); border-color: var(--iv-navy); color: #fff; }
    .iv-tabs input:focus-visible + label { outline: 3px solid rgba(208,82,8,.35); outline-offset: 2px; }

    .sd { width: 8px; height: 8px; border-radius: 50%; background: #9aa1b8; display: inline-block; flex-shrink: 0; }
    .sd.paid { background: #1a8a4a; } .sd.partial { background: #e0a100; } .sd.overdue { background: #c0392b; } .sd.unpaid { background: #9aa1b8; }

    /* Results */
    #ivResults { transition: opacity .2s; min-height: 200px; }
    #ivResults.is-loading { opacity: .4; pointer-events: none; }
    .iv-chip { display: inline-flex; align-items: center; gap: 8px; background: #fff1e8; border: 1px solid #ffd9c2; color: #a8410a; border-radius: 999px; padding: 4px 10px 4px 13px; font-size: .78rem; font-weight: 600; text-decoration: none; transition: all .2s; }
    .iv-chip:hover { background: var(--iv-orange); border-color: var(--iv-orange); color: #fff; }

    .iv-panel { background: #fff; border: 1px solid var(--iv-line); border-radius: 18px; overflow: hidden; box-shadow: 0 4px 16px rgba(17,27,72,.05); }
    .iv-table { --bs-table-bg: transparent; }
    .iv-table thead th { background: var(--iv-soft); color: #66708f; font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; padding: 14px 18px; border-bottom: 1px solid var(--iv-line); white-space: nowrap; }
    .iv-table tbody td { padding: 15px 18px; vertical-align: middle; border-bottom: 1px solid #eef0f7; font-size: .88rem; }
    .iv-table tbody tr:last-child td { border-bottom: 0; }
    .iv-row { cursor: pointer; animation: ivIn .4s both; transition: background .2s; }
    .iv-row:hover { background: #fffaf6; }
    .iv-row td:first-child { box-shadow: inset 4px 0 0 0 transparent; transition: box-shadow .25s; }
    .iv-row.st-overdue td:first-child { box-shadow: inset 4px 0 0 0 #c0392b; }
    .iv-row.st-partial td:first-child { box-shadow: inset 4px 0 0 0 #e0a100; }
    .iv-row:hover td:first-child { box-shadow: inset 4px 0 0 0 var(--iv-orange); }
    @keyframes ivIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

    .iv-num { font-weight: 800; color: var(--iv-navy); text-decoration: none; }
    .iv-num:hover { color: var(--iv-orange); }
    .iv-strong { font-weight: 600; color: var(--iv-navy); }
    .iv-sub { font-size: .76rem; color: var(--iv-muted); margin-top: 2px; display: flex; align-items: center; gap: 5px; }
    .iv-sub i { color: #a3abc2; }
    .iv-sub.bad { color: #c0392b; font-weight: 700; } .iv-sub.bad i { color: #c0392b; }
    .iv-sub.warn { color: #b97500; font-weight: 700; } .iv-sub.warn i { color: #b97500; }
    .iv-bal { font-weight: 800; color: var(--iv-navy); font-size: .98rem; white-space: nowrap; letter-spacing: -.01em; }
    .iv-bal.zero { color: #1a8a4a; }
    .iv-meter { height: 5px; border-radius: 999px; background: #edf0f6; overflow: hidden; margin-top: 6px; min-width: 110px; }
    .iv-meter i { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #1a8a4a, #4cc486); }

    .iv-pill { display: inline-flex; align-items: center; gap: 7px; border-radius: 999px; padding: 4px 12px; font-size: .78rem; font-weight: 700; white-space: nowrap; }
    .iv-pill .sd { background: currentColor; }
    .iv-pill.paid { background: #e5f7ee; color: #167347; } .iv-pill.partial { background: #fff4d7; color: #936000; }
    .iv-pill.overdue { background: #ffebe9; color: #b42318; } .iv-pill.unpaid { background: #edf0f4; color: #5d6678; }
    .iv-pill.overdue .sd { animation: ivPulse 1.4s infinite; }
    @keyframes ivPulse { 50% { opacity: .3; } }

    .iv-go-link { display: inline-flex; align-items: center; gap: 6px; font-size: .82rem; font-weight: 700; border: 1.5px solid var(--iv-line); background: #fff; color: var(--iv-navy); border-radius: 10px; padding: 6px 12px; text-decoration: none; position: relative; z-index: 2; transition: all .2s; }
    .iv-row:hover .iv-go-link { background: var(--iv-orange); border-color: var(--iv-orange); color: #fff; }

    .iv-empty { padding: 56px 20px; text-align: center; }
    .iv-empty-icon { width: 64px; height: 64px; display: inline-grid; place-items: center; border-radius: 18px; background: #fff1e8; color: var(--iv-orange); font-size: 1.8rem; animation: ivFloat 3s ease-in-out infinite; }
    @keyframes ivFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-7px); } }

    @media (max-width: 991.98px) { .iv-bar { top: 60px; } }
    @media (max-width: 767.98px) {
        .iv-table thead { display: none; }
        .iv-table, .iv-table tbody, .iv-table tr, .iv-table td { display: block; width: 100%; }
        .iv-table tbody tr { padding: 14px 16px; border-bottom: 1px solid #eef0f7; }
        .iv-table tbody td { border: 0; padding: 6px 0; display: flex; justify-content: space-between; align-items: center; gap: 12px; text-align: right; }
        .iv-table tbody td[data-label]::before { content: attr(data-label); font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--iv-muted); text-align: left; }
        .iv-table tbody td.iv-first { display: block; text-align: left; padding-bottom: 10px; }
        .iv-table tbody td.iv-last { justify-content: flex-end; padding-top: 10px; }
        .iv-sub { justify-content: flex-end; }
        .iv-table tbody td.iv-first .iv-sub { justify-content: flex-start; }
    }
    @media (prefers-reduced-motion: reduce) { .iv-row, .iv-empty-icon, .iv-pill.overdue .sd { animation: none; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('ivForm');
    const results = document.getElementById('ivResults');
    const stats = document.getElementById('ivStats');
    const progress = document.getElementById('ivProgress');
    const search = document.getElementById('ivSearch');
    const box = document.getElementById('ivSearchBox');
    let timer = null, controller = null;

    function setStatus(v) {
        const r = form.querySelector('input[name="status"][value="' + v + '"]');
        (r || document.getElementById('iv-st-all')).checked = true;
    }
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
        progress.className = 'iv-progress on';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
            .then(function (r) { if (!r.ok) throw new Error(); return r.text(); })
            .then(function (html) {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const fresh = doc.getElementById('ivResults');
                if (!fresh) { window.location.href = url; return; }
                results.innerHTML = fresh.innerHTML;
                const fs = doc.getElementById('ivStats');
                if (fs) stats.innerHTML = fs.innerHTML;
                if (!noPush) history.pushState({}, '', url);
                if (scroll) window.scrollTo({ top: results.getBoundingClientRect().top + window.scrollY - 150, behavior: 'smooth' });
            })
            .catch(function (e) { if (e.name !== 'AbortError') window.location.href = url; })
            .finally(function () {
                results.classList.remove('is-loading');
                progress.className = 'iv-progress done';
                setTimeout(function () { progress.className = 'iv-progress'; }, 350);
            });
    }
    function live(delay) { clearTimeout(timer); timer = setTimeout(function () { load(buildUrl()); }, delay); }
    function resetAll() { search.value = ''; box.classList.remove('has-value'); setStatus(''); live(0); }

    search.addEventListener('input', function () { box.classList.toggle('has-value', !!search.value); live(400); });
    document.getElementById('ivClear').addEventListener('click', function () {
        search.value = ''; box.classList.remove('has-value'); search.focus(); live(0);
    });
    form.addEventListener('submit', function (e) { e.preventDefault(); live(0); });
    form.querySelectorAll('input[name="status"]').forEach(function (r) { r.addEventListener('change', function () { live(0); }); });

    stats.addEventListener('click', function (e) {
        const b = e.target.closest('[data-set-status]');
        if (b) { setStatus(b.dataset.setStatus); live(0); }
    });

    results.addEventListener('click', function (e) {
        const chip = e.target.closest('[data-chip]');
        if (chip) {
            e.preventDefault();
            const k = chip.dataset.chip;
            if (k === 'all') { resetAll(); return; }
            if (k === 'search') { search.value = ''; box.classList.remove('has-value'); }
            else if (k === 'status') setStatus('');
            live(0);
            return;
        }
        const pg = e.target.closest('.pagination a.page-link');
        if (pg && pg.href) { e.preventDefault(); load(pg.href, true); return; }

        const row = e.target.closest('.iv-row');
        if (row && !e.target.closest('a, button') && row.dataset.href) {
            if (e.metaKey || e.ctrlKey) window.open(row.dataset.href, '_blank');
            else window.location.href = row.dataset.href;
        }
    });

    window.addEventListener('popstate', function () { load(window.location.href, false, true); });
    document.addEventListener('keydown', function (e) {
        const t = (document.activeElement || {}).tagName || '';
        if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(t)) { e.preventDefault(); search.focus(); }
    });

    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.querySelectorAll('#ivStats [data-count]').forEach(function (el) {
            const target = parseFloat(el.dataset.count) || 0;
            if (target <= 0) return;
            const start = performance.now(), dur = 1000;
            (function tick(now) {
                const p = Math.min((now - start) / dur, 1);
                el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString('en-US');
                if (p < 1) requestAnimationFrame(tick);
            })(start);
        });
    }
});
</script>
@endsection
@extends('layouts.app')

@section('title', 'My Payments')
@section('page_title', 'My Payments')
@section('page_subtitle', 'Track your rent payments, transaction references and confirmations.')

@section('content')
@php
    $money = fn ($amount, $currency = 'RWF') =>
        ($currency ?: 'RWF') . ' ' . number_format((float) $amount, 2);

    $statusDetails = function ($status) {
        return match (strtolower((string) $status)) {
            'confirmed' => ['Confirmed', 'confirmed'],
            'pending'   => ['Pending', 'pending'],
            'failed'    => ['Failed', 'failed'],
            'rejected'  => ['Rejected', 'failed'],
            'cancelled' => ['Cancelled', 'neutral'],
            default     => [ucfirst((string) ($status ?: 'Unknown')), 'neutral'],
        };
    };

    $methodLabels = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'mobile_money' => 'Mobile Money',
        'card' => 'Card',
        'payment_gateway' => 'Payment Gateway',
        'other' => 'Other',
    ];
    $methodIcons = [
        'cash' => 'bi-cash-stack',
        'bank_transfer' => 'bi-bank',
        'mobile_money' => 'bi-phone',
        'card' => 'bi-credit-card',
        'payment_gateway' => 'bi-globe2',
        'other' => 'bi-wallet2',
    ];
    $statusOptions = ['confirmed', 'pending', 'failed', 'rejected', 'cancelled'];
    $currentStatus = (string) request('status', '');

    $chips = [];
    if (request()->filled('search')) { $chips['search'] = '“' . request('search') . '”'; }
    if (request()->filled('status')) { $chips['status'] = ucfirst(request('status')); }
    if (request()->filled('method')) { $chips['method'] = $methodLabels[request('method')] ?? \Illuminate\Support\Str::headline(request('method')); }
@endphp

<div class="py-wrap">
    <div class="py-progress" id="pyProgress"></div>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h2 class="h5 fw-bold mb-1" style="color:var(--py-navy)">Payment overview</h2>
            <p class="text-muted small mb-0">Review recorded payments and check whether they have been confirmed.</p>
        </div>
        @if (\Illuminate\Support\Facades\Route::has('tenant-portal.rent-invoices.index'))
            <a href="{{ route('tenant-portal.rent-invoices.index') }}" class="py-btn-line">
                <i class="bi bi-receipt"></i> My Invoices
            </a>
        @endif
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4" id="pyStats">
        <div class="col-12 col-xl-5">
            <div class="py-hero h-100">
                <div class="eyebrow"><i class="bi bi-check-circle-fill me-1"></i> Confirmed amount</div>
                <div class="amount" data-count="{{ (float) $summary['confirmed_amount'] }}">{{ number_format($summary['confirmed_amount'], 0) }}</div>
                <div class="sub">Total of all confirmed payments</div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <span class="py-hchip"><strong>{{ number_format($summary['confirmed_count']) }}</strong> confirmed</span>
                    <span class="py-hchip"><strong>{{ number_format($summary['total']) }}</strong> total</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="row g-3 h-100">
                <div class="col-6">
                    <div class="py-stat">
                        <div class="ico o"><i class="bi bi-wallet2"></i></div>
                        <div><strong>{{ number_format($summary['total']) }}</strong><span>Total payments</span></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="py-stat">
                        <div class="ico g"><i class="bi bi-shield-check"></i></div>
                        <div><strong>{{ number_format($summary['confirmed_count']) }}</strong><span>Confirmed</span></div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="py-stat {{ $summary['pending_count'] > 0 ? 'attn' : '' }}">
                        <div class="ico a"><i class="bi bi-hourglass-split"></i></div>
                        <div class="flex-grow-1">
                            <strong>{{ number_format($summary['pending_count']) }}</strong>
                            <span>Pending · awaiting confirmation</span>
                        </div>
                        @if($summary['pending_count'] > 0)
                            <button type="button" class="py-go" data-set-status="pending">Review <i class="bi bi-arrow-right"></i></button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search + filters --}}
    <form method="GET" action="{{ route('tenant-portal.payments.index') }}" id="pyForm" class="py-bar mb-4" role="search">
        <div class="py-bar-row">
            <div class="py-search {{ request()->filled('search') ? 'has-value' : '' }}" id="pySearchBox">
                <i class="bi bi-search"></i>
                <input type="search" name="search" id="pySearch" value="{{ request('search') }}"
                       placeholder="Search payment number or reference" aria-label="Search payments" autocomplete="off">
                <button type="button" class="py-x" id="pyClear" aria-label="Clear search"><i class="bi bi-x-lg"></i></button>
            </div>

            <select name="method" id="pyMethod" class="py-select" aria-label="Payment method">
                <option value="">All methods</option>
                @foreach ($methodLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('method') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="py-tabs" role="radiogroup" aria-label="Payment status">
            <input type="radio" name="status" id="py-st-all" value="" @checked($currentStatus === '')>
            <label for="py-st-all">All</label>
            @foreach ($statusOptions as $s)
                <input type="radio" name="status" id="py-st-{{ $s }}" value="{{ $s }}" @checked($currentStatus === $s)>
                <label for="py-st-{{ $s }}"><span class="sd {{ $statusDetails($s)[1] }}"></span>{{ ucfirst($s) }}</label>
            @endforeach
        </div>
    </form>

    {{-- Results (swapped live) --}}
    <section id="pyResults" aria-live="polite">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <div class="text-muted small me-2">
                <strong>{{ number_format($payments->total()) }}</strong> {{ \Illuminate\Support\Str::plural('payment', $payments->total()) }}
            </div>
            @foreach($chips as $key => $label)
                <a class="py-chip" data-chip="{{ $key }}" href="{{ route('tenant-portal.payments.index', request()->except($key, 'page')) }}">
                    {{ $label }} <i class="bi bi-x-lg" style="font-size:.62rem"></i>
                </a>
            @endforeach
            @if(count($chips) > 1)
                <a href="{{ route('tenant-portal.payments.index') }}" class="small fw-bold" data-chip="all">Clear all</a>
            @endif
        </div>

        <div class="py-panel">
            @if ($payments->count())
                <div class="table-responsive">
                    <table class="table py-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Transaction reference</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $payment)
                                @php
                                    [$statusLabel, $statusKey] = $statusDetails($payment->status);
                                    $showUrl = route('tenant-portal.payments.show', $payment->id);
                                    $mIcon = $methodIcons[$payment->payment_method] ?? 'bi-wallet2';
                                @endphp
                                <tr class="py-row st-{{ $statusKey }}" data-href="{{ $showUrl }}" style="animation-delay: {{ min($loop->index, 10) * 40 }}ms">
                                    <td class="py-first">
                                        <a href="{{ $showUrl }}" class="py-num">{{ $payment->payment_number }}</a>
                                        <div class="py-sub">
                                            <i class="bi bi-door-open"></i>
                                            {{ $payment->unit ? 'Unit ' . $payment->unit->unit_number : 'Rental payment' }}
                                        </div>
                                    </td>
                                    <td data-label="Date" class="text-nowrap">
                                        <div class="py-strong">{{ $payment->payment_date?->format('d M Y') ?? '—' }}</div>
                                        @if($payment->payment_date)<div class="py-sub">{{ $payment->payment_date->diffForHumans() }}</div>@endif
                                    </td>
                                    <td data-label="Method">
                                        <span class="py-method"><i class="bi {{ $mIcon }}"></i>{{ $methodLabels[$payment->payment_method] ?? \Illuminate\Support\Str::headline($payment->payment_method ?? 'Other') }}</span>
                                        @if ($payment->provider)<div class="py-sub">{{ $payment->provider }}</div>@endif
                                    </td>
                                    <td data-label="Reference">
                                        @if ($payment->transaction_reference)
                                            <span class="py-ref">
                                                <span class="text-break">{{ $payment->transaction_reference }}</span>
                                                <button type="button" class="py-copy" data-copy="{{ $payment->transaction_reference }}" aria-label="Copy reference" title="Copy reference"><i class="bi bi-clipboard"></i></button>
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td data-label="Amount" class="text-end text-nowrap">
                                        <span class="py-amt">{{ $money($payment->amount, $payment->currency) }}</span>
                                    </td>
                                    <td data-label="Status">
                                        <span class="py-pill {{ $statusKey }}"><span class="sd"></span>{{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-end py-last">
                                        <a href="{{ $showUrl }}" class="py-go-link">View <i class="bi bi-arrow-up-right"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 p-3 border-top">
                    <span class="small text-muted">
                        Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ $payments->total() }} payments
                    </span>
                    {{ $payments->withQueryString()->links() }}
                </div>
            @else
                <div class="py-empty">
                    <div class="py-empty-icon mb-3"><i class="bi bi-wallet2"></i></div>
                    <h3 class="h6 fw-bold" style="color:var(--py-navy)">No payments found</h3>
                    <p class="text-muted small mb-3">Your recorded payments will appear here.</p>
                    @if (count($chips))
                        <a href="{{ route('tenant-portal.payments.index') }}" class="py-btn-line" data-chip="all">Clear filters</a>
                    @endif
                </div>
            @endif
        </div>
    </section>
</div>

<style>
    .py-wrap { --py-navy: var(--terra-navy, #19265d); --py-orange: var(--terra-orange, #D05208); --py-line: #e4e8f2; --py-soft: #f4f6fb; --py-muted: #748094; }

    .py-progress { position: fixed; left: 0; top: 0; height: 3px; width: 0; background: var(--py-orange); z-index: 2000; opacity: 0; transition: width .4s, opacity .3s; }
    .py-progress.on { width: 70%; opacity: 1; }
    .py-progress.done { width: 100%; opacity: 0; }

    .py-btn-line { display: inline-flex; align-items: center; gap: 8px; border: 1.5px solid var(--py-line); background: #fff; color: var(--py-navy); font-weight: 700; font-size: .85rem; border-radius: 12px; padding: 9px 16px; text-decoration: none; transition: all .2s; }
    .py-btn-line:hover { background: var(--py-navy); border-color: var(--py-navy); color: #fff; }

    /* Stats */
    .py-hero { position: relative; overflow: hidden; border-radius: 20px; padding: 24px; color: #fff; background: linear-gradient(120deg, #19265d, #2b3f94); }
    .py-hero::before { content: ""; position: absolute; width: 300px; height: 300px; right: -80px; top: -150px; border-radius: 50%; background: radial-gradient(circle, rgba(208,82,8,.55), transparent 68%); pointer-events: none; }
    .py-hero > * { position: relative; }
    .py-hero .eyebrow { font-size: .72rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #ffd2b6; }
    .py-hero .amount { font-size: clamp(1.9rem, 4.5vw, 2.8rem); font-weight: 800; letter-spacing: -.05em; line-height: 1.1; margin: 8px 0 2px; overflow-wrap: anywhere; }
    .py-hero .amount::before { content: "RWF "; font-size: .45em; font-weight: 700; opacity: .75; letter-spacing: 0; }
    .py-hero .sub { color: rgba(255,255,255,.75); font-size: .86rem; }
    .py-hchip { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18); border-radius: 10px; padding: 6px 12px; font-size: .8rem; }
    .py-stat { display: flex; align-items: center; gap: 14px; background: #fff; border: 1px solid var(--py-line); border-radius: 16px; padding: 16px 18px; height: 100%; transition: transform .3s, box-shadow .3s, border-color .3s; }
    .py-stat:hover { transform: translateY(-3px); box-shadow: 0 12px 26px rgba(25,38,93,.1); }
    .py-stat.attn { border-color: #f3d58a; background: #fffbf0; }
    .py-stat .ico { width: 46px; height: 46px; border-radius: 13px; display: grid; place-items: center; font-size: 1.2rem; flex-shrink: 0; }
    .ico.o { background: #fff1e8; color: var(--py-orange); } .ico.g { background: #e8f7ee; color: #1a8a4a; } .ico.a { background: #fff3da; color: #c98500; }
    .py-stat strong { display: block; font-size: 1.45rem; font-weight: 800; letter-spacing: -.03em; color: var(--py-navy); line-height: 1.1; }
    .py-stat span { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--py-muted); }
    .py-go { border: 0; background: var(--py-orange); color: #fff; font-weight: 700; font-size: .8rem; border-radius: 10px; padding: 7px 14px; transition: all .2s; }
    .py-go:hover { background: #b94707; transform: translateX(2px); }

    /* Bar */
    .py-bar { position: sticky; top: 72px; z-index: 20; background: #fff; border: 1px solid var(--py-line); border-radius: 16px; padding: 10px; box-shadow: 0 10px 28px rgba(17,27,72,.08); }
    .py-bar-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .py-search { position: relative; flex: 1 1 260px; min-width: 0; }
    .py-search > i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--py-orange); pointer-events: none; }
    .py-search input { width: 100%; border: 0; background: var(--py-soft); border-radius: 12px; padding: 12px 40px 12px 42px; font-size: .93rem; font-weight: 500; transition: box-shadow .2s, background .2s; }
    .py-search input:focus { outline: 0; background: #fff; box-shadow: 0 0 0 3px rgba(208,82,8,.18); }
    .py-x { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 28px; height: 28px; border: 0; border-radius: 50%; background: transparent; color: #8b93ac; display: none; }
    .py-search.has-value .py-x { display: block; }
    .py-x:hover { background: #ffeadc; color: var(--py-orange); }
    .py-select { border: 1.5px solid var(--py-line); background: #fff; color: var(--py-navy); font-weight: 600; font-size: .88rem; border-radius: 12px; padding: 11px 14px; min-height: 46px; }
    .py-select:focus { outline: 0; border-color: var(--py-orange); }
    .py-tabs { display: flex; gap: 6px; overflow-x: auto; padding: 12px 2px 4px; scrollbar-width: none; }
    .py-tabs::-webkit-scrollbar { display: none; }
    .py-tabs input { position: absolute; opacity: 0; pointer-events: none; }
    .py-tabs label { display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; border: 1.5px solid var(--py-line); border-radius: 999px; padding: 6px 14px; font-size: .82rem; font-weight: 600; cursor: pointer; user-select: none; background: #fff; transition: all .2s; }
    .py-tabs label:hover { border-color: var(--py-orange); color: var(--py-orange); }
    .py-tabs input:checked + label { background: var(--py-navy); border-color: var(--py-navy); color: #fff; }
    .py-tabs input:focus-visible + label { outline: 3px solid rgba(208,82,8,.35); outline-offset: 2px; }

    .sd { width: 8px; height: 8px; border-radius: 50%; background: #9aa1b8; display: inline-block; flex-shrink: 0; }
    .sd.confirmed { background: #1a8a4a; } .sd.pending { background: #e0a100; } .sd.failed { background: #c0392b; } .sd.neutral { background: #9aa1b8; }

    /* Results */
    #pyResults { transition: opacity .2s; min-height: 200px; }
    #pyResults.is-loading { opacity: .4; pointer-events: none; }
    .py-chip { display: inline-flex; align-items: center; gap: 8px; background: #fff1e8; border: 1px solid #ffd9c2; color: #a8410a; border-radius: 999px; padding: 4px 10px 4px 13px; font-size: .78rem; font-weight: 600; text-decoration: none; transition: all .2s; }
    .py-chip:hover { background: var(--py-orange); border-color: var(--py-orange); color: #fff; }

    .py-panel { background: #fff; border: 1px solid var(--py-line); border-radius: 18px; overflow: hidden; box-shadow: 0 4px 16px rgba(17,27,72,.05); }
    .py-table { --bs-table-bg: transparent; }
    .py-table thead th { background: var(--py-soft); color: #66708f; font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; padding: 14px 18px; border-bottom: 1px solid var(--py-line); white-space: nowrap; }
    .py-table tbody td { padding: 15px 18px; vertical-align: middle; border-bottom: 1px solid #eef0f7; font-size: .88rem; }
    .py-table tbody tr:last-child td { border-bottom: 0; }
    .py-row { cursor: pointer; animation: pyIn .4s both; transition: background .2s; }
    .py-row:hover { background: #fffaf6; }
    .py-row td:first-child { box-shadow: inset 4px 0 0 0 transparent; transition: box-shadow .25s; }
    .py-row.st-pending td:first-child { box-shadow: inset 4px 0 0 0 #e0a100; }
    .py-row.st-failed td:first-child { box-shadow: inset 4px 0 0 0 #c0392b; }
    .py-row:hover td:first-child { box-shadow: inset 4px 0 0 0 var(--py-orange); }
    @keyframes pyIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

    .py-num { font-weight: 800; color: var(--py-navy); text-decoration: none; }
    .py-num:hover { color: var(--py-orange); }
    .py-strong { font-weight: 600; color: var(--py-navy); }
    .py-sub { font-size: .76rem; color: var(--py-muted); margin-top: 2px; }
    .py-sub i { margin-right: 5px; color: #a3abc2; }
    .py-method { display: inline-flex; align-items: center; gap: 8px; font-weight: 600; color: var(--py-navy); }
    .py-method i { width: 28px; height: 28px; border-radius: 8px; background: #fff1e8; color: var(--py-orange); display: grid; place-items: center; font-size: .85rem; }
    .py-ref { display: inline-flex; align-items: center; gap: 6px; background: var(--py-soft); border-radius: 8px; padding: 3px 4px 3px 10px; font-size: .8rem; font-weight: 600; color: var(--py-navy); max-width: 240px; }
    .py-copy { border: 0; background: transparent; color: var(--py-muted); width: 26px; height: 26px; border-radius: 6px; flex-shrink: 0; transition: all .2s; position: relative; z-index: 2; }
    .py-copy:hover { background: #ffe3d0; color: var(--py-orange); }
    .py-amt { font-weight: 800; font-size: .98rem; color: var(--py-navy); letter-spacing: -.01em; }

    .py-pill { display: inline-flex; align-items: center; gap: 7px; border-radius: 999px; padding: 4px 12px; font-size: .78rem; font-weight: 700; white-space: nowrap; }
    .py-pill .sd { background: currentColor; }
    .py-pill.confirmed { background: #e5f7ee; color: #167347; } .py-pill.pending { background: #fff4d7; color: #936000; }
    .py-pill.failed { background: #ffebe9; color: #b42318; } .py-pill.neutral { background: #edf0f4; color: #5d6678; }
    .py-pill.pending .sd { animation: pyPulse 1.6s infinite; }
    @keyframes pyPulse { 50% { opacity: .3; } }

    .py-go-link { display: inline-flex; align-items: center; gap: 6px; font-size: .82rem; font-weight: 700; border: 1.5px solid var(--py-line); background: #fff; color: var(--py-navy); border-radius: 10px; padding: 6px 12px; text-decoration: none; position: relative; z-index: 2; transition: all .2s; }
    .py-row:hover .py-go-link { background: var(--py-orange); border-color: var(--py-orange); color: #fff; }

    .py-empty { padding: 56px 20px; text-align: center; }
    .py-empty-icon { width: 64px; height: 64px; display: inline-grid; place-items: center; border-radius: 18px; background: #fff1e8; color: var(--py-orange); font-size: 1.8rem; animation: pyFloat 3s ease-in-out infinite; }
    @keyframes pyFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-7px); } }

    @media (max-width: 991.98px) { .py-bar { top: 60px; } }
    @media (max-width: 767.98px) {
        .py-table thead { display: none; }
        .py-table, .py-table tbody, .py-table tr, .py-table td { display: block; width: 100%; }
        .py-table tbody tr { padding: 14px 16px; border-bottom: 1px solid #eef0f7; }
        .py-table tbody td { border: 0; padding: 6px 0; display: flex; justify-content: space-between; align-items: center; gap: 12px; text-align: right; }
        .py-table tbody td[data-label]::before { content: attr(data-label); font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--py-muted); text-align: left; }
        .py-table tbody td.py-first { display: block; text-align: left; padding-bottom: 10px; }
        .py-table tbody td.py-last { justify-content: flex-end; padding-top: 10px; }
    }
    @media (prefers-reduced-motion: reduce) { .py-row, .py-empty-icon, .py-pill.pending .sd { animation: none; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('pyForm');
    const results = document.getElementById('pyResults');
    const stats = document.getElementById('pyStats');
    const progress = document.getElementById('pyProgress');
    const search = document.getElementById('pySearch');
    const box = document.getElementById('pySearchBox');
    const method = document.getElementById('pyMethod');
    let timer = null, controller = null;

    function setStatus(v) {
        const r = form.querySelector('input[name="status"][value="' + v + '"]');
        (r || document.getElementById('py-st-all')).checked = true;
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
        progress.className = 'py-progress on';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
            .then(function (r) { if (!r.ok) throw new Error(); return r.text(); })
            .then(function (html) {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const fresh = doc.getElementById('pyResults');
                if (!fresh) { window.location.href = url; return; }
                results.innerHTML = fresh.innerHTML;
                const fs = doc.getElementById('pyStats');
                if (fs) stats.innerHTML = fs.innerHTML;
                if (!noPush) history.pushState({}, '', url);
                if (scroll) window.scrollTo({ top: results.getBoundingClientRect().top + window.scrollY - 150, behavior: 'smooth' });
            })
            .catch(function (e) { if (e.name !== 'AbortError') window.location.href = url; })
            .finally(function () {
                results.classList.remove('is-loading');
                progress.className = 'py-progress done';
                setTimeout(function () { progress.className = 'py-progress'; }, 350);
            });
    }
    function live(delay) { clearTimeout(timer); timer = setTimeout(function () { load(buildUrl()); }, delay); }
    function resetAll() {
        search.value = ''; box.classList.remove('has-value'); method.value = ''; setStatus(''); live(0);
    }

    search.addEventListener('input', function () { box.classList.toggle('has-value', !!search.value); live(400); });
    document.getElementById('pyClear').addEventListener('click', function () {
        search.value = ''; box.classList.remove('has-value'); search.focus(); live(0);
    });
    form.addEventListener('submit', function (e) { e.preventDefault(); live(0); });
    method.addEventListener('change', function () { live(0); });
    form.querySelectorAll('input[name="status"]').forEach(function (r) { r.addEventListener('change', function () { live(0); }); });

    stats.addEventListener('click', function (e) {
        const b = e.target.closest('[data-set-status]');
        if (b) { setStatus(b.dataset.setStatus); live(0); }
    });

    results.addEventListener('click', function (e) {
        const copyBtn = e.target.closest('.py-copy');
        if (copyBtn) {
            e.preventDefault();
            if (navigator.clipboard) {
                navigator.clipboard.writeText(copyBtn.dataset.copy).then(function () {
                    const i = copyBtn.querySelector('i');
                    i.className = 'bi bi-check2';
                    setTimeout(function () { i.className = 'bi bi-clipboard'; }, 1500);
                });
            }
            return;
        }
        const chip = e.target.closest('[data-chip]');
        if (chip) {
            e.preventDefault();
            const k = chip.dataset.chip;
            if (k === 'all') { resetAll(); return; }
            if (k === 'search') { search.value = ''; box.classList.remove('has-value'); }
            else if (k === 'status') setStatus('');
            else if (k === 'method') method.value = '';
            live(0);
            return;
        }
        const pg = e.target.closest('.pagination a.page-link');
        if (pg && pg.href) { e.preventDefault(); load(pg.href, true); return; }

        const row = e.target.closest('.py-row');
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

    /* Count-up for confirmed amount */
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.querySelectorAll('#pyStats [data-count]').forEach(function (el) {
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
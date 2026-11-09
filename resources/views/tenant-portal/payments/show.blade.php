@extends('layouts.app')

@section('title', 'Payment Details')
@section('page_title', 'Payment Details')
@section('page_subtitle', 'Review your transaction, confirmation status and invoice allocations.')

@section('content')
@php
    $money = fn ($amount, $currency = null) =>
        ($currency ?: $rentPayment->currency ?: 'RWF')
        . ' '
        . number_format((float) $amount, 2);

    $statusKey = strtolower((string) $rentPayment->status);
    [$statusLabel, $tone, $statusIcon] = match ($statusKey) {
        'confirmed' => ['Confirmed', 'ok', 'bi-check-circle-fill'],
        'pending'   => ['Pending', 'wait', 'bi-hourglass-split'],
        'failed'    => ['Failed', 'bad', 'bi-x-circle-fill'],
        'rejected'  => ['Rejected', 'bad', 'bi-exclamation-circle-fill'],
        'cancelled' => ['Cancelled', 'off', 'bi-slash-circle'],
        default     => [ucfirst((string) ($rentPayment->status ?: 'Unknown')), 'off', 'bi-info-circle'],
    };

    $methodIcons = [
        'cash' => 'bi-cash-stack', 'bank_transfer' => 'bi-bank', 'mobile_money' => 'bi-phone',
        'card' => 'bi-credit-card', 'payment_gateway' => 'bi-globe2', 'other' => 'bi-wallet2',
    ];
    $mIcon = $methodIcons[$rentPayment->payment_method] ?? 'bi-wallet2';

    $amount = (float) $rentPayment->amount;
    $allocated = (float) $rentPayment->allocated_amount;
    $unallocated = (float) $rentPayment->unallocated_amount;
    $allocPct = ($statusKey === 'confirmed' && $amount > 0) ? max(0, min(100, round(($allocated / $amount) * 100))) : 0;

    $message = match (true) {
        $statusKey === 'confirmed' => 'This payment is confirmed in the rental management system.',
        $statusKey === 'pending'   => 'This payment is awaiting confirmation. Contact your property manager if you need assistance.',
        default => 'The current status is ' . strtolower($statusLabel) . '. Contact your property manager for clarification if necessary.',
    };

    // Progress steps
    $failed = in_array($statusKey, ['failed', 'rejected', 'cancelled'], true);
    $steps = [
        ['Recorded', 'done', 'bi-pencil-square', $rentPayment->payment_date?->format('d M Y')],
    ];
    if ($failed) {
        $steps[] = [$statusLabel, 'bad', $statusIcon, null];
    } elseif ($statusKey === 'confirmed') {
        $steps[] = ['Confirmed', 'done', 'bi-shield-check', null];
        $steps[] = [$allocated > 0 ? 'Allocated' : 'Awaiting allocation', $allocated > 0 ? 'done' : 'current', 'bi-receipt', null];
    } else {
        $steps[] = ['Awaiting confirmation', 'current', 'bi-hourglass-split', null];
        $steps[] = ['Allocated to invoices', 'todo', 'bi-receipt', null];
    }
@endphp

<div class="pd-wrap">

    {{-- Top bar --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 pd-noprint">
        <a href="{{ route('tenant-portal.payments.index') }}" class="pd-btn-line">
            <i class="bi bi-arrow-left"></i> Back to payments
        </a>
        <div class="d-flex flex-wrap gap-2">
            @if (\Illuminate\Support\Facades\Route::has('tenant-portal.rent-invoices.index'))
                <a href="{{ route('tenant-portal.rent-invoices.index') }}" class="pd-btn-line"><i class="bi bi-receipt"></i> My invoices</a>
            @endif
            <button type="button" class="pd-btn" id="pdPrint"><i class="bi bi-printer"></i> Print receipt</button>
        </div>
    </div>

    <div class="row g-4">
        {{-- Main --}}
        <div class="col-12 col-xl-8">

            {{-- Receipt --}}
            <section class="pd-card pd-receipt pd-in">
                <div class="pd-banner {{ $tone }}">
                    <div class="pd-banner-ico"><i class="bi {{ $statusIcon }}"></i></div>
                    <div class="flex-grow-1">
                        <div class="pd-banner-title">Payment {{ strtolower($statusLabel) }}</div>
                        <div class="pd-banner-text">{{ $message }}</div>
                    </div>
                </div>

                <div class="pd-top">
                    <div>
                        <div class="pd-k">Payment number</div>
                        <div class="pd-num">{{ $rentPayment->payment_number }}</div>
                    </div>
                    <div class="text-md-end">
                        <div class="pd-k">Amount</div>
                        <div class="pd-amount">{{ $money($rentPayment->amount) }}</div>
                    </div>
                </div>

                {{-- Progress --}}
                <ol class="pd-steps">
                    @foreach ($steps as $s)
                        <li class="{{ $s[1] }}">
                            <span class="dot"><i class="bi {{ $s[1] === 'done' ? 'bi-check-lg' : $s[2] }}"></i></span>
                            <span class="lbl">{{ $s[0] }}@if($s[3])<small>{{ $s[3] }}</small>@endif</span>
                        </li>
                    @endforeach
                </ol>

                {{-- Details grid --}}
                <div class="pd-grid">
                    <div class="pd-item">
                        <div class="ic"><i class="bi bi-calendar3"></i></div>
                        <div><div class="pd-k">Payment date</div><div class="pd-v">{{ $rentPayment->payment_date?->format('d M Y') ?? '—' }}</div></div>
                    </div>
                    <div class="pd-item">
                        <div class="ic"><i class="bi {{ $mIcon }}"></i></div>
                        <div><div class="pd-k">Payment method</div><div class="pd-v">{{ \Illuminate\Support\Str::headline($rentPayment->payment_method ?? 'Other') }}</div></div>
                    </div>
                    <div class="pd-item">
                        <div class="ic"><i class="bi bi-building"></i></div>
                        <div><div class="pd-k">Provider</div><div class="pd-v">{{ $rentPayment->provider ?: '—' }}</div></div>
                    </div>
                    <div class="pd-item">
                        <div class="ic"><i class="bi bi-currency-exchange"></i></div>
                        <div><div class="pd-k">Currency</div><div class="pd-v">{{ $rentPayment->currency ?: 'RWF' }}</div></div>
                    </div>
                    <div class="pd-item">
                        <div class="ic"><i class="bi bi-door-open"></i></div>
                        <div><div class="pd-k">Unit</div><div class="pd-v">{{ $rentPayment->unit?->unit_number ?? '—' }}</div></div>
                    </div>
                    <div class="pd-item">
                        <div class="ic"><i class="bi bi-file-earmark-text"></i></div>
                        <div><div class="pd-k">Lease reference</div><div class="pd-v">{{ $rentPayment->lease?->lease_number ?? '—' }}</div></div>
                    </div>
                </div>

                <div class="pd-ref">
                    <div>
                        <div class="pd-k">Transaction reference</div>
                        <div class="pd-v" id="pdRefText">{{ $rentPayment->transaction_reference ?: '—' }}</div>
                    </div>
                    @if ($rentPayment->transaction_reference)
                        <button type="button" class="pd-copy pd-noprint" id="pdCopy" data-copy="{{ $rentPayment->transaction_reference }}">
                            <i class="bi bi-clipboard"></i> <span>Copy</span>
                        </button>
                    @endif
                </div>

                @if ($rentPayment->notes)
                    <div class="pd-note">
                        <div class="pd-k"><i class="bi bi-chat-left-text me-1"></i>Payment notes</div>
                        <p class="mb-0">{{ $rentPayment->notes }}</p>
                    </div>
                @endif
            </section>

            {{-- Allocations --}}
            <section class="pd-card mt-4 pd-in" style="animation-delay:.1s">
                <div class="pd-head">
                    <div>
                        <h3>Invoice allocation history</h3>
                        <p>See how this payment has been applied to your invoices.</p>
                    </div>
                    @if($rentPayment->allocations->count())
                        <span class="pd-count">{{ $rentPayment->allocations->count() }} {{ \Illuminate\Support\Str::plural('allocation', $rentPayment->allocations->count()) }}</span>
                    @endif
                </div>

                @forelse ($rentPayment->allocations as $allocation)
                    <div class="pd-alloc">
                        <div class="pd-alloc-ico"><i class="bi bi-receipt"></i></div>
                        <div class="flex-grow-1" style="min-width:0">
                            @if ($allocation->invoice)
                                <a href="{{ route('tenant-portal.rent-invoices.show', $allocation->invoice->id) }}" class="pd-inv">{{ $allocation->invoice->invoice_number }}</a>
                                <div class="pd-sub"><i class="bi bi-calendar-event"></i>Due {{ $allocation->invoice->due_date?->format('d M Y') ?? '—' }}</div>
                            @else
                                <div class="pd-inv" style="color:var(--pd-navy)">Invoice allocation</div>
                                <div class="pd-sub">Invoice details unavailable</div>
                            @endif
                            @if ($allocation->allocated_at)
                                <div class="pd-sub"><i class="bi bi-clock"></i>Allocated {{ $allocation->allocated_at->format('d M Y, H:i') }}</div>
                            @endif
                            @if ($allocation->notes)
                                <div class="pd-sub"><i class="bi bi-chat-left-text"></i>{{ $allocation->notes }}</div>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="pd-alloc-amt">{{ $money($allocation->amount) }}</div>
                            @if ($allocation->invoice)
                                <div class="pd-sub justify-content-end">Balance: {{ $money($allocation->invoice->balance_due, $allocation->invoice->currency) }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="pd-empty">
                        <i class="bi bi-receipt"></i>
                        <p class="text-muted small mt-2 mb-0">No invoice allocations have been recorded for this payment.</p>
                    </div>
                @endforelse

                @if ($statusKey === 'confirmed')
                    <div class="pd-totals">
                        <div><span>Total allocated</span><small>Applied to invoices</small></div>
                        <strong>{{ $money($rentPayment->allocated_amount) }}</strong>
                    </div>
                    <div class="pd-totals">
                        <div><span>Unallocated balance</span><small>Confirmed funds not yet allocated</small></div>
                        <strong>{{ $money($rentPayment->unallocated_amount) }}</strong>
                    </div>
                @endif
            </section>
        </div>

        {{-- Sidebar --}}
        <div class="col-12 col-xl-4">
            <div class="pd-sticky">
                <section class="pd-hero pd-in" style="animation-delay:.05s">
                    <div class="eyebrow">Payment amount</div>
                    <div class="amt">{{ $money($rentPayment->amount) }}</div>
                    <span class="pd-hpill {{ $tone }}"><i class="bi {{ $statusIcon }}"></i>{{ $statusLabel }}</span>

                    <div class="pd-hline"></div>
                    <div class="eyebrow">Transaction reference</div>
                    <div class="ref">{{ $rentPayment->transaction_reference ?: 'No reference recorded' }}</div>
                </section>

                <section class="pd-card mt-3 pd-in" style="animation-delay:.12s">
                    <h3 class="pd-side-title">Payment summary</h3>

                    @if ($statusKey === 'confirmed')
                        <div class="pd-meter" role="progressbar" aria-valuenow="{{ $allocPct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Amount allocated">
                            <i data-w="{{ $allocPct }}"></i>
                        </div>
                        <div class="pd-meter-lbl"><span>{{ $allocPct }}% allocated</span><span>{{ 100 - $allocPct }}% remaining</span></div>
                    @endif

                    <div class="pd-row"><span>Amount received</span><strong>{{ $money($rentPayment->amount) }}</strong></div>
                    <div class="pd-row"><span>Allocated</span><strong class="text-success">{{ $money($rentPayment->allocated_amount) }}</strong></div>
                    <div class="pd-row last"><span>Unallocated</span><strong>{{ $money($rentPayment->unallocated_amount) }}</strong></div>
                </section>

                <section class="pd-help pd-noprint pd-in" style="animation-delay:.18s">
                    <i class="bi bi-headset"></i>
                    <div>
                        <strong>Something looks wrong?</strong>
                        <p>Contact your property manager and quote payment number <b>{{ $rentPayment->payment_number }}</b>.</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
    .pd-wrap { --pd-navy: var(--terra-navy, #19265d); --pd-orange: var(--terra-orange, #D05208); --pd-line: #e4e8f2; --pd-soft: #f4f6fb; --pd-muted: #748094; }
    .pd-in { animation: pdIn .5s both; }
    @keyframes pdIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }

    .pd-btn, .pd-btn-line { display: inline-flex; align-items: center; gap: 8px; font-weight: 700; font-size: .85rem; border-radius: 12px; padding: 9px 16px; text-decoration: none; transition: all .2s; cursor: pointer; }
    .pd-btn { background: var(--pd-orange); border: 1px solid var(--pd-orange); color: #fff; }
    .pd-btn:hover { background: #b94707; border-color: #b94707; color: #fff; transform: translateY(-1px); box-shadow: 0 8px 18px rgba(208,82,8,.28); }
    .pd-btn-line { background: #fff; border: 1.5px solid var(--pd-line); color: var(--pd-navy); }
    .pd-btn-line:hover { background: var(--pd-navy); border-color: var(--pd-navy); color: #fff; }

    .pd-card { background: #fff; border: 1px solid var(--pd-line); border-radius: 20px; padding: 24px; box-shadow: 0 4px 18px rgba(25,38,93,.04); }
    .pd-receipt { padding: 0; overflow: hidden; }
    .pd-k { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: var(--pd-muted); margin-bottom: 4px; }
    .pd-v { font-weight: 700; color: var(--pd-navy); font-size: .95rem; overflow-wrap: anywhere; }

    /* Banner */
    .pd-banner { display: flex; gap: 16px; align-items: center; padding: 18px 24px; }
    .pd-banner.ok { background: #e8f7ee; color: #146c3a; } .pd-banner.wait { background: #fff4d7; color: #8a5a00; }
    .pd-banner.bad { background: #ffebe9; color: #a32121; } .pd-banner.off { background: #eef0f6; color: #5a6380; }
    .pd-banner-ico { width: 46px; height: 46px; border-radius: 50%; background: rgba(255,255,255,.7); display: grid; place-items: center; font-size: 1.4rem; flex-shrink: 0; }
    .pd-banner.wait .pd-banner-ico i { animation: pdSpin 3s linear infinite; }
    @keyframes pdSpin { to { transform: rotate(360deg); } }
    .pd-banner-title { font-weight: 800; letter-spacing: -.01em; }
    .pd-banner-text { font-size: .85rem; opacity: .9; margin-top: 2px; }

    .pd-top { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 14px; padding: 24px 24px 8px; }
    .pd-num { font-size: 1.15rem; font-weight: 800; color: var(--pd-navy); letter-spacing: -.01em; }
    .pd-amount { font-size: clamp(1.6rem, 3.5vw, 2.2rem); font-weight: 800; color: var(--pd-navy); letter-spacing: -.04em; line-height: 1.1; overflow-wrap: anywhere; }

    /* Steps */
    .pd-steps { list-style: none; display: flex; margin: 22px 24px 8px; padding: 0; }
    .pd-steps li { flex: 1; position: relative; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 8px; }
    .pd-steps li::before { content: ""; position: absolute; top: 17px; left: -50%; width: 100%; height: 3px; background: #e4e8f2; z-index: 0; }
    .pd-steps li:first-child::before { display: none; }
    .pd-steps li.done::before, .pd-steps li.current::before { background: #1a8a4a; }
    .pd-steps li.bad::before { background: #c0392b; }
    .pd-steps .dot { position: relative; z-index: 1; width: 36px; height: 36px; border-radius: 50%; display: grid; place-items: center; background: #fff; border: 3px solid #e4e8f2; color: #a3abc2; font-size: .9rem; }
    .pd-steps li.done .dot { background: #1a8a4a; border-color: #1a8a4a; color: #fff; }
    .pd-steps li.current .dot { border-color: #e0a100; color: #e0a100; box-shadow: 0 0 0 5px rgba(224,161,0,.18); }
    .pd-steps li.bad .dot { background: #c0392b; border-color: #c0392b; color: #fff; }
    .pd-steps .lbl { font-size: .78rem; font-weight: 700; color: var(--pd-navy); line-height: 1.25; }
    .pd-steps .lbl small { display: block; font-weight: 500; color: var(--pd-muted); font-size: .72rem; margin-top: 2px; }
    .pd-steps li.todo .lbl { color: var(--pd-muted); }

    .pd-grid { display: grid; grid-template-columns: 1fr; gap: 12px; padding: 18px 24px 8px; }
    @media (min-width: 576px) { .pd-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 992px) { .pd-grid { grid-template-columns: repeat(3, 1fr); } }
    .pd-item { display: flex; gap: 12px; align-items: center; padding: 12px 14px; border: 1px solid var(--pd-line); border-radius: 14px; transition: border-color .2s, background .2s; }
    .pd-item:hover { border-color: rgba(208,82,8,.35); background: #fffaf6; }
    .pd-item .ic { width: 38px; height: 38px; border-radius: 11px; background: #fff1e8; color: var(--pd-orange); display: grid; place-items: center; flex-shrink: 0; }
    .pd-item .pd-k { margin: 0; }

    .pd-ref { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 12px 24px 0; padding: 14px 16px; background: var(--pd-soft); border-radius: 14px; }
    .pd-copy { border: 1.5px solid var(--pd-line); background: #fff; color: var(--pd-navy); font-weight: 700; font-size: .8rem; border-radius: 10px; padding: 6px 12px; transition: all .2s; white-space: nowrap; }
    .pd-copy:hover { background: var(--pd-orange); border-color: var(--pd-orange); color: #fff; }
    .pd-note { margin: 12px 24px 0; padding: 14px 16px; border-radius: 14px; background: #fffbf0; border: 1px solid #f3e3b0; font-size: .9rem; }
    .pd-receipt > :last-child { margin-bottom: 24px; }

    /* Allocations */
    .pd-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 14px; }
    .pd-head h3 { font-size: 1.05rem; font-weight: 800; color: var(--pd-navy); margin: 0; letter-spacing: -.02em; }
    .pd-head p { margin: 2px 0 0; font-size: .82rem; color: var(--pd-muted); }
    .pd-count { background: var(--pd-soft); color: var(--pd-navy); font-size: .76rem; font-weight: 700; border-radius: 999px; padding: 4px 12px; white-space: nowrap; }
    .pd-alloc { display: flex; gap: 14px; align-items: flex-start; padding: 14px; border: 1px solid var(--pd-line); border-radius: 14px; transition: border-color .2s, background .2s; }
    .pd-alloc + .pd-alloc { margin-top: 10px; }
    .pd-alloc:hover { border-color: rgba(208,82,8,.35); background: #fffaf6; }
    .pd-alloc-ico { width: 42px; height: 42px; border-radius: 12px; background: #e8f7ee; color: #1a8a4a; display: grid; place-items: center; flex-shrink: 0; }
    .pd-inv { font-weight: 800; color: var(--pd-navy); text-decoration: none; }
    a.pd-inv:hover { color: var(--pd-orange); }
    .pd-sub { display: flex; align-items: center; gap: 6px; font-size: .76rem; color: var(--pd-muted); margin-top: 3px; }
    .pd-sub i { color: #a3abc2; }
    .pd-alloc-amt { font-weight: 800; color: #167347; white-space: nowrap; }
    .pd-empty { text-align: center; padding: 28px 12px; border: 1.5px dashed #dce1eb; border-radius: 14px; background: #fafbfe; }
    .pd-empty i { font-size: 1.9rem; color: var(--pd-orange); }
    .pd-totals { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 0 0; margin-top: 14px; border-top: 1px solid var(--pd-line); }
    .pd-totals + .pd-totals { border-top: 0; margin-top: 0; }
    .pd-totals span { display: block; font-weight: 800; font-size: .88rem; color: var(--pd-navy); }
    .pd-totals small { color: var(--pd-muted); font-size: .76rem; }
    .pd-totals strong { color: var(--pd-navy); white-space: nowrap; }

    /* Sidebar */
    .pd-sticky { position: sticky; top: 84px; }
    .pd-hero { position: relative; overflow: hidden; border-radius: 20px; padding: 24px; color: #fff; background: linear-gradient(120deg, #19265d, #2b3f94); }
    .pd-hero::before { content: ""; position: absolute; width: 280px; height: 280px; right: -80px; top: -140px; border-radius: 50%; background: radial-gradient(circle, rgba(208,82,8,.55), transparent 68%); pointer-events: none; }
    .pd-hero > * { position: relative; }
    .pd-hero .eyebrow { font-size: .7rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #ffd2b6; }
    .pd-hero .amt { font-size: clamp(1.6rem, 3.5vw, 2.1rem); font-weight: 800; letter-spacing: -.04em; margin: 8px 0 14px; overflow-wrap: anywhere; line-height: 1.15; }
    .pd-hero .ref { font-size: .88rem; color: rgba(255,255,255,.85); overflow-wrap: anywhere; margin-top: 4px; }
    .pd-hline { height: 1px; background: rgba(255,255,255,.2); margin: 18px 0; }
    .pd-hpill { display: inline-flex; align-items: center; gap: 7px; border-radius: 999px; padding: 5px 13px; font-size: .78rem; font-weight: 800; }
    .pd-hpill.ok { background: #e5f7ee; color: #167347; } .pd-hpill.wait { background: #fff4d7; color: #936000; }
    .pd-hpill.bad { background: #ffebe9; color: #b42318; } .pd-hpill.off { background: #edf0f4; color: #5d6678; }

    .pd-side-title { font-size: 1rem; font-weight: 800; color: var(--pd-navy); margin: 0 0 14px; }
    .pd-meter { height: 9px; border-radius: 999px; background: #edf0f6; overflow: hidden; }
    .pd-meter > i { display: block; height: 100%; width: 0; border-radius: 999px; background: linear-gradient(90deg, #1a8a4a, #4cc486); transition: width 1.1s cubic-bezier(.22,1,.36,1); }
    .pd-meter-lbl { display: flex; justify-content: space-between; font-size: .72rem; font-weight: 700; color: var(--pd-muted); margin: 6px 0 14px; }
    .pd-row { display: flex; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid #f0f1f5; font-size: .86rem; }
    .pd-row.last { border-bottom: 0; padding-bottom: 0; }
    .pd-row span { color: var(--pd-muted); } .pd-row strong { color: var(--pd-navy); text-align: right; overflow-wrap: anywhere; }

    .pd-help { display: flex; gap: 12px; margin-top: 16px; padding: 16px; border: 1.5px dashed #d9deeb; border-radius: 16px; background: #fafbfe; }
    .pd-help > i { font-size: 1.4rem; color: var(--pd-orange); }
    .pd-help strong { color: var(--pd-navy); font-size: .88rem; }
    .pd-help p { margin: 2px 0 0; font-size: .8rem; color: var(--pd-muted); }

    @media (max-width: 575.98px) {
        .pd-banner, .pd-top { padding-left: 16px; padding-right: 16px; }
        .pd-steps, .pd-ref, .pd-note { margin-left: 16px; margin-right: 16px; }
        .pd-grid { padding-left: 16px; padding-right: 16px; }
        .pd-steps .lbl { font-size: .7rem; }
        .pd-alloc { flex-wrap: wrap; }
    }
    @media (max-width: 1199.98px) { .pd-sticky { position: static; } }
    @media (prefers-reduced-motion: reduce) { .pd-in, .pd-banner.wait .pd-banner-ico i { animation: none; } .pd-meter > i { transition: none; } }

    @media print {
        .pd-noprint, .pd-help { display: none !important; }
        .pd-sticky { position: static; }
        .pd-card, .pd-hero { box-shadow: none; break-inside: avoid; }
        .pd-hero { background: #fff !important; color: #000 !important; border: 1px solid #000; }
        .pd-hero .eyebrow, .pd-hero .ref { color: #000 !important; }
        .col-xl-8, .col-xl-4 { width: 100% !important; flex: 0 0 100% !important; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    requestAnimationFrame(function () {
        document.querySelectorAll('.pd-meter > i[data-w]').forEach(function (el) { el.style.width = el.dataset.w + '%'; });
    });

    var print = document.getElementById('pdPrint');
    if (print) print.addEventListener('click', function () { window.print(); });

    var copy = document.getElementById('pdCopy');
    if (copy && navigator.clipboard) {
        copy.addEventListener('click', function () {
            navigator.clipboard.writeText(copy.dataset.copy).then(function () {
                var i = copy.querySelector('i'), s = copy.querySelector('span');
                i.className = 'bi bi-check2'; s.textContent = 'Copied!';
                setTimeout(function () { i.className = 'bi bi-clipboard'; s.textContent = 'Copy'; }, 1600);
            });
        });
    }
});
</script>
@endsection
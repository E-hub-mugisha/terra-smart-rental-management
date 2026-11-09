@extends('layouts.app')

@section('title', 'Invoice Details')
@section('page_title', 'Invoice Details')
@section('page_subtitle', 'Review your invoice charges, due date and payment history.')

@section('content')
@php
$money = fn ($amount) =>
($rentInvoice->currency ?: 'RWF') . ' ' . number_format((float) $amount, 2);

$total = (float) $rentInvoice->total_amount;
$paid = (float) $rentInvoice->amount_paid;
$balance = (float) $rentInvoice->balance_due;
$paidPct = $total > 0 ? max(0, min(100, round(($paid / $total) * 100))) : 0;

if ($balance <= 0) {
    $key='paid' ; $statusLabel='Paid' ; $icon='bi-check-circle-fill' ;
    } elseif ($rentInvoice->is_overdue) {
    $key = 'overdue'; $statusLabel = 'Overdue'; $icon = 'bi-exclamation-triangle-fill';
    } elseif ($paid > 0) {
    $key = 'partial'; $statusLabel = 'Partially Paid'; $icon = 'bi-pie-chart-fill';
    } else {
    $key = 'unpaid'; $statusLabel = 'Unpaid'; $icon = 'bi-hourglass-split';
    }

    $due = $rentInvoice->due_date;
    $daysDiff = $due ? (int) now()->startOfDay()->diffInDays($due->copy()->startOfDay(), false) : null;
    $dueText = null;
    if ($daysDiff !== null && $key !== 'paid') {
    $dueText = $daysDiff < 0
        ? abs($daysDiff) . ' ' . \Illuminate\Support\Str::plural('day', abs($daysDiff)) . ' overdue'
        : ($daysDiff===0 ? 'Due today' : 'Due in ' . $daysDiff . ' ' . \Illuminate\Support\Str::plural('day', $daysDiff));
        }

        $message=match ($key) { 'paid'=> 'This invoice has been fully paid. Thank you!',
        'overdue' => 'This invoice is overdue. Please contact your property manager about payment.',
        'partial' => 'A partial payment has been received. Please settle the remaining balance by ' . ($due?->format('d M Y') ?? 'the due date') . '.',
        default => 'Please settle the remaining balance by ' . ($due?->format('d M Y') ?? 'the due date') . '.',
        };

        // Timeline
        $steps = [
        ['Issued', 'done', 'bi-file-earmark-plus', $rentInvoice->issue_date?->format('d M Y')],
        ['Payment due', $key === 'paid' ? 'done' : ($key === 'overdue' ? 'bad' : 'current'), 'bi-calendar-event', $due?->format('d M Y')],
        ['Paid in full', $key === 'paid' ? 'done' : 'todo', 'bi-patch-check', null],
        ];
        @endphp

        <div class="id-wrap">

            {{-- Top bar --}}
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 id-noprint">
                <a href="{{ route('tenant-portal.rent-invoices.index') }}" class="id-btn-line">
                    <i class="bi bi-arrow-left"></i> Back to invoices
                </a>
                <div class="d-flex flex-wrap gap-2">
                    @if (\Illuminate\Support\Facades\Route::has('tenant-portal.payments.index'))
                    <a href="{{ route('tenant-portal.payments.index') }}" class="id-btn-line"><i class="bi bi-clock-history"></i> Payment history</a>
                    @endif
                    <button type="button" class="id-btn" id="idPrint"><i class="bi bi-printer"></i> Print invoice</button>
                </div>
            </div>

            <div class="row g-4">
                {{-- Main --}}
                <div class="col-12 col-xl-8">

                    <section class="id-card id-doc id-in">
                        <div class="id-banner {{ $key }}">
                            <div class="id-banner-ico"><i class="bi {{ $icon }}"></i></div>
                            <div class="flex-grow-1">
                                <div class="id-banner-title">
                                    {{ $statusLabel }}@if($dueText) · {{ $dueText }}@endif
                                </div>
                                <div class="id-banner-text">{{ $message }}</div>
                            </div>
                        </div>

                        <div class="id-top">
                            <div>
                                <div class="id-k">Rental invoice</div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="id-num" id="idNum">{{ $rentInvoice->invoice_number }}</div>
                                    <button type="button" class="id-copy id-noprint" id="idCopy" aria-label="Copy invoice number" title="Copy invoice number"><i class="bi bi-clipboard"></i></button>
                                </div>
                                <div class="id-type"><i class="bi bi-tag"></i>{{ \Illuminate\Support\Str::headline($rentInvoice->invoice_type ?? 'rent') }}</div>
                            </div>
                            <div class="text-md-end">
                                <div class="id-k">Total amount</div>
                                <div class="id-amount">{{ $money($rentInvoice->total_amount) }}</div>
                            </div>
                        </div>

                        {{-- Timeline --}}
                        <ol class="id-steps">
                            @foreach ($steps as $s)
                            <li class="{{ $s[1] }}">
                                <span class="dot"><i class="bi {{ $s[1] === 'done' ? 'bi-check-lg' : $s[2] }}"></i></span>
                                <span class="lbl">{{ $s[0] }}@if($s[3])<small>{{ $s[3] }}</small>@endif</span>
                            </li>
                            @endforeach
                        </ol>

                        {{-- Details --}}
                        <div class="id-grid">
                            <div class="id-item">
                                <div class="ic"><i class="bi bi-calendar-check"></i></div>
                                <div>
                                    <div class="id-k">Issue date</div>
                                    <div class="id-v">{{ $rentInvoice->issue_date?->format('d M Y') ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="id-item">
                                <div class="ic"><i class="bi bi-calendar-range"></i></div>
                                <div>
                                    <div class="id-k">Billing period starts</div>
                                    <div class="id-v">{{ $rentInvoice->billing_period_start?->format('d M Y') ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="id-item">
                                <div class="ic"><i class="bi bi-calendar-range-fill"></i></div>
                                <div>
                                    <div class="id-k">Billing period ends</div>
                                    <div class="id-v">{{ $rentInvoice->billing_period_end?->format('d M Y') ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="id-item {{ $key === 'overdue' ? 'bad' : '' }}">
                                <div class="ic"><i class="bi bi-alarm"></i></div>
                                <div>
                                    <div class="id-k">Payment due date</div>
                                    <div class="id-v">{{ $due?->format('d M Y') ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="id-item">
                                <div class="ic"><i class="bi bi-door-open"></i></div>
                                <div>
                                    <div class="id-k">Unit</div>
                                    <div class="id-v">{{ $rentInvoice->unit?->unit_number ?? '—' }}</div>
                                </div>
                            </div>
                            <div class="id-item">
                                <div class="ic"><i class="bi bi-file-earmark-text"></i></div>
                                <div>
                                    <div class="id-k">Lease reference</div>
                                    <div class="id-v">{{ $rentInvoice->lease?->lease_number ?? '—' }}</div>
                                </div>
                            </div>
                        </div>

                        @if ($rentInvoice->description)
                        <div class="id-note">
                            <div class="id-k"><i class="bi bi-card-text me-1"></i>Description</div>
                            <p class="mb-0">{{ $rentInvoice->description }}</p>
                        </div>
                        @endif
                        @if ($rentInvoice->notes)
                        <div class="id-note warn">
                            <div class="id-k"><i class="bi bi-chat-left-text me-1"></i>Additional notes</div>
                            <p class="mb-0 text-muted">{{ $rentInvoice->notes }}</p>
                        </div>
                        @endif
                    </section>

                    {{-- Breakdown --}}
                    <section class="id-card mt-4 id-in" style="animation-delay:.1s">
                        <div class="id-head">
                            <h3>Invoice breakdown</h3><span class="id-cur">{{ $rentInvoice->currency ?: 'RWF' }}</span>
                        </div>

                        <div class="id-lines">
                            <div class="id-line">
                                <div>
                                    <div class="id-line-t">{{ \Illuminate\Support\Str::headline($rentInvoice->invoice_type ?? 'rent') }}</div>
                                    <div class="id-line-s">{{ $rentInvoice->description ?: 'Charges for the billing period shown above.' }}</div>
                                </div>
                                <strong>{{ $money($rentInvoice->subtotal) }}</strong>
                            </div>
                            <div class="id-line">
                                <div>
                                    <div class="id-line-t">Tax</div>
                                </div>
                                <strong>{{ $money($rentInvoice->tax_amount) }}</strong>
                            </div>
                            <div class="id-line total">
                                <div>
                                    <div class="id-line-t">Total invoice amount</div>
                                </div>
                                <strong>{{ $money($rentInvoice->total_amount) }}</strong>
                            </div>
                            <div class="id-line">
                                <div>
                                    <div class="id-line-t">Amount paid</div>
                                </div>
                                <strong class="text-success">− {{ $money($rentInvoice->amount_paid) }}</strong>
                            </div>
                            <div class="id-line due {{ $balance > 0 ? 'owed' : 'clear' }}">
                                <div>
                                    <div class="id-line-t">Outstanding balance</div>
                                </div>
                                <strong>{{ $money($rentInvoice->balance_due) }}</strong>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- Sidebar --}}
                <div class="col-12 col-xl-4">
                    <div class="id-sticky">
                        <section class="id-hero id-in" style="animation-delay:.05s">
                            <div class="eyebrow">{{ $balance > 0 ? 'Balance to pay' : 'Balance' }}</div>
                            <div class="amt">{{ $money($rentInvoice->balance_due) }}</div>
                            <span class="id-hpill {{ $key }}"><i class="bi {{ $icon }}"></i>{{ $statusLabel }}</span>

                            <div class="id-meter" role="progressbar" aria-valuenow="{{ $paidPct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Amount paid">
                                <i data-w="{{ $paidPct }}"></i>
                            </div>
                            <div class="id-meter-lbl"><span>{{ $paidPct }}% paid</span><span>{{ $money($rentInvoice->total_amount) }}</span></div>

                            <div class="id-hline"></div>
                            <div class="id-hrow"><span>Total invoice amount</span><strong>{{ $money($rentInvoice->total_amount) }}</strong></div>
                            <div class="id-hrow"><span>Amount paid</span><strong>{{ $money($rentInvoice->amount_paid) }}</strong></div>
                            <div class="id-hrow"><span>Remaining balance</span><strong class="peach">{{ $money($rentInvoice->balance_due) }}</strong></div>

                            <div class="id-hmsg">
                                <i class="bi {{ $key === 'paid' ? 'bi-check-circle' : ($key === 'overdue' ? 'bi-exclamation-triangle' : 'bi-calendar-check') }} me-1"></i>
                                {{ $message }}
                            </div>
                        </section>

                        <section class="id-card mt-3 id-in" style="animation-delay:.12s">
                            <div class="id-head">
                                <h3>Payment allocations</h3>
                                @if($rentInvoice->allocations->count())<span class="id-cur">{{ $rentInvoice->allocations->count() }}</span>@endif
                            </div>

                            @forelse ($rentInvoice->allocations as $allocation)
                            @php
                            $allocatedAmount = $allocation->allocated_amount
                            ?? $allocation->amount_allocated
                            ?? $allocation->amount
                            ?? 0;
                            @endphp
                            <div class="id-alloc">
                                <div class="id-alloc-ico"><i class="bi bi-cash-coin"></i></div>
                                <div class="flex-grow-1" style="min-width:0">
                                    <div class="fw-bold small" style="color:var(--id-navy)">Payment allocation</div>
                                    <div class="id-sub"><i class="bi bi-clock"></i>{{ $allocation->created_at?->format('d M Y') ?? 'Date unavailable' }}</div>
                                    @if ($allocation->reference ?? false)
                                    <div class="id-sub"><i class="bi bi-hash"></i>{{ $allocation->reference }}</div>
                                    @endif
                                </div>
                                <div class="id-alloc-amt">{{ $money($allocatedAmount) }}</div>
                            </div>
                            @empty
                            <div class="id-empty">
                                <i class="bi bi-clock-history"></i>
                                <p class="small text-muted mt-2 mb-0">No payment allocations are recorded for this invoice yet.</p>
                            </div>
                            @endforelse
                        </section>

                        <section class="id-help id-noprint id-in" style="animation-delay:.18s">
                            <i class="bi bi-headset"></i>
                            <div>
                                <strong>Questions about this invoice?</strong>
                                <p>Contact your property manager and quote <b>{{ $rentInvoice->invoice_number }}</b>.</p>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .id-wrap {
                --id-navy: var(--terra-navy, #19265d);
                --id-orange: var(--terra-orange, #D05208);
                --id-line: #e4e8f2;
                --id-soft: #f4f6fb;
                --id-muted: #748094;
            }

            .id-in {
                animation: idIn .5s both;
            }

            @keyframes idIn {
                from {
                    opacity: 0;
                    transform: translateY(12px);
                }

                to {
                    opacity: 1;
                    transform: none;
                }
            }

            .id-btn,
            .id-btn-line {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                font-weight: 700;
                font-size: .85rem;
                border-radius: 12px;
                padding: 9px 16px;
                text-decoration: none;
                transition: all .2s;
                cursor: pointer;
            }

            .id-btn {
                background: var(--id-orange);
                border: 1px solid var(--id-orange);
                color: #fff;
            }

            .id-btn:hover {
                background: #b94707;
                border-color: #b94707;
                color: #fff;
                transform: translateY(-1px);
                box-shadow: 0 8px 18px rgba(208, 82, 8, .28);
            }

            .id-btn-line {
                background: #fff;
                border: 1.5px solid var(--id-line);
                color: var(--id-navy);
            }

            .id-btn-line:hover {
                background: var(--id-navy);
                border-color: var(--id-navy);
                color: #fff;
            }

            .id-card {
                background: #fff;
                border: 1px solid var(--id-line);
                border-radius: 20px;
                padding: 24px;
                box-shadow: 0 4px 18px rgba(25, 38, 93, .04);
            }

            .id-doc {
                padding: 0;
                overflow: hidden;
            }

            .id-k {
                font-size: .68rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: .07em;
                color: var(--id-muted);
                margin-bottom: 4px;
            }

            .id-v {
                font-weight: 700;
                color: var(--id-navy);
                font-size: .95rem;
                overflow-wrap: anywhere;
            }

            /* Banner */
            .id-banner {
                display: flex;
                gap: 16px;
                align-items: center;
                padding: 18px 24px;
            }

            .id-banner.paid {
                background: #e8f7ee;
                color: #146c3a;
            }

            .id-banner.partial {
                background: #fff4d7;
                color: #8a5a00;
            }

            .id-banner.overdue {
                background: #ffebe9;
                color: #a32121;
            }

            .id-banner.unpaid {
                background: #eef0f6;
                color: #4a5272;
            }

            .id-banner-ico {
                width: 46px;
                height: 46px;
                border-radius: 50%;
                background: rgba(255, 255, 255, .7);
                display: grid;
                place-items: center;
                font-size: 1.35rem;
                flex-shrink: 0;
            }

            .id-banner.overdue .id-banner-ico i {
                animation: idBlink 1.5s infinite;
            }

            @keyframes idBlink {
                50% {
                    opacity: .4;
                }
            }

            .id-banner-title {
                font-weight: 800;
                letter-spacing: -.01em;
            }

            .id-banner-text {
                font-size: .85rem;
                opacity: .9;
                margin-top: 2px;
            }

            .id-top {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: flex-end;
                gap: 14px;
                padding: 24px 24px 8px;
            }

            .id-num {
                font-size: 1.2rem;
                font-weight: 800;
                color: var(--id-navy);
                letter-spacing: -.01em;
            }

            .id-type {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                margin-top: 6px;
                font-size: .8rem;
                font-weight: 600;
                color: var(--id-muted);
                background: var(--id-soft);
                border-radius: 8px;
                padding: 3px 10px;
            }

            .id-amount {
                font-size: clamp(1.6rem, 3.5vw, 2.2rem);
                font-weight: 800;
                color: var(--id-navy);
                letter-spacing: -.04em;
                line-height: 1.1;
                overflow-wrap: anywhere;
            }

            .id-copy {
                border: 0;
                background: var(--id-soft);
                color: var(--id-navy);
                width: 30px;
                height: 30px;
                border-radius: 8px;
                transition: all .2s;
            }

            .id-copy:hover {
                background: #ffe3d0;
                color: var(--id-orange);
            }

            /* Timeline */
            .id-steps {
                list-style: none;
                display: flex;
                margin: 22px 24px 8px;
                padding: 0;
            }

            .id-steps li {
                flex: 1;
                position: relative;
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 8px;
            }

            .id-steps li::before {
                content: "";
                position: absolute;
                top: 17px;
                left: -50%;
                width: 100%;
                height: 3px;
                background: #e4e8f2;
                z-index: 0;
            }

            .id-steps li:first-child::before {
                display: none;
            }

            .id-steps li.done::before,
            .id-steps li.current::before {
                background: #1a8a4a;
            }

            .id-steps li.bad::before {
                background: #c0392b;
            }

            .id-steps .dot {
                position: relative;
                z-index: 1;
                width: 36px;
                height: 36px;
                border-radius: 50%;
                display: grid;
                place-items: center;
                background: #fff;
                border: 3px solid #e4e8f2;
                color: #a3abc2;
                font-size: .9rem;
            }

            .id-steps li.done .dot {
                background: #1a8a4a;
                border-color: #1a8a4a;
                color: #fff;
            }

            .id-steps li.current .dot {
                border-color: #e0a100;
                color: #e0a100;
                box-shadow: 0 0 0 5px rgba(224, 161, 0, .18);
            }

            .id-steps li.bad .dot {
                background: #c0392b;
                border-color: #c0392b;
                color: #fff;
                box-shadow: 0 0 0 5px rgba(192, 57, 43, .15);
            }

            .id-steps .lbl {
                font-size: .78rem;
                font-weight: 700;
                color: var(--id-navy);
                line-height: 1.25;
            }

            .id-steps .lbl small {
                display: block;
                font-weight: 500;
                color: var(--id-muted);
                font-size: .72rem;
                margin-top: 2px;
            }

            .id-steps li.todo .lbl {
                color: var(--id-muted);
            }

            .id-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 12px;
                padding: 18px 24px 8px;
            }

            @media (min-width: 576px) {
                .id-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (min-width: 992px) {
                .id-grid {
                    grid-template-columns: repeat(3, 1fr);
                }
            }

            .id-item {
                display: flex;
                gap: 12px;
                align-items: center;
                padding: 12px 14px;
                border: 1px solid var(--id-line);
                border-radius: 14px;
                transition: border-color .2s, background .2s;
            }

            .id-item:hover {
                border-color: rgba(208, 82, 8, .35);
                background: #fffaf6;
            }

            .id-item .ic {
                width: 38px;
                height: 38px;
                border-radius: 11px;
                background: #fff1e8;
                color: var(--id-orange);
                display: grid;
                place-items: center;
                flex-shrink: 0;
            }

            .id-item .id-k {
                margin: 0;
            }

            .id-item.bad {
                border-color: #f5c2bd;
                background: #fff8f7;
            }

            .id-item.bad .ic {
                background: #fdecec;
                color: #c0392b;
            }

            .id-item.bad .id-v {
                color: #c0392b;
            }

            .id-note {
                margin: 12px 24px 0;
                padding: 14px 16px;
                border-radius: 14px;
                background: var(--id-soft);
                font-size: .9rem;
            }

            .id-note.warn {
                background: #fffbf0;
                border: 1px solid #f3e3b0;
            }

            .id-doc> :last-child {
                margin-bottom: 24px;
            }

            /* Breakdown */
            .id-head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                margin-bottom: 14px;
            }

            .id-head h3 {
                font-size: 1.05rem;
                font-weight: 800;
                color: var(--id-navy);
                margin: 0;
                letter-spacing: -.02em;
            }

            .id-cur {
                background: var(--id-soft);
                color: var(--id-navy);
                font-size: .76rem;
                font-weight: 700;
                border-radius: 999px;
                padding: 4px 12px;
            }

            .id-line {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                padding: 14px 16px;
                border-bottom: 1px solid #eef0f7;
            }

            .id-line:last-child {
                border-bottom: 0;
            }

            .id-line-t {
                font-weight: 700;
                color: var(--id-navy);
                font-size: .92rem;
            }

            .id-line-s {
                font-size: .8rem;
                color: var(--id-muted);
                margin-top: 2px;
            }

            .id-line strong {
                color: var(--id-navy);
                white-space: nowrap;
            }

            .id-lines {
                border: 1px solid var(--id-line);
                border-radius: 14px;
                overflow: hidden;
            }

            .id-line.total {
                background: var(--id-soft);
            }

            .id-line.total strong {
                font-size: 1.05rem;
            }

            .id-line.due.owed {
                background: #fff3f1;
            }

            .id-line.due.owed strong {
                color: #c0392b;
                font-size: 1.1rem;
            }

            .id-line.due.clear {
                background: #eefaf3;
            }

            .id-line.due.clear strong {
                color: #1a8a4a;
                font-size: 1.1rem;
            }

            /* Sidebar */
            .id-sticky {
                position: sticky;
                top: 84px;
            }

            .id-hero {
                position: relative;
                overflow: hidden;
                border-radius: 20px;
                padding: 24px;
                color: #fff;
                background: linear-gradient(120deg, #19265d, #2b3f94);
            }

            .id-hero::before {
                content: "";
                position: absolute;
                width: 280px;
                height: 280px;
                right: -80px;
                top: -140px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(208, 82, 8, .55), transparent 68%);
                pointer-events: none;
            }

            .id-hero>* {
                position: relative;
            }

            .id-hero .eyebrow {
                font-size: .7rem;
                font-weight: 800;
                letter-spacing: .1em;
                text-transform: uppercase;
                color: #ffd2b6;
            }

            .id-hero .amt {
                font-size: clamp(1.6rem, 3.5vw, 2.1rem);
                font-weight: 800;
                letter-spacing: -.04em;
                margin: 8px 0 14px;
                overflow-wrap: anywhere;
                line-height: 1.15;
            }

            .id-hpill {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                border-radius: 999px;
                padding: 5px 13px;
                font-size: .78rem;
                font-weight: 800;
            }

            .id-hpill.paid {
                background: #e5f7ee;
                color: #167347;
            }

            .id-hpill.partial {
                background: #fff4d7;
                color: #936000;
            }

            .id-hpill.overdue {
                background: #ffebe9;
                color: #b42318;
            }

            .id-hpill.unpaid {
                background: #edf0f4;
                color: #5d6678;
            }

            .id-meter {
                height: 9px;
                border-radius: 999px;
                background: rgba(255, 255, 255, .18);
                overflow: hidden;
                margin-top: 18px;
            }

            .id-meter>i {
                display: block;
                height: 100%;
                width: 0;
                border-radius: 999px;
                background: linear-gradient(90deg, #4cc486, #8be6b4);
                transition: width 1.1s cubic-bezier(.22, 1, .36, 1);
            }

            .id-meter-lbl {
                display: flex;
                justify-content: space-between;
                gap: 8px;
                font-size: .74rem;
                font-weight: 700;
                color: rgba(255, 255, 255, .75);
                margin-top: 6px;
            }

            .id-hline {
                height: 1px;
                background: rgba(255, 255, 255, .2);
                margin: 18px 0 12px;
            }

            .id-hrow {
                display: flex;
                justify-content: space-between;
                gap: 12px;
                padding: 6px 0;
                font-size: .85rem;
            }

            .id-hrow span {
                color: rgba(255, 255, 255, .72);
            }

            .id-hrow strong {
                text-align: right;
                overflow-wrap: anywhere;
            }

            .id-hrow .peach {
                color: #FFD3B9;
            }

            .id-hmsg {
                margin-top: 14px;
                padding: 12px 14px;
                border-radius: 12px;
                background: rgba(255, 255, 255, .1);
                font-size: .8rem;
                color: rgba(255, 255, 255, .88);
            }

            .id-alloc {
                display: flex;
                gap: 12px;
                align-items: flex-start;
                padding: 12px;
                border: 1px solid var(--id-line);
                border-radius: 14px;
                transition: border-color .2s, background .2s;
            }

            .id-alloc+.id-alloc {
                margin-top: 10px;
            }

            .id-alloc:hover {
                border-color: rgba(208, 82, 8, .35);
                background: #fffaf6;
            }

            .id-alloc-ico {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                background: #e8f7ee;
                color: #1a8a4a;
                display: grid;
                place-items: center;
                flex-shrink: 0;
            }

            .id-alloc-amt {
                font-weight: 800;
                color: #167347;
                font-size: .88rem;
                text-align: right;
            }

            .id-sub {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: .74rem;
                color: var(--id-muted);
                margin-top: 3px;
            }

            .id-sub i {
                color: #a3abc2;
            }

            .id-empty {
                text-align: center;
                padding: 26px 12px;
                border: 1.5px dashed #dce1eb;
                border-radius: 14px;
                background: #fafbfe;
            }

            .id-empty i {
                font-size: 1.8rem;
                color: var(--id-orange);
            }

            .id-help {
                display: flex;
                gap: 12px;
                margin-top: 16px;
                padding: 16px;
                border: 1.5px dashed #d9deeb;
                border-radius: 16px;
                background: #fafbfe;
            }

            .id-help>i {
                font-size: 1.4rem;
                color: var(--id-orange);
            }

            .id-help strong {
                color: var(--id-navy);
                font-size: .88rem;
            }

            .id-help p {
                margin: 2px 0 0;
                font-size: .8rem;
                color: var(--id-muted);
            }

            @media (max-width: 575.98px) {

                .id-banner,
                .id-top {
                    padding-left: 16px;
                    padding-right: 16px;
                }

                .id-steps,
                .id-note {
                    margin-left: 16px;
                    margin-right: 16px;
                }

                .id-grid {
                    padding-left: 16px;
                    padding-right: 16px;
                }

                .id-card {
                    padding: 18px 16px;
                }

                .id-doc {
                    padding: 0;
                }

                .id-steps .lbl {
                    font-size: .7rem;
                }
            }

            @media (max-width: 1199.98px) {
                .id-sticky {
                    position: static;
                }
            }

            @media (prefers-reduced-motion: reduce) {

                .id-in,
                .id-banner.overdue .id-banner-ico i {
                    animation: none;
                }

                .id-meter>i {
                    transition: none;
                }
            }

            @media print {

                .id-noprint,
                .id-help {
                    display: none !important;
                }

                .id-sticky {
                    position: static;
                }

                .id-card,
                .id-hero {
                    box-shadow: none;
                    break-inside: avoid;
                }

                .id-hero {
                    background: #fff !important;
                    color: #000 !important;
                    border: 1px solid #000;
                }

                .id-hero .eyebrow,
                .id-hrow span,
                .id-hrow .peach,
                .id-meter-lbl,
                .id-hmsg {
                    color: #000 !important;
                }

                .col-xl-8,
                .col-xl-4 {
                    width: 100% !important;
                    flex: 0 0 100% !important;
                }
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                requestAnimationFrame(function() {
                    document.querySelectorAll('.id-meter > i[data-w]').forEach(function(el) {
                        el.style.width = el.dataset.w + '%';
                    });
                });

                var print = document.getElementById('idPrint');
                if (print) print.addEventListener('click', function() {
                    window.print();
                });

                var copy = document.getElementById('idCopy');
                if (copy && navigator.clipboard) {
                    copy.addEventListener('click', function() {
                        navigator.clipboard.writeText(document.getElementById('idNum').textContent.trim()).then(function() {
                            var i = copy.querySelector('i');
                            i.className = 'bi bi-check2';
                            setTimeout(function() {
                                i.className = 'bi bi-clipboard';
                            }, 1600);
                        });
                    });
                }
            });
        </script>
        @endsection